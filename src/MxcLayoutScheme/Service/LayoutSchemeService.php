<?php

namespace MxcLayoutScheme\Service;

use Zend\Mvc\MvcEvent;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ProvidesEvents;
use Zend\Stdlib\Parameters;
use Zend\ServiceManager\ServiceManager;
use MxcLayoutScheme\Service\MxcLayoutSchemeServiceOptions;
use MxcLayoutScheme\Service\LayoutSchemeOptions;

class LayoutSchemeService implements ListenerAggregateInterface 
{
	const HOOK_SELECT_SCHEME      = 'select-scheme';
	
	use ProvidesEvents;
	
	protected $serviceManager;
	
	protected $options = null;
	protected $schemeOptions = array();
	protected $params  = null;
	
	public function __construct(ServiceManager $sm) {
		$this->setServiceManager($sm);
		$this->params = new Parameters;
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
			->set('controllerInstance',$ctrl)
			->set('module',substr($controllerclass,0,strpos($controllerclass,'\\')))
			->set('controller',substr($controller,0,strrpos($controller,'Controller')))
			->set('action',$routeMatch->getParam('action'))
			->set('route',$routeMatch->getMatchedRouteName());
		$this->selectLayout();
	}
	
	/**
	 * select template according to active scheme
	 */
	public function selectLayout() {

		$schemeOptions = $this->getActiveSchemeOptions();
		
		if ($schemeOptions->getEnableRouteLayouts()) {
			$templates = $schemeOptions->getRouteLayouts();
			$route = $this->params->get('route');
			if ($this->applyLayout($templates, $route)) return;
		}
		
		if ($schemeOptions->getEnableMcaLayouts()) {
			$templates = $schemeOptions->getMcaLayouts();
			$module = $this->params->get('module');
			$controller = $this->params->get('controller');
			$action = $this->params->get('action');
			
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
	
		if (!isset($templates[$key])) return false;
		$this->params->get('controllerInstance')->layout($templates[$key]);
		return true;
	}
	
	/**
	 * Attach the aggregate to the specified event manager
	 *
	 * @param  EventManagerInterface $events
	 * @return void
	 */

	public function attach(EventManagerInterface $events)
	{
		$this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'),-100);
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
	 * @return MxcLayoutSchemeServiceOptions $options
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
	 * @param  string $name
	 * @param  multi_type $default
	 * @return param($name,$default)
	 */
	public function getParam($name,$default) {
		return $this->params->get($name, $default);
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
	 * setup LayoutSchemeOptions according to currently active scheme
	 * 
	 * @return MxcLayoutSchemeOptions
	 */
	protected function getActiveSchemeOptions() {
		$this->getEventManager()->trigger(LayoutSchemeService::HOOK_SELECT_SCHEME, $this->getOptions());
		return $this->getSchemeOptions($this->getOptions()->getActiveScheme());
	}
	
}
