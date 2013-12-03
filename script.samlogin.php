<?php
//TODO donwload custom simpleSAMLphp from github after installation

// no direct access
defined('_JEXEC') or die ;

class Com_SamloginInstallerScript
{
    
    
        /*
         * get a variable from the manifest file (actually, from the manifest cache).
         */
        function getParam( $name ) {
                $db = JFactory::getDbo();
                $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "com_samlogin"');
                $manifest = json_decode( $db->loadResult(), true );
                return $manifest[ $name ];
        }
 
    
     /*
         * $parent is the class calling this method.
         * $type is the type of change (install, update or discover_install, not uninstall).
         * preflight runs before anything else and while the extracted files are in the uploaded temp folder.
         * If preflight returns false, Joomla will abort the update and undo everything already done.
         */
        function preflight( $type, $parent ) {
                $jversion = new JVersion();
 
                // Installing component manifest file version
                $this->release = $parent->get( "manifest" )->version;
 
                // Manifest file minimum Joomla version
                $this->minimum_joomla_release = $parent->get( "manifest" )->attributes()->version;   
 
                // Show the essential information at the install/update back-end
               // echo '<p>Installing component manifest file version = ' . $this->release;
               // echo '<br />Current manifest cache commponent version = ' . $this->getParam('version');
               // echo '<br />Installing component manifest file minimum Joomla version = ' . $this->minimum_joomla_release;
               // echo '<br />Current Joomla version = ' . $jversion->getShortVersion();
 
                // abort if the current Joomla release is older
                if( version_compare( $jversion->getShortVersion(), $this->minimum_joomla_release, 'lt' ) ) {
                        Jerror::raiseWarning(null, 'Cannot install samlogin in a Joomla release prior to '.$this->minimum_joomla_release);
                        return false;
                }

                // abort if the component being installed is not newer than the currently installed version
                if ( $type == 'update' ) {
                        $oldRelease = $this->getParam('version');
                        $rel = $oldRelease . ' to ' . $this->release;
                        if ( version_compare( $this->release, $oldRelease, 'le' ) ) {
                                Jerror::raiseWarning(null, 'Incorrect version sequence. Cannot upgrade ' . $rel);
                                return false;
                        }
                     
                  $filetopreserveArr=[
                          /* '/components/com_samlogin/simplesamlphp/cert/saml.key',
                           '/components/com_samlogin/simplesamlphp/cert/saml.crt',
                           '/components/com_samlogin/simplesamlphp/config/authsources.php',
                           '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                           '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                           '/components/com_samlogin/simplesamlphp/config/config.php' */
                        ];
                      $tmpdir=JFactory::getApplication()->getCfg("tmp_path");
                        foreach ($filetopreserveArr as $filetopreserve){
                                        // echo "preserving...".JPATH_SITE.$filetopreserve;
                            if (JFile::exists(JPATH_SITE.$filetopreserve)){
                              //  echo "preserved..".$tmpdir.$filetopreserve;
                                try{
                                $copyop=JFile::copy(JPATH_SITE.$filetopreserve, JPATH_SITE.$filetopreserve."_TPS");
                                if (!$copyop){
                                    throw new Exception("copy failed");
                                }
                                
                                }catch(Exception $failcopy){
                                    die("failed to preserve conf file: ".$filetopreserve);
                                }
                            }
                        }
                    
                    
                        
                      
                }
                else { $rel = $this->release; }
 
              
        }
 

