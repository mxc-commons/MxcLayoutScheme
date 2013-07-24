<?php
namespace MxcLayoutScheme\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use RuntimeException;

class LayoutSchemePlugin extends AbstractPlugin implements ServiceManagerAwareInterface {

	protected $serviceManager = null;
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
		// apply variables to main layout view model
		// we do not necessarily have a controller
		if($this->getController()) {
			$this->getController()->layout()->setVariables($variables);
		}
		// apply variables to all child view models
		$views = $this->getChildViewModels();
		foreach ($views as $view) {
			$view->setVariables($variables);
		}
	}
	
	/**
	 * @return the $serviceManager
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
	 * @return the $layoutSchemeService
	 */
	public function getLayoutSchemeService() {
		if (!$this->layoutSchemeService) {
			$sm = $this->getServiceManager();
			if (!$sm) {
				throw new RuntimeException(sprintf('No ServiceManager reference available. Should be injected onBootstrap.'));
			}
			$this->layoutSchemeService = $sm->get('mxclayoutscheme_service');
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
		}
		return $this->controller;
	}
	
}
