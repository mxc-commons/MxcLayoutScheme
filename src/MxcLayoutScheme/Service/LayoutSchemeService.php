<?php

namespace MxcLayoutScheme\Service;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ProvidesEvents;
use Zend\Stdlib\Parameters;
use Zend\Filter\Word\CamelCaseToDash;
use MxcLayoutScheme\Service\LayoutSchemeServiceOptions;
use MxcLayoutScheme\Service\LayoutSchemeOptions;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class LayoutSchemeService implements ListenerAggregateInterface, ServiceLocatorAwareInterface 
{
	const HOOK_PRE_SELECT_SCHEME      = 'pre-select-scheme';
	const HOOK_POST_SELECT_LAYOUT	  = 'post-select-layout';
	
	use ProvidesEvents;
	
	protected $serviceLocator;
	
	protected $options = null;
	protected $schemeOptions = array();
	protected $params  = null;
	protected $childViewModels = array();
	protected $completed = false;
	protected $hasPluginSetActiveScheme = false;
	protected $skipPreSelectSchemeEvent = false;
	protected $skipPostSelectLayoutEvent = false;

	public function attach(EventManagerInterface $events)
	{
		$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'),-1000);
	    $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'),-1000);
	}
	
	/**
	 * Detach aggregate listeners from the specified event manager
	 *
	 * @param  EventManagerInterface $events
	 * @return void
	 */
	public function detach(EventManagerInterface $events)
	{
		foreach ($this->listeners as $index => $listener) {
			if ($events->detach($listener)) {
				unset($this->listeners[$index]);
			}
		}
	}
	
	/**
	 * handle dispatch event
	 */
	public  function onDispatch(MvcEvent $e)
	{
        // prevent multiple execution
	    if ($this->completed) return;
	    
		$ctrl = $e->getTarget();
		$controllerclass = get_class($ctrl);
		$controller = substr($controllerclass,strrpos($controllerclass,'\\')+1);
		$routeMatch = $e->getRouteMatch();
		$this->setParam('controller',$ctrl)
			->setParam('moduleName',substr($controllerclass,0,strpos($controllerclass,'\\')))
			->setParam('controllerName',substr($controller,0,strrpos($controller,'Controller')))
			->setParam('actionName',$routeMatch->getParam('action'))
			->setParam('routeName',$routeMatch->getMatchedRouteName());
		
		// event handler may modify the active scheme
		if (!$this->getSkipPreSelectSchemeEvent()) {
    		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_PRE_SELECT_SCHEME, $this);
		}
		$this->selectLayout();
		$this->completed = true;
		// event handler may add variables to the layout
		if (!$this->getSkipPostSelectLayoutEvent()) {
		  $this->getEventManager()->trigger(LayoutSchemeService::HOOK_POST_SELECT_LAYOUT, $this);
		}
	}
	
	public function onDispatchError(MvcEvent $e) {
	    $model = $e->getResult();
        if (!$model instanceof ViewModel) {
            return;
        }
        $response = $e->getResponse();
        $statusCode = ($response) ? $response->getStatusCode() : null;
        $error = $e->getError();
        $this->setParam('statusCode', $statusCode);
        $this->setParam('error', $error);
        
		// event handler may modify the active scheme
		if (!$this->getSkipPreSelectSchemeEvent()) {
    		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_PRE_SELECT_SCHEME, $this);
		}
        $this->SelectErrorLayout();
		// event handler may add variables to the layout
		if (!$this->getSkipPostSelectLayoutEvent()) {
		  $this->getEventManager()->trigger(LayoutSchemeService::HOOK_POST_SELECT_LAYOUT, $this);
		}
	}
	
	/**
	 * select template according to active scheme
	 */
	public function selectLayout() {
		
		$schemeOptions = $this->getActiveSchemeOptions();
		
		if ($schemeOptions->getEnableRouteLayouts()) {
			$templates = $schemeOptions->getRouteLayouts();
			$route = $this->getParam('routeName');
			if ($this->applyLayout($templates, $route)) return;
		}
		
		if ($schemeOptions->getEnableMcaLayouts()) {
			$templates = $schemeOptions->getMcaLayouts();
			$module = $this->getParam('moduleName');
			$controller = $this->getParam('controllerName');
			$action = $this->getParam('actionName');
			
			if ($this->applyLayout($templates, $module.'\\'.$controller.'\\'.$action)) return;
			if ($this->applyLayout($templates, $module.'\\'.$controller)) return;
			if ($this->applyLayout($templates, $module)) return;
		}
		
		if ($this->applyLayout($schemeOptions->getDefault(),'global')) return;

	}
	/**
	 * select template according to error or response status scheme
	 */
	public function selectErrorLayout() {
		
		$schemeOptions = $this->getActiveSchemeOptions();

        $templates = $schemeOptions->getErrorLayouts();
		$error = $this->getParam('error');
		if ($this->applyLayout($templates, $error)) return;
        
        $templates = $schemeOptions->getHttpStatusLayouts();
        
		$status = (string) $this->getParam('statusCode');
		if ($this->applyLayout($templates, $status)) return;
		
		if ($this->applyLayout($schemeOptions->getDefault(),'global')) return;
	}
	
	/**
	 * @param array  $templates
	 * @param string $key
	 * 
	 * @return bool
	 */
	public function applyLayout($templates,$key) {
	
		if (!isset($templates[$key]['layout'])) return false;
		$view = $this->getServiceLocator()->get('view_manager')->getViewModel();
		$view->setTemplate($templates[$key]['layout']);
		$this->applyChildViewModels($view, $templates, $key);
		return true;
	}
	
	/**
	 * @param ViewModel $layout
	 * @param array $templates
	 * @param string $key
	 *
	 * @return bool
	 */
	public function applyChildViewModels($layout, $templates, $key) {
		$templateName = $templates[$key]['layout'];
		$childViewModelList = $this->getActiveSchemeOptions()->getDefaultChildViewModels();
		$childViewModels = isset($childViewModelList[$templateName]) ? $childViewModelList[$templateName] : null;
		
		if (!$childViewModels) return;
		
		$overrideChildViewModels = isset($templates[$key]['child_view_models']) ? $templates[$key]['child_view_models'] : null;
		
		if ($overrideChildViewModels) {
			$childViewModels = array_merge($childViewModels,$overrideChildViewModels);
		}
		
		$filter = new CamelCaseToDash();
		
		foreach ($childViewModels as $capture => $template) {
			switch ($template) {
				case null:
				case '<default>': 
					$template = strtolower($filter->filter($this->getParam('moduleName').'\\'.
										   $this->getParam('controllerName').'\\'.
										   $this->getParam('actionName').'-'.
										   $capture));
					break;
				case '<none>':
					$template = null;
					break;
				default:
					break;
			}

			if (!$template) continue;
			
			$view = new ViewModel();
			$view->setTemplate($template);
			$layout->addChild($view,$capture);

			// keep reference for controller plugin use
			$this->setChildViewModel($capture, $view);
		}
	}
	
	public function pluginSetVariables($controller, $variables, $override = false) {
	    if (!$this->completed && $controller) {
	    	// called early -> need to complete first
	        $this->onDispatch($controller->getEvent());
	    }
	    return $this->setVariables($variables, $override); 
	}
	
	/**
	 * apply variables to controller view model and all child view models
	 * @param array: $variables
	 */
	public function setVariables($variables, $override = false) {
	    // apply variables to main layout view model
	    $layout = $this->getServiceLocator()->get('view_manager')->getViewModel()->setVariables($variables, $override);
		// apply variables to all child view models
		$views = $this->getChildViewModels();
		foreach ($views as $view) {
			$view->setVariables($variables, $override);
		}
	}
	
	/**
	 * register one or more additional schemes
	 * existing scheme with same names gets replaced
	 * 
	 * @oaram array()
	 * 
	 */
	protected function registerSchemes($schemes) {
		$this->getOptions()->registerSchemes($schemes);
	}
	
	/**
	 * @param string $capture
	 * @param ViewModel $view
	 */
	protected function setChildViewModel($capture, $view) {
		$this->childViewModels[$capture] = $view;
	}
	
	/**
     * @param $controller
	 * @param $capture
	 * @param $default
	 *
	 * @return mixed | ViewModel
	 */
	protected function pluginGetChildViewModel($controller, $capture, $default = null) {
		if (!$this->completed && $controller) {
			$this->onDispatch($controller->getEvent());
		}
		return $this->getChildViewModel($capture, $default);
	}
	
	
	/**
	 * @param $capture
	 * @param $default
	 *
	 * @return mixed | ViewModel
	 */
	protected function getChildViewModel($capture, $default = null) {
	   return isset($this->childViewModels[$capture]) ? $this->childViewModels[$capture] : $default;
	}

	/**
	 * @return LayoutSchemeServiceOptions $options
	 */
	public function getOptions() {
		if (!$this->options) {
			$this->setOptions($this->getServiceLocator()->get('mxclayoutscheme_service_options'));
		}
		return $this->options;
	}

	/**
	 * @param LayoutSchemeOptions $options
	 */
	public function setOptions($options) {
		$this->options = $options;
	}
	
	/**
	 * @return the $childViewModels
	 */
	public function pluginGetChildViewModels($controller) {
	    if (!$this->completed && $controller) {
            $this->onDispatch($controller->getEvent());
	    }
		return $this->childViewModels;
	}
	
	/**
	 * @return the $childViewModels
	 */
	public function getChildViewModels() {
		return $this->childViewModels;
	}
	
	/**
	 * @param multitype: $childViewModels
	 */
	public function setChildViewModels($childViewModels) {
		$this->childViewModels = $childViewModels;
	}
		
	/**
	 * @param  string $name
	 * @param  multi_type $default
	 * @return param($name,$default)
	 */
	public function getParam($name, $default = null) {
	   return $this->getParams()->get($name, $default);
	}

	/**
	 * @param  string $name
	 * @param  mixed $value
	 * @return $this
	 */
	public function setParam($name, $value) {
	   $this->getParams()->set($name, $value);
	   return $this;
	}
	
	/**
	 * set active scheme in associated options object
	 * 
	 * @param string
	 */
	public function pluginSetActiveScheme($activeScheme, $skipPreSchemeSelectEvent = false) {
	    $this->setActiveScheme($activeScheme);
	    $this->hasPluginSetActiveScheme = true;
	    $this->skipPreSchemeSelectEvent = $skipPreSchemeSelectEvent;
	}
	
	/**
	 * set active scheme in associated options object
	 * 
	 * @param string
	 */
	public function setActiveScheme($activeScheme) {
		$this->getOptions()->setActiveScheme($activeScheme);
	}

	/**
	 * setup LayoutSchemeOptions according to currently active scheme
	 * 
	 * @return LayoutSchemeOptions
	 */
	protected function getActiveSchemeOptions() {
		return $this->getSchemeOptions($this->getOptions()->getActiveScheme());
	}
	
	/**
	 * setup LayoutSchemeOptions according to $schemeName
	 * 
	 * @param  string $schemeName
	 * @return MxcLayoutSchemeOptions
	 */
	protected function getSchemeOptions($schemeName) {
		if (!isset($this->schemeOptions[$schemeName])) {
			$schemes = $this->getOptions()->getSchemes();
			if (isset($schemes[$schemeName])) {
				$this->schemeOptions[$schemeName] = new LayoutSchemeOptions($schemes[$schemeName]);
			} else {
				$this->schemeOptions[$schemeName] = new LayoutSchemeOptions;
			}	
		}
		return $this->schemeOptions[$schemeName];
	}

	/**
	 * @return the $skipPreSelectSchemeEvent
	 */
	public function getSkipPreSelectSchemeEvent() {
		return $this->skipPreSelectSchemeEvent;
	}

	/**
	 * @return the $skipPostSelectLayoutEvent
	 */
	public function getSkipPostSelectLayoutEvent() {
		return $this->skipPostSelectLayoutEvent;
	}

	/**
	 * @param boolean $skipPreSelectSchemeEvent
	 */
	public function setSkipPreSelectSchemeEvent($skipPreSelectSchemeEvent) {
		$this->skipPreSelectSchemeEvent = $skipPreSelectSchemeEvent;
	}

	/**
	 * @param boolean $skipPostSelectLayoutEvent
	 */
	public function setSkipPostSelectLayoutEvent($skipPostSelectLayoutEvent) {
		$this->skipPostSelectLayoutEvent = $skipPostSelectLayoutEvent;
	}

	/**
	 * @return the $params
	 */
	public function getParams() {
	    if (!$this->params) {
	        $this->params = new Parameters();
	    }
	    return $this->params;
	}
	
	/**
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
	    $this->serviceLocator = $serviceLocator;
	}
	
	/**
	 * @return the $serviceLocator
	 */
	public function getServiceLocator() {
	    return $this->serviceLocator;
	}
}
