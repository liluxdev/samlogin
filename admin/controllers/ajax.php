<?php

defined('_JEXEC') or die;
jimport('joomla.application.component.controlleradmin');
//Import filesystem libraries.
jimport('joomla.filesystem.file');

class SAMLoginControllerAjax extends SAMLoginController {

    public static function aquireLock($lockname) {
        $toret = array();
        $lockFile = JPATH_COMPONENT_SITE . "/$lockname.lockfile";
        $lock = file_exists($lockFile);
        if ($lock) {
            if ((time() - filemtime($lockFile)) < 60 * 3) {
                // $toret['additionalMessages'][] = array("msg" => "Cannot aquire exclusive lock, please retry in few minutes, maybe another user is installing it now", "level" => "warning");
                return false;
            } else {
                //lock expired
            }
        }
        file_put_contents($lockFile, "-");
        return true;
    }

    public static function aquireLockTerminateOnFail($lockname) {
        $toret = array();
        $lockFile = JPATH_COMPONENT_SITE . "/$lockname.lockfile";
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
        return true;
    }

    public static function releaseLock($lockname) {
        $toret = array();
        $lockFile = JPATH_COMPONENT_SITE . "/$lockname.lockfile";
        $lock = file_exists($lockFile);
        if ($lock) {
            unlink($lockFile);
        }
    }

    static function sendAjaxHeaders() {
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header('Content-type: application/json');
    }

    static $AJAX_MESSAGE_INFO = "info";
    static $AJAX_MESSAGE_SUCCSS = "success";
    static $AJAX_MESSAGE_WARNING = "warning";
    static $AJAX_MESSAGE_DANGER = "danger";

    /**
     * 
     * @param type $msg message to push
     * @param type $level severity level: info (default) ,success,warning,danger (use AJAX_MESSAGE constants)
     */
    public static function enqueueAjaxMessage($msg, $level) {
        if (!isset($level)) {
            $level = self::$AJAX_MESSAGE_INFO;
        }
        self::$ajaxBuffer['additionalMessages'][] = array("msg" => $msg, "level" => $level);
    }

    public static function appendAjaxReturnVar($name, $value) {
        self::$ajaxBuffer[$name] = $value;
    }

    private static $ajaxBuffer = array();

    protected static function initAjaxBuffer() {
        self::$ajaxBuffer = array();
        self::$ajaxBuffer['additionalMessages'] = array(); //messae to toast
    }

    protected static function sendAjaxBuffer() {
        echo json_encode(self::$ajaxBuffer);
        die();
    }

    public function getParametersJSON() {

        self::sendAjaxHeaders();

        self::initAjaxBuffer();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            //  $params = JComponentHelper::getParams('com_samlogin');
            /*  	$component = JTable::getInstance('component');
              $component->loadByOption('com_samlogin');
              $instance = new JParameter($component->params, JPATH_ADMINISTRATOR.'/components/com_samlogin/config.xml');
             */
            self::appendAjaxReturnVar("params", $instance);
        }

