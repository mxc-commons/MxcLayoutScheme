MxcLayoutScheme
===============
Version 0.4.0 created by Frank Hein and the mxc-commons team.

MxcLayoutScheme is part of the [maxence openBeee initiative](http://www.maxence.de/mxcweb/index.php/themen/open-business/)
by [maxence business consulting gmbh, Germany](http://www.maxence.de). 

Introduction
------------

Did you ever want to apply a different layout phtml based on the route matched or for a particular module, controller or action? This is what MxcLayoutScheme can do for you. 

MxcLayoutScheme allows to dynamically exchange the layout template used by the renderer. You define layout schemes which are a collection of rules to select layouts. Within each scheme you can assign a distinct layout to a particular route matched. Further you can define a distinct layout for each module, controller and action.

MxcLayoutScheme supports the configuration child ViewModels together with associated view templates to get rendered to captures you define.

Further, MxcLayoutScheme intercepts dispatch errors. You can apply layouts for particular error codes and http status codes the same way you do for routes
and controllers.   

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

**7. Provide hooks for pre- and postprocessing**

**8. Provide a controller plugin to control scheme selection and setup of layout variables**

**9. Provide support for dispatch errors**

In the current version you can either assign the layout variables within the controller action via `layoutScheme` controller plugin. Alternatively you may supply an event handler for pre- and postprocessing. We provide an example here.

Installation
------------

### Main Setup

#### By cloning project

1. Clone this project into your `./vendor/` directory.

#### With composer

1. Add this project in your composer.json:

		json

			"require": {
				"mxc-commons/mxc-generics": "dev-master,
				"mxc-commons/mxc-layoutscheme": "dev-master"
			}


2. Now tell composer to download MxcLayoutScheme by running the command:

	    bash

	    $ php composer.phar update
    

#### Post installation

1. Enabling it in your `application.config.php`file.

		php

		    <?php
		    return array(
		        'modules' => array(
		            // ...
					'MxcGenerics',
		            'MxcLayoutScheme',
		        ),
		        // ...
		    );

2. Copy options file 'mxclayoutscheme.global.php.dist' to your configuration\autoload directory. Rename it to 'mxclayoutscheme.global.php'.


Options
-------

The MxcLayoutScheme module has options to setup and select layout schemes. After installing MxcLayoutScheme, copy
`./vendor/maxence/MxcLayoutScheme/config/mxclayoutscheme.global.php.dist` to
`./config/autoload/mxclayoutscheme.global.php` and change the values as desired.

`Options are structured in two sections:

- **defaults** - Settings to control service operation
- **options** -  Settings for different layout schemes

		'mxclayoutscheme' => array(
			'defaults' => array(
				'active_scheme' => 'myScheme',   //-- name of your layout scheme definition 
				'enable_mca_layouts' => true,    //-- apply layouts based on
										    	 //-- module, controller, action 
				'enable_route_layouts' => true   //-- apply layouts based on routes`
				'enable_error_layouts' => true;  //-- apply layouts for dispatch errors
				'enable_status_layouts' => true; //-- apply layouts based on response status codes
			),
			'options' => array(
				<scheme-definition>,
				...
			),
		);


- **active_scheme** - Name of the scheme which is active by default. Default is `zf2-standard`. 
- **enable_mca_layouts** - Rules for module, controller, action get applied for layout selection. Default: `true`
- **enable_route_layouts** - Rules for module, controller, action get applied for layout selection. Default: `true`
- **enable_status_layouts** - Rules based on status code get applied for layout selection on dispatch errors. Default: `true`
- **enable_error_layouts** - Rules based on event error code get applied for layout selection on dispatch errors. Default: `true`


Each `<scheme-definition>` is structured into four sections:

		'myScheme' => array(
			'mca_layouts' => array(
				'options' => array(
					<mca-rule-definition>,
					...
				),
				'defaults => array(
					<default-settings>
				),
			),
			'route_layouts' => array(
				'options' => array(
					<route-rule-definition>,
					...
				),
				'defaults => array(
					<default-settings>
				),
			),
			'error_layouts' => array(
				'options' => array(
					<error-rule-definition>,
					...
				),
				'defaults => array(
					<default-settings>
				),
			),
			'status_layouts' => array(
				'options' => array(
					<status-rule-definition>,
					...
				),
				'defaults => array(
					<default-settings>
				),
			),
		);
				
Each of these sections is optional (regardless of the `enable_xxxLayouts` settings).

Rule Definition Keys
--

All `<xxx-rule-definition>` have identical structure. The array key specifies the rule,
the values specify the layout templates to apply.


#### `<mca_layouts>`

Keys for `<mca_layouts>` are like `<moduleName>[\<controllerName>[\<actionName>]]`, 
where `<moduleName>` is the name of the module, `<controllerName>` is the name of the controller (without 'Controller' suffix), `<actionName>` is the name of the the controller action.
		
		Examples

			'MyModule'							//--- applies to all controllers in module
			'MyModule\MyController'				//--- applies to all actions of a controller
			'MyModule\MyController\MyAction'	//--- applies to a particular controller action

