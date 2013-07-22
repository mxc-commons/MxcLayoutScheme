MxcLayoutScheme
===============
Version 1.0.0 created by Frank Hein and the mxc-commons team.

MxcLayoutScheme is part of the [maxence openBeee initiative](http://www.maxence.de/mxcweb/index.php/themen/open-business/)
by [maxence business consulting gmbh, Germany](http://www.maxence.de). 

Introduction
------------

Did you ever want to apply a different layout phtml based on the route matched or for a particular module, controller or action? This
is what MxcLayoutScheme can do for you. 

MxcLayoutScheme allows to dynamically exchange the layout template used by the renderer. You define layout schemes which are a collection of rules to select layouts. Within each scheme you can assign a distinct layout to a particular route matched. Further you can define a distinct layout for each module, controller and action.

MxcLayoutScheme provides an event interface to allow you to select the layout scheme applied at bootstrap time.

Requirements
------------

* [Zend Framework 2](https://github.com/zendframework/zf2) (latest master)

Features / Goals
----------------



**1. 	Provide the capability to assign the layout dynamically** 
  
- based on the current route matched
- for each module
- for each controller
- for each action implemented by a controller

**2. Provide hierarchical matching of modules, controllers, actions and routes**

- route match supersedes action match
- action match supersedes controller match
- controller match supersedes module match
- module match supersedes global settings

**3. Encapsulate layout selection rules into layout schemes**



**4. Allow selection of active layout scheme based on custom criteria**

You may select the layout scheme selection in response to an event provided.

**5. Allow configuration of a global default layout for each layout scheme**

Installation
------------

### Main Setup

#### By cloning project

1. Clone this project into your `./vendor/` directory.

#### With composer

1. Add this project in your composer.json:

    ```json
    "require": {
        "mxc-commons/mxc-layoutscheme": "dev-master"
    }
    ```

2. Now tell composer to download MxcLayoutScheme by running the command:

    ```bash
    $ php composer.phar update
    ```

#### Post installation

1. Enabling it in your `application.config.php`file.

    ```php
    <?php
    return array(
        'modules' => array(
            // ...
            'MxcLayoutScheme',
        ),
        // ...
    );
    ```

Options
-------

The MxcLayoutScheme module has options to setup and select layout schemes. After installing MxcLayoutScheme, copy
`./vendor/maxence/MxcLayoutScheme/config/mxclayoutscheme.global.php.dist` to
`./config/autoload/mxclayoutscheme.global.php` and change the values as desired.

The following options are available:

- **active_scheme** - Name of the scheme which is active by default. Default is `zf2`. Note: If you do not define a `zf2` scheme the application default layout gets used.
- **schemes** - Array of layout schemes. Default: `array()` 

Within each scheme definition the following sections are available:

- **route_layouts** - Array of rules: `<routeName>` => `<templateName>`, where routeName is the name of a registered route and templateName is the name of a registered template. Default: `array()`
- **mca_layouts** - Array of rules: `<moduleName>[\<controllerName>[\<actionName>]] => <templateName>`, where moduleName is the name of the module, controllerName is the name of the controller (without the "Controller" suffix (if class name is IndexController then controllerName is Index)), actionName is the name of the the controller action. Default: `array()`
- **default** - Array of rules: `'global'` => `<templateName>`. 'global' currently is the only key supported by MxcLayoutScheme. Default: `array()`

- Find further documentation and configuration examples in `mxclayoutschemes.global.php.dist`  

How MxcLayoutScheme works
-------------------------

1. MxcLayoutScheme hooks into the dispatch event of the application's EventManager.
2. On dispatch MxcLayoutScheme evaluates the current route matched, the module name, the controller name and the action name.
3. Then MxcLayoutScheme triggers an `MxcLayoutSchemeService::HOOK_SELECT_SCHEME` event. If you registered an event handler for that event in the bootstrap somewhere you can set the active scheme with `$e->getTarget()->setActiveScheme($schemeName)` with a `$schemeName`of your choice.
4. Then, MxcLayoutScheme loads the currently active scheme.  
3. MxcLayoutScheme checks the `route_layouts` for a key matching the matched route name. If the key exists it applies the layout template registered to that match and exits.
4. MxcLayoutScheme then checks the `mca_layouts` for a key matching Module\Conroller\Action. If the key exists the the layout template registered to that match gets applied and event handling exits.
5. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching Module\Conroller. If the key exists the the layout template registered to that match gets applied and event handling exits.
6. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching the Module. If the key exists the the layout template registered to that match gets applied and event handling exits.
6. Finally, MxcLayoutScheme checks the `default` for a key `global`. If the key exists the the layout template registered to that match gets applied and event handling exits.

Example Event Handler to compute the active scheme
--------------------------------------------------

	use MxcLayoutScheme\Service\LayoutSchemeService;

	...

    public function onBootstrap(MvcEvent $e)
    {
    	$app = $e->getApplication();
    	$em = $app->getEventManager();
		$sem = $em->getSharedManager();
    	$sem->attach('MxcLayoutScheme\Service\MxcLayoutSchemeService',MxcLayoutSchemeService::HOOK_SELECT_SCHEME, 	function($e) {
				// ... compute $schemeName here
				$e->getTarget()->setActiveScheme($schemeName);
			}, 100);
    }


Notes
-----

If you do not define a scheme or if the active scheme does not register a global default layout the ViewManager
configuration gets applied. If you define a global default layout within your schemes it overrides the ViewManager
configuration.

Common use cases for MxcSchemeLayout are 

1. Apply different layouts for mobile devices based on mobile detection and distinct mobile route definitions
2. Apply different layouts for authenticated and anonymous users
3. Apply different layouts for functional modules (themes)

Credits
-------

MxcLayoutScheme was inspired by [EdpModuleLayouts](https://github.com/EvanDotPro/EdpModuleLayouts) by Evan Coury. 
EdpModuleLayouts is a lean and mean module which allows to set module specific layouts. 