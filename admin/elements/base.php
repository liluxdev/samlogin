<?php
/**
 * @version     $Id: base.php 2437 2013-01-29 14:14:53Z lefteris.kavadas $
 * @package     Samlogin
 * @author      JoomlaWorks http://www.joomlaworks.net
 * @copyright   Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license     http://www.joomlaworks.net/license
 */

// no direct access
defined('_JEXEC') or die ;

if (version_compare(JVERSION, '1.6.0', 'lt'))
{
	jimport('joomla.html.parameter.element');
	class SamloginField extends JElement
	{
		public function fetchElement($name, $value, &$node, $controlName)
		{
			$this->setupField($name, $value, $node, $controlName);
			return $this->fetchInput();
		}

		public function fetchTooltip($label, $description, &$node, $controlName, $name)
		{
			if (method_exists($this, 'fetchLabel'))
			{
				$this->setupLabel($name, $label, $description, $node, $controlName);
				return $this->fetchLabel();
			}
			else
			{
				return parent::fetchTooltip($label, $description, $node, $controlName, $name);
			}

		}

		protected function setupField($name, $value, $node, $controlName)
		{
			$this->name = $name;
			$this->value = $value;
			$this->element = $node;
			$this->options['control'] = $controlName;
		}

		protected function setupLabel($name, $label, $description, &$node, $controlName)
		{
			$this->name = $name;
			$this->label = $label;
			$this->description = $description;
			$this->element = $node;
			$this->options['control'] = $controlName;
		}

	}

}
else
{
	jimport('joomla.form.formfield');
	class SamloginField extends JFormField
	{
		function getInput()
		{
			return $this->fetchInput();
		}

		function getLabel()
		{
			if (method_exists($this, 'fetchLabel'))
			{
				return $this->fetchLabel();
			}
			else
			{
				return parent::getLabel();
			}
		}

	}

}
