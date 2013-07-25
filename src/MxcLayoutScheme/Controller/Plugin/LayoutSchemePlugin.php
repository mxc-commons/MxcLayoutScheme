<?php
namespace MxcLayoutScheme\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use RuntimeException;

class LayoutSchemePlugin extends AbstractPlugin {

	protected $layoutSchemeService = null;

	/**
	 * Set the active scheme
	 *
	 * @param string $activeScheme
	 */
	public function setActiveScheme($activeScheme, $skipPreSchemeSelectEvent = false) {
		$this->getLayoutSchemeService()->pluginSetActiveScheme($activeScheme, $skipPreSchemeSelectEvent);
	}
	
	/**
	 * Get the active scheme
	 */
	public function getActiveScheme() {
		return $this->getLayoutSchemeService()->getActiveScheme();
	}
	
	/**
	 * Retrieve array of child view models
	 * 
	 * @param $capture
	 */
	public function getChildViewModels() {
		return $this->getLayoutSchemeService()->pluginGetChildViewModels($this->getController());
	}
	
	/**
	 * Retrieve child view model registered to capture  
	 * 
	 * @param $capture
	 */
	public function getChildViewModel($capture) {
		return $this->getLayoutSchemeService()->pluginGetChildViewModel($this->getController(), $capture);
	}
	
	/**
	 * Apply a set of variables to the layout view model and all it's child view models 
	 * 
	 * @param $variables
	 */
	public function setVariables($variables, $override = false) {
	    $this->getLayoutSchemeService()->pluginSetVariables($this->getController(), $variables, $override);
	}
	
	/**
	 * Prevent LayoutSchemeService::HOOK_PRE_SELECT_SCHEME to get triggered (for this run only) 
	 * 
	 * @param $variables
	 */
	public function skipPreSelectSchemeEvent() {
	    $this->getLayoutSchemeService()->setSkipPreSelectSchemeEvent(true);
	}

	/**
	 * Prevent LayoutSchemeService::HOOK_POST_SELECT_LAYOUT to get triggered (for this run only) 
	 * 
	 * @param $variables
	 */
	public function skipPostSelectLayoutEvent() {
		$this->getLayoutSchemeService()->setSkipPostSelectLayoutEvent(true);
	}
	
	/**
	 * @param LayoutSchemeService
	 */
	public function setLayoutSchemeService($layoutSchemeService) {
        $this->layoutSchemeService = $layoutSchemeService;
	}
	
	/**
	 * @return the $layoutSchemeService
	 */
	public function getLayoutSchemeService() {
		if (!$this->layoutSchemeService) {
			throw new RuntimeException(sprintf('No LayoutSchemeService reference available. Should be injected onBootstrap.'));
		}
		return $this->layoutSchemeService;
	}
	
	/**
	 * Get the current controller instance
	 *
	 * @return null|Dispatchable
	 */
	public function getController()
	{
	    if (!$this->controller) {
		    throw new RuntimeException(sprintf('No Controller reference available. Sorry.'));
	    } 
		return $this->controller;
	}
}
