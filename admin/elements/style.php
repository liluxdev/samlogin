<?php
/**
 * @version     $Id: style.php 2672 2013-04-03 12:50:12Z lefteris.kavadas $
 * @package     Samlogin
 * @author      JoomlaWorks http://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license     http://www.joomlaworks.net/license
 */

// no direct access
defined('_JEXEC') or die ;

require_once JPATH_SITE.'/administrator/components/com_samlogin/elements/base.php';

class SamloginFieldStyle extends SamloginField
{
	public function fetchInput()
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet(JURI::base(true).'/components/com_samlogin/css/style.css?v=0.7g');
		return NULL;
	}

	public function fetchLabel()
	{
		return NULL;
	}

}

class JFormFieldStyle extends SamloginFieldStyle
{
	var $type = 'style';
}

class JElementStyle extends SamloginFieldStyle
{
	var $_name = 'style';
}
