<?php
// No direct access to this file
defined('_JEXEC') or die;

include_once(JPATH_COMPONENT_ADMINISTRATOR."/libs/array_smart_dump.inc.php");



class SSPConfManager{
    
    
    static function getAuthsourcesConf(){
                require_once(JPATH_SITE."/components/com_samlogin/simplesamlphp/lib/_autoload.php");
                require(JPATH_SITE."/components/com_samlogin/simplesamlphp/config/authsources.php");
                return $config;
    }
    
    static function mergeParamsWithConf($params,$app){
        
        $authsourcesConf = self::getAuthsourcesConf();
             $discoType=$params->get("sspas_discotype","0");
             if ($discoType==="0"||$discoType===0){
                 $authsourcesConf["default-sp"]["discoURL"]=$params->get("sspas_discourl","https://discovery.renater.fr/test");
             }else{
                 $discoUrl=$params->get("sspas_discotype",null);
                 
                 if ($discoUrl=="null"){
                     $discoUrl=null;
                 }
          
                 switch ($discoType){
                     case "discopower":
                         $discoUrl=  str_ireplace("http://", "https://",JURI::root())."/components/com_samlogin/simplesamlphp/www/module.php/discopower/disco.php";
                     break;
                     case "discojuice_standalone":
                         
                        $app =& JApplication::getInstance('site');
                        $router = $app->getRouter('site');
                        $wrong_route = $router->build( 'index.php?option=com_samlogin&view=discojuice' )->toString();
                        $correct_route = preg_replace("|^" . JURI::base(true) . "|","",$wrong_route);
                        $protocolhost = JURI::getInstance()->getScheme() . '://' . JURI::getInstance()->getHost();
                        $absolute_url = $protocolhost . $correct_route;


                        /* $discojuiceurl=  //replace /administrator
                            str_replace(JURI::base(), JURI::root(),
                                    JRoute::_('index.php?option=com_samlogin&view=discojuice')); */
                        
                         $discoUrl=  str_ireplace("http://", "https://",$absolute_url);
                     break;
                     case "discojuice_embedded":
                         $discoUrl=  null;
                     break;
                     default: 
                         $discoUrl=null;
                 }
                 
                 $authsourcesConf["default-sp"]["discoURL"]= $discoUrl;
             }
            // die(  $authsourcesConf["default-sp"]["discoURL"]);
        self::saveAuthsourcesConf($authsourcesConf, $app);
      
        
        $config=self::getConf();
        $paramsArr=$params->toArray();
        $useMetarefresh=false;
        
   
        
        foreach ($paramsArr as $key => $value) {
            if (stripos($key,"ssp_")===0){
                $realkey=  str_ireplace("ssp_", "", $key);
                $realkey=  str_ireplace("__", ".", $realkey);
                switch ($realkey){
                    case "use_metarefresh":
                        $value = ($value=="1" || $value==1);
                        if ($value){
                             $useMetarefresh=true;
                        }
                        break;
                    
                    case "showerrors":
                        $value = ($value=="1" || $value==1);
                    case "debug":
                        $value = ($value=="1" || $value==1);
                    case "debug.validatexml":
                        $value = ($value=="1" || $value==1);
                    default:
                         $config[$realkey]=$value;
                }
          
            }
        }
        
        
        
        $config["metadata.sources"]=array(array("type"=>"flatfile"));
        $configmetarefreshSrcs=array();
        foreach ($paramsArr as $key => $value) {
            if (stripos($key,"sspmeta_")===0){
                $realkey=  str_ireplace("sspmeta_", "", $key);
                $realkey=  str_ireplace("__", ".", $realkey);
                
                if (!$useMetarefresh){
                    if (filter_var($value, FILTER_VALIDATE_URL) !== false){
                     $config["metadata.sources"][]=array("type"=>"xml","url"=>$value);
                    }else{
                       $url= trim($value);
                        if (!empty($url)){
                            $app->enqueueMessage("Invalid metadata url: ".$url,"warning");
                        }
                    }
                }else{
                    if (filter_var($value, FILTER_VALIDATE_URL) !== false){
                         $configmetarefreshSrcs[]=array("src"=>$value);
                    }else{
                         $url= trim($value);
                        if (!empty($url)){
                            $app->enqueueMessage("Invalid metadata url: ".$url,"warning");
                        }
                    }
                     
                }
                
          
            }
        }
        
        if($useMetarefresh){
             $config["metadata.sources"][]=array("type"=>"flatfile","directory"=>"metadata/federations");
             self::saveMetarefreshSrcConf($configmetarefreshSrcs,$app);
             $cronConf=array(
                    "key"=>$params->get("sspcron_secret","changeme"),
                    'allowed_tags' => array('daily', 'hourly', 'frequent'),
                    'debug_message' => $params->get("sspcron_debug",false)==1,
                    'sendemail' => $params->get("sspcron_email",false)==1,  
             );
             self::saveCronConf($cronConf,$app);
        }
        
       
        
        return $config;
    }
    
