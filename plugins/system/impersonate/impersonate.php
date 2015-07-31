<?php

defined('_JEXEC') or die;


class PlgSystemImpersonate extends JPlugin
{
    
     public function onBeforeRender()
    {  
       
        $app = JFactory::getApplication();
        $doc = JFactory::getDocument();
        $option = $app->input->get('option', null, 'cmd');
        $view = $app->input->get('view', null, 'cmd');
        $layout = $app->input->get('layout', null, 'cmd');
        $id = $app->input->get('id', 0, 'int');
      
     
        if ($app->isAdmin() && $option == 'com_users' && $view == 'user' && $layout == 'edit' && $id) {

            $js = '<script type="text/javascript">
			Joomla.submitbuttonOld = Joomla.submitbutton;
			Joomla.submitbutton = function(task) {
			if(task == "switchuser") {
			window.open("' . JURI::root() . 'index.php?su=1&uid=' . $id . '");
			return false;
			}else{
			Joomla.submitbuttonOld(task);
			}
			}</script>';

            $content = $doc->getBuffer('component');
            $content = $content . $js;
            $doc->setBuffer($content, 'component');

            JToolBarHelper::divider();
            JToolBarHelper::custom('switchuser', 'upload', 'upload', 'Switch to User', false);
        }
    }

    public function onAfterInitialise()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $user = JFactory::getUser();
        $userId = $app->input->getInt('uid', 0, 'int');

        if ($app->isAdmin() || !$app->input->get('su', 0, 'int') || !$userId) {
            return;
        }

        if ($user->id == $userId) {
            return $app->redirect('index.php', JText::sprintf('You already logged in as user &quot;%s&quot;', $user->name), 'warning');
        }

        if ($user->id) {
            return $app->redirect('index.php', JText::_('You would login as another user, please logout first'), 'warning');
        }

        $query = $db->getQuery(true)
            ->select('userid')
            ->from('#__session')
            ->where('session_id = ' . $db->quote($app->input->cookie->get(md5(JApplication::getHash('administrator')))))
            ->where('client_id = 1')
            ->where('guest = 0');

        $db->setQuery($query);

        if (!$db->loadResult()) {
            return $app->redirect('index.php', JText::_('Back-end User Session Expired'), 'error');
        }

        $instance = JFactory::getUser($userId);

        if ($instance instanceof Exception) {
            return $app->redirect('index.php', JText::_('User login failed'), 'error');
        }

        if ($instance->get('block') == 1) {
            return $app->redirect('index.php', JText::_('JERROR_NOLOGIN_BLOCKED'), 'error');
        }

        $instance->set('guest', 0);

        $session = JFactory::getSession();
        $session->set('user', $instance);

        $app->checkSession();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__session'))
            ->set($db->quoteName('guest') . ' = ' . $db->quote($instance->get('guest')))
            ->set($db->quoteName('username') . ' = ' . $db->quote($instance->get('username')))
            ->set($db->quoteName('userid') . ' = ' . (int)$instance->get('id'))
            ->where($db->quoteName('session_id') . ' = ' . $db->quote($session->getId()));
        $db->setQuery($query);
        $db->execute();

        $app->redirect('index.php', JText::sprintf('You have login successfully as user &quot;%s&quot;', $instance->name));
    }
    
}
