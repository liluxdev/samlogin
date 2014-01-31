<?php


// no direct access
defined('_JEXEC') or die ;

$user = JFactory::getUser();
$layout = ($user->guest) ? 'default' : 'logout';
$layoutFile=JModuleHelper::getLayoutPath('mod_samlogin', $params->get('template', 'default').'/'.$layout);

require ($layoutFile);
