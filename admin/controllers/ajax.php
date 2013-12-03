<?php

defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');

class SAMLoginControllerAjax extends SAMLoginController {

    // Overwriting JView display method
    function display($tpl = null) {

        // Assign data to the view
        $this->item = $this->get('Item');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }

        // Display the view
        parent::display($tpl);
    }

    public function genkey() {
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            $app = JFactory::getApplication();
            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/keymanager.php");
            KeyManager::genkey($app);
            JRequest::setVar("layout","closeme");
            $this->display();
        }
    }
    
    
    public function keyRotateEndPeriod() {
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            $app = JFactory::getApplication();
            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/keymanager.php");
            KeyManager::keyrotateEndPeriod($app);
            JRequest::setVar("layout","closeme");
            $this->display();
        }
    }
    
  

    public function saveSSPconf() {
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            $app = JFactory::getApplication();
            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
         
            $params = JComponentHelper::getParams('com_samlogin');
            $config =  SSPConfManager::mergeParamsWithConf($params,$app);
            $config["samlogin_test"]=time();
            
            SSPConfManager::saveConf($config,$app);
            JRequest::setVar("layout","closeme");
            $this->display();
        }
    }
    
    
    public function installSimpleSAMLphp(){
        $user = JFactory::getUser();
        $app = JFactory::getApplication();
 
        if ($user->authorise('core.admin', 'com_samlogin')) {
          
          $downloadURL= "https://github.com/creativeprogramming/simplesamlphp-samlogin/raw/master/ssp.zip";//"https://github.com/creativeprogramming/simplesamlphp-samlogin/archive/master.zip";
          $zipTmpPath=JPATH_COMPONENT_SITE."/simplesamlphp-samlogin-master.zip";
          $extractDir=JPATH_COMPONENT_SITE."/simplesamlphp/";
          @mkdir($extractDir);
        //  $extractTmpDir=JPATH_COMPONENT_SITE."/simplesamlphp_tmp/";
        //  @mkdir($extractTmpDir);
          if( ini_get('allow_url_fopen') ) {
              $this->_preserveSSPConf($app);
              $app->enqueueMessage("Downloading SimpleSAMLphp...");
              @ob_flush();
              $zipFileData = file_get_contents($downloadURL);
              file_put_contents($zipTmpPath,$zipFileData);
              $zip = new ZipArchive;
                $res = $zip->open($zipTmpPath);
                if ($res === TRUE) {
                  $zip->extractTo($extractDir);
                  $zip->close();
              //    rename($extractTmpDir."/simplesamlphp-samlogin-master", $extractDir);
                } else {
                      $app->enqueueMessage("PHP failed to extract the downloaded .zip file, please".
                   "<a href='$downloadURL'>download manually ssp.zip</a>".
                   //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                   "and extract it to ".$extractDir,"error");
                
                }
             $this->_restorePreservedSSPConf($app);
             $app->enqueueMessage("SimpleSAMLphp installed, please close this window and refresh the dashboard page now.");
          } else{
                      $app->enqueueMessage("PHP failed to download the downloaded .zip file, please".
                   "<a href='$downloadURL'>download manually simplesamlphp-samlogin-master.zip</a>".
                   //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                   "and extract it to ".$extractDir,"error");
                  
          }
         
               
        }else{
            $app->enqueueMessage("Unauthorized","error");
        }
    }
    
    
    private function _preserveSSPConf($app){
                      $filetopreserveArr=[
                          '/components/com_samlogin/simplesamlphp/cert/saml.key',
                           '/components/com_samlogin/simplesamlphp/cert/saml.crt',
                           '/components/com_samlogin/simplesamlphp/config/authsources.php',
                           '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                           '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                           '/components/com_samlogin/simplesamlphp/config/config.php'
                        ];
                      $tmpdir=JFactory::getApplication()->getCfg("tmp_path");
                        foreach ($filetopreserveArr as $filetopreserve){
                                        $app->enqueueMessage( "preserving...".JPATH_SITE.$filetopreserve,"warning");
                            if (JFile::exists(JPATH_SITE.$filetopreserve)){
                              //  echo "preserved..".$tmpdir.$filetopreserve;
                                try{
                                $copyop=JFile::copy(JPATH_SITE.$filetopreserve, JPATH_SITE.$filetopreserve."_TPS");
                                if (!$copyop){
                                    throw new Exception("copy failed");
                                }
                                
                                }catch(Exception $failcopy){
                                       $app->enqueueMessage("failed to preserve conf file: ".$filetopreserve,"error");
                                }
                            }
                        }
       
    }
    
    private function _restorePreservedSSPConf($app){
                        $filetopreserveArr=[
                           '/components/com_samlogin/simplesamlphp/cert/saml.key',
                           '/components/com_samlogin/simplesamlphp/cert/saml.crt',
                           '/components/com_samlogin/simplesamlphp/config/authsources.php',
                           '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                           '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                           '/components/com_samlogin/simplesamlphp/config/config.php' 
                        ];
                        foreach ($filetopreserveArr as $filetorestore){
                            
                            $tmpdir=JFactory::getApplication()->getCfg("tmp_path");
                            if (JFile::exists(JPATH_SITE.$filetorestore."_TPS")){
                                 $app->enqueueMessage("restored ".$filetorestore,"warning");
                              try{
                              $copyop=JFile::move(JPATH_SITE.$filetorestore."_TPS", JPATH_SITE.$filetorestore);
                                if (!$copyop){
                                    throw new Exception("copy failed");
                                } 
                             // $status->confpreserved[]=$filetorestore;
                                }catch(Exception $failcopy){
                                    $app->enqueueMessage("failed to restore conf file: ".$filetorestore,"error");
                                }
                            }
                      }  
    }

}

