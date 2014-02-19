<?php
// no direct access
defined('_JEXEC') or die;
@ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9'); //avoid being blocked

if (version_compare(JVERSION, '1.6.0', 'ge'))
{
	$user = JFactory::getUser();
	if (!$user->authorise('core.manage', 'com_samlogin'))
	{
		JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
		$mainframe = JFactory::getApplication();
		$mainframe->redirect('index.php');
	}
}

// import joomla controller library
jimport('joomla.application.component.controller');
// Add css
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::base(true).'/components/com_samlogin/resources/css/style.css?v=1');
JText::script();
JText::script("SAMLOGIN_JS_CONFIRM_GENKEY");
JText::script("SAMLOGIN_JS_CONFIRM_ROTATEKEY");
JText::script("SAMLOGIN_JS_CONFIRM_WRITESSP");
JText::script("SAMLOGIN_JS_CONFIRM_INSTALLSSP");

$document->addScriptDeclaration("window.JS_JOOMLA_JURI_ADMIN_BASE='".JURI::base(true)."'");
$document->addScript(JURI::base(true).'/components/com_samlogin/resources/js/samlogin.js?v=5');

JLoader::register('SAMLoginController', JPATH_COMPONENT.'/controllers/controller.php');
JLoader::register('SAMLoginModel', JPATH_COMPONENT.'/models/model.php');
JLoader::register('SAMLoginView', JPATH_COMPONENT.'/views/view.php');



$view = JRequest::getCmd('view', 'ajax');
JLoader::register('SAMLoginController'.$view, JPATH_COMPONENT.'/controllers/'.$view.'.php');
$classname = 'SAMLoginController'.$view;

$controller = new $classname();

$controller->execute(JRequest::getWord('task'));
$controller->redirect();
