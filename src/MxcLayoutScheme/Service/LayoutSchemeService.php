<?php

namespace MxcLayoutScheme\Service;

use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ProvidesEvents;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\Parameters;
use Zend\View\Model\ViewModel;
use MxcGenerics\Stdlib\GenericOptions;

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
	protected $contentTemplate = null;
	protected $standardLayout = false;
	protected $useControllerContentTemplate = false;
	protected $skipPreSelectSchemeEvent = false;
	protected $skipPostSelectLayoutEvent = false;

	public function attach(EventManagerInterface $events)
	{
		$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'),100);
	    $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'),-2000);
	    $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'),-2000);
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
	
	protected function setParams(MvcEvent $e) {
		$routeMatch = $e->getRouteMatch();
		
		$controller = $routeMatch->getParam('controller');
		$config = $this->getServiceLocator()->get('Config');
		$controllers = isset($config['controllers']['invokables']) ? $config['controllers']['invokables'] : array();
		$module = $controllers[$controller];
		$module       = substr($module,0,strpos($module,'\\'));
		
		$this->setParam('module',$module)
			->setParam('controller',$controller)
			->setParam('action',$routeMatch->getParam('action'))
			->setParam('route',$routeMatch->getMatchedRouteName());
	}
	
	public function onDispatch(MvcEvent $e) {
	    if ($this->useControllerContentTemplate) { 
	        $this->useControllerContentTemplate = false;
	        return;
	    }
	    if ($this->contentTemplate) {
		    $view = $this->getServiceLocator()->get('view_manager')->getViewModel();
		    $children = $view->getChildren();
		    foreach ($children as $child) {
		        if ($child->captureTo() === 'content') break;
		    }
		    $child->setTemplate($this->contentTemplate);
	    } 
	}
	
	/**
	 * handle route event
	 */
	public  function onRoute(MvcEvent $e)
	{
		$this->setParams($e);
		
		// event handler may modify the active scheme
		if (!$this->getSkipPreSelectSchemeEvent()) {
    		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_PRE_SELECT_SCHEME, $this);
		}
		$this->selectLayout();
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
		
        //--- try to apply route rule based layout
		if ($schemeOptions->getEnableRouteLayouts()) {
			$routeLayouts = $schemeOptions->getRouteLayouts();
			$route = $this->getParam('route');
			if ($this->applyLayoutEx($routeLayouts, $route)) return;
		}
		
        //--- try to apply mca rule based layout
		if ($schemeOptions->getEnableMcaLayouts()) {
			$mcaLayouts = $schemeOptions->getMcaLayouts();
			$module = $this->getParam('module');
			$controller = $this->getParam('controller');
			$action = $this->getParam('action');
			if ($this->applyLayoutEx($mcaLayouts, $module.'\\'.$controller.'\\'.$action)) return;
			if ($this->applyLayoutEx($mcaLayouts, $module.'\\'.$controller)) return;
			if ($this->applyLayoutEx($mcaLayouts, $module)) return;
		}
		
        //--- try to apply route rule based default layout
		if ($schemeOptions->getEnableRouteLayouts()) {
			$routeLayouts = $schemeOptions->getRouteLayouts();
			if ($this->applyLayoutEx($routeLayouts)) return;
		}
		
        //--- try to apply mca rule based default layout
		if ($schemeOptions->getEnableMcaLayouts()) {
			$mcaLayouts = $schemeOptions->getMcaLayouts();
			if ($this->applyLayoutEx($mcaLayouts)) return;
		}
	}
	
	/**
	 * select template according to error or response status scheme
	 */
	public function selectErrorLayout() {
		
		$schemeOptions = $this->getActiveSchemeOptions();

        //--- try to apply error rule based layout
		if ($schemeOptions->getEnableErrorLayouts()) {
    		$errorLayouts = $schemeOptions->getErrorLayouts();
    		$error = $this->getParam('error');
    		if ($this->applyLayoutEx($errorLayouts, $error)) return;
		}
        
        //--- try to apply status rule based layout
		if ($schemeOptions->getEnableStatusLayouts()) {
    		$statusLayouts = $schemeOptions->getHttpStatusLayouts();
    		$status = (string) $this->getParam('statusCode');
    		if ($this->applyLayoutEx($statusLayouts, $status)) return;
		}
		
        //--- try to apply error rule based default layout
		if ($schemeOptions->getEnableErrorLayouts()) {
		    if ($this->applyLayoutEx($errorLayouts)) return;
		}
		
        //--- try to apply status rule based default layout
		if ($schemeOptions->getEnableStatusLayouts()) {
		    if ($this->applyLayoutEx($statusLayouts)) return;
		}
	}

	protected function loadLayout($templates, $key = null) {
	    //-- return false if no templates array provided
        if (!$templates) return false;
  	    
        //-- return false if the requested option set does not exist 
	    if ($key && !isset($templates['options'][$key])) return false;

	    $layoutOptions = new GenericOptions($templates, $key);

        //-- return false if the option set does not specify a root layout
        //-- and the view_manager config section does not contain a 'layout' key
        if (!$layoutOptions->getLayout()) { 
	       if ($this->getStandardLayout != false) {
	           $layoutOptions->setLayout($this->standardLayout);
	       } else {
	           return false;
	       }
        }
	    
	    return $layoutOptions;
	}
	
	protected function applyLayoutEx($templates, $key = null) {
	    $options = $this->loadLayout($templates, $key);
	    if (false === $options) return false;
	    return $this->applyLayout($options);
	}
	
	/**
	 * @param array  $templates
	 * @param string $key
	 * 
	 * @return bool
	 */
	protected function applyLayout(GenericOptions $layoutOptions, $applyContent = false) {

		$layout = $this->getServiceLocator()->get('view_manager')->getViewModel();
        $layoutRoot = $layoutOptions->getLayout();		
		if ($layoutRoot) $layout->setTemplate($layoutRoot);
    	$filter = new CamelCaseToDash();
		
		foreach ($layoutOptions as $capture => $template) {
		    
	        if ($capture === 'layout') continue;
	        if (($applyContent === false) && ($capture === 'content')) {
	            $this->contentTemplate = $layoutOptions->getContent();
	               continue;
	        }
	        
			switch ($template) {
				case null:
				case '<default>': 
					$template = strtolower($filter->filter($this->getParam('module').'\\'.
										   $this->getParam('controller').'\\'.
										   $this->getParam('action').'-'.
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
	        
		return true;
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
	 * @param string $capture
	 * @param ViewModel $view
	 */
	protected function setChildViewModel($capture, $view) {
		$this->childViewModels[$capture] = $view;
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
		    $config = $this->getServiceLocator()->get('Configuration');
		    $this->options = new GenericOptions(isset($config['mxclayoutscheme']) ? $config['mxclayoutscheme'] : array());
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
		    $config = $this->getServiceLocator()->get('Config')['mxclayoutscheme'];
		    
		    $scheme = new GenericOptions($config, $schemeName);
		    $this->schemeOptions[$schemeName] = $scheme;
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
	
	/**
	 * @param boolean $flag
	 */
	public function useControllerContentTemplate($flag) {
	    $this->useControllerContentTemplate = $flag;
	}
	
	protected function getStandardLayout() {
	    if (!$this->standardLayout) {
	        $config = $this->getServiceLocator()->get('Config');
	        $this->standardLayout = isset($config['view_manager']['layout']) ? $config['view_manager']['layout'] : false;
	    }
	    return $this->standardLayout;
	}
	
}
