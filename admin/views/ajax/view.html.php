<?php


// no direct access
defined('_JEXEC') or die ;

class SAMLoginViewAjax extends SAMLoginView
{

	function display($tpl = null)
	{
            
                $doc=JFactory::getDocument();
                
                $xml = simplexml_load_string(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR."/samlogin.xml"));
                $json = json_encode($xml);
                $componentInfoArray = json_decode($json,TRUE);
                $version=$componentInfoArray["version"];
                $this->assignRef('version', $version);

             
                $user = JFactory::getUser();
		$params = JComponentHelper::getParams('com_samlogin');
		JToolBarHelper::title('', 'samlogin-logo.png');
		$toolbar = JToolBar::getInstance('toolbar');
                
                
                
		if (version_compare(JVERSION, '1.6.0', 'ge'))
		{
			if ($user->authorise('core.admin', 'com_samlogin'))
			{
				JToolBarHelper::preferences('com_samlogin', 480, 740, 'SAMLOGIN_SETTINGS', '', 'window.parent.location.reload()');
                             	//JToolBarHelper::custom('ajax.genkey', "genkey.png", "genkey-over.png", 'SAMLOGIN_GENKEYS', false);
			}
		}
		else
		{
			$toolbar->appendButton('Popup', 'config', 'SAMLOGIN_COM_SETTINGS', 'index.php?option=com_samlogin&view=settings', 720, 480);
		}
		if (version_compare(JVERSION, '3.0', 'ge'))
		{
			JHtml::_('behavior.framework');
                        JHtml::_('jquery.framework');
                       
      
			$helpButton = '
     <button class="btn btn-small" rel="'.JText::_('SAMLOGIN_HELP').'" 
        onClick="
        Joomla.popupWindow(\'http://www.creativeprogramming.it/doc/samlogin\', \''.JText::_('SAMLOGIN_COM_HELP', true).'\', 990, 600, 1)" href="#">'
                                . '<i class="uk-icon-question-circle"></i> '.JText::_('SAMLOGIN_COM_HELP').'</button>'
                                . '';
                       
                        if ($user->authorise('core.admin', 'com_samlogin'))
			{
                            $genkeyButton = '<button class="btn btn-small" rel="'.JText::_('SAMLOGIN_GENKEY').'" onclick="samlogin_regenkeys();" href="#"><i class="uk-icon-random"></i> '.JText::_('SAMLOGIN_GENKEY').'</button>';
                            $toolbar->appendButton('Custom', $genkeyButton);
                            $rotateEndButton = '<button class="btn btn-small" rel="'.JText::_('SAMLOGIN_KEYROTATEEND').'" onclick="samlogin_keyRotateEndPeriod();" href="#"><i class="uk-icon-legal"></i> '.JText::_('SAMLOGIN_KEYROTATE_END').'</button>';
                            $toolbar->appendButton('Custom', $rotateEndButton);    
                            $saveconfButton = '<button class="btn btn-small" rel="'.JText::_('SAMLOGIN_SAVESSPCONF').'" onclick="samlogin_saveSSPConf();" href="#"><i class="uk-icon-download"></i> '.JText::_('SAMLOGIN_SAVESSPCONF').'</button>';
                            $toolbar->appendButton('Custom', $saveconfButton);      
                        }
                        
                }
		else
		{
                     $doc->addScript(JURI::root(true)."/media/samlogin/assets/js/jquery-1.11.0.min.js");
                     $doc->addScript(JURI::root(true)."/media/samlogin/assets/js/jquery-migrate-1.2.1.min.js");
                  //   $doc->addScript("http://www.skypeassets.com/i/scom/js/skype-uri.js");
		
                     $helpButton = '        
<a class="toolbar" onclick="popupWindow(\'http://www.creativeprogramming.it/doc/samlogin\', \''.JText::_('SAMLOGIN_COM_HELP', true).'\', 990, 600, 1)" href="#"><span title="Help" class="icon-32-help"></span>'.JText::_('SAMLOGIN_COM_HELP').'</a>';
		
                        if ($user->authorise('core.admin', 'com_samlogin'))
			{
                            $genkeyButton = '<a class="toolbar" onclick="samlogin_regenkeys();" href="#"><span title="Re generate SSL Keys" class="icon-keygenerate"></span>'.JText::_('SAMLOGIN_GENKEY').'</a>';
                            $toolbar->appendButton('Custom', $genkeyButton);
                             $rotateEndButton = '<a class="toolbar" onclick="samlogin_keyRotateEndPeriod();" href="#"><span title="End Key Rotate Period" class="icon-keyrotate"></span>'.JText::_('SAMLOGIN_KEYROTATE_END').'</a>';
                            $toolbar->appendButton('Custom', $rotateEndButton);
                            $saveconfButton = '<a class="toolbar" onclick="samlogin_saveSSPConf();" href="#"><span title="Write SSP Configuration" class="icon-save-ssp"></span>'.JText::_('SAMLOGIN_SAVESSPCONF').'</a>';
                            $toolbar->appendButton('Custom', $saveconfButton);
                        }
                }
                $baseAjaxURL= JUri::base()."?option=com_samlogin&view=ajax"; //base() returns administrator if called from it
                $doc->addScriptDeclaration("window.samloginBaseAjaxURL='$baseAjaxURL';");
                
                //$doc->addScript("http://www.skypeassets.com/i/scom/js/skype-uri.js");
                 
                
                $doc->addScript("https://samlogin25.creativeprogramming.it/samloginUpdaterVersionCheck.js.php?v=$version");
		
                $doc->addStyleSheet(JURI::root(true)."/media/samlogin/assets/uikit/css/uikit.almost-flat.min.css");
                $doc->addStyleSheet(JURI::root(true)."/media/samlogin/assets/uikit/css/addons/uikit.almost-flat.addons.min.css");
               // $doc->addStyleSheet("/media/samlogin/assets/uikit/css/uikit.min.css");
               // $doc->addStyleSheet("/media/samlogin/assets/uikit/css/addons/uikit.addons.min.css");
                
                $doc->addScript(JURI::root(true)."/media/samlogin/assets/uikit/js/uikit.min.js");
                $doc->addScript(JURI::root(true)."/media/samlogin/assets/uikit/js/addons/notify.min.js");
		
               
                $toolbar->appendButton('Custom', $helpButton); //TODO: redmine ask for support
		$checks = array();
                
                $SSPCheckFile= JPATH_COMPONENT_SITE."/simplesamlphp/VERSION_INFO";
                $vinfo=  file_get_contents($SSPCheckFile);
                if ($vinfo===FALSE){
                       $checks['sspCheck']=false; 
                }else{
                    $checks['sspCheck']=$vinfo; 
                    require_once(JPATH_SITE."/components/com_samlogin/simplesamlphp/lib/_autoload.php");
                    require_once(JPATH_SITE."/components/com_samlogin/simplesamlphp/config/config.php");




                    $checks['sspConfDebug'] = "<pre>".print_r($config,true)."</pre>";

                    $checks['sspConf'] = $config;
                    $checks['metarefresh'] = isset($config["metadata.sources"][1]["directory"]);

                    $checks["metarefreshSAML2IdpLastUpdate"]= @date ("F d Y H:i:s",@filemtime(JPATH_SITE."/components/com_samlogin/simplesamlphp/metadata/federations/saml20-idp-remote.php"));

                    unset($config);
                    require_once(JPATH_SITE."/components/com_samlogin/simplesamlphp/config/authsources.php");
                    $checks['sspAuthsourcesConf'] =  $config;
                    if ( isset($checks['sspAuthsourcesConf']) 
                       && isset($checks['sspAuthsourcesConf']["default-sp"])
                        && isset($checks['sspAuthsourcesConf']["default-sp"]["new_privatekey"])){
                        $checks['keyrotation_msg']= JText::_("SAMLOGIN_KEYROTATION_ON");
                    }else{
                        $checks['keyrotation_msg']=JText::_("SAMLOGIN_KEYROTATION_OFF");
                    }
                    $checks['secretsaltChanged'] = $checks['sspConf']["secretsalt"]=="defaultsecretsalt" ? false : true;
                    $checks['adminpassChanged'] =  $checks['sspConf']["auth.adminpassword"]!="1234"  ? true : false;
                    //die ($checks['sspConf']["auth.adminpassword"].  $checks['adminpassChanged']);
                    $sslTestURL=str_ireplace("http://","https://",JURI::root())."components/com_samlogin/simplesamlphp/www/module.php/saml/sp/metadata.php/default-sp?output=xhtml";


                    $JoomlaBaseURLPath= JURI::root( true );
                //    die($JoomlaBaseURLPath);
                    if ($JoomlaBaseURLPath =="" || stripos("/".$checks['sspConf']["baseurlpath"],$JoomlaBaseURLPath)===0){
                          $checks['baseurlpath']=true;
                    }else{
                         $checks['baseurlpath']=false;
                    }

                    $checks["metadataURL"]=$sslTestURL;

                     $checks["cronLink"]=str_ireplace("http://","https://",JURI::root())."components/com_samlogin/simplesamlphp/www/module.php/cron/cron.php?key=".$params->get("sspcron_secret","changeme")."&tag=hourly&output=xhtml";
                      $checks["cronSuggestion"]=
                    "# Run cron: [hourly]\n".
                    "01 * * * * /usr/bin/curl -k -A \"Mozilla/5.0\" --silent \"". $checks["cronLink"]."\" > /dev/null 2>&1".
                    "";
                      //  die($sslTestURL);
                    $httpHeaders=get_headers($sslTestURL);
                    if ($httpHeaders==FALSE){
                          $checks['sslEnabled']=FALSE;
                    }else{ 
                        $checks['sslEnabled'] = stristr($httpHeaders[0],"200 OK");
                    }
                    require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
                    $SSPKeyURLPath=  SSPConfManager::getCertURLPath();

                    $privatekeyTestURL=str_ireplace("https://","http://",JURI::root()).$SSPKeyURLPath."saml.key";
                   // echo $privatekeyTestURL;
                    $httpHeaders=get_headers($privatekeyTestURL);    
                    if ($httpHeaders==FALSE){
                        $checks['privatekey'] = "<b style='color:orange;'>CAN'T CHECK, please click this <a href='$privatekeyTestURL'>link</a> and ensure it is not showing/downloading a certificate: it must be blank.</b>";
                    }else{ 
                           $checks['privatekey'] = (stristr($httpHeaders[0],"200")!=FALSE) ? 
                                                "<a style='color:red;' href='$privatekeyTestURL'>FAIL</a>" :
                                                 "<a style='color:green;'  href='$privatekeyTestURL'>PASSED</a>";
                    }
                    $privatekeySSLTestURL=str_ireplace("http://","https://",JURI::root()).$SSPKeyURLPath."saml.key";
                  //  die(print_r(get_headers($privatekeySSLTestURL),true));
                    $httpHeaders=get_headers($privatekeySSLTestURL);
                    if ($httpHeaders==FALSE){
                        $checks['privatekeySSL'] = "<b style='color:orange;'>CAN'T CHECK, please click this <a href='$privatekeySSLTestURL'>link</a> and ensure it is not showing/downloading a certificate: it must be blank.</b>";
                    }else{ 
                        $checks['privatekeySSL'] = (stristr($httpHeaders[0],"200")!=FALSE) ? 
                                                "<a style='color:red;' href='$privatekeySSLTestURL'>FAIL</a>" :
                                                 "<a style='color:green;' href='$privatekeySSLTestURL'>PASSED</a>";
                            //FALSE;//"<a href='$privatekeySSLTestURL'>FAIL</a>";
                    }


                    $testURL=str_ireplace("https://","http://",JURI::root())."/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                   // die(print_r(get_headers($testURL),true));
                    $httpHeaders=get_headers($testURL);
                    if ($httpHeaders==FALSE){
                        $checks['logswww'] = "<b style='color:orange;'>CAN'T CHECK, please click this <a href='$testURL'>link</a> and ensure it is not showing/downloading a certificate: it must be blank.</b>";
                    }else{ 
                        $checks['logswww'] = stristr($httpHeaders[0],"200")==FALSE;
                    }

                       $testURL=str_ireplace("http://","https://",JURI::root())."/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                  //  die(print_r(get_headers($privatekeySSLTestURL),true));
                    $httpHeaders=get_headers($testURL);
                    if ($httpHeaders==FALSE){
                        $checks['logswwws'] = "<b style='color:orange;'>CAN'T CHECK, please click this <a href='$testURL'>link</a> and ensure it is not showing/downloading a certificate: it must be blank.</b>";
                    }else{ 
                        $checks['logswwws'] = stristr($httpHeaders[0],"200")==FALSE;
                    }

                $privKeyFile=SSPConfManager::getCertDirPath()."/saml.key";
                    $privKey= file_get_contents($privKeyFile);
                $privKeyDefFile=JPATH_SITE."/components/com_samlogin/simplesamlphp/cert/saml.default.key";
                    $privKeyDef= file_get_contents($privKeyDefFile);

                    if ($privKey==$privKeyDef){
                            $checks['privKeyChanged'] = false;
                    }else{
                          $checks['privKeyChanged'] = true;
                    }


                    $checks['authPlugin'] = JPluginHelper::isEnabled('authentication', 'samlogin');
                    $checks['userPlugin'] = JPluginHelper::isEnabled('user', 'samlogin');
                   
                   
                }
	
		$checks['php'] = phpversion();
		$checks['curl'] = extension_loaded('curl');
                $checks['mcrypt'] = extension_loaded('mcrypt');
                $checks['xml'] = extension_loaded('xml');
		$checks['hash_hmac'] = function_exists('hash_hmac');
		$checks['json'] = extension_loaded('json');
		$this->assignRef('checks', $checks);
                
                //	$component = JTable::getInstance('component');
		//	$component->loadByOption('com_samlogin');
		//	$paramsfull = new JParameter($params, JPATH_ADMINISTRATOR.'/components/com_samlogin/config.xml');
               // die(print_r($params->toArray(),true));
                        $this->assignRef('fields', $this->getForm());
            
		if ($checks['userPlugin'])
		{
			$application = JFactory::getApplication();
			$db = JFactory::getDBO();
			if (version_compare(JVERSION, '2.5', 'ge'))
			{
				$db->setQuery("SELECT element, ordering FROM #__extensions WHERE type = 'plugin' AND folder = 'user' AND (element = 'joomla' OR element = 'samlogin')");
			}
			else
			{
				$db->setQuery("SELECT element, ordering FROM #__plugins WHERE folder = 'user' AND (element = 'joomla' OR element = 'samlogin')");
			}
			$plugins = $db->loadObjectList();
			$orderingValues = array();
			foreach ($plugins as $plugin)
			{
				$orderingValues[$plugin->element] = $plugin->ordering;
			}
			if ($orderingValues['joomla'] > $orderingValues['samlogin'])
			{
				$application->enqueueMessage(JText::_('SAMLOGIN_USER_PLUGIN_ORDERING_NOTICE'), 'notice');
			}
                        
                        
                        if (version_compare(JVERSION, '2.5', 'ge'))
			{
				$db->setQuery("SELECT element, ordering FROM #__extensions WHERE type = 'plugin' AND folder = 'authentication' AND (element = 'joomla' OR element = 'samlogin')");
			}
			else
			{
				$db->setQuery("SELECT element, ordering FROM #__plugins WHERE folder = 'authentication' AND (element = 'joomla' OR element = 'samlogin')");
			}
			$plugins = $db->loadObjectList();
			$orderingValues = array();
			foreach ($plugins as $plugin)
			{
				$orderingValues[$plugin->element] = $plugin->ordering;
			}
			if ($orderingValues['joomla'] > $orderingValues['samlogin'])
			{
				$application->enqueueMessage(JText::_('SAMLOGIN_AUTH_PLUGIN_ORDERING_NOTICE'), 'notice');
			}
                        
                        
			if (JFile::exists(JPATH_ADMINISTRATOR.'/components/com_k2/k2.php'))
			{
				$db->setQuery("SELECT COUNT(*) FROM #__k2_user_groups");
				$result = $db->loadResult();
				if (!$result)
				{
					$application->enqueueMessage(JText::_('SAMLOGIN_K2USERGROUN_UNSET_NOTICE'), 'notice');
				}
			}
		}
		parent::display($tpl);
	}
        
        
        public function getForm()
	{
		$option = "com_samlogin";
		if (version_compare(JVERSION, '2.5.0', 'ge'))
		{
			$component = JComponentHelper::getComponent($option);
			JForm::addFormPath(JPATH_ADMINISTRATOR.'/components/'.$option);
			$form = JForm::getInstance($option.'.settings', 'config', array('control' => 'jform'), false, '/config');
			$form->bind($component->params);
		}
		else
		{
			$component = JTable::getInstance('component');
			$component->loadByOption($option);
			$form = new JParameter($component->params, JPATH_ADMINISTRATOR.DS.'components'.DS.$option.DS.'config.xml');
		}
		return $form;
	}
       
