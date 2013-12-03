<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * @package		Joomla.Site
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */
// Set flag that this is a parent file.
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);


if (file_exists(dirname(__FILE__) . '/../../../defines.php')) {
    include_once dirname(__FILE__) . '/../../../defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__FILE__) . "/../../../");
    require_once JPATH_BASE . 'includes/defines.php';
}

require_once JPATH_BASE . 'includes/framework.php';

// Mark afterLoad in the profiler.
JDEBUG ? $_PROFILER->mark('afterLoad') : null;

// Instantiate the application.
$app = JFactory::getApplication('site');

// Initialise the application.
$app->initialise();  //WARN: IF JOOMLA PAGE CACHE IS THIS WILL ENABLE AJAX PAGE CACHE
//BUT IF YOU COMMENT JINPUT DOESN'T WORK
// SO USE A TIME GET ANTI CACHE PARAM FOR NOW

   
// Mark afterIntialise in the profiler.
//JDEBUG ? $_PROFILER->mark('afterInitialise') : null;
//$app->route();

?>
