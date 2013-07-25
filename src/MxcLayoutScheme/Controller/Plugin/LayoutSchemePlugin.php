<?php
namespace MxcLayoutScheme\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use RuntimeException;

class LayoutSchemePlugin extends AbstractPlugin {

	protected $layoutSchemeService = null;

	/**
	 * Retrieve array of child view models
	 * 
	 * @param $capture
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
	 */
	public function setVariables($variables) {
	    $this->getLayoutSchemeService()->setVariables($variables);
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
			// seems like we get instantiated besides the normal flow of operation (e.g. in bootstrap)
			// so let's ask our service for a controller instance
			$this->setController($this->getLayoutSchemeService()->getParam('controller'));
		    if (!$this->controller) {
			    throw new RuntimeException(sprintf('No Controller reference available. Sorry.'));
		    } 
		}
		return $this->controller;
	}
	
}
