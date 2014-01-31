<?php
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

$view = JRequest::getCmd('view', 'default');

if (JFile::exists(JPATH_COMPONENT.'/controllers/'.$view.'.php'))
{
    
JLoader::register('SAMLoginHelper', JPATH_COMPONENT_ADMINISTRATOR.'/helpers/samlogin.php');
JLoader::register('SAMLoginController', JPATH_COMPONENT_ADMINISTRATOR.'/controllers/controller.php');
JLoader::register('SAMLoginModel', JPATH_COMPONENT_ADMINISTRATOR.'/models/model.php');
JLoader::register('SAMLoginView', JPATH_COMPONENT_ADMINISTRATOR.'/views/view.php');


//	JLoader::register('SamloginHelper', JPATH_COMPONENT.'/helpers/samlogin.php');
	require_once (JPATH_COMPONENT.'/controllers/'.$view.'.php');
	$classname = 'SAMLoginController'.$view;
	$controller = new $classname();
	$controller->execute(JRequest::getWord('task'));
	$controller->redirect();
}
