<?php
/**
 * @version		$Id: model.php 2437 2013-01-29 14:14:53Z lefteris.kavadas $
 * @package		SocialConnect
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		http://www.joomlaworks.net/license
 */

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