	public function save()
	{
		$option = $this->getState('option');
		$data = $this->getState('data');
		if (version_compare(JVERSION, '2.5.0', 'ge'))
		{
			$table = JTable::getInstance('extension');

			// Save the rules.
			if (isset($data['params']) && isset($data['params']['rules']))
			{
				$rules = new JAccessRules($data['params']['rules']);
				$asset = JTable::getInstance('asset');

				if (!$asset->loadByName($data['option']))
				{
					$root = JTable::getInstance('asset');
					$root->loadByName('root.1');
					$asset->name = $data['option'];
					$asset->title = $data['option'];
					$asset->setLocation($root->id, 'last-child');
				}
				$asset->rules = (string)$rules;

				if (!$asset->check() || !$asset->store())
				{
					$this->setError($asset->getError());
					return false;
				}

				// We don't need this anymore
				unset($data['option']);
				unset($data['params']['rules']);
			}

			// Load the previous Data
			if (!$table->load($data['id']))
			{
				$this->setError($table->getError());
				return false;
			}

			unset($data['id']);

			// Bind the data.
			if (!$table->bind($data))
			{
				$this->setError($table->getError());
				return false;
			}

			// Check the data.
			if (!$table->check())
			{
				$this->setError($table->getError());
				return false;
			}

			// Store the data.
			if (!$table->store())
			{
				$this->setError($table->getError());
				return false;
			}

			// Clean the component cache.
			$this->cleanCache('_system');

			return true;
		}
		else
		{
			$component = JTable::getInstance('component');
			$component->loadByOption($option);
			$component->bind($data);
			if (!$component->check())
			{
				$this->setError($component->getError());
				return false;
			}
			if (!$component->store())
			{
				$this->setError($component->getError());
				return false;
			}
		}
		return true;
	}


}
