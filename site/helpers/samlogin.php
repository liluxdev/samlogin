<?php
// no direct access
defined('_JEXEC') or die ;

class SamloginHelper
{

	public static function setUserData(&$user)
	{
		$session = JFactory::getSession();
		if (!$user->guest)
		{
			if ($session->get('SAMLoginIdP'))
			{
				$user->samloginIdP = $session->get('SAMLoginIdP');
			}
		
			if (!isset($user->samloginImage) || empty($user->samloginImage))
			{
				$user->samloginImage = 'http://www.gravatar.com/avatar/'.md5($user->email).'?s=80&d='.urlencode(JURI::root().'media/samlogin/images/avatar.jpg');
			}
		}
	}

	public static function setVariables($params)
	{
		$user = JFactory::getUser();
		$variables = array();
		$variables['returnURL'] = self::getReturnURL($params);
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			$variables['option'] = 'com_users';
			$variables['task'] = ($user->guest) ? 'user.login' : 'user.logout';
			$variables['resetPasswordLink'] = JRoute::_('index.php?option=com_users&view=reset');
			$variables['remindUsernameLink'] = JRoute::_('index.php?option=com_users&view=remind');
			$variables['registrationLink'] = JRoute::_('index.php?option=com_users&view=registration');
			$variables['passwordFieldName'] = 'password';
		}
		else
		{
			$variables['option'] = 'com_user';
			$variables['task'] = ($user->guest) ? 'login' : 'logout';
			$variables['resetPasswordLink'] = JRoute::_('index.php?option=com_user&view=reset');
			$variables['remindUsernameLink'] = JRoute::_('index.php?option=com_user&view=remind');
			$variables['registrationLink'] = JRoute::_('index.php?option=com_user&view=register');
			$variables['passwordFieldName'] = 'passwd';
		}

		$variables['introductionMessage'] = ($params->get('introductionMessage') == 'custom') ? $params->get('customIntroductionMessage') : JText::_('JW_SC_LOGIN_INTRODUCTION_MESSAGE_VALUE');
		$variables['registrationMessage'] = ($params->get('registrationMessage') == 'custom') ? $params->get('customRegistrationMessage') : JText::_('JW_SC_LOGIN_REGISTRATION_MESSAGE_VALUE');
		$variables['signInMessage'] = ($params->get('signInMessage') == 'custom') ? $params->get('customSignInMessage') : JText::_('JW_SC_LOGIN_SIGN_IN_MESSAGE_VALUE');
		$variables['footerMessage'] = ($params->get('footerMessage') == 'custom') ? $params->get('customFooterMessage') : JText::_('JW_SC_LOGIN_FOOTER_MESSAGE_VALUE');
		$variables['rememberMe'] = JPluginHelper::isEnabled('system', 'remember');
	        	
                $variables['ssoLink'] = JRoute::_('index.php?option=com_samlogin&view=login&task=initSSO&return='.$variables['returnURL']);  
	
