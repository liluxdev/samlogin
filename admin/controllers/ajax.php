<?php

defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');
//Import filesystem libraries.
jimport('joomla.filesystem.file');

class SAMLoginControllerAjax extends SAMLoginController {

    function sendAjaxHeaders() {
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header('Content-type: application/json');
    }

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
            echo KeyManager::genkey($app);
            //   JRequest::setVar("layout", "closeme");
            //   $this->display();
        }
        die();
    }

    public function keyRotateEndPeriod() {
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            $app = JFactory::getApplication();
            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/keymanager.php");
            echo KeyManager::keyrotateEndPeriod($app);
            //JRequest::setVar("layout", "closeme");
            //$this->display();
        }
        die();
    }

    public function saveSSPconf() {
        $this->sendAjaxHeaders();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            $app = JFactory::getApplication();
            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");

            $params = JComponentHelper::getParams('com_samlogin');
            $config = SSPConfManager::mergeParamsWithConf($params, $app);
            $config["samlogin_test"] = time();

            echo SSPConfManager::saveConf($config, $app);
            // JRequest::setVar("layout", "closeme");
            // $this->display();
        }
        die();
    }

    public function installSimpleSAMLphp_download() {
        $this->sendAjaxHeaders();
        ini_set('user_agent', 'Mozilla/5.0 (Linux; U; Linux; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
        $toret = array();
        $toret['additionalMessages'] = array(); //messae to toast
        $toret["bytes"] = 0;
        $user = JFactory::getUser();
        $app = JFactory::getApplication();
        require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/downloader.php");

        if ($user->authorise('core.admin', 'com_samlogin')) {
            $downloadURLs = [
                "1.11.n" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/ssp.zip",
                "1.11.f" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/ssp.f.zip",
                "1.11.n-a" => "http://creativeprogramming.it/dev/dist/ssp.zip",
                "1.11.f-a" => "http://creativeprogramming.it/dev/dist/ssp.f.zip",
            ];

            $dlid = str_ireplace("../", "", $_GET["dlid"]);
            if (!isset($dlid)) {
                die("no download id specified");
            }
            $downloadURL = $downloadURLs[$dlid];
            if (!isset($downloadURL)) {
                die("no valid download id");
            }

            ////"https://github.com/creativeprogramming/simplesamlphp-samlogin/archive/master.zip";
            // $downloadURLFallback=""
            $zipTmpPath = JPATH_COMPONENT_SITE . "/simplesamlphp-samlogin-master_$dlid.zip";

            $lockFile = $zipTmpPath . ".dllockfile";
            $lock = file_exists($lockFile);
            if ($lock) {
                if ((time() - filemtime($lockFile)) < 60 * 3) {
                    $toret['additionalMessages'][] = array("msg" => "Cannot aquire exclusive lock, please retry in few minutes, maybe another user is installing it now", "level" => "warning");
                    echo json_encode($toret);
                    die();
                } else {
                    //lock expired
                }
            }
            file_put_contents($lockFile, "-");

            $extractDir = JPATH_COMPONENT_SITE . "/simplesamlphp/";
            @mkdir($extractDir);

            $this->_preserveSSPConf($app,$toret);

                $httpHeaders = get_headers($downloadURL);
                if ($httpHeaders !== FALSE) {
                    $responseIs200OK = stristr($httpHeaders[0], "200 OK") || stristr($httpHeaders[0], "302 Found");
                    if ($responseIs200OK) {
                        
                    } else {
                        $msg = "Failed to download from this repository, try alternate repository";

                        $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
                        echo json_encode($toret);
                        $lockClean=unlink($lockFile);
                        if (!$lockClean){
                                   $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
                        }
                        
                        die();
                    }
                }
            $zipFileData = SamloginHelperDownloader::downloadAndReturn($downloadURL); //file_get_contents($downloadURL);
            if (!$zipFileData) {
                $msg = "PHP failed to download .zip file, please" .
                        "<a href='$downloadURL'>download manually ssp.zip</a>" .
                        //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                        "and extract it to " . $extractDir;

                $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
            }
            file_put_contents($zipTmpPath, $zipFileData);
            $bytes = filesize($zipTmpPath);
            $toret['additionalMessages'][] = array("msg" => $bytes . " bytes downloaded.", "level" => "success");
            $toret["bytes"] = $bytes;
            // $app->enqueueMessage(filesize($zipTmpPath) . " bytes written.");
            $zipFileData = file_get_contents($zipTmpPath);
            if (!$zipFileData) {
                $msg = "PHP failed to write .zip file, please check your filesystem write permission on path $extractDir or " .
                        "<a href='$downloadURL'>download manually ssp.zip</a>" .
                        //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                        "and extract it to " . $extractDir;
                $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
            }
               $lockClean=unlink($lockFile);
                        if (!$lockClean){
                                   $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
                        }
        } else {
            $toret['additionalMessages'][] = array("msg" => "Your administrator login session expired or you are not authorized ", "level" => "danger");
        }

        echo json_encode($toret);
        die();
    }

    public function installSimpleSAMLphp_extract() {
        $this->sendAjaxHeaders();
        ini_set('user_agent', 'Mozilla/5.0 (Linux; U; Linux; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
        $toret = array();
        $toret['additionalMessages'] = array(); //messae to toast
        $toret["bytes"] = 0;

        $toret['versionInfo'] = FALSE;

        $user = JFactory::getUser();
        $app = JFactory::getApplication();

        if ($user->authorise('core.admin', 'com_samlogin')) {


            $dlid = str_ireplace("../", "", $_GET["dlid"]);
            if (!isset($dlid)) {
                die("no download id specified");
            }


            ////"https://github.com/creativeprogramming/simplesamlphp-samlogin/archive/master.zip";
            // $downloadURLFallback=""
            $zipTmpPath = JPATH_COMPONENT_SITE . "/simplesamlphp-samlogin-master_$dlid.zip";


            $lockFile = JPATH_COMPONENT_SITE . "/simplesamlphp-samlogin-master.extract.lockfile";
            $lock = file_exists($lockFile);
            if ($lock) {
                if ((time() - filemtime($lockFile)) < 60 * 3) {
                    $toret['additionalMessages'][] = array("msg" => "Cannot aquire exclusive lock, please retry in few minutes, maybe another user is installing it now", "level" => "warning");
                    echo json_encode($toret);
                    die();
                } else {
                    //lock expired
                }
            }
            file_put_contents($lockFile, "-");

            $extractDir = JPATH_COMPONENT_SITE . "/simplesamlphp/";
            @mkdir($extractDir);
            //  $extractTmpDir=JPATH_COMPONENT_SITE."/simplesamlphp_tmp/";
            //  @mkdir($extractTmpDir);
            //$checkIfCanDownload=ini_get('allow_url_fopen');

            $zip = new ZipArchive;
            $res = $zip->open($zipTmpPath);
            if ($res === TRUE) {
                $zip->extractTo($extractDir);
                $zip->close();
                $toret['versionInfo'] = file_get_contents($extractDir . "/VERSION_INFO");
                if ($toret['versionInfo']) {
                    $toret['additionalMessages'][] = array("msg" => "SimpleSAMLphp installed (" . $toret['versionInfo'] . "),"
                        . " dashboard page will be refreshed soon.", "level" => "success");
                } else {
                    $msg = "PHP failed to extract to $extractDir the downloaded .zip file ($zipTmpPath), please" .
                            "<a href='$downloadURL'>download manually simplesamlphp-samlogin-master.zip</a>" .
                            //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                            "and extract it to " . $extractDir;
                    $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
                }

                //    rename($extractTmpDir."/simplesamlphp-samlogin-master", $extractDir);
            } else {
                $msg = "PHP failed to extract the downloaded .zip file ($zipTmpPath), please" .
                        "<a href='$downloadURL'>download manually simplesamlphp-samlogin-master.zip</a>" .
                        //"and place it at ".$zipTmpPath." on your server (FTP upload?)".
                        "and extract it to " . $extractDir;
                $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
            }
            $this->_restorePreservedSSPConf($app);
            // $app->enqueueMessage("SimpleSAMLphp installed, please close this window and refresh the dashboard page now.");

             $lockClean=unlink($lockFile);
                        if (!$lockClean){
                                   $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
                        }
        } else {
            //  $app->enqueueMessage("Unauthorized", "error");
            $toret['additionalMessages'][] = array("msg" => "Your administrator login session expired or you are not authorized ", "level" => "danger");
        }
        echo json_encode($toret);
        die();
    }


    public function doConfigTests() {
        $this->sendAjaxHeaders();
        $user = JFactory::getUser();
        $app = JFactory::getApplication();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            //error_reporting(0);

            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/downloader.php");
            $xml = simplexml_load_string(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR . "/samlogin.xml"));
            $json = json_encode($xml);
            $componentInfoArray = json_decode($json, TRUE);
            $version = $componentInfoArray["version"];

            $user = JFactory::getUser();
            $params = JComponentHelper::getParams('com_samlogin');

            $checks = array();
            $checks['additionalMessages'] = array(); //messae to toast

            $SSPCheckFile = JPATH_COMPONENT_SITE . "/simplesamlphp/VERSION_INFO";
            $vinfo = file_get_contents($SSPCheckFile);
            if ($vinfo === FALSE) {
                $checks['sspCheck'] = false;
            } else {
                $checks['sspCheck'] = $vinfo;
                require_once(JPATH_SITE . "/components/com_samlogin/simplesamlphp/lib/_autoload.php");
                require_once(JPATH_SITE . "/components/com_samlogin/simplesamlphp/config/config.php");




                // $checks['sspConfDebug'] = "<pre>" . print_r($config, true) . "</pre>";

                $sspConf = $config;
                $checks['metarefresh'] = isset($config["metadata.sources"][1]["directory"]);

                $checks["metarefreshSAML2IdpLastUpdate"] = @date("F d Y H:i:s", @filemtime(JPATH_SITE . "/components/com_samlogin/simplesamlphp/metadata/federations/saml20-idp-remote.php"));

                unset($config);
                require_once(JPATH_SITE . "/components/com_samlogin/simplesamlphp/config/authsources.php");
                $sspAuthsourcesConfig = $config;
                if (isset($sspAuthsourcesConfig) && isset($sspAuthsourcesConfig["default-sp"]) && isset($sspAuthsourcesConfig["default-sp"]["new_privatekey"])) {
                    $checks['keyrotation'] = true;
                } else {
                    $checks['keyrotation'] = false;
                }
                unset($config);
                $checks['secretsaltChanged'] = $sspConf["secretsalt"] == "defaultsecretsalt" ? false : true;
                $checks['adminpassChanged'] = $sspConf["auth.adminpassword"] != "1234" ? true : false;
                //die ($checks['sspConf']["auth.adminpassword"].  $checks['adminpassChanged']);

                $nonsslTestURL = str_ireplace("https://", "http://", JURI::root()) . "/components/com_samlogin/simplesamlphp/www/module.php/saml/sp/metadata.php/default-sp?output=xhtml";
                $sslTestURL = str_ireplace("http://", "https://", JURI::root()) . "/components/com_samlogin/simplesamlphp/www/module.php/saml/sp/metadata.php/default-sp?output=xhtml";


                $JoomlaBaseURLPath = JURI::root(true);
                //    die($JoomlaBaseURLPath);
                if ($JoomlaBaseURLPath == "" || stripos("/" . $sspConf["baseurlpath"], $JoomlaBaseURLPath) === 0) {
                    $checks['baseurlpath'] = true;
                } else {
                    $checks['baseurlpath'] = false;
                }

                $checks["metadataURL"] = $sslTestURL;

                $checks["cronLink"] = str_ireplace("http://", "https://", JURI::root()) . "/components/com_samlogin/simplesamlphp/www/module.php/cron/cron.php?key=" . $params->get("sspcron_secret", "changeme") . "&tag=hourly";
                $checks["cronSuggestion"] = "# Run cron: [hourly]\n" .
                        "01 * * * * curl -k --silent \"" . $checks["cronLink"] . "\" > /dev/null 2>&1" .
                        "";
                //  die($sslTestURL);

                $metadataSSLPageContent = SamloginHelperDownloader::downloadAndReturn($nonsslTestURL);
                if ($metadataSSLPageContent == FALSE) {
                    $checks['metadataPublished'] = FALSE;
                } else {
                    $checks['metadataPublished'] = stristr($metadataSSLPageContent, "EntityDescriptor") ? TRUE : $metadataSSLPageContent;
                }


                $metadataSSLPageContent = SamloginHelperDownloader::downloadAndReturn($sslTestURL);
                if ($metadataSSLPageContent == FALSE) {
                    $checks['metadataPublishedSSL'] = FALSE;
                } else {
                    $checks['metadataPublishedSSL'] = stristr($metadataSSLPageContent, "EntityDescriptor") ? TRUE : $metadataSSLPageContent;
                    // $checks['metadataDebugPage']= $metadataSSLPageContent;
                }
                require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
                $SSPKeyURLPath = SSPConfManager::getCertURLPath();

                $privatekeyTestURL = str_ireplace("https://", "http://", JURI::root()) . $SSPKeyURLPath . "saml.key";
                // echo $privatekeyTestURL;
                $privatekeyTestURLContent = SamloginHelperDownloader::downloadAndReturn($privatekeyTestURL);
                $checks['privatekey'] = $privatekeyTestURLContent === FALSE ? TRUE : $privatekeyTestURLContent;
                if ($checks['privatekey'] !== FALSE) {
                    $httpHeaders = get_headers($privatekeyTestURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['privatekey'] = FALSE;
                        } else {
                            $checks['privatekey'] = TRUE;
                        }
                    }
                }


                $privatekeySSLTestURL = str_ireplace("http://", "https://", JURI::root()) . $SSPKeyURLPath . "saml.key";
                //  die(print_r(get_headers($privatekeySSLTestURL),true));
                $privatekeySSLTestURLContent = SamloginHelperDownloader::downloadAndReturn($privatekeySSLTestURL);
                $checks['privatekeySSL'] = $privatekeySSLTestURLContent === FALSE ? TRUE : $privatekeySSLTestURLContent;
                if ($checks['privatekeySSL'] !== FALSE) {
                    $httpHeaders = get_headers($privatekeySSLTestURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['privatekeySSL'] = FALSE;
                        } else {
                            $checks['privatekeySSL'] = TRUE;
                        }
                    }
                }


                $testURL = str_ireplace("https://", "http://", JURI::root()) . "/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                // die(print_r(get_headers($testURL),true));
                $logUrlContent = SamloginHelperDownloader::downloadAndReturn($testURL);
                $checks['logswww'] = $logUrlContent === FALSE ? TRUE : $logUrlContent;
                if ($checks['logswww'] !== FALSE) {
                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['logswww'] = FALSE;
                        } else {
                            $checks['logswww'] = TRUE;
                        }
                    }
                }



                $testURL = str_ireplace("http://", "https://", JURI::root()) . "/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                $logUrlContent = SamloginHelperDownloader::downloadAndReturn($testURL);
                $checks['logswwws'] = $logUrlContent === FALSE ? TRUE : $logUrlContent;
                if ($checks['logswwws'] !== FALSE) {
                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['logswwws'] = FALSE;
                        } else {
                            $checks['logswwws'] = TRUE;
                        }
                    }
                }

                $privKeyFile = SSPConfManager::getCertDirPath() . "/saml.key";
                $privKey = file_get_contents($privKeyFile);
                $privKeyDefFile = JPATH_SITE . "/components/com_samlogin/simplesamlphp/cert/saml.default.key";
                $privKeyDef = file_get_contents($privKeyDefFile);

                if ($privKey == $privKeyDef) {
                    $checks['privKeyChanged'] = false;
                } else {
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




            if ($checks['userPlugin']) {
                $application = JFactory::getApplication();
                $db = JFactory::getDBO();
                if (version_compare(JVERSION, '2.5', 'ge')) {
                    $db->setQuery("SELECT element, ordering FROM #__extensions WHERE type = 'plugin' AND folder = 'user' AND (element = 'joomla' OR element = 'samlogin')");
                } else {
                    $db->setQuery("SELECT element, ordering FROM #__plugins WHERE folder = 'user' AND (element = 'joomla' OR element = 'samlogin')");
                }
                $plugins = $db->loadObjectList();
                $orderingValues = array();
                foreach ($plugins as $plugin) {
                    $orderingValues[$plugin->element] = $plugin->ordering;
                }
                $checks['userPlugin'] = "On and ordered as #" . $orderingValues['samlogin'];
                if ($orderingValues['joomla'] > $orderingValues['samlogin']) {
                    //     $application->enqueueMessage(JText::_('SAMLOGIN_USER_PLUGIN_ORDERING_NOTICE'), 'notice');
                    $checks['additionalMessages'][] = array("msg" => "SAMLogin user plugin is ordered after the Joomla user plugin. Please go in the Plugin manager and order samlogin first ", "level" => "warning");
                }
            }
            if ($checks['authPlugin']) {
                $application = JFactory::getApplication();
                $db = JFactory::getDBO();
                if (version_compare(JVERSION, '2.5', 'ge')) {
                    $db->setQuery("SELECT element, ordering FROM #__extensions WHERE type = 'plugin' AND folder = 'authentication' AND (element = 'joomla' OR element = 'samlogin')");
                } else {
                    $db->setQuery("SELECT element, ordering FROM #__plugins WHERE folder = 'authentication' AND (element = 'joomla' OR element = 'samlogin')");
                }

                $plugins = $db->loadObjectList();
                $orderingValues = array();
                foreach ($plugins as $plugin) {
                    $orderingValues[$plugin->element] = $plugin->ordering;
                }

                if ($orderingValues['joomla'] < $orderingValues['samlogin']) {
                    //    $application->enqueueMessage(JText::_('SAMLOGIN_AUTH_PLUGIN_ORDERING_NOTICE'), 'notice');
                    $checks['authPlugin'] = "<i class='uk-icon-warning'></i> On but bad order <a target='_blank' class='uk-button uk-button-mini uk-button-danger' href='" . JUri::base() . "?option=com_plugins&view=plugins'>go to plugin manager</a>";
                    $checks['additionalMessages'][] = array("msg" => "SAMLogin authentication plugin is ordered after the Joomla authentication plugin. Please go in the Plugin manager and order samlogin first ", "level" => "warning");
                } else {
                    $checks['authPlugin'] = "On and ordered correctly";
                }
            }
        } else {
            $checks['additionalMessages'] = array();
            $checks['additionalMessages'][] = array("msg" => "Your administrator login session expired or you are not authorized ", "level" => "danger");
        }

        print json_encode($checks);
        die();
    }

    private function _preserveSSPConf($app,&$toret) {
      
            $lockFile = JPATH_COMPONENT_SITE . "/preserveConf.lockfile";
            $lock = file_exists($lockFile);
            if ($lock) {
                if ((time() - filemtime($lockFile)) < 60 * 3) {
                    $toret['additionalMessages'][] = array("msg" => "Preserving configuration file skipped, lock already present", "level" => "warning");
                   // echo json_encode($toret);
                  //  die();
                } else {
                    //lock expired
                }
            }
            file_put_contents($lockFile, "-");

        $filetopreserveArr = array(
            '/components/com_samlogin/simplesamlphp/cert/saml.key',
            '/components/com_samlogin/simplesamlphp/cert/saml.crt',
            '/components/com_samlogin/simplesamlphp/config/authsources.php',
            '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
            '/components/com_samlogin/simplesamlphp/config/module_cron.php',
            '/components/com_samlogin/simplesamlphp/config/config.php'
        );

        $tmpdir = JFactory::getApplication()->getCfg("tmp_path");
        foreach ($filetopreserveArr as $filetopreserve) {
            //  $app->enqueueMessage("preserving..." . JPATH_SITE . $filetopreserve, "warning");
            if (JFile::exists(JPATH_SITE . $filetopreserve)) {
                //  echo "preserved..".$tmpdir.$filetopreserve;
                try {
                    $copyop = JFile::copy(JPATH_SITE . $filetopreserve, JPATH_SITE . $filetopreserve . "_TPS");
                    if (!$copyop) {
                        throw new Exception("copy failed");
                    }
                } catch (Exception $failcopy) {
                    // $app->enqueueMessage("failed to preserve conf file: " . $filetopreserve, "error");
                }
            }
        }
      //DO IT IN RESTORE:  unlink($lockFile);
    }

    private function _restorePreservedSSPConf($app) {
        
        
        $filetopreserveArr = array(
            '/components/com_samlogin/simplesamlphp/cert/saml.key',
            '/components/com_samlogin/simplesamlphp/cert/saml.crt',
            '/components/com_samlogin/simplesamlphp/config/authsources.php',
            '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
            '/components/com_samlogin/simplesamlphp/config/module_cron.php',
            '/components/com_samlogin/simplesamlphp/config/config.php'
        );
        foreach ($filetopreserveArr as $filetorestore) {

            $tmpdir = JFactory::getApplication()->getCfg("tmp_path");
            if (JFile::exists(JPATH_SITE . $filetorestore . "_TPS")) {
                // $app->enqueueMessage("restored " . $filetorestore, "warning");
                try {
                    $copyop = JFile::move(JPATH_SITE . $filetorestore . "_TPS", JPATH_SITE . $filetorestore);
                    if (!$copyop) {
                        throw new Exception("copy failed");
                    }
                    // $status->confpreserved[]=$filetorestore;
                } catch (Exception $failcopy) {
                    //   $app->enqueueMessage("failed to restore conf file: " . $filetorestore, "error");
                }
            }
        }
        $lockFile = JPATH_COMPONENT_SITE . "/preserveConf.lockfile";
           $lockClean=unlink($lockFile);
                        if (!$lockClean){
                                   $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
                        }
    }

}
