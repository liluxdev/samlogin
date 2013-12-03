<?php
defined('_JEXEC') or die ;

// no direct access
defined('_JEXEC') or die ;

jimport('joomla.application.component.controller');

if (version_compare(JVERSION, '3.0', 'ge'))
{
	class SAMLoginController extends JControllerLegacy
	{
		public function display($cachable = false, $urlparams = array())
		{
			parent::display($cachable, $urlparams);
		}

	}

}
elseif (version_compare(JVERSION, '2.5', 'ge'))
{
	class SAMLoginController extends JController
	{
		public function display($cachable = false, $urlparams = false)
		{
			parent::display($cachable, $urlparams);
		}

	}

}
else
{
	class SAMLoginController extends JController
	{
		public function display($cachable = false)
		{
			parent::display($cachable);
		}

	}

}
