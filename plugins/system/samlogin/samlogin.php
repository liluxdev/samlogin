<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgSystemSamlogin extends JPlugin {

    public function plgSystemSamlogin(&$subject, $config) {
        parent::__construct($subject, $config);
    }

    public function onAfterRoute() {
        $application = JFactory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
     


        if ($application->isSite() && $view == 'login' && ($option == 'com_users' || $option == 'com_user') && $_SERVER['REQUEST_METHOD'] == 'GET') {
            $return = JRequest::getString("return");
            jimport('joomla.application.component.helper');
            $samloginParams = JComponentHelper::getParams('com_samlogin');

            $overrideLogin = $samloginParams->get('overrideJoomlaLoginPage', 1) == 1;
            if ($overrideLogin) {
                
                $component = JComponentHelper::getComponent('com_samlogin');
                $menu = $application->getMenu();
                $items = version_compare(JVERSION, '2.5', 'ge') ? $menu->getItems('component_id', $component->id) : $menu->getItems('componentid', $component->id);
                if (count($items)) {
                    $router = JSite::getRouter();
                    $link = $router->getMode() == JROUTER_MODE_SEF ? 'index.php?Itemid=' . $items[0]->id : $items[0]->link . '&Itemid=' . $items[0]->id;
                } else {
                    $link = 'index.php?option=com_samlogin&view=login';
                    if (!empty($return)) {
                        $link = 'index.php?option=com_samlogin&view=login&return=' . $return;
                    } else {
                        $link = 'index.php?option=com_samlogin&view=login&return=' . urlencode(base64_encode(JURI::current()));
                        $return = urlencode(base64_encode(JURI::current()));
                    }
                    $customItem = $samloginParams->get('loginPage', 0);
                    if ($customItem) {
                        $link = 'index.php?Itemid=' . $customItem;
                           if (!empty($return)) {
                           $link .= "&return=". $return;
                           }
                    }
                }
                $redirect = JRoute::_($link, false);
                $application->redirect($redirect);
            }
        }
    }

}