    static function getCertDirPath(){
           $SSPConf=self::getConf();
           $certdir=$SSPConf["certdir"];
  
           if (stripos($certdir,"/") === 0){
                  $SSPKeyPath=$certdir;
           }
           else{
               $SSPKeyPath=JPATH_SITE."/components/com_samlogin/simplesamlphp/".$certdir;
           }
           return $SSPKeyPath;
    }
    
       static function getCertURLPath(){
           $SSPConf=self::getConf();
           $certdir=$SSPConf["certdir"];
           if (stripos($certdir,"/") === 0){
               $howmanyslashes = JPATH_SITE;
               $slashes=preg_split("\/", $howmanyslashes);
               $backwardTest="";
               foreach($slashes as $nomattdir){
                   $backwardTest.="/../";
               }
               $SSPKeyPath=$backwardTest.$certdir;
           }
           else{
               $SSPKeyPath="/components/com_samlogin/simplesamlphp/".$certdir;
           }
           return $SSPKeyPath;
    }
           
    
    static function saveCronConf($cronconf,$app){
        $SSPConfPath=JPATH_COMPONENT_SITE."/simplesamlphp/config/";

        $oldSSPConf=  file_get_contents($SSPConfPath."module_cron.php");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPConfPath."module_cron.until_$datetimestring.php", $oldSSPConf);
        require($SSPConfPath."module_cron.php");
        $config= array_merge($config,$cronconf);
       // $config["samlogin_lastchanged"] = $datetimestring;
        $newConfFileStr = array_smart_dump($config,"config");
        
        file_put_contents($SSPConfPath."module_cron.php", "<?php /* This conf file was generated by samlogin for Joomla!, but you can modify it! */\n".$newConfFileStr);
    
        $app->enqueueMessage(JText::_('SAMLOGIN_GENCRONSECRET_CONF_OK'));
        return true;
    }
    
     static function saveMetarefreshSrcConf($srcArray,$app){
        $SSPConfPath=JPATH_COMPONENT_SITE."/simplesamlphp/config/";

        $oldSSPConf=  file_get_contents($SSPConfPath."config-metarefresh.php");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPConfPath."config-metarefresh.until_$datetimestring.php", $oldSSPConf);
        require($SSPConfPath."config-metarefresh.php");
        $config["sets"]["samlogin"]["sources"]=$srcArray;
       // $config["samlogin_lastchanged"] = $datetimestring;
        $newConfFileStr = array_smart_dump($config,"config");
        
        file_put_contents($SSPConfPath."config-metarefresh.php", "<?php /* This conf file was generated by samlogin for Joomla!, but you can modify it! */\n".$newConfFileStr);
    
        $app->enqueueMessage(JText::_('SAMLOGIN_GENMETARERESH_CONF_OK'));
        return true;
    }
    
    static function saveAuthsourcesConf($config,$app){
        $SSPConfPath=JPATH_COMPONENT_SITE."/simplesamlphp/config/";

        $oldSSPConf=  file_get_contents($SSPConfPath."authsources.php");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPConfPath."authsources.until_$datetimestring.php", $oldSSPConf);
        
       
       // $config["samlogin_lastchanged"] = $datetimestring;
        $newConfFileStr = array_smart_dump($config,"config");
        
        file_put_contents($SSPConfPath."authsources.php", "<?php /* This conf file was generated by samlogin for Joomla!, but you can modify it! */\n".$newConfFileStr);
    
        $app->enqueueMessage(JText::_('SAMLOGIN_GENCONF_OK'));
        return true;
    }
    
    
    static function getConf(){
                require_once(JPATH_SITE."/components/com_samlogin/simplesamlphp/lib/_autoload.php");
                require(JPATH_SITE."/components/com_samlogin/simplesamlphp/config/config.php");
                return $config;
    }
    
    static function saveConf($config,$app){
    
        $SSPConfPath=JPATH_COMPONENT_SITE."/simplesamlphp/config/";

        $oldSSPConf=  file_get_contents($SSPConfPath."config.php");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPConfPath."config.until_$datetimestring.php", $oldSSPConf);

        $config["samlogin_lastchanged"] = $datetimestring;
         
        $newConfFileStr = array_smart_dump($config,"config");
    
        file_put_contents($SSPConfPath."config.php", "<?php /* This conf file was generated by samlogin for Joomla!, but you can modify it! */\n".$newConfFileStr);

        
        $app->enqueueMessage(JText::_('SAMLOGIN_GENCONF_OK'));
        return true;
       
    }
}
?>
