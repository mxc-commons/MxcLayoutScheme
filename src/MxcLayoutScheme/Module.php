<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/MxcSwitchLayout for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace MxcLayoutScheme;

use MxcGenerics\Stdlib\GenericOptions;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
    
    public function onBootstrap(MvcEvent $e)
    {
    	$app = $e->getApplication();
    	$sm = $app->getServiceManager();
    	$em = $app->getEventManager();
    	$lss = $sm->get('mxclayoutscheme_service');
    	$em->attach($lss);
    	$sm->get('ControllerPluginManager')->get('layoutScheme')->setLayoutSchemeService($lss);
    }
    
    public function getControllerPluginConfig() {
    	return array(
    		'invokables' => array(
    			'layoutScheme' => 'MxcLayoutScheme\Controller\Plugin\LayoutSchemePlugin',
    		)
    	);
    }
    
    public function getServiceConfig()
    {
        return array(
    		'invokables' => array(
				'mxclayoutscheme_service' 	=> 'MxcLayoutScheme\Service\LayoutSchemeService'
   			),
	    );
	}
}
