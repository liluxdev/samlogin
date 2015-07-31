<?php

defined('_JEXEC') or die; 

if ($this->params->get('enable_samlogin', 1)) {    
     JFactory::getApplication()->redirect($this->ssoLink);          
}
if ($this->params->get('enable_fbconnect', 0)) { 
            	  JFactory::getApplication()->redirect($this->facebookSSOLink);             
}?>
           