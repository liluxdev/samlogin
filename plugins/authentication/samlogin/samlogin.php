<?php

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
        //   die($query);
        // die(print_r($query->__toString(),true)); 
        //  //phpconsole(print_r($query->__toString(),true),"rastrano");
        return $db->loadResult();
    }

    private function _getSAMLAttributeFirstValue($key, $attributes, $default = null) {
        if (!array_key_exists($key, $attributes)) {
            return $default;
        }
        return $attributes[$key][0];
    }

    private function _getSAMLAttributeValues($key, $attributes, $default = null) {
        if (!array_key_exists($key, $attributes)) {
            return $default;
        }
        return $attributes[$key];
    }

    private function _pregMatchSAMLAttributeValues($attrname, $regex, $attributes) {
        if (!array_key_exists($attrname, $attributes)) {
            return FALSE;
        }
        $attrvals = $attributes[$attrname];
        foreach ($attrvals as $val) {
            //phpconsole("does ".$val." matches ".$regex,"rastrano");
            if (preg_match("/" . $regex . "/", $val)) {
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

        $defaultRegistered = $samloginParams->get("defaultRegistered", true);
        $newusergroups = array();
        if ($defaultRegistered) {
            $registeredGroupId = $samloginParams->get("registeredGroupId", 2);
            $newusergroups = array($registeredGroupId); //2 is registered
        } else {
            
        }



        //phpconsole("New  groups:".print_r($newusergroups,true),"rastrano");
        $newusergroupsForHist[] = array();
        $authorized = false;
        foreach ($rulesConf as $key => $val) {

            if (strpos($key, 'rule_') === 0) {
                if (stristr($key, "_attr")) {
                    $ruleno = str_replace("rule_", "", $key);
                    $ruleno = str_replace("_attr", "", $ruleno);

                    $ruleattr = $samloginParams->get("rule_" . $ruleno . "_attr", "");
                    if ($ruleattr) {
                        $ruleassigngroup = $samloginParams->get("rule_" . $ruleno . "_assigngroup", "");
                        $ruleRegexp = $samloginParams->get("rule_" . $ruleno . "_regex", "");

                        $sqlMatch = $samloginParams->get("rule_" . $ruleno . "_sql", "");
                        $sqlMatchOrig = $sqlMatch;
                        // echo $sqlMatch;

                        $ruleType = "";
                        if (!empty($sqlMatch)) {
                            $db = JFactory::getDbo();
                            $matches = array();
                            preg_match_all("/::([a-zA-Z0-9\.:]{1,99})::/", $sqlMatch, $matches);
                            $matchcount = -1;
                            foreach ($matches as $matcharrelm) {
                                $match = $matcharrelm[0];
                                $matchcount++;
                                if ($matchcount == 0) {
                                    continue;
                                }

                                if ($match != "NAMEID") {
                                    $attrValues = $this->_getSAMLAttributeValues($match, $samlresponse, null);
                                } else {
                                    $currentSession = JFactory::getSession();
                                    $SAMLoginnameId = $currentSession->get("SAMLoginNameId", '');
                                    if (empty($SAMLoginnameId)) {
                                        die("Empty NameID array");
                                    } else {
                                        $SAMLoginnameId = json_decode($SAMLoginnameId, true);
                                        $SAMLoginnameId = $SAMLoginnameId["Value"];
                                        if (empty($SAMLoginnameId)) {
                                            die("Empty NameID");
                                        }
                                        $attrValues = array();
                                        $attrValues[] = $SAMLoginnameId;
                                    }
                                }

                                if (!is_null($attrValues)) {
                                    if (count($attrValues) > 1) {
                                        //SQL Match can only used on single valued attributes for now and for security contraint
                                    } else {
                                        if (count($attrValues) == 1) {
                                            $value = $attrValues[0];
                                            $antiSQLiValue = "'" . addslashes(trim($value)) . "'";
                                            $sqlMatch = strtr($sqlMatch, array(
                                                "::" . $match . "::" => $antiSQLiValue
                                                    )
                                            );
                                        }
                                    }
                                }
                            }
                            /*  $sqlMatch=preg_replace_callback(
                              '|<p>\s*\w|',
                              create_function(
                              // l'apice singolo è essenziale qui,
                              // o in alternativa occorre usare la sequenza di escape \$
                              // per tutte le occorrenze di $
                              '$matches',
                              'return strtolower($matches[0]);'
                              ),
                              $sqlMatch
                              ); */
                            if ($sqlMatch != $sqlMatchOrig) { //check if strtr replaced the placeholders
                                $db->setQuery($sqlMatch);
                                //    echo "<br/>query is:".$sqlMatch;
                                $matchingArr = $db->loadAssocList();
                                //  echo "<br/>result is:".print_r($matchingArr,true);
                                $matching = count($matchingArr) > 0;
                            } else {
                                $matching = FALSE;
                            }
                            $ruleType = "sql";
                        }


                        if (!empty($ruleRegexp)) {
                            $ruleType = "regex";
                            //phpconsole($ruleattr." matches ".$ruleRegexp,"rastrano");
                            $matching = $this->_pregMatchSAMLAttributeValues($ruleattr, $ruleRegexp, $samlresponse);
                        }

                        ////phpconsole($ruleno." matches? ".$ruleRegexp."??? ".print_r($samlresponse,true),"rastrano");
                        if ($matching !== FALSE) {
                            $authorized = true;
                            //phpconsole($ruleno." matches ".$ruleRegexp,"rastrano");
                            $newusergroups[] = $ruleassigngroup;
                            $currentSession = JFactory::getSession();
                            $SAMLoginIdP = $currentSession->get("SAMLoginIdP", '');
                            $keytomem = "rule_" . $ruleno .
                                    " v:" . $matching . " t:" . $ruleType
                                    . " idp:" . $SAMLoginIdP;
                            $newusergroupsForHist[$keytomem] = $ruleassigngroup;
                        }
                    }
                }
            }
        }

//die("testing");
        if (!$authorized) {
            $defaultDeny = $samloginParams->get("defaultDeny", false);
            if ($defaultDeny) {
                return false;
            }
        }
        $timeid = time();

        //   //phpconsole(print_r($latestSamloginAssignedGroups,true),"rastrano");


        $preserveManualUserGroup = $samloginParams->get("preserveManualUserGroup", true);
        if ($preserveManualUserGroup) {
            $latestSamloginAssignedGroups = $this->_getLastestGroupAssigned($user);

            $manualOldUsergroups = array_diff($oldusergroups, $latestSamloginAssignedGroups);

            //merge only the old groups NON-samlogin assigned groups
            $newusergroups = array_merge($newusergroups, $manualOldUsergroups);
            foreach ($newusergroupsForHist as $key => $hitem) {
                if (in_array($hitem, $manualOldUsergroups)) {
                    //don't storicize as saml added a manually added usergroup
                    unset($newusergroupsForHist[$key]);
                    $newusergroupsForHist["manual"] = $hitem; //bust storicize it as a manual! (to track/log access time authz)
                }
            }
        }

        $this->_saveAddedGroupHist($newusergroupsForHist, $user, $timeid); //don't save the modified newusergroups array here: only the saml added one
        $user->set("groups", $newusergroups);
        //     $user->name = $response->fullname; //bug: different field naming in joomla table, check will fail

        $saved = $user->save();

        //Reflection: TODO maintain protected __authGroups in user.php JUser
        $refObject = new ReflectionObject($user);
        $refProperty = $refObject->getProperty('_authGroups');
        $refProperty->setAccessible(true);
        $refProperty->setValue($user, $newusergroups); //schedure a re fetch of auth group for the in-memory session user object
        //
        //also JAccess has a group cache
        //
        JAccess::clearStatics(); //this clears things up $groupByUser cahced array!
        //
        //see user.php getAuthorisedGroups() method
        @$user->getAuthorisedGroups(); //ensure the re-fetch of auth groups for the in-memory session user object

        return true;
    }

    private function _getLastestGroupAssigned($user, $excludeInitiator = "manual") {
        $toret = array();
        $isJoomla3 = ((float) JVERSION) >= 3.0;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("username,MAX(timeid) AS timeid")
                ->from('#__samlogin_authz_hist')
                ->where("username = " . $db->quote($user->get("username")))
                ->group("username");
        $db->setQuery($query, 0, 1); //limit is here


        $maxTidRow = $db->loadAssoc();
        $lastTimeId = $maxTidRow["timeid"];
        if ($lastTimeId == null) {
            $lastTimeId = 0;
        }
        $query = null;
        $query = $db->getQuery(true);
        $query->select("username," . $db->quoteName("group") . " AS grpid,initiator")
                ->from('#__samlogin_authz_hist')
                ->where(array(
                    "timeid = " . $lastTimeId,
                    "username = " . $db->quote($user->get("username"))
                        ), "AND");
        $db->setQuery($query);
//die(print_r($query->__toString(),true));

        $lastGroupsRow = $db->loadAssocList();
        if (is_array($lastGroupsRow)) {
            foreach ($lastGroupsRow as $row) {
                $initiator = $row["initiator"];
                if ($initiator != $excludeInitiator) {
                    $toret[] = $row["grpid"];
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
            if (is_array($thisgroup)) {
                $thisgroup = -1;
            }
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
        $mappingConfExtra = $samloginParams->get("extramapping", '');
        if (!empty($mappingConfExtra)) {
            $arr = json_decode($mappingConfExtra, true);
            foreach ($arr as $key => $val) {
                $mappingConf["mapping_extra::" . $key] = $val;
            }
        }

        $extraFields = array();

        foreach ($mappingConf as $key => $val) {
            // mapping parameters prefix
            if (strpos($key, 'mapping_') === 0) {
                $joomlaKey = substr($key, strlen("mapping_"));
                $isExtraField = false;
                $extraFieldGroup = "";
                if (strstr($joomlaKey, "::")) {
                    $tmp = explode("::", $joomlaKey);
                    $isExtraField = true;
                    $joomlaKey = $tmp[2];
                    $extraFieldGroup = $tmp[1];
                }
                if (!empty($val)) {
                    $attrNameAlternatives = explode("|", $val) ? explode("|", $val) : array($val);
                    //  //phpconsole($attrNameAlternatives,"rastrano");
                    //  print_r($attrNameAlternatives);
                    $attrFound = false;
                    foreach ($attrNameAlternatives as $attrNameSyntax) {
                        if ($attrFound) {
                            break;
                        }
                        $attrConcat = explode("+", $attrNameSyntax) ? explode("+", $attrNameSyntax) : array($attrNameSyntax);
                        $firstAttrOfConcat = true;
                        foreach ($attrConcat as $attrName) {
                            if ($firstAttrOfConcat) {
                                if (!$isExtraField) {
                                    $response->$joomlaKey = $this->_getSAMLAttributeFirstValue($attrName, $samlresponse);
                                } else {
                                    if (!is_array($extraFields[$extraFieldGroup])) {
                                        $extraFields[$extraFieldGroup] = array();
                                    }
                                    $extraFields[$extraFieldGroup][$joomlaKey] = $this->_getSAMLAttributeFirstValue($attrName, $samlresponse);
                                }
                                $firstAttrOfConcat = false;
                            } else {
                                if (!$isExtraField) {
                                    $response->$joomlaKey .= " " . $this->_getSAMLAttributeFirstValue($attrName, $samlresponse, "");
                                } else {

                                    $extraFields[$extraFieldGroup][$joomlaKey] = $extraFields[$extraFieldGroup][$joomlaKey] . " " . $this->_getSAMLAttributeFirstValue($attrName, $samlresponse);
                                }
                            }
                            if (!is_null($response->$joomlaKey) && !empty($response->$joomlaKey)) {
                                $response->$joomlaKey = trim($response->$joomlaKey); //avoid one space values
                            }
                            if (
                                    (
                                    !is_null($response->$joomlaKey) && !empty($response->$joomlaKey)
                                    ) ||
                                    (
                                    defined($extraFieldGroup) && defined($extraFields[$extraFieldGroup]) && is_array($extraFields[$extraFieldGroup]) && !empty($extraFields[$extraFieldGroup][$joomlaKey])
                                    )
                            ) {
                                //
                                //       print "<hr/>Found $joomlaKey : using ".print_r($attrName,true)." with value".$response->$joomlaKey."<hr/>";
                                $attrFound = true;
                            }
                        }
                    }
                }
            }
        }

        $extraFieldsTmp = array();
        foreach ($extraFields as $extraFieldGroup => $objArr) {
            if ($extraFieldGroup == "flat_user_table_field") {
                $extraFieldsTmp = array_merge($extraFieldsTmp, $objArr);
            } else {
                $extraFieldsTmp[$extraFieldGroup] = $objArr;
            }
        }
        //    die(print_r($extraFieldsTmp,true));

        if (empty($response->email)) {
            $useDummyEmails = $samloginParams->get("useDummyEmails", false);
            if ($useDummyEmails) {
                $response->email = $response->username . "@" . $samloginParams->get("dummyEmailDomain", strtr($_SERVER['HTTP_HOST'], array("www." => "")));
            }
        }


        $useNameID = $samloginParams->get("useNameId", false);
        if ($useNameID) {
            $currentSession = JFactory::getSession();
            $SAMLoginnameId = $currentSession->get("SAMLoginNameId", '');
            if (empty($SAMLoginnameId)) {
                die("Empty NameID array");
            } else {
                $SAMLoginnameId = json_decode($SAMLoginnameId, true);
                $SAMLoginnameId = $SAMLoginnameId["Value"];
                if (empty($SAMLoginnameId)) {
                    die("Empty NameID");
                }
            }

            $response->username = $SAMLoginnameId;
        }


        $response->type = 'SAMLogin';


        // //phpconsole($response, "rastrano");

        $userid = $this->getUserIdByUsernameOrMail($response->username);


        if ($userid) {
            $user = JFactory::getUser($userid);

            $updateUsernameIfMailOverlap = $samloginParams->get("updateUsernameIfMailOverlap", false);

            if ($updateUsernameIfMailOverlap && $response->username != $user->username) {
                //  $user->username = $response->username;
                //  $saved = $user->save(true); //this overrides current response values!!
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
                $query->update('#__users')
                        ->set('username = ' . $db->Quote($response->username))
                        ->where("username = ". $db->Quote($user->username). " AND id=".$userid);
                $db->setQuery($query);
                $db->execute();
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

        return $extraFieldsTmp;
    }

    public function _updateAndGetUser($response, $samloginParams, $extraFields) {
        // Establish userid of authenticated user, or 0 when user does not exist
        $user = null;
        $userid = JUserHelper::getUserId($response->username);

        if ($userid) {
            $user = JFactory::getUser($userid);

            //print_r($user->get('username'));
            // Detect changes, and update if required
            $changed = true;
            if ($changed) {
                $getusrprop = version_compare(JVERSION, '3.2', 'l') ?
                        $response->getProperties() : (array) $response;
                //          print("new user props: ".print_r($getusrprop,true));
                $getusrprop["name"] = $getusrprop["fullname"];  //the JUser object has different naming for that prop
                if (is_array($extraFields)) {
                    $getusrprop = array_merge($getusrprop, $extraFields);
                }
                //    die("binding user props:" . print_r($getusrprop, true));
                $user->bind($getusrprop);
                $user->save(true); // true means: updateOnly
                //     die("new user obj: ".print_r($user,true));
            } else {
                $getusrprop = version_compare(JVERSION, '3.2', 'l') ?
                        $response->getProperties() : (array) $response;
                $getusrprop["name"] = $getusrprop["fullname"]; //the JUser object has different naming f
                if (is_array($extraFields)) {
                    $getusrprop = array_merge($extraFields, $getusrprop);
                }
                //   die("binding user props:" . print_r($getusrprop, true));

                $user->bind($getusrprop);
            }
        } else {
            // Store as new user

            $user = new JUser();
            $user->username = $userid;
            $user->usertype = 'SAMLogin';
            if ($samloginParams->get("requireApproval", false)) {
                $user->block = 1;
                $user->activation = 0;
            } else {
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

            $getusrprop = version_compare(JVERSION, '3.2', 'l') ?
                    $response->getProperties() : (array) $response;
            $user->bind($getusrprop);

            $saved = $user->save();
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

            $extraFields = $this->_mapAttributes($response, $SAMLoginAttrs, $samloginParams);

            $allowOnlyExistingUsernames = $samloginParams->get("allowOnlyExistingUsernames", false);
            if ($allowOnlyExistingUsernames) {
                $userid = JUserHelper::getUserId($response->username);
                //    print("userid: ".$userid);
                if ($userid == 0) { //getUserId returns 0 if user is not found
                    $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_FAILURE : JAUTHENTICATE_STATUS_FAILURE;
                    $response->error_message = JText::_('SAMLOGIN_NON_EXISTING_ACCOUNT');
                    $currentSession->set("samloginFailErrcode", 'SAMLOGIN_NON_EXISTING_ACCOUNT');
                    return false;
                }
            }
            //  print_r($response);
            //    print_r($response->getErrors());
            //  die("testing");



            $user = $this->_updateAndGetUser($response, $samloginParams, $extraFields);

            $allowed = $this->_checkAuthZRules($user, $response, $SAMLoginAttrs, $samloginParams);
            if (!$allowed) {
                $response->error_message = $samloginParams->get("defaultDenyMsg", JText::_('SAMLOGIN_NOT_AUTHZ'));
                $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_FAILURE : JAUTHENTICATE_STATUS_FAILURE;
                $currentSession->set("samloginUnauthzMessage", $response->error_message);
                $errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=unauthzAlert&msg=session');
                try {  //TODO: if session.storage not sql, destroy the session
                    /**
                     * The _include script registers a autoloader for the simpleSAMLphp libraries. It also
                     * initializes the simpleSAMLphp config class with the correct path.
                     */
                    require_once(JPATH_BASE . '/components/com_samlogin/simplesamlphp/lib/_autoload.php');
                    /*
                     * Explisit instruct consent page to send no-cache header to browsers
                     * to make sure user attribute information is not store on client disk.
                     *
                     * In an vanilla apache-php installation is the php variables set to:
                     * session.cache_limiter = nocache
                     * so this is just to make sure.
                     */


                    /* Load simpleSAMLphp, configuration and metadata */
                    $config = SimpleSAML_Configuration::getInstance();
                    $session = SimpleSAML_Session::getInstance();
                    $selfUrl = SimpleSAML_Utilities::selfURL();

//new sp api
                    $as = new SimpleSAML_Auth_Simple('default-sp'); //new sp api

                    $as->logout(array(
                        "ReturnStateParam" => "SAMLLoginSLOState",
                        "ReturnStateStage" => "SAMLLoginSLOStage",
                        //    "ErrorURL" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleErr' . $extraReturnURLParams),
                        //"ReturnTo" => JRoute::_('index.php?option=com_samlogin&view=login&task=handleSuccess' . $extraReturnURLParams),
                        "ReturnTo" => $errUrl
                            //  "KeepPost" => FALSE
                    ));
                    //$errUrl = JRoute::_('index.php?option=com_samlogin&view=login&task=logoutAlert&msg=session');
                    // $app->redirect($errUrl);      
                } catch (Exception $e) {

                    JFactory::getApplication()->redirect($errUrl);
                }
                return false;
            }
            $user = $this->_updateAndGetUser($response, $samloginParams, $extraFields);  //final to sync group changes in session
            // die(print_r($user,true)."debbuging at line: ".__LINE__);
            $response->error_message = '';
        } else {
            $response->status = version_compare(JVERSION, '3.0', 'ge') ? JAuthentication::STATUS_FAILURE : JAUTHENTICATE_STATUS_FAILURE;
        }
    }

}
