<?php

namespace MxcLayoutScheme\Service;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ProvidesEvents;
use Zend\Stdlib\Parameters;
use Zend\ServiceManager\ServiceManager;
use Zend\Filter\Word\CamelCaseToDash;
use MxcLayoutScheme\Service\LayoutSchemeServiceOptions;
use MxcLayoutScheme\Service\LayoutSchemeOptions;

class LayoutSchemeService implements ListenerAggregateInterface 
{
	const HOOK_PRE_SCHEME_SELECT      = 'pre-scheme-select';
	const HOOK_POST_LAYOUT_SELECT	  = 'post-layout-select';
	
	use ProvidesEvents;
	
	protected $serviceManager;
	
	protected $options = null;
	protected $schemeOptions = array();
	protected $params  = null;
	protected $childViewModels = array();
	
	public function __construct(ServiceManager $sm) {
		$this->setServiceManager($sm);
		$this->params = new Parameters;
	}

	public function attach(EventManagerInterface $events)
	{
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
		$ctrl = $e->getTarget();
		$controllerclass = get_class($ctrl);
		$controller = substr($controllerclass,strrpos($controllerclass,'\\')+1);
		$routeMatch = $e->getRouteMatch();
		$this->params
			->set('controller',$ctrl)
			->set('moduleName',substr($controllerclass,0,strpos($controllerclass,'\\')))
			->set('controllerName',substr($controller,0,strrpos($controller,'Controller')))
			->set('actionName',$routeMatch->getParam('action'))
			->set('routeName',$routeMatch->getMatchedRouteName());
		
		// event handler may modify the active scheme
		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_PRE_SCHEME_SELECT, $this);
		$this->selectLayout();
		// event handler may add variables to the layout
		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_POST_LAYOUT_SELECT, $this);
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
	 * @param array  $templates
	 * @param string $key
	 * 
	 * @return bool
	 */
	public function applyLayout($templates,$key) {
	
		if (!isset($templates[$key]['layout'])) return false;
		$this->params->get('controller')->layout($templates[$key]['layout']);
		$this->applyChildViewModels($templates, $key);
		return true;
	}
	
	/**
	 * @param $templates
	 * @param $key
	 *
	 * @return bool
	 */
	public function applyChildViewModels($templates, $key) {
		$controller = $this->getParam('controller', null);

		//--- return if we do not have a controller instance
		if (!$controller) return;
		$templateName = $templates[$key]['layout'];
		$childViewModelList = $this->getActiveSchemeOptions()->getDefaultChildViewModels();
		$childViewModels = isset($childViewModelList[$templateName]) ? $childViewModelList[$templateName] : null;
		
		if (!$childViewModels) return;
		
		$overrideChildViewModels = isset($templates[$key]['child_view_models']) ? $templates[$key]['child_view_models'] : null;
		
		if ($overrideChildViewModels) {
			$childViewModels = array_merge($childViewModels,$overrideChildViewModels);
		}
		
		$layout = $controller->layout();
		
		$filter = new CamelCaseToDash();
		
		foreach ($childViewModels as $capture => $template) {
			switch ($template) {
				case null:
				case '<default>': 
					$template = strtolower($filter->filter($this->params->get('moduleName').'\\'.
										   $this->params->get('controllerName').'\\'.
										   $this->params->get('actionName').'-'.
										   $capture));
					break;
				case '<none>':
					$template = null;
				default:
					break;
			}

			if (!$template) break;
			
			$view = new ViewModel();
			$view->setTemplate($template);
			$layout->addChild($view,$capture);

			// keep reference for controller plugin use
			$this->setChildViewModel($capture, $view);
		}
	}
	
	/**
	 * apply variables to controller view model and all child view models
	 * @param array: $variables
	 */
	public function setVariables($variables, $override = false) {
		// apply variables to main layout view model
		$controller = $this->getParam('controller');
		if($controller) {
			$controller->layout()->setVariables($variables, $override);
		}
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
			$this->setOptions($this->getServiceManager()->get('mxclayoutscheme_service_options'));
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
	 * @return $serviceManager
	 */
	public function getServiceManager() {
		return $this->serviceManager;
	}

	/**
	 * @param ServiceManager $serviceManager
	 */
	public function setServiceManager(ServiceManager $serviceManager) {
		$this->serviceManager = $serviceManager;
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
		return $this->params->get($name, $default);
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
}
