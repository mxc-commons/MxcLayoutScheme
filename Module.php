<?php
/**
 * This file is placed here for compatibility with Zendframework 2's ModuleManager.
 * It allows usage of this module even without composer.
 * The original Module.php is in 'src/MxcLayoutScheme' in order to respect PSR-0
 */

// modules.zendframework.com does not recognize this module without the next two lines  
namespace MxcLayoutScheme;
class Module {}

require_once __DIR__ . '/src/MxcLayoutScheme/Module.php';