        self::sendAjaxBuffer();
    }

    public static function _saveParams() {
        $option = "com_samlogin";

        if (version_compare(JVERSION, '2.5.0', 'ge')) {


            $data = JRequest::getVar('jform', array(), 'post', 'array');


            // Validate the form
            JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/' . $option);
            $form = JForm::getInstance('com_samlogin.settings', 'config', array(
                        'control' => 'jform',
                        'load_data' => true
                            ), false, '/config');

            // Use Joomla! model for saving settings
            if (version_compare(JVERSION, '3.2', 'ge')) {
                require_once JPATH_SITE . '/components/com_config/model/cms.php';
                require_once JPATH_SITE . '/components/com_config/model/form.php';
            }

            JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_config/models');
            $model = JModelLegacy::getInstance('Component', 'ConfigModel');
            $params = $model->validate($form, $data);
            if ($params === false) {
                $errors = $model->getErrors();
                $msg = $errors[0] instanceof Exception ? $errors[0]->getMessage() : $errors[0];
                self::enqueueAjaxMessage($msg, "warning");
            }

            $data = array(
                'params' => $params,
                //'id' => $id,
                'option' => $option
            );
        } else {
            //JRequest::checkToken() or jexit('Invalid Token');
            $data = JRequest::get('post');
        }


        if (version_compare(JVERSION, '2.5.0', 'ge')) {
            $component = JComponentHelper::getComponent($option);
            $data["id"] = $component->id;
            JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/' . $option);
            $form = JForm::getInstance($option . '.settings', 'config', array('control' => 'jform'), false, '/config');
            $form->bind($component->params);
        } else {
            $component = JTable::getInstance('component');
            $component->loadByOption($option);
            $data["id"] = $component->id;

            $form = new JParameter($component->params, JPATH_ADMINISTRATOR . DS . 'components' . DS . $option . DS . 'config.xml');
        }


        // die(print_r($component, true));


        if (version_compare(JVERSION, '2.5.0', 'ge')) {

            $table = JTable::getInstance('extension');

            // Save the rules.
            if (isset($data['params']) && isset($data['params']['rules'])) {
                $rules = new JAccessRules($data['params']['rules']);
                $asset = JTable::getInstance('asset');

                if (!$asset->loadByName($data['option'])) {
                    $root = JTable::getInstance('asset');
                    $root->loadByName('root.1');
                    $asset->name = $data['option'];
                    $asset->title = $data['option'];
                    $asset->setLocation($root->id, 'last-child');
                }
                $asset->rules = (string) $rules;

                if (!$asset->check() || !$asset->store()) {
                    self::enqueueAjaxMessage($table->getError(), "danger");
                    return false;
                }

                // We don't need this anymore
                unset($data['option']);
                unset($data['params']['rules']);
            }

            // Load the previous Data
            if (!$table->load($data['id'])) {
                self::enqueueAjaxMessage($table->getError(), "danger");
                return false;
            }

            unset($data['id']);

            // Bind the data.
            if (!$table->bind($data)) {
                self::enqueueAjaxMessage($table->getError(), "danger");
                return false;
            }

            // Check the data.
            if (!$table->check()) {

                self::enqueueAjaxMessage($table->getError(), "danger");
                return false;
            }

            // Store the data.
            if (!$table->store()) {
                self::enqueueAjaxMessage($table->getError(), "danger");
                return false;
            }

            // Clean the component cache.
         //is protected   $model->cleanCache('_system');
            $reflectionMethod = new ReflectionMethod(get_class($model), 'cleanCache');
            $reflectionMethod->setAccessible(true);
            $reflectionMethod->invoke($model, '_system');
            
            self::enqueueAjaxMessage("Samlogin settings saved", "success");
            return true;
        } else {
            $component = JTable::getInstance('component');
            $component->loadByOption($data['option']);
            $component->bind($data);
            if (!$component->check()) {

                self::enqueueAjaxMessage($component->getError(), "danger");
                return false;
            }
            if (!$component->store()) {
                self::enqueueAjaxMessage($component->getError(), "danger");
                return false;
            }
        }

        self::enqueueAjaxMessage("Samlogin settings saved", "success");
        return true;
    }

    public function saveSettings() {

        self::sendAjaxHeaders();

        self::initAjaxBuffer();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            self::aquireLockTerminateOnFail("saveparams");
                self::_saveParams();
                $params = JComponentHelper::getParams('com_samlogin');
                self::appendAjaxReturnVar("params", $params);
            self::releaseLock("saveparams");
        }

        self::sendAjaxBuffer();
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

        self::sendAjaxHeaders();

        self::initAjaxBuffer();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            self::aquireLockTerminateOnFail("saveconf");

            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
            if (SSPConfManager::setSaveConfMode(SSPConfManager::$SAVECONF_PRODUCTION)) {


                $app = JFactory::getApplication();
                require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/keymanager.php");
                KeyManager::genkey($app);

                //   JRequest::setVar("layout", "closeme");
                //   $this->display();
                SSPConfManager::commitSaveConfModeLock(SSPConfManager::$SAVECONF_PRODUCTION);
            } else {
                self::enqueueAjaxMessage("Cannot aquire lock for production settings overriding, please retry later", self::$AJAX_MESSAGE_WARNING);
            }
            self::releaseLock("saveconf");
        }

        self::sendAjaxBuffer();
    }

    public function keyRotateEndPeriod() {
        self::sendAjaxHeaders();

        self::initAjaxBuffer();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            self::aquireLockTerminateOnFail("saveconf");

            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
            if (SSPConfManager::setSaveConfMode(SSPConfManager::$SAVECONF_PRODUCTION)) {


                $app = JFactory::getApplication();
                require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/keymanager.php");
                KeyManager::keyrotateEndPeriod($app);

                //JRequest::setVar("layout", "closeme");
                //$this->display();
                SSPConfManager::commitSaveConfModeLock(SSPConfManager::$SAVECONF_PRODUCTION);
            } else {
                self::enqueueAjaxMessage("Cannot aquire lock for production settings overriding, please retry later", self::$AJAX_MESSAGE_WARNING);
            }
            self::releaseLock("saveconf");
        }

        self::sendAjaxBuffer();
    }

    public function saveSSPconf() {
        self::sendAjaxHeaders();

        self::initAjaxBuffer();
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            self::aquireLockTerminateOnFail("saveconf");

            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
            if (SSPConfManager::setSaveConfMode(SSPConfManager::$SAVECONF_PRODUCTION)) {

                $app = JFactory::getApplication();


                $params = JComponentHelper::getParams('com_samlogin');
                $config = SSPConfManager::mergeParamsWithConf($params, $app);
                $config["samlogin_test"] = true;

                SSPConfManager::saveConf($config, $app);

                // JRequest::setVar("layout", "closeme");
                // $this->display();
                SSPConfManager::commitSaveConfModeLock(SSPConfManager::$SAVECONF_PRODUCTION);
            } else {
                self::enqueueAjaxMessage("Cannot aquire lock for production settings overriding, please retry later", self::$AJAX_MESSAGE_WARNING);
            }
            self::releaseLock("saveconf");
        }
        self::sendAjaxBuffer();
    }

    private static function simulateConfigWrite() {
        self::sendAjaxHeaders();
        self::initAjaxBuffer(); //just for having vars, but this is not ajax call
        $user = JFactory::getUser();
        if ($user->authorise('core.admin', 'com_samlogin')) {
            self::aquireLockTerminateOnFail("saveconf");

            require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
            if (SSPConfManager::setSaveConfMode(SSPConfManager::$SAVECONF_SIMULATE)) {

                $app = JFactory::getApplication();


                $params = JComponentHelper::getParams('com_samlogin');
                $config = SSPConfManager::mergeParamsWithConf($params, $app);
                $config["samlogin_test"] = true;

                SSPConfManager::saveConf($config, $app);

                // JRequest::setVar("layout", "closeme");
                // $this->display();
                SSPConfManager::commitSaveConfModeLock(SSPConfManager::$SAVECONF_SIMULATE);
            } else {
                self::enqueueAjaxMessage("Cannot aquire lock for preview settings overriding, please retry later", self::$AJAX_MESSAGE_WARNING);
            }
            self::releaseLock("saveconf");
        }

        // self::sendAjaxBuffer();        
    }

    public function installSimpleSAMLphp_download() {
        self::sendAjaxHeaders();
        ini_set('user_agent', 'Mozilla/5.0 (Linux; U; Linux; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
        $toret = array();
        $toret['additionalMessages'] = array(); //messae to toast
        $toret["bytes"] = 0;
        $user = JFactory::getUser();
        $app = JFactory::getApplication();
        require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/downloader.php");

        if ($user->authorise('core.admin', 'com_samlogin')) {
            $downloadURLs = array(
                "1.11.legacy" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/z-dist/ssp.zip",
                "1.11.n" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/z-dist/ssp.1.11.n.zip",
                "1.11.f" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/z-dist/ssp.1.11.f.zip",
                "1.12.n" => "https://raw.githubusercontent.com/creativeprogramming/simplesamlphp-samlogin/master/z-dist/ssp.1.12.n.zip",
                //alternate:
                "1.11.legacy-a" => "http://creativeprogramming.it/dev/dist/ssp-samlogin/ssp.zip",
                "1.11.n-a" => "http://creativeprogramming.it/dev/dist/ssp-samlogin/ssp.1.11.n.zip",
                "1.11.f-a" => "http://creativeprogramming.it/dev/dist/ssp-samlogin/ssp.1.11.f.zip",
                "1.12.n-a" => "http://creativeprogramming.it/dev/dist/ssp-samlogin/ssp.1.12.n.zip",
            );



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

            $migrationMode = ($_GET["migrationMode"] == 1);


            if (!$migrationMode) {
                $this->_preserveSSPConf($app, $toret);
            } else {

                $this->_preserveSSPConfMigration($app, $toret);
                $toret['additionalMessages'][] = array("msg" => "Migration mode on", "level" => "warning");
            }

            $httpHeaders = get_headers($downloadURL);
            if ($httpHeaders !== FALSE) {
                $responseIs200OK = stristr($httpHeaders[0], "200 OK") || stristr($httpHeaders[0], "302 Found");
                if ($responseIs200OK) {
                    
                } else {
                    $msg = "Failed to download from this repository, try alternate repository";

                    $toret['additionalMessages'][] = array("msg" => $msg, "level" => "danger");
                    echo json_encode($toret);
                    $lockClean = unlink($lockFile);
                    if (!$lockClean) {
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
            $lockClean = unlink($lockFile);
            if (!$lockClean) {
                $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
            }
        } else {
            $toret['additionalMessages'][] = array("msg" => "Your administrator login session expired or you are not authorized ", "level" => "danger");
        }

        echo json_encode($toret);
        die();
    }

    public function installSimpleSAMLphp_extract() {
        self::sendAjaxHeaders();
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

            $migrationMode = ($_GET["migrationMode"] == 1);


            if (!$migrationMode) {
                $this->_restorePreservedSSPConf($app);
            } else {

                $this->_restorePreservedSSPConfMigration($app);
                $toret['additionalMessages'][] = array("msg" => "Migration succeeded", "level" => "success");
            }


            // $app->enqueueMessage("SimpleSAMLphp installed, please close this window and refresh the dashboard page now.");

            $lockClean = unlink($lockFile);
            if (!$lockClean) {
                $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
            }
        } else {
            //  $app->enqueueMessage("Unauthorized", "error");
            $toret['additionalMessages'][] = array("msg" => "Your administrator login session expired or you are not authorized ", "level" => "danger");
        }
        echo json_encode($toret);
        die();
    }

    private static function _startsWith($haystack, $needle) {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    private static function _endsWith($haystack, $needle) {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public function doConfigTests() {
        self::sendAjaxHeaders();
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

                require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
                $dummyarr = array();

                if (self::aquireLock("nosimluate")) {
                    SSPConfManager::checkConfSync($dummyarr); //to create file first boot
                    self::simulateConfigWrite();
                    $checks['configIsInSync'] = SSPConfManager::checkConfSync($checks['additionalMessages']);
                    self::releaseLock("nosimluate");
                }

                $checks['sspCheck'] = $vinfo;
                require_once(JPATH_SITE . "/components/com_samlogin/simplesamlphp/lib/_autoload.php");

                if (isset($config)) {
                    unset($config);
                }
                require(JPATH_SITE . "/components/com_samlogin/simplesamlphp/config/config.php");




                $checks['sspConfDebug'] = "<pre>" . print_r($config, true) . "</pre>";

                $sspConf = $config;

                $checks['metarefresh'] = isset($config["metadata.sources"][1]["directory"]);
                $cachedMetadataFile = JPATH_SITE . "/components/com_samlogin/simplesamlphp/metadata/federations/saml20-idp-remote.php";
                if (file_exists($cachedMetadataFile)) {
                    $lastMetadataCacheUpdate = filemtime($cachedMetadataFile);
                    if (time() - $lastMetadataCacheUpdate < ( (60 * 60 * 1) + 60 )) {
                        $checks["metarefreshSAML2IdpLastUpdate"] = @date("F d Y H:i:s", $lastMetadataCacheUpdate) . " " .
                                "<span class='uk-button uk-button-mini uk-button-success'>" .
                                "<i class='uk-icon-check'></i> </span> ";
                    } else {
                        $checks["metarefreshSAML2IdpLastUpdate"] = @date("F d Y H:i:s", $lastMetadataCacheUpdate) .
                                "<span class='uk-button uk-button-mini uk-button-danger'>" .
                                "<i class='uk-icon-warning'></i>The cronjob isn't running with hourly frequency</span> ";
                    }
                } else {
                    $checks["metarefreshSAML2IdpLastUpdate"] = "Never";
                }

                if (isset($config)) {
                    unset($config);
                } require(JPATH_SITE . "/components/com_samlogin/simplesamlphp/config/authsources.php");
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


                $neededJoomlaBaseURLPath = JURI::root(true) . '/components/com_samlogin/simplesamlphp/www/';
                while (self::_startsWith($neededJoomlaBaseURLPath, "/")) {
                    $neededJoomlaBaseURLPath = substr($neededJoomlaBaseURLPath, 1);
                }

                //   die($JoomlaBaseURLPath);
                $checks['baseurlpath'] = ($sspConf["baseurlpath"] == $neededJoomlaBaseURLPath) ? TRUE : "Should be: `" . $neededJoomlaBaseURLPath . "` but `" . $sspConf["baseurlpath"] . "` found";


                $checks["metadataURL"] = $sslTestURL;

                $checks["cronLink"] = str_ireplace("http://", "https://", JURI::root()) . "components/com_samlogin/simplesamlphp/www/module.php/cron/cron.php?key=" . $params->get("sspcron_secret", "changeme") . "&tag=hourly&output=xhtml";
                $checks["cronSuggestion"] = "# Run cron: [hourly]\n" .
                        "01 * * * * /usr/bin/curl -k -A \"Mozilla/5.0\" --silent \"" . $checks["cronLink"] . "\" > /dev/null 2>&1" .
                        "";
                //  die($sslTestURL);

                $metadataSSLPageContent = SamloginHelperDownloader::downloadAndReturn($nonsslTestURL);
                if ($metadataSSLPageContent == FALSE) {
                    $checks['metadataPublished'] = FALSE;
                } else {
                    $checks['metadataPublished'] = stristr($metadataSSLPageContent, "simpleSAMLphp") ? TRUE : "No route to simpleSAMLphp, if you are using nginx add a proper location";
                    if ($checks['metadataPublished'] === TRUE) {
                        $checks['metadataPublished'] = stristr($metadataSSLPageContent, "EntityDescriptor") ? TRUE : "Invalid metadata";
                    }
                }
                if ($checks['metadataPublished'] === FALSE) {
                    $httpHeaders = get_headers($nonsslTestURL);
                    if ($httpHeaders === FALSE) {
                        $checks['metadataPublished'] = "<i class='uk-icon-question-circle'></i> your PHP wasn't able to check the metadata URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank'  href='$nonsslTestURL'>please verify the url manually</a> (it should show xml metadata to pass this test)";
                    }
                }


                $metadataSSLPageContent = SamloginHelperDownloader::downloadAndReturn($sslTestURL);
                if ($metadataSSLPageContent == FALSE) {
                    $checks['metadataPublishedSSL'] = FALSE;
                } else {
                    $checks['metadataPublishedSSL'] = stristr($metadataSSLPageContent, "simpleSAMLphp") ? TRUE : "No route to simpleSAMLphp, if you are using nginx add a proper location";
                    if ($checks['metadataPublishedSSL'] === TRUE) {
                        $checks['metadataPublishedSSL'] = stristr($metadataSSLPageContent, "EntityDescriptor") ? TRUE : "Invalid metadata";
                    }              // $checks['metadataDebugPage']= $metadataSSLPageContent;
                }
                if ($checks['metadataPublishedSSL'] === FALSE) {
                    $httpHeaders = get_headers($sslTestURL);
                    if ($httpHeaders === FALSE) {
                        $checks['metadataPublishedSSL'] = "<i class='uk-icon-question-circle'></i> your PHP wasn't able to check the metadata SSL URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank' href='$sslTestURL'>please verify the url manually</a> (it should show xml metadata to pass this test)";
                    }
                }

                require_once(JPATH_COMPONENT_ADMINISTRATOR . "/helpers/sspconfmanager.php");
                $SSPKeyURLPath = SSPConfManager::getCertURLPath();

                $privatekeyTestURL = str_ireplace("https://", "http://", JURI::root()) . $SSPKeyURLPath . "saml.key";
                // echo $privatekeyTestURL;
                $privatekeyTestURLContent = SamloginHelperDownloader::downloadAndReturn($privatekeyTestURL);
                $checks['privatekey'] = $privatekeyTestURLContent === FALSE ? TRUE : $privatekeyTestURLContent;
                if ($checks['privatekey'] !== TRUE) {
                    $httpHeaders = get_headers($privatekeyTestURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['privatekey'] = FALSE;
                        } else {
                            $checks['privatekey'] = TRUE;
                        }
                    }
                } else {

                    $httpHeaders = get_headers($privatekeyTestURL);
                    if ($httpHeaders === FALSE) {
                        $checks['privatekey'] = "<i class='uk-icon-question-warning'></i> <i class='uk-icon-question-circle'></i> your PHP wasn't able to check the private key URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank' href='$privatekeyTestURL'>please verify the url manually</a> (it should return forbidden error to pass this check)";
                    }
                }



                $privatekeySSLTestURL = str_ireplace("http://", "https://", JURI::root()) . $SSPKeyURLPath . "saml.key";
                //  die(print_r(get_headers($privatekeySSLTestURL),true));
                $privatekeySSLTestURLContent = SamloginHelperDownloader::downloadAndReturn($privatekeySSLTestURL);
                $checks['privatekeySSL'] = $privatekeySSLTestURLContent === FALSE ? TRUE : $privatekeySSLTestURLContent;
                if ($checks['privatekeySSL'] !== TRUE) {
                    $httpHeaders = get_headers($privatekeySSLTestURL);
                    if ($httpHeaders !== FALSE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['privatekeySSL'] = FALSE;
                        } else {
                            $checks['privatekeySSL'] = TRUE;
                        }
                    }
                } else {

                    $httpHeaders = get_headers($privatekeySSLTestURL);
                    if ($httpHeaders === FALSE) {
                        $checks['privatekeySSL'] = "<i class='uk-icon-question-warning'></i> <i class='uk-icon-question-circle'></i> your PHP wasn't able to check the private key SSL URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank' href='$privatekeySSLTestURL'>please verify the url manually</a> (it should return 403 to pass this check)";
                    }
                }


                $testURL = str_ireplace("https://", "http://", JURI::root()) . "/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                // die(print_r(get_headers($testURL),true));
                $logUrlContent = SamloginHelperDownloader::downloadAndReturn($testURL);
                $checks['logswww'] = $logUrlContent === FALSE ? TRUE : $logUrlContent;
                if ($checks['logswww'] !== FALSE) {
                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders !== TRUE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['logswww'] = FALSE;
                        } else {
                            $checks['logswww'] = TRUE;
                        }
                    }
                } else {

                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders === FALSE) {
                        $checks['logswww'] = "<i class='uk-icon-question-warning'></i> <i class='uk-icon-question-circle'></i> your PHP wasn't able to check the log URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank' href='$testURL'>please verify the url manually</a> (it should return forbidden error to pass this check)";
                    }
                }



                $testURL = str_ireplace("http://", "https://", JURI::root()) . "/components/com_samlogin/simplesamlphp/log/_placeholder.php";
                $logUrlContent = SamloginHelperDownloader::downloadAndReturn($testURL);
                $checks['logswwws'] = $logUrlContent === FALSE ? TRUE : $logUrlContent;
                if ($checks['logswwws'] !== FALSE) {
                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders !== TRUE) {
                        $responseIs200OK = stristr($httpHeaders[0], "200 OK");
                        if ($responseIs200OK) {
                            $checks['logswwws'] = FALSE;
                        } else {
                            $checks['logswwws'] = TRUE;
                        }
                    }
                } else {

                    $httpHeaders = get_headers($testURL);
                    if ($httpHeaders === FALSE) {
                        $checks['logswww'] = "<i class='uk-icon-question-warning'></i> <i class='uk-icon-question-circle'></i> your PHP wasn't able to check the log URL"
                                . " (maybe your server is behind a proxy and can't reach your final url and port, or php has allow_url_fopen off) "
                                . "<a target='_blank' href='$testURL'>please verify the url manually</a> (it should return forbidden error to pass this check)";
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

    private function recursiveRemoveDirButNotBackups($dir, &$toret) {
        $rmcountdir = 0;
        $rmfiledir = 0;
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != ".." && !stristr($object, ".backup_until")) {
                    if (filetype($dir . "/" . $object) == "dir") {
                        rmdir($dir . "/" . $object);
                        $rmcountdir++;
                    } else {
                        unlink($dir . "/" . $object);
                        $rmfiledir++;
                    }
                }
            }
            reset($objects);
            rmdir($dir);
            $rmcountdir++;
            $toret['additionalMessages'][] = array("msg" => "Cleaning $rmcountdir dirs and $rmfiledir files (old SimpleSAMLphp)", "level" => "info");
        }
    }

    private function _preserveSSPConfMigration($app, &$toret) {
        $this->recursiveRemoveDirButNotBackups(JPATH_SITE . "/components/com_samlogin/simplesamlphp/", $toret);
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
                //    '/components/com_samlogin/simplesamlphp/config/authsources.php',
                //    '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                //    '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                //    '/components/com_samlogin/simplesamlphp/config/config.php'
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

    private function _restorePreservedSSPConfMigration($app) {


        $filetopreserveArr = array(
            '/components/com_samlogin/simplesamlphp/cert/saml.key',
            '/components/com_samlogin/simplesamlphp/cert/saml.crt',
                //   '/components/com_samlogin/simplesamlphp/config/authsources.php',
                //   '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                //   '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                //   '/components/com_samlogin/simplesamlphp/config/config.php'
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
        $lockClean = unlink($lockFile);
        if (!$lockClean) {
            $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
        }
    }

    private function _preserveSSPConf($app, &$toret) {

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
        $lockClean = unlink($lockFile);
        if (!$lockClean) {
            $toret['additionalMessages'][] = array("msg" => "Cannot release lock", "level" => "warning");
        }
    }

}