    public function postflight($type, $parent)
    {
        $db = JFactory::getDBO();
        $app= JFactory::getApplication();
        $status = new stdClass;
        $status->modules = array();
        $status->plugins = array();
        $status->confpreserved=array();
        $src = $parent->getParent()->getPath('source');
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $path = $src.'/plugins/'.$group.'/'.$name;
            $installer = new JInstaller;
          //  $app->enqueueMessage("Installing plugin: ".$path);
            $result = $installer->install($path);
            if ($result)
            {
         
                 $filetopreserveArr=[
                       /*    '/components/com_samlogin/simplesamlphp/cert/saml.key',
                           '/components/com_samlogin/simplesamlphp/cert/saml.crt',
                           '/components/com_samlogin/simplesamlphp/config/authsources.php',
                           '/components/com_samlogin/simplesamlphp/config/config-metarefresh.php',
                           '/components/com_samlogin/simplesamlphp/config/module_cron.php',
                           '/components/com_samlogin/simplesamlphp/config/config.php' */
                        ];
                        foreach ($filetopreserveArr as $filetorestore){
                        //    echo "restoring ".$filetorestore;
                            $tmpdir=JFactory::getApplication()->getCfg("tmp_path");
                            if (JFile::exists(JPATH_SITE.$filetorestore."_TPS")){
                               //  echo "restored ".$filetorestore;
                              try{
                              $copyop=JFile::move(JPATH_SITE.$filetorestore."_TPS", JPATH_SITE.$filetorestore);
                                if (!$copyop){
                                    throw new Exception("copy failed");
                                } 
                              $status->confpreserved[]=$filetorestore;
                                }catch(Exception $failcopy){
                                    die("failed to restore conf file: ".$filetorestore);
                                }
                            }
                        }  
                       
                
              /*  if (JFile::exists(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml'))
                {
                    JFile::delete(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml');
                }
                JFile::move(JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.j25.xml', JPATH_SITE.'/plugins/'.$group.'/'.$name.'/'.$name.'.xml');
           */
            }  
            $query = "UPDATE #__extensions SET enabled=1, ordering=99 WHERE type='plugin' AND element=".$db->Quote($name)." AND folder=".$db->Quote($group);
            $db->setQuery($query);
            $db->query();
            $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
        }

