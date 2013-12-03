<?php

/**
 * @version		$Id: socialconnectfacebook.php 2437 2013-01-29 14:14:53Z lefteris.kavadas $
 * @package		SocialConnect
 * @author		JoomlaWorks http://www.joomlaworks.net
 * @copyright	Copyright (c) 2006 - 2013 JoomlaWorks Ltd. All rights reserved.
 * @license		http://www.joomlaworks.net/license
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgAuthenticationSamlogin extends JPlugin {

    function plgAuthenticationSamlogin(&$subject, $config) {
        parent::__construct($subject, $config);
    }

    public static function getUserIdByUsernameOrMail($usernameormail) {
        // Initialise some variables
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__users'));
        $query->where($db->quoteName('username') . ' = ' . $db->quote($usernameormail) . ' OR ' . $db->quoteName('email') . ' = ' . $db->quote($usernameormail));
        $db->setQuery($query, 0, 1);
        //  //phpconsole(print_r($query->__toString(),true),"rastrano");
        return $db->loadResult();
    }

    private function _getSAMLAttributeFirstValue($key, $attributes, $default = null) {
        if (!array_key_exists($key, $attributes)) {
            return $default;
        }
        return $attributes[$key][0];
    }
    
    private function _pregMatchSAMLAttributeValues($attrname,$regex, $attributes) {
        if (!array_key_exists($attrname, $attributes)) {
            return FALSE;
        }
        $attrvals= $attributes[$attrname];
        foreach($attrvals as $val){
   //phpconsole("does ".$val." matches ".$regex,"rastrano");
            if (preg_match("/".$regex."/", $val)){
                   //phpconsole("YES,  ".$val." matches ".$regex,"rastrano");
                return $val;
            }
        }
        return FALSE;
    }

    private function _checkAuthZRules($user, $response, $samlresponse, $samloginParams) {
        $rulesConf = $samloginParams->toArray();
        //phpconsole($rulesConf, "rastrano");
        //phpconsole($user->get("groups"), "rastrano");
        $oldusergroups = $user->get("groups");
        
        $defaultRegistered = $samloginParams->get("defaultRegistered",true);
        $newusergroups = array(); 
        if($defaultRegistered){
            $registeredGroupId=$samloginParams->get("registeredGroupId",2);
            $newusergroups = array($registeredGroupId); //2 is registered
        }else{
        
       
        }
       
        //phpconsole("New  groups:".print_r($newusergroups,true),"rastrano");
        $newusergroupsForHist[] = array();
        foreach ($rulesConf as $key => $val) {

            if (strpos($key, 'rule_') === 0) {
                if (stristr($key, "_attr")) {
                    $ruleno = str_replace("rule_", "", $key);
                    $ruleno = str_replace("_attr", "", $ruleno);
        
                    $ruleattr = $samloginParams->get("rule_" . $ruleno . "_attr", "");
                    if ($ruleattr) {
                        $ruleassigngroup = $samloginParams->get("rule_" . $ruleno . "_assigngroup", "");
                        $ruleRegexp = $samloginParams->get("rule_" . $ruleno . "_regex", "");
                                   //phpconsole($ruleattr." matches ".$ruleRegexp,"rastrano");
                        $matching = $this->_pregMatchSAMLAttributeValues($ruleattr,$ruleRegexp,$samlresponse);
                                       ////phpconsole($ruleno." matches? ".$ruleRegexp."??? ".print_r($samlresponse,true),"rastrano");
                        if ($matching!==FALSE){
                            //phpconsole($ruleno." matches ".$ruleRegexp,"rastrano");
                            $newusergroups[] = $ruleassigngroup;
                            $currentSession = JFactory::getSession();
                            $SAMLoginIdP = $currentSession->get("SAMLoginIdP", '');
                            $keytomem="rule_" . $ruleno.
                                " v:".$matching
                                    ." idp:".$SAMLoginIdP;
                            $newusergroupsForHist[$keytomem] = $ruleassigngroup;
                        }
                    }
                }
            }
        }



        $timeid = time();

     //   //phpconsole(print_r($latestSamloginAssignedGroups,true),"rastrano");
      

        $preserveManualUserGroup = $samloginParams->get("preserveManualUserGroup", true);
        if ($preserveManualUserGroup) {
            $latestSamloginAssignedGroups =  $this->_getLastestGroupAssigned($user);

            $manualOldUsergroups = array_diff($oldusergroups, $latestSamloginAssignedGroups); 
       
            //merge only the old groups NON-samlogin assigned groups
            $newusergroups = array_merge($newusergroups, $manualOldUsergroups);
            foreach ($newusergroupsForHist as $key => $hitem){
                 if (in_array($hitem, $manualOldUsergroups)){
                     //don't storicize as saml added a manually added usergroup
                     unset($newusergroupsForHist[$key]);
                     $newusergroupsForHist["manual"]=$hitem; //bust storicize it as a manual! (to track/log access time authz)
                 }
             }
        }
       
        $this->_saveAddedGroupHist($newusergroupsForHist, $user, $timeid); //don't save the modified newusergroups array here: only the saml added one
        $user->set("groups", $newusergroups);
   //     $user->name = $response->fullname; //bug: different field naming in joomla table, check will fail
             
        $saved = $user->save();
        
        //Reflection: TODO maintain protected __authGroups in user.php JUser
        $refObject   = new ReflectionObject( $user );
        $refProperty = $refObject->getProperty( '_authGroups' );
        $refProperty->setAccessible( true );
        $refProperty->setValue($user, $newusergroups);//schedure a re fetch of auth group for the in-memory session user object
        //
        //also JAccess has a group cache
        //
        JAccess::clearStatics(); //this clears things up $groupByUser cahced array!
        //
        //see user.php getAuthorisedGroups() method
        @$user->getAuthorisedGroups();//ensure the re-fetch of auth groups for the in-memory session user object
      
        $this->_getSAMLAttributeFirstValue($attrName, $samlresponse, "");
    }

    private function _getLastestGroupAssigned($user,$excludeInitiator="manual") {
        $toret=array();
        $isJoomla3 = ((float) JVERSION) >= 3.0;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("username,MAX(timeid) AS timeid")
                ->from('#__samlogin_authz_hist')
                ->where("username = ".$db->quote($user->get("username")))
                ->group("username");
        $db->setQuery($query,0,1); //limit is here

        
        $maxTidRow = $db->loadAssoc();
        $lastTimeId = $maxTidRow["timeid"];
        $query=null;
        $query = $db->getQuery(true);
        $query->select("username,".$db->quoteName("group")." AS grpid,initiator")
                ->from('#__samlogin_authz_hist')
                ->where(array(
                    "timeid = ".$lastTimeId,
                    "username = ".$db->quote($user->get("username"))
                ),"AND");
        $db->setQuery($query);

      
        $lastGroupsRow = $db->loadAssocList();
    if (is_array($lastGroupsRow)){
        foreach($lastGroupsRow as $row){
            $initiator=$row["initiator"];
            if ($initiator!=$excludeInitiator){
                $toret[]=$row["grpid"];
            }
        }
    }
        return $toret;
      
    }

    private function _saveAddedGroupHist($groups, $user, $timeid) {
        foreach ($groups as $initiator => $thisgroup) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            // Insert columns.
            $columns = array('username', 'group', 'email', 'initiator', 'timeid');
            // Insert values.
            $values = array(
                $db->quote($user->get("username"))
                , $thisgroup
                , $db->quote($user->get("email"))
                , $db->quote($initiator)
                , $timeid);

            $query->insert($db->quoteName('#__samlogin_authz_hist'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(",", $values));


            $db->setQuery($query);

            $isJoomla3 = ((float) JVERSION) >= 3.0;
            if ($isJoomla3) {
                $db->execute();
            } else {
                $db->query();
            }
        }
    }

    private function _mapAttributes(&$response, $samlresponse, $samloginParams) {
        $mappingConf = $samloginParams->toArray();
        //   //phpconsole($mappingConf, "rastrano");
        foreach ($mappingConf as $key => $val) {
            // mapping parameters prefix
            if (strpos($key, 'mapping_') === 0) {
                $joomlaKey = substr($key, strlen("mapping_"));
                if (!empty($val)) {
                    $attrNameAlternatives = explode("|", $val) ? explode("|", $val) : array($val);
                    //  //phpconsole($attrNameAlternatives,"rastrano");
                    $attrFound = false;
                    foreach ($attrNameAlternatives as $attrNameSyntax) {
                        if ($attrFound) {
                            break;
                        }
                        $attrConcat = explode("+", $attrNameSyntax) ? explode("+", $attrNameSyntax) : array($attrNameSyntax);
                        $firstAttrOfConcat = true;
                        foreach ($attrConcat as $attrName) {
                            if ($firstAttrOfConcat) {
                                $response->$joomlaKey = $this->_getSAMLAttributeFirstValue($attrName, $samlresponse);
                                $firstAttrOfConcat = false;
                            } else {
                                $response->$joomlaKey .= " ".$this->_getSAMLAttributeFirstValue($attrName, $samlresponse, "");
                            }
                            if (!empty($response->$joomlaKey)) {
                                $attrFound = true;
                            }
                        }
                    }
                }
            }
        }
        $response->type = 'SAMLogin';


        // //phpconsole($response, "rastrano");

        $userid = $this->getUserIdByUsernameOrMail($response->username);


        if ($userid) {
            $user = JFactory::getUser($userid);

            $updateUsernameIfMailOverlap = $samloginParams->get("updateUsernameIfMailOverlap", false);

            if ($updateUsernameIfMailOverlap && $response->username != $user->username) {
                $user->username = $response->username;
                $saved = $user->save(true);
                //   //phpconsole("username updated $saved","rastrano");
            }
            //print_r($user->get('username'));

            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('id, password');
            $query->from('#__users');
            $query->where('username=' . $db->Quote($response->username));
            //." OR email= ".$db->Quote($response->email));
            $db->setQuery($query);
            $result = $db->loadObject();

            if ($result) { //if user exists
                if ($samloginParams->get("preserveLocalPassword", false)) {
                    $parts = explode(':', $result->password);
                    $crypt = $parts[0];
                    $salt = @$parts[1];
                    $password = $crypt . ':' . $salt;

                    $response->password = $password;
                    $response->password_clear = $password;
                } else {
                    $response->password = '';
                    $response->password_clear = '';
                }
            } else { //if user doesn't exist yet
                if ($samloginParams->get("generateRandomLocalPassword", false)) {
                    $salt = JUserHelper::genRandomPassword(32);
                    $rndpwdLength = 12;
                    $passwd = $this->randomPassword($rndpwdLength);
                    $crypt = JUserHelper::getCryptedPassword($passwd, $salt);
                    $password = $crypt . ':' . $salt;
                    $response->password = $password;
                    $response->password_clear = $password;
                } else {
                    $response->password = '';
                    $response->password_clear = '';
                }
            }
        }
    }

    public function _updateAndGetUser($response, $samloginParams) {
        // Establish userid of authenticated user, or 0 when user does not exist
        $user = null;
        $userid = JUserHelper::getUserId($response->username);

        if ($userid) {
            $user = JFactory::getUser($userid);

            //print_r($user->get('username'));
            // Detect changes, and update if required
            $changed = true;
            if ($changed) {
                $getusrprop = $response->getProperties();
                $user->bind($getusrprop);
                $user->save(true); // true means: updateOnly
            } else {
                $getusrprop = $response->getProperties();
                $user->bind($getusrprop);
            }
        } else {
            // Store as new user
            
                $user = new JUser();
                $user->username=$userid;
                $user->usertype = 'SAMLogin';
                if ($samloginParams->get("requireApproval", false)) {
                    $user->block = 1;
                    $user->activation = 0;
                }else{
                 $user->block = 0;    
                 $user->activation = 0;
                }
                $user->sendEmail = 1;
                //$user->registerDate='0000-00-00 00:00:00';
                //$user->lastvisitDate='0000-00-00 00:00:00';
                $user->params = '';
                $user->lastResetTime = '0000-00-00 00:00:00';
                $user->resetCount = 0;
                
                $user->name = $response->fullname; //different field naming in joomla table
                
                $getusrprop = $response->getProperties();
                $user->bind($getusrprop);
              
                $saved=$user->save();
              //  $user = JFactory::getUser($userid); //sync needed
               
            }
        

        return $user;
    }

    function onUserAuthenticate($credentials, $options, &$response) {
        $this->onAuthenticate($credentials, $options, $response);
    }

    function onAuthenticate($credentials, $options, &$response) {
        $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_FAILURE : JAUTHENTICATE_STATUS_FAILURE;

        jimport('joomla.application.component.helper');
        $samloginParams = JComponentHelper::getParams('com_samlogin');
        //    //phpconsole("params are: " . print_r($samloginParams->toArray(), true), "rastrano");
        // Include the externally configured SimpleSAMLphp instance 
        $currentSession = JFactory::getSession();

        $SAMLoginIsAuthN = $currentSession->get("SAMLoginIsAuthN", false);
        if ($SAMLoginIsAuthN === true) {
            $SAMLoginSession = $currentSession->get("SAMLoginSession", '');
            $SAMLoginAttrs = $currentSession->get("SAMLoginAttrs", '');
            $SAMLoginIdP = $currentSession->get("SAMLoginIdP", '');
            $SAMLoginSP = $currentSession->get("SAMLoginSP", '');
            $SAMLoginnameId = $currentSession->get("SAMLoginNameId", '');

            /*  $component = JTable::getInstance('component');
              $component->loadByOption('com_samlogin');
              $instance = new JParameter($component->params, JPATH_ADMINISTRATOR.'/components/com_samlogin/config.xml');
              echo(print_r($instance,true)); */
            //    //phpconsole("attributes are: " . print_r($SAMLoginAttrs, true), "rastrano");
            //  die(print_r($SAMLoginAttrs,true));
            // Check for access token
        //phpconsole(print_r($SAMLoginAttrs,true),"rastrano");

            $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_SUCCESS : JAUTHENTICATE_STATUS_SUCCESS;
            $response->type = 'SAMLogin';
            $response->error_message = '';

            $this->_mapAttributes($response, $SAMLoginAttrs, $samloginParams);

             print_r($response);
             print_r($response->getErrors());
           //  die("testing");


            $user = $this->_updateAndGetUser($response, $samloginParams);
            $this->_checkAuthZRules($user, $response, $SAMLoginAttrs, $samloginParams);
            $user = $this->_updateAndGetUser($response, $samloginParams);  //final to sync group changes in session
           
            $response->error_message = '';
        } else {
            $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_FAILURE : JAUTHENTICATE_STATUS_FAILURE;
        }
  
        
    }

}
