<?php
// no direct access
defined('_JEXEC') or die ;

class SamloginViewMessage extends SAMLoginView
{

	function display($tpl = null)
	{       
		$this->addViewVariables();
		$this->setPageTitle();
		$this->loadHelper('Samlogin');
		SamloginHelper::loadHeadData($this->params, 'component');
		SamloginHelper::setUserData($this->user);
		$componentParams = JComponentHelper::getParams('com_samlogin');
	
	      
		$layout = ($this->user->guest) ? 'default' : 'authenticated';
		$this->setLayout($layout);
		$this->addTemplatePaths();
		parent::display();
	}



	private function getParams()
	{
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			$application = JFactory::getApplication();
			$params = $application->getParams('com_samlogin');
		}
		else
		{
			$params = JComponentHelper::getParams('com_samlogin');
		}
		return $params;
	}

	private function addTemplatePaths()
	{
		// Get application
		$application = JFactory::getApplication();

		// Get params
		$params = $this->getParams();

		// Look for template files in component folders
		$this->addTemplatePath(JPATH_COMPONENT.'/templates/message');
		$this->addTemplatePath(JPATH_COMPONENT.'/templates/message/default');

		// Look for overrides in template folder (Component template structure)
		$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/templates/message/');
		$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/templates/message/default');

		// Look for overrides in template folder (Joomla! template structure)
		$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/message/default');
		$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/message/');

		// Look for specific Component theme files
		if ($params->get('template', 'default'))
		{
			$this->addTemplatePath(JPATH_COMPONENT.'/templates/message/'.$params->get('template', 'default'));
			$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/templates/message/'.$params->get('template', 'default'));
			$this->addTemplatePath(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/message/'.$params->get('template', 'default'));
		}
	}

	private function setPageTitle()
	{
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			$application = JFactory::getApplication();
			$params = $this->getParams();
			$title = $params->get('page_title');
			if ($application->getCfg('sitename_pagetitles', 0) == 1)
			{
				$title = JText::sprintf('JPAGETITLE', $application->getCfg('sitename'), $params->get('page_title'));
			}
			elseif ($application->getCfg('sitename_pagetitles', 0) == 2)
			{
				$title = JText::sprintf('JPAGETITLE', $params->get('page_title'), $application->getCfg('sitename'));
			}
			$document = JFactory::getDocument();
			$document->setTitle($title);
		}
	}

	private function addStyles()
	{
		$document = JFactory::getDocument();
		$params = $this->getParams();
		$application = JFactory::getApplication();
		if (JFile::exists(JPATH_SITE.'/templates/'.$application->getTemplate().'/html/com_samlogin/disojuice/'.$params->get('template', 'default').'/css/style.css'))
		{
			$document->addStylesheet(JURI::root(true).'/templates/'.$application->getTemplate().'/html/com_samlogin/message/'.$params->get('template', 'default').'/css/style.css?v=0.7');
		}
		else
		{
			$document->addStylesheet(JURI::root(true).'/components/com_samlogin/templates/message/'.$params->get('template', 'default').'/css/style.css?v=0.7');
		}

	}

	private function addViewVariables()
	{
		$user = JFactory::getUser();
		$this->assignRef('user', $user);
		$params = $this->getParams();
		$this->assignRef('params', $params);
	}

}