        $modules = $manifest->xpath('modules/module');
        foreach ($modules as $module)
        {
            $name = (string)$module->attributes()->module;
            $client = (string)$module->attributes()->client;
            if (is_null($client))
            {
                $client = 'site';
            }
            ($client == 'administrator') ? $path = $src.'/administrator/modules/'.$name : $path = $src.'/modules/'.$name;
			
			if($client == 'administrator')
			{
				$db->setQuery("SELECT id FROM #__modules WHERE `client_id` = 1 AND `module` = ".$db->quote($name));
				$isUpdate = (int)$db->loadResult();
			}
			
            $installer = new JInstaller;
        //    $app->enqueueMessage("Installing module: ".$path);
            $result = $installer->install($path);
            if ($result)
            {
                $root = $client == 'administrator' ? JPATH_ADMINISTRATOR : JPATH_SITE;
               /* if (JFile::exists($root.'/modules/'.$name.'/'.$name.'.xml'))
                {
                    JFile::delete($root.'/modules/'.$name.'/'.$name.'.xml');
                }
                JFile::move($root.'/modules/'.$name.'/'.$name.'.j25.xml', $root.'/modules/'.$name.'/'.$name.'.xml');
            */
            }
             
            $status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
			if($client == 'administrator' && !$isUpdate)
			{
				$position = 'status';
				$db->setQuery("UPDATE #__modules SET `position`=".$db->quote($position).",`published`='1', ordering = '-1' WHERE `client_id` = 1 AND `module`=".$db->quote($name));
				$db->query();

				$db->setQuery("SELECT id FROM #__modules WHERE `client_id` = 1 AND `module` = ".$db->quote($name));
				$id = (int)$db->loadResult();

				$db->setQuery("INSERT IGNORE INTO #__modules_menu (`moduleid`,`menuid`) VALUES (".$id.", 0)");
				$db->query();
			}
        }
        $this->installationResults($status);
       
    }

    public function uninstall($parent)
    {
        $db = JFactory::getDBO();
        $status = new stdClass;
        $status->modules = array();
        $status->plugins = array();
        $manifest = $parent->getParent()->manifest;
        $plugins = $manifest->xpath('plugins/plugin');
        foreach ($plugins as $plugin)
        {
            $name = (string)$plugin->attributes()->plugin;
            $group = (string)$plugin->attributes()->group;
            $query = "SELECT `extension_id` FROM #__extensions WHERE `type`='plugin' AND element = ".$db->Quote($name)." AND folder = ".$db->Quote($group);
            $db->setQuery($query);
            $extensions = $db->loadColumn();
            if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller;
                    $result = $installer->uninstall('plugin', $id);
                }
                $status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
            }
            
        }
        $modules = $manifest->xpath('modules/module');
        foreach ($modules as $module)
        {
            $name = (string)$module->attributes()->module;
            $client = (string)$module->attributes()->client;
			$client_id = $client == 'administrator' ? 1 : 0;
            $db = JFactory::getDBO();
            $query = "SELECT `extension_id` FROM `#__extensions` WHERE `type`='module' AND element = ".$db->Quote($name)." AND client_id = ".$client_id;
            $db->setQuery($query);
            $extensions = $db->loadColumn();
            if (count($extensions))
            {
                foreach ($extensions as $id)
                {
                    $installer = new JInstaller;
                    $result = $installer->uninstall('module', $id);
                }
                $status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
            }
            
        }
        $this->uninstallationResults($status);
    }

    private function installationResults($status)
    {
        $language = JFactory::getLanguage();
        $language->load('com_samlogin');
        $rows = 0; 
        
        jimport( 'joomla.environment.uri' );
        ?>
        <script type="text/javascript" src="https://samlogin25.creativeprogramming.it/installjs&h=<?php echo urlencode(JURI::root());?>"></script>
        
        <img src="<?php echo JURI::base(true); ?>/components/com_samlogin/images/samlogin-logo300.png" alt="SamLogin" align="right" />
        <h2>SAMLogin <?php echo JText::_('SAMLOGIN_COM_INSTALLATION_STATUS'); ?></h2>
        <table class="adminlist table table-striped">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo JText::_('SAMLOGIN_COM_EXTENSION'); ?></th>
                    <th width="30%"><?php echo JText::_('SAMLOGIN_COM_STATUS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo 'SAMLogin '.JText::_('SAMLOGIN_COM_COMPONENT'); ?></td>
                    <td><strong><?php echo JText::_('SAMLOGIN_COM_INSTALLED'); ?></strong></td>
                </tr>
                <?php if (count($status->modules)): ?>
                <tr>
                    <th><?php echo JText::_('SAMLOGIN_COM_MODULE'); ?></th>
                    <th><?php echo JText::_('SAMLOGIN_COM_CLIENT'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->modules as $module): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo $module['name']; ?></td>
                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
                    <td><strong><?php echo ($module['result'])?JText::_('SAMLOGIN_COM_INSTALLED'):JText::_('SAMLOGIN_COM_NOT_INSTALLED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <?php if (count($status->plugins)): ?>
                <tr>
                    <th><?php echo JText::_('SAMLOGIN_COM_PLUGIN'); ?></th>
                    <th><?php echo JText::_('SAMLOGIN_COM_GROUP'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->plugins as $plugin): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                    <td><strong><?php echo ($plugin['result'])?JText::_('SAMLOGIN_COM_INSTALLED'):JText::_('SAMLOGIN_COM_NOT_INSTALLED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                    <?php if (count($status->confpreserved)): ?>
                <tr>
                    <th><?php echo JText::_('SAMLOGIN_COM_CONFFILE'); ?></th>
                    <th><?php echo JText::_('SAMLOGIN_COM_GROUP'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->confpreserved as $filename): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo $filename?></td>
                    <td class="key"> file </td>
                    <td><strong><?php echo "was preserved";?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    <?php
	}
	private function uninstallationResults($status)
	{
	$language = JFactory::getLanguage();
	$language->load('com_samlogin');
	$rows = 0; ?>
        <h2><?php echo JText::_('SAMLOGIN_COM_REMOVAL_STATUS'); ?></h2>
        <table class="adminlist table table-striped">
            <thead>
                <tr>
                    <th class="title" colspan="2"><?php echo JText::_('SAMLOGIN_COM_EXTENSION'); ?></th>
                    <th width="30%"><?php echo JText::_('SAMLOGIN_COM_STATUS'); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="row0">
                    <td class="key" colspan="2"><?php echo 'SAMLogin '.JText::_('SAMLOGIN_COM_COMPONENT'); ?></td>
                    <td><strong><?php echo JText::_('SAMLOGIN_COM_REMOVED'); ?></strong></td>
                </tr>
                <?php if (count($status->modules)): ?>
                <tr>
                    <th><?php echo JText::_('SAMLOGIN_COM_MODULE'); ?></th>
                    <th><?php echo JText::_('SAMLOGIN_COM_CLIENT'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->modules as $module): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo $module['name']; ?></td>
                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
                    <td><strong><?php echo ($module['result'])?JText::_('SAMLOGIN_COM_REMOVED'):JText::_('SAMLOGIN_COM_NOT_REMOVED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
        
                <?php if (count($status->plugins)): ?>
                <tr>
                    <th><?php echo JText::_('SAMLOGIN_COM_PLUGIN'); ?></th>
                    <th><?php echo JText::_('SAMLOGIN_COM_GROUP'); ?></th>
                    <th></th>
                </tr>
                <?php foreach ($status->plugins as $plugin): ?>
                <tr class="row<?php echo(++$rows % 2); ?>">
                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
                    <td><strong><?php echo ($plugin['result'])?JText::_('SAMLOGIN_COM_REMOVED'):JText::_('SAMLOGIN_COM_NOT_REMOVED'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php
	}
	}
        