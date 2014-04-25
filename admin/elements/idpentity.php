<?php


// no direct access
defined('_JEXEC') or die ;

require_once JPATH_SITE.'/administrator/components/com_samlogin/elements/base.php';

class SamloginFieldIdpEntity extends SamloginField
{
	public function fetchInput()
	{

		//jimport('joomla.filesystem.folder');
		$app = JFactory::getApplication();
		$fieldName = (version_compare(JVERSION, '1.6.0', 'ge')) ? $this->name : $this->options['control'].'['.$this->name.']';
		/*$extension = (version_compare(JVERSION, '1.6.0', 'ge')) ? (string)$this->element->attributes()->extension : $this->element->attributes('extension');
		$type = (JString::strpos($extension, 'com_') === 0) ? 'component' : 'module';
		$basePath = ($type == 'component') ? JPATH_SITE.'/components/'.$extension.'/templates' : JPATH_SITE.'/modules/'.$extension.'/tmpl';
		$baseFolders = JFolder::folders($basePath);*/
             
                
                $metadataFiles=array(
                    JPATH_SITE.'/components/com_samlogin/simplesamlphp/metadata/federations/saml20-idp-remote.php',
                   // JPATH_SITE.'/components/com_samlogin/simplesamlphp/metadata/saml20-idp-hosted.php',
                    JPATH_SITE.'/components/com_samlogin/simplesamlphp/metadata/saml20-idp-remote.php'
                );
                
                $metadata=array();//this will be filled by required files
                
                foreach ($metadataFiles as $metadataFile){
                    if (file_exists($metadataFile)){    
                        @require $metadataFile;
                    }
                }
		//$db = JFactory::getDBO();
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			
		}
		else
		{
			
		}
		
		foreach ($metadata as $entityId=>$details)
		{
                    $entityName=$entityId;
			if (isset($details["name"]) && isset($details["name"]["en"])){
                               $entityName = $details["name"]["en"];
                        }
			$options[] = JHTML::_('select.option', $entityId, $entityName);
		}

		array_unshift($options, JHTML::_('select.option', '', "Use the discovery service"));

		return JHTML::_('select.genericlist', $options, $fieldName, 'class="inputbox"', 'value', 'text', $this->value);
	}

}

class JFormFieldIdpEntity extends SamloginFieldIdpEntity
{
	var $type = 'idpentity';
}

class JElementIdpEntity extends SamloginFieldIdpEntity
{
	var $_name = 'idpentity';
}
