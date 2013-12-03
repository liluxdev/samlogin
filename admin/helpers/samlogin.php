<?php
// No direct access to this file
defined('_JEXEC') or die;
 
/**
 * SAMLogin component helper.
 */
abstract class SAMLoginHelper
{
        /**
         * Configure the Linkbar.
         */
        public static function addSubmenu($submenu) 
        {
                JSubMenuHelper::addEntry(JText::_('COM_SAMLOGIN_SUBMENU_MESSAGES'),
                                         'index.php?option=com_samlogin', $submenu == 'messages');
                JSubMenuHelper::addEntry(JText::_('COM_SAMLOGIN_SUBMENU_CATEGORIES'),
                                         'index.php?option=com_categories&view=categories&extension=com_samlogin',
                                         $submenu == 'categories');
                // set some global property
                $document = JFactory::getDocument();
                if ($submenu == 'categories') 
                {
                        $document->setTitle(JText::_('COM_SAMLOGIN_ADMINISTRATION_CATEGORIES'));
                }
        }
}