          	$variables['accountLink'] = JRoute::_((version_compare(JVERSION, '1.6.0', 'ge')) ? 'index.php?option=com_users&view=profile&layout=edit' : 'index.php?option=com_user&view=user&task=edit');
		return $variables;
	}

	public static function loadHeadData(&$params, $type = 'module',$view="login")
	{

		jimport('joomla.filesystem.file');
		JHTML::_('behavior.modal');
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$document = JFactory::getDocument();
		$template = $params->get('template', 'default');
		if ($type == 'component')
		{
			if (JFile::exists(JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_samlogin/'.$view.'/'.$template.'/css/style.css'))
			{
				$document->addStylesheet(JURI::root(true).'/templates/'.$mainframe->getTemplate().'/html/com_samlogin/'.$view.'/'.$template.'/css/style.css?v=0.7b');
			}
			else
			{
				$document->addStylesheet(JURI::root(true).'/components/com_samlogin/templates/'.$view.'/'.$template.'/css/style.css?v=0.7b');
			}
		}
		else
		{
			if (JFile::exists(JPATH_SITE.'/templates/'.$mainframe->getTemplate().'/html/com_samlogin/'.$view.'/'.$template.'/css/style.css'))
			{
				$document->addStylesheet(JURI::root(true).'/templates/'.$mainframe->getTemplate().'/html/com_samlogin/'.$view.'/'.$template.'/css/style.css?v=0.7b');
			}
			else
			{
				$document->addStylesheet(JURI::root(true).'/modules/com_samlogin/tmpl/'.$view.'/'.$template.'/css/style.css?v=0.7b');
			}
		}
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			$componentParams = JComponentHelper::getParams('com_samlogin');
		}
		else
		{
			$component = JComponentHelper::getComponent('com_samlogin');
			$componentParams = new JParameter($component->params);
		}
		$params->merge($componentParams);
		$usersConfig = JComponentHelper::getParams('com_users');
		$params->set('allowUserRegistration', $usersConfig->get('allowUserRegistration'));


		//$document->addScript(JURI::root(true).'/components/com_samlogin/js/samlogin.js?v=1.0.2');

	}

	public static function getReturnURL($params)
	{
		$user = JFactory::getUser();
		$type = ($user->guest) ? 'login' : 'logout';
		if ($itemid = $params->get($type))
		{
			$mainframe = JFactory::getApplication();
			$menu = $mainframe->getMenu();
			$item = $menu->getItem($itemid);
			if ($item)
			{
				$url = JRoute::_('index.php?Itemid='.$itemid, false);
			}
			else
			{
				$uri = JFactory::getURI();
				$url = $uri->toString(array('path', 'query', 'fragment'));
			}
		}
		else
		{
			$uri = JFactory::getURI();
			$url = $uri->toString(array('path', 'query', 'fragment'));
		}
                
                if ($params->get('usesecure',false)){
                   $url = strtr($url,array("http://"=>"https://"));
                }
		return base64_encode($url);
	}

	public static function getMenu($params)
	{
		$user = JFactory::getUser();
		$mainframe = JFactory::getApplication();
		$menu = $mainframe->getMenu();
		$links = array();
		if ($user->guest || !$params->get('menutype'))
		{
			return $links;
		}
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			require_once (JPATH_SITE.'/modules/mod_menu/helper.php');
			$params->set('showAllChildren', 1);
			$links = modMenuHelper::getList($params);
		}
		else
		{
			$links = $menu->getItems('menutype', $params->get('menutype'));
		}

		$active = $menu->getActive();
		$activeID = isset($active) ? $active->id : $menu->getDefault()->id;
		$path = isset($active) ? $active->tree : array();
		$popUpOptions = $options = 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,'.$params->get('window_open');
		foreach ($links as $link)
		{

			if (version_compare(JVERSION, '1.6.0', 'ge'))
			{
				$link->href = $link->flink;
			}
			else
			{
				$link->title = $link->name;
				$link->level = $link->sublevel;
				switch ($link->type)
				{
					case 'separator' :
						continue;
						break;

					case 'url' :
						if ((strpos($link->link, 'index.php?') === 0) && (strpos($link->link, 'Itemid=') === false))
						{
							$link->url = $link->link.'&amp;Itemid='.$link->id;
						}
						else
						{
							$link->url = $link->link;
						}
						break;

					default :
						$router = JSite::getRouter();
						$link->url = $router->getMode() == JROUTER_MODE_SEF ? 'index.php?Itemid='.$link->id : $link->link.'&Itemid='.$link->id;
						break;
				}

				$iParams = version_compare(JVERSION, '1.6.0', 'ge') ? new JRegistry($link->params) : new JParameter($link->params);
				$iSecure = $iParams->def('secure', 0);
				if ($link->home == 1)
				{
					$link->url = JURI::base();
				}
				elseif (strcasecmp(substr($link->url, 0, 4), 'http') && (strpos($link->link, 'index.php?') !== false))
				{
					$link->url = JRoute::_($link->url, true, $iSecure);
				}
				else
				{
					$link->url = str_replace('&', '&amp;', $link->url);
				}
				$link->href = $link->url;
			}

			// Build the class attribute
			$link->class = 'item-'.$link->id;
			if ($link->id == $activeID)
			{
				$link->class .= ' current';
			}
			if (in_array($link->id, $path))
			{
				$link->class .= ' active';
			}
			elseif ($link->type == 'alias')
			{
				$aliasToId = $link->params->get('aliasoptions');
				if (count($path) > 0 && $aliasToId == $path[count($path) - 1])
				{
					$link->class .= ' active';
				}
				elseif (in_array($aliasToId, $path))
				{
					$link->class .= ' alias-parent-active';
				}
			}
			if (isset($link->deeper) && $link->deeper)
			{
				$link->class .= ' deeper';
			}
			if ($link->parent)
			{
				$link->class .= ' parent';
			}
			if (!empty($class))
			{
				$link->class = trim($link->class);
			}

		}
		return $links;
	}

	public static function getK2Menu()
	{
		jimport('joomla.filesystem.file');
		$user = JFactory::getUser();
		$links = array();
		if ($user->guest || !JFile::exists(JPATH_SITE.'/components/com_k2/k2.php'))
		{
			return $links;
		}
		require_once (JPATH_SITE.'/components/com_k2/helpers/utilities.php');
		require_once (JPATH_SITE.'/components/com_k2/helpers/permissions.php');
		if (JRequest::getCmd('option') != 'com_k2')
		{
			K2HelperPermissions::setPermissions();
		}
		if (K2HelperPermissions::canAddItem())
		{
			$links['add'] = JRoute::_('index.php?option=com_k2&view=item&task=add&tmpl=component');
		}
		require_once (JPATH_SITE.'/components/com_k2/helpers/route.php');
		$links['user'] = JRoute::_(K2HelperRoute::getUserRoute($user->id));
		$links['comments'] = JRoute::_('index.php?option=com_k2&view=comments&tmpl=component');
		return $links;
	}

	public static function getK2Avatar($user)
	{
		$avatar = null;
		$db = JFactory::getDBO();
		$query = "SELECT id FROM #__k2_users WHERE userID = ".(int)$user->id;
		$db->setQuery($query);
		$K2UserID = $db->loadResult();
		if ($K2UserID)
		{
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
			$row = JTable::getInstance('K2User', 'Table');
			$row->load($K2UserID);
			if ($row->image)
			{
				$avatar = JURI::root(true).'/media/k2/users/'.$row->image;
			}
		}
		return $avatar;
	}



}
