<?php


defined('_JEXEC') or die ;

jimport('joomla.application.component.model');

if (version_compare(JVERSION, '3.0', 'ge'))
{
	class SAMLoginModel extends JModelLegacy
	{
	}

}
else
{
	class SAMLoginModel extends JModel
	{
	}

}
