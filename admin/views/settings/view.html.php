<?php

// no direct access
defined('_JEXEC') or die;

class SAMLoginViewSettings extends SAMLoginView
{

	function display($tpl = null)
	{
		JHTML::_('behavior.tooltip');
		jimport('joomla.html.pane');
		
		$model = JModel::getInstance('Settings', 'SAMLoginModel');

	
		$params = $model->getParams();
		$this->assignRef('params', $params);
		$pane = JPane::getInstance('Tabs');
		$this->assignRef('pane', $pane);
		parent::display($tpl);
	}

}
