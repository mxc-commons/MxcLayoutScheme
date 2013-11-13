<?php
namespace MxcLayoutScheme\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use RuntimeException;

class LayoutSchemePlugin extends AbstractPlugin {

	protected $layoutSchemeService = null;
	
	protected $useControllerContentTemplate = false;
	
	/**
	 * Retrieve array of child view models
	 * 
	 * @param $flag    default true
	 */
	public function useControllerContentTemplate($flag = true) {
	    $this->getLayoutSchemeService()->useControllerContentTemplate($flag);
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
	 */
	public function getChildViewModels() {
		return $this->getLayoutSchemeService()->getChildViewModels();
	}
	
	/**
	 * Retrieve child view model registered to capture  
	 * 
	 * @param $capture
	 */
	public function getChildViewModel($capture) {
		return $this->getLayoutSchemeService()->getChildViewModel($capture);
	}
	
	/**
	 * Apply a set of variables to the layout view model and all it's child view models 
	 * 
	 * @param $variables
	 * @param $override    default false
	 */
	public function setVariables($variables, $override = false) {
	    $this->getLayoutSchemeService()->setVariables($variables, $override);
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
}
