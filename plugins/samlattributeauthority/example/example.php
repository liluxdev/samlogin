<?php
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * Samlogin example attribute authority fetcher plugin
 */
class plgSamlattributeauthorityExample extends JPlugin {
   
    private static $fetchedAttrPrefix = "attribute_authority_example";

    public function plgSamlattributeauthorityExample(&$subject, $config) {
        parent::__construct($subject, $config);
    }

    /**
     * 
     * This event is called before the SAML attribute mappings, before completing a SAML login, 
     * it is useful to fetch additional user attribute from external or custom attributes authorities
     * before the mapping is done so if you modify the $samlAssertionAttributes array as show in the example 
     * (done with dummy values and no fetching, the fetcher logic is up to you), you will be able to use this variables
     * in Components->Samlogin->Settings->Attr.Mappings in order to be able to directlty use the external data in
     * your samlogin Joomla user mappings eg. in this example you can map Joomla username to "attribute_authority_example_username"
     * that in this case will have the unique demo value of "fetched_username_demo_$samlNameId"
     * 
     * @param Array $samlAssertionAttributes the current saml assertion attrbutes, passed by reference so you can modify it adding or overriding 
     * the fetched values. PAY ATTENTION: always add values as nested array
     * @param mixed $samlNameId the SAML NameId received in the assertion, usually this is good as key for the attribute autority 
     * (anyway you can also use another attribute in the $samlAssertionAttributes as key, eg. $samlAssertionAttributes["eduPersonPrincipalName"])
     * @param JRegistry $samloginparams the samlogin extension main settings, don't needed just provided as commodity if you have to do some checks on the configuration
     */
    public function onBeforeSAMLoginAttributeMappings(&$samlAssertionAttributes,$samlNameId,$samloginparams) {
        //$application = JFactory::getApplication();
        //die("I'm an attribute fetcher plugin, and the developer is testing me (please retry later): ".print_r($samlAssertionAttributes,true));
        
        /** REPLACE THIS EXAMPLE WITH YOUR CUSTOM FETCHER LOGIC
         * 
         * 
         *  PLEASE ALSO VERSION YOUR CHANGES IF YOU WILL NOT RENAME THE example.php/example.xml PLUGIN 
         *  (you should rename it and reinstall it to do a clean work)
         * 
        **/
        
        ////be careful with following: that's how override a single valued attribute (notice the 0)
        //$samlAssertionAttributes["givenName"][0] = "your fetched value".$samlNameId; 
        
        $samlAssertionAttributes[self::$fetchedAttrPrefix."_username"][] = "fetched_username_demo_".$samlNameId;
        $samlAssertionAttributes[self::$fetchedAttrPrefix."_fullname"][] = "fetched_fullname_demo_".$samlNameId;
        $samlAssertionAttributes[self::$fetchedAttrPrefix."_email"][] = "fetched_email_demo".$samlNameId."@test".$samlNameId.".com";
    }
    
    /**
     * 
     * This event is called when a SAML login is successfully completed
     * 
     * @param JUser $user the JUser (logged user) object passed by reference, enjoy it for your third parties integrations! 
     * Anyway remember to use the save() method on it or an equivalent db query if you want to persist your modifications on it and be able
     * to saw in the logged in user as the passing by reference in this case is not enough to save your overrides
     * @param Array $samloginAttributes the final saml assertion attrbutes, inlcuding the fetched attributes 
     * by the onBeforeSAMLoginAttributeMappings events, you can't modify it this time
     * @param mixed $samlNameId the SAML NameId received in the assertion, usually this is good as key for the attribute autority 
     * (anyway you can also use another attribute in the $samlAssertionAttributes as key, eg. $samlAssertionAttributes["eduPersonPrincipalName"])
     * @param JRegistry $samloginparams the samlogin extension main settings, don't needed just provided as commodity if you have to do some checks on the configuration
     */
    public function onAfterSAMLoginLoggedIn(&$user,$samloginAttributes,$samlNameId,$samloginparams){
        //example: 
        $joomla_user_id = $user->get("id");
        //die("debugging after login saml plugin, user id is ( $joomla_user_id ) user object is: ".print_r($user,true));
        //Remember to do $user->save() if you modified something
    }

}
