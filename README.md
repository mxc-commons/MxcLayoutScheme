MxcLayoutScheme
===============
Version 1.0.0 created by Frank Hein and the mxc-commons team.

MxcLayoutScheme is part of the [maxence openBeee initiative](http://www.maxence.de/mxcweb/index.php/themen/open-business/)
by [maxence business consulting gmbh, Germany](http://www.maxence.de). 

Introduction
------------

Did you ever want to apply a different layout phtml based on the route matched or for a particular module, controller or action? This is what MxcLayoutScheme can do for you. 

MxcLayoutScheme allows to dynamically exchange the layout template used by the renderer. You define layout schemes which are a collection of rules to select layouts. Within each scheme you can assign a distinct layout to a particular route matched. Further you can define a distinct layout for each module, controller and action.

Further, MxcLayoutScheme supports the configuration child ViewModels together with associated view templates to get rendered to captures you define.  

MxcLayoutScheme provides an event interface to allow you to select the layout scheme applied at bootstrap time.

Requirements
------------

* [Zend Framework 2](https://github.com/zendframework/zf2) (latest master)

Features / Goals
----------------

Main design goal of MxcLayoutScheme is to encapsulate the layout specific settings within the applied layout
view template as far as possible. We want to achieve that within the controller action as less as possible remains to be done regarding the layout. So controller programmers can focus on the page `'content'` part of the page regardless of the target layout (which can be very different for different target platforms (JQuery, JQuery Mobile, Dojo, ... whatever).


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

**4. Support to add child ViewModels to the layout by configuration**

**5. Allow selection of active layout scheme based on custom criteria**

- You may select the layout scheme selection in response to an event provided.

**6. Allow configuration of a global default layout for each layout scheme**

**7. [TODO] Default (child) ViewModel variables (fixed and custom computed)**

In the current version you can either assign the layout variables within the controller action via `layoutScheme` controller plugin. Alternatively you may supply an event handler for postprocessing. We provide an example here.

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

- **route_layouts** - Array of rules: `<routeName> => array( 'layout' => <templateName>, ['child_view_models' => array ( 'capture' => 'childTemplateName', ... )]`, where `routeName` is the name of a registered route and `templateName` is the name of a registered template to be used as the layout. `childViewModels` optionally define
 a ViewModels which get added to the layout using addChild. `capture`defines the capture the child view template defined by `childTemplateName`gets rendered to.  Allthough you can define child ViewModels for each rule this is not recommended. We recommend to use `default_child_view_models` (see below) to define defaults and to use rule specific `child_view_models` to override the default settings if needed. See section 'Child Template Names' for further functional options. Default: `array()` (does nothing)
- **mca_layouts** - Array of rules: `<moduleName>[\<controllerName>[\<actionName>]] =>` `array( 'layout' => <templateName>, ['child_view_models' => array ( 'capture' => 'childTemplateName', ... )]`, where moduleName is the name of the module, controllerName is the name of the controller (without the "Controller" suffix (if class name is IndexController then controllerName is Index)), actionName is the name of the the controller action. Setup for each 
`<moduleName>[\<controllerName>[\<actionName>]]` is the same as in `route_layouts` (see above). 
Default: `array()`
- **default** - Array of rules: `'global'` => `<templateName>`. 'global' currently is the only key supported by MxcLayoutScheme. Default: `array()`
- **default_child_view_models** - `array <templateName> => array( <capture> => <childTemplateName>, ...)`. For each template identified by `templateName` you can define a list of child View Models. `capture` identifies the capture the child ViewModel's template idenitified by `templateName` gets rendered to.

Find further documentation and configuration examples in `mxclayoutschemes.global.php.dist`  

How MxcLayoutScheme works
-------------------------

1. On Bootstrap MxcLayoutScheme hooks into the dispatch event of the application's EventManager with low priority (-1000).
2. On Bootstrap MxcLayoutScheme instantiates the controller plugin `'layoutScheme'` to inject a reference to the application's ServiceManager instance.
2. On dispatch MxcLayoutScheme evaluates the current route matched, the module name, the controller name and the action name.
3. Then MxcLayoutScheme triggers an `MxcLayoutSchemeService::HOOK_PRE_SCHEME_SELECT` event. If you registered an event handler for that event in the bootstrap somewhere you can set the active scheme with `$e->getTarget()->setActiveScheme($schemeName)` with a `$schemeName`of your choice.
4. Then, MxcLayoutScheme loads the currently active scheme.  
5. MxcLayoutScheme checks the `route_layouts` for a key matching the matched route name. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. Go to 10.
6. MxcLayoutScheme then checks the `mca_layouts` for a key matching Module\Conroller\Action. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. Go to 10.
7. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching Module\Conroller. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. Go to 10.
8. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching the Module. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. Go to 10.
9. Finally, MxcLayoutScheme checks the `default` for a key `global`. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. 
10. Finally MxcLayoutScheme triggers an `MxcLayoutSchemeService::HOOK_POST_LAYOUT_SELECT` event. You may register an event handler to do custom post processing. Example: Assign variables to the selected layout ViewModel and it's child ViewModels using the controller plugin. See an example below

When applying child layouts MxcLayoutScheme maintains references to the child ViewModels for use by the `'layoutScheme'` controller plugin. The controller plugin enables you to apply variables to the child view models
from within the controller action.

Example Event Handler to choose the active scheme
--------------------------------------------------

	use MxcLayoutScheme\Service\LayoutSchemeService;

	...

    public function onBootstrap(MvcEvent $e)
    {
    	$app = $e->getApplication();
    	$em = $app->getEventManager();
		$sem = $em->getSharedManager();
    	$sem->attach('MxcLayoutScheme\Service\LayoutSchemeService',
			LayoutSchemeService::HOOK_PRE_SCHEME_SELECT, 
			function($e) {
				// ... compute $schemeName here
				$e->getTarget()->setActiveScheme($schemeName);
			}, 100);
    }

Example Event Handler to do some postprocessing after layout selection has finished
-------------------------------------------------------------------------------------

	use MxcLayoutScheme\Service\LayoutSchemeService;

	...

    public function onBootstrap(MvcEvent $e)
    {
    	$app = $e->getApplication();
    	$em = $app->getEventManager();
		$sem = $em->getSharedManager();
    	$sem->attach('MxcLayoutScheme\Service\LayoutSchemeService',
			LayoutSchemeService::HOOK_POST_LAYOUT_SELECT, 
			function($e) {
				$serviceManager = $e->getApplication()->getServiceManager();				
				
				// get the layoutScheme controller plugin for convenience
				$layoutSchemePlugin = $serviceManager
					->get('ControllerPluginManager')->get('layoutScheme');
				
				// get the layoutSchemeService
				$layoutSchemeService = $serviceManager->get('layoutscheme_service');
				
				// inject the applications service manager to the controller plugin
				$layoutSchemePlugin->setServiceManager($serviceManager);

				// Note: If the $controller member of the plugin is null (because we
				// instantiate the plugin here beside normal flow of operation), the 
				// plugin automatically retrieves the controller instance from the
				// layoutSchemeService

				// ready to go

				$variables = array( 
					'berliner' => 'Ich',
					'ein' => 'bin',
					'bin' => 'ein',
					'ich' => 'Berliner! :)',
				);

				// apply this set of variables to the layout view model and all registered
				// registered child view models
				$layoutSchemePlugin->setVariables($variables); 		

			}, 100);
    }

Special Child Template Names
----------------------------

You can configure `default_child_view_models` globally for a particular scheme and override these default settings if needed in each particular rule using `child_view_models`.

A child view model is defined by an array entry `<capture> => <templateName>`. `templateName` is either the name of an explicitly registered template in the ViewManager's `template_map` or a template name which can automatically be resolved via the ViewManager's `template_path_stack`.

Additionally, there are two reserved values which you can use instead: `'<default>'` and `'<none>'` (including < and >)

####templateName `'<default>'`:

If you specify `<default>` as the templateName, MxcLayoutScheme computes a template name which can be resolved by the `TemplatePathStack` resolver. Based on the actual `<module>`, `<controller>` and `<action>` the rule `<capture> => <default>` the `Zend\Filter\Word\CamelCaseToDash` gets applied to the string `<module>\<controller>\<action>-<capture>` and the result gets assigned lowercase to the templateName. 

######Example 1:

May the module be `Reporting`, the controller class `WbsController`, the action be `listAction`.
May the the child ViewModel definition be `'header' => '<default>'`.

The templateName computes to `'reporting\wbs\list-header'`. If you provide a template named `list-header.phtml` within the folder `view\reporting\wbs\` of your module directory it gets found by the `TemplatePathStack` resolver when rendering the `header` capture of the layout (by `<?php echo $this->header ?>` in the layout template)

######Example 2:

May the module be `Reporting`, the controller class `WbsController`, the action be `prjListAction`. 
May the the child ViewModel definition be `'panelLeft' => '<default>'`.

The templateName computes to `'reporting\wbs\prj-list-panel-left'`.

####templateName `'<none>'` :

If you specify `'<none>'` as the templateName the computation of the particular capture gets omitted. There is no need to specify a `'<none>'` rule within the `'default_child_view_models'` section. That would be the same as not specifying that particular capture at all.

`'<none>'` rules are used to override `'default_child_view_models'` for a particular route rule or mca rule.

######Example:

Given a layout template named `'layout\master'` which renders to captures `panelLeft` and `content`. `panelLeft` provides a standard left navigation. Could look like this:

	master.phtml:

		<html>
			<header>
				...
			</header>
			<body>
				<?php if ($this->panelLeft) : ?>
					<div data-role="panel-left">
						<?php echo $this->panelLeft ?>
					</div>
				<?php endif; ?>
				<div data-role="content">
					<?php echo $this->panelLeft ?>
				</div>
			</body>
	</html>

In certain cases you do not want to render a left navigation. The login page is a good example if anonymous users are not allowed to navigate through the application at all.  

If we define have a layout scheme `'master'` defined like this a default child ViewModel gets applied to capture `'panelLeft'` and template `'layout\leftNavigation'`. This child ViewModel gets applied by default every time MxcLayoutScheme assigns the layout `'layout\master'`. The mca rule `'ZfcUser\User\login'` overrides the default
`'panelLeft'` setting by defining `'panelLeft' => '<none>'`. The resulting markup does not contain not contain
the `<div data-role="panel-left"> ... </div>` section at all.

	mxclayout_scheme.global.php:
	
		return array(
			'active_scheme' => 'master',
			'schemes' => array(
				'master' => array(
					'mca_layouts' => array(
						'ZfcUser\User\login' => array(
							'layout' => 'layout\master',
							'child_view_models' => array(
								'panelLeft' => '<none>',
							),
						),
					),
					'default' => array(
						'global' => array(
							'layout' => 'layout\master'
						),
					),
					'default_child_view_models' => array(
						'layout\master' => array(
							'panelLeft' => 'layout\leftNavigation',
						),
					),
				),
			),
		);


The Controller Plugin
---------------------

MxcLayoutScheme registers a controller plugin to allow access to the child view models of the layout applied.
From within a controller action you can access the controller plugin with `$this->layoutScheme`.

`layoutScheme` provides the following interfaces:

**getChildViewModel($capture):** returns the child view model registered for the $capture capture. Null if capture is not registered.
**getChildViewModels():** returns the array of child view models like `array ( 'capture' => ViewModel )`  
**setVariables($param):** see ViewModels `setVariables` for $param specification. The `layoutScheme setVariables` function applies the same variables provided through $params to the controller's layout and to all registered child ViewModels.  

#####Note

If you want or need to assign different sets of variables to the main layout and the child layouts you can do this by explicit access and assignment.

Example:

	$this->layout()->setVariables($varMain); // assign variables to the layout's ViewModel
	$this->layoutScheme()->getChildViewModel('panelLeft')->setVariables($varPanelLeft); // assign variables to leftPanel child

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