Action rules are evaluated before controller rules. Controller rules are evaluated before Module rules. So if you apply an action rule and a module rule for the same module, the
module rule applies for all controllers and actions but the one action which has an own
rule.
			

#### `<route_layouts>`	

Keys for `<route_layouts>` are like `<route>`, where `<route>` is a registered route. 

#### `<status_layouts>`	

Keys for `<status_layouts>` are like `<status>`, where `<status>` is status code (type string).

		Example

			'403'			//--- applies to status code 403
 
#### `<error_layouts>`

Keys for `<error_layouts>` are like `<error>`, where `<error>` is the error code returned by the `MvcEvent` (type string).

Rule Definition Values
---

Each rule definition is a list of values like `<capture> => <template>`. The special capture `'layout'` defines the layout applied to the root ViewModel. All other captures define child ViewModels which get applied to the root ViewModel with the capture `<capture>` using the template `<template>`. Template names must be resolvable by either TemplatePathStack or TemplateMap resolvers.

		Example mca rule:

			'MyModule\MyController\index' => array(
				'layout' 	=> 'layout/layout',
				'panelLeft' => 'layout/panel-left',
				'header' 	=> 'layout/header',
				'footer' 	=> 'layout/footer',
			),


Rule Construction
---

While constructing the layout set to apply the service initializes the set with the values from the `'defaults'` section. Then, if a rule matches, the particular set associated with the rule overrides/extends the default set.

		Example

			route_layouts => array(
				'options' => array(
					'home' => array(
						'panelLeft' => 'layout/panel-left'
					),
				),
				'defaults' => array(
					'layout' => 'layout/layout',
					'header' => 'layout/header',
					'footer' => 'layout/footer',
				),
			),
 
Accessing the route `home` causes a match of the according rule. Defaults get applied and afterwards the settings from the matched rule. So the resulting template set is:

		Example result

			array(
				'panelLeft' => 'layout/panel-left'
				'layout' => 'layout/layout',
				'header' => 'layout/header',
				'footer' => 'layout/footer',
			),
 


How MxcLayoutScheme works
-------------------------

1. On Bootstrap MxcLayoutScheme hooks into the dispatch event of the application's EventManager with low priority (-1000).
2. On Bootstrap MxcLayoutScheme instantiates the controller plugin `'layoutScheme'` to inject a reference to the application's ServiceManager instance.
3. On dispatch MxcLayoutScheme evaluates the current route matched, the module name, the controller name and the action name.
4. Then MxcLayoutScheme triggers an `MxcLayoutSchemeService::HOOK_PRE_SELECT_SCHEME` event. If you registered an event handler for that event in the bootstrap somewhere you can set the active scheme with `$e->getTarget()->setActiveScheme($schemeName)` with a `$schemeName`of your choice. Alternatively, you can set the active scheme within the controller action using the controller plugin: `$this->layoutScheme()->setActiveScheme($schemeName)`.
5. Then, MxcLayoutScheme loads the currently active scheme.  
6. MxcLayoutScheme checks the `route_layouts` for a key matching the matched route name. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. If match continue at 11.
7. MxcLayoutScheme then checks the `mca_layouts` for a key matching Module\Conroller\Action. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. If match continue at 11.
8. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching Module\Conroller. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. If match continue at 11.
9. Then, MxcLayoutScheme checks the `mca_layouts` for a key matching the Module. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. If match continue at 11.
10. Then, MxcLayoutScheme checks the `default` for a key `global`. If the key exists the layout template registered to that match gets applied. If the rule defines child view models they get merged with the (optionally) defined default child view models and get applied to the layout. 
11. Finally MxcLayoutScheme triggers an `MxcLayoutSchemeService::HOOK_POST_LAYOUT_SELECT` event. You may register an event handler to do custom post processing. Example: Assign variables to the selected layout ViewModel and it's child ViewModels using the controller plugin. See an example below

When applying child layouts MxcLayoutScheme maintains references to the child ViewModels for use by the `'layoutScheme'` controller plugin. The controller plugin enables you to apply variables to the child view models
from within the controller action.

How MxcLayoutScheme handles dispatch errors
-------------------------------------------

When a dispatch error occurs, MxcLayoutScheme first checks for a rule applying to the error code from the `$event->getError()`. If no rule applies MxcLayoutScheme then checks for a rule applying to the status code from $event->getResponse()->getStatusCode().


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
			LayoutSchemeService::HOOK_PRE_SELECT_SCHEME, 
			function($e) {
				switch ($weather) {
					case 'sunny':
						$schemeName = 'sun';
						break;
					case 'rainy':
						$schemeName = 'umbrella';
						break;
					default:
						$schemeName = 'default';
						break;
				}
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
        		LayoutSchemeService::HOOK_POST_SELECT_LAYOUT,
        		function($e) {
        			$variables = array(
        					'berliner' => 'Ich',
        					'ein' => 'bin',
        					'bin' => 'ein',
        					'ich' => 'Berliner! :)'
        			);
        
        			// apply this set of variables to the layout view model and all registered
        			// registered child view models
        			$e->getTarget()->setVariables($variables);
        
        		}, 100);

    }

