<?php
// no direct access
defined('_JEXEC') or die ;

if (version_compare(JVERSION, '3.0', 'ge'))
{
	jimport('legacy.view.legacy');
	class SAMLoginView extends JViewLegacy
	{
	}

}
else
{
	jimport('joomla.application.component.view');
	class SAMLoginView extends JView
	{
	}

}
