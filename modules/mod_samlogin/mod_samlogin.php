<?php
/**
 * @version		$Id: mod_socialconnect.php 2437 2013-01-29 14:14:53Z lefteris.kavadas $
 * @package		SocialConnect
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		http://www.joomlaworks.net/license
 */

// no direct access
defined('_JEXEC') or die ;

$user = JFactory::getUser();
$layout = ($user->guest) ? 'default' : 'logout';
$layoutFile=JModuleHelper::getLayoutPath('mod_samlogin', $params->get('template', 'default').'/'.$layout);

require ($layoutFile);