Special Child Template Names
----------------------------

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
					<?php echo $this->content ?>
				</div>
			</body>
	</html>

In some cases you may not want to render a left navigation. The login page is a good example if anonymous users are not allowed to navigate through the application at all.  

If we define have a layout scheme `'master'` defined like this a default child ViewModel gets applied to capture `'panelLeft'` and template `'layout\leftNavigation'`. This child ViewModel gets applied by default every time MxcLayoutScheme assigns the layout `'layout\master'`. The mca rule `'ZfcUser\User\login'` overrides the default
`'panelLeft'` setting by defining `'panelLeft' => '<none>'`. The resulting markup does not contain not contain
the `<div data-role="panel-left"> ... </div>` section at all.

	mxclayout_scheme.global.php:
	
		return array(
			'options' => array(
				'master' => array(
					'mca_layouts' => array(
						'options' => array(
							'ZfcUser\User\login' => array(
								'layout' => 'layout\master',
								'panelLeft' => '<none>',
							),
						),
						'defaults' => array(
							'panelLeft' => 'layout\leftNavigation',
						),
					),
				),
			),
			'defaults': 
				'active_scheme' => 'master',
				'enable_mca_layouts => true,
				'enable_route_layouts => true,
				'enable_error_layouts => true,
				'enable_status_layouts => true,
		);


The Controller Plugin
---------------------

MxcLayoutScheme registers a controller plugin to allow access to the child view models of the layout applied.
From within a controller action you can access the controller plugin with `$this->layoutScheme`.

`layoutScheme` provides the following interfaces:


**getChildViewModel($capture):** returns the child view model registered for the $capture capture. Null if capture is not registered.

**getChildViewModels():** returns the array of child view models like `array ( 'capture' => ViewModel )`  

**setVariables($variables, $override = false):** see ViewModels `setVariables` for parameter specification. The `layoutScheme setVariables` function applies the same variables provided through `$variables` to the controller's layout and to all registered child ViewModels.  

**getActiveScheme():** get the currently active layout scheme. 

**setActiveScheme($schemeName, $skipPreSelectSchemeEvent = false):** set the layout scheme to use. Set `skipPreSelectSchemeEvent` to `true` then the `LayoutSchemeService::HOOK_PRE_SELECT_EVENT` will not get triggered by `LayoutSchemeService`.

**skipPreSelectSchemeEvent():** disable triggering of `LayoutSchemeService::HOOK_PRE_SELECT_SCHEME` this time. This event would get triggered after the view action before rendering. You may want to disable the event to prevent the global event handler to override the active scheme you set via `$this->layoutScheme()->setActiveScheme()`. 
######Note:
The event is not skipped by default. Within the global event handler you can check if the active scheme was set by the controller plugin using `LayoutSchemeService->hasPluginSetActiveScheme()` to prevent overriding.

**skipPostSelectLayoutEvent():** disable triggering of `LayoutSchemeService::HOOK_POST_SELECT_LAYOUT` this time.

**hasPluginSetActiveScheme()**: returns `true` if the active scheme was set previously through by the user through `layoutScheme()->setActiveScheme($schemeName)`.

#####Note

If you want or need to assign different sets of variables to the main layout and the child layouts you can do this by explicit access and assignment.

Example:

	$this->layout()->setVariables($varMain); // assign variables to the layout's ViewModel
	$this->layoutScheme()->getChildViewModel('panelLeft')->setVariables($varPanelLeft); // assign variables to leftPanel child

Important:

`LayoutSchemeService` registers to the `MvcEvent::EVENT_DISPATCH` with priority of -1000. So layout selection
happens *after* your controller action has returned *before* the page gets rendered. This means in particular that at the time the controller action is executed the `LayoutSchemeService` does not know anything about the layout selected and it's child view models.    

To get around this `LayoutSchemeService` was designed for early execution of the onDispatch event handler if called via the controller plugin. If this preponed event handling happens the service prevents event processing to happen again when triggered globally afterwards. Even in early processing the service triggers the `HOOK_PRE_SELECT_SCHEME` and `HOOK_POST_SELECT_LAYOUT` events.

If you do not want one or both events to get triggered make sure to call `layoutScheme()->skipPostSelectLayoutEvent()` and/or `layoutScheme()->skipPreSelectSchemeEvent()` *before* you call `getChildViewModel()`, `getChildViewModels()` or `setVariables()`. Otherwise the event(s) will get fired.   

Notes
-----

If you do not define a scheme or if the active scheme does not register a global default layout the ViewManager
configuration gets applied. If you define a global default layout within your schemes it overrides the ViewManager
configuration.

Common use cases for MxcSchemeLayout are 

1. Apply different layouts for mobile devices based on mobile detection and distinct mobile route definitions
2. Apply different layouts for authenticated and anonymous users or based on user roles
3. Apply different layouts for functional modules (themes)

Credits
-------

MxcLayoutScheme was inspired by [EdpModuleLayouts](https://github.com/EvanDotPro/EdpModuleLayouts) by Evan Coury. 
EdpModuleLayouts is a lean and mean module which allows to set module specific layouts.

License
-------

MxcLayoutScheme is released under the New BSD License. See `license.txt`. 