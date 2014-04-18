<?php
// No direct access to this file
defined('_JEXEC') or die;
$phpseclibPath=JPATH_COMPONENT_ADMINISTRATOR."/libs/phpseclib/phpseclib0.3.5/";
set_include_path(get_include_path() . PATH_SEPARATOR .$phpseclibPath );
include_once(JPATH_COMPONENT_ADMINISTRATOR."/libs/phpseclib/phpseclib0.3.5/Crypt/RSA.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR."/libs/phpseclib/phpseclib0.3.5/File/X509.php");
include_once("sspconfmanager.php");


class KeyManager{

    static function genkey($app){
           
                    // create private key for CA cert
            // (you should probably print it out if you want to reuse it)
            $CAPrivKey = new Crypt_RSA();
            extract($CAPrivKey->createKey());
            $CAPrivKey->loadKey($privatekey);

            $pubKey = new Crypt_RSA();
            $pubKey->loadKey($publickey);
            $pubKey->setPublicKey();

            //echo "the private key for the CA cert (can be discarded):\r\n\r\n";
            //echo $privatekey;
            //echo "\r\n\r\n";


            // create a self-signed cert that'll serve as the CA
            $subject = new File_X509();
            $subject->setPublicKey($pubKey);
            $subject->setDNProp('id-at-organizationName', 'PHP Generated');

            $issuer = new File_X509();
            $issuer->setPrivateKey($CAPrivKey);
            $issuer->setDN($CASubject = $subject->getDN());

            $x509 = new File_X509();
            $x509->setStartDate('-1 month');
            $x509->setEndDate('+10 year');
            $x509->setSerialNumber(chr(1));
            $x509->makeCA();

            $result = $x509->sign($issuer, $subject);
           //echo "the CA cert to be imported into the browser is as follows:\r\n\r\n";
           // echo $x509->saveX509($result);
           // echo "\r\n\r\n";


            // create private key / x.509 cert for stunnel / website
            $privKey = new Crypt_RSA();
            extract($privKey->createKey());
            $privKey->loadKey($privatekey);

            $pubKey = new Crypt_RSA();
            $pubKey->loadKey($publickey);
            $pubKey->setPublicKey();

            $subject = new File_X509();
            $subject->setPublicKey($pubKey);
            $subject->setDNProp('id-at-organizationName', 'PHP Generated Cert');
            $subject->setDomain('nomatter.nomatter');

            $issuer = new File_X509();
            $issuer->setPrivateKey($CAPrivKey);
            $issuer->setDN($CASubject);

            $x509 = new File_X509();
            $x509->setStartDate('-1 month');
            $x509->setEndDate('+10 year');
            $x509->setSerialNumber(chr(1));

            $result = $x509->sign($issuer, $subject);
           // echo "the stunnel.pem contents are as follows:\r\n\r\n";
           // echo $privKey->getPrivateKey();
           // echo "\r\n";
           // echo $x509->saveX509($result);
           // echo "\r\n";
           $PEMprivatekey = $privKey->getPrivateKey();
           $PEMcert=$x509->saveX509($result);
           
          $SSPKeyPath=  SSPConfManager::getCertDirPath();
          
        $privatekeyTmp = $PEMprivatekey; //see extract() php doc to learn how this var is created
        $publickeyTmp = $PEMcert; //see extract() php doc to learn how this var is created
        
        file_put_contents($SSPKeyPath."saml.key.tmp", $privatekeyTmp);
        file_put_contents($SSPKeyPath."saml.crt.tmp", $publickeyTmp );
        
        $privtest=  file_get_contents($SSPKeyPath."saml.key.tmp");
        $pubtest=  file_get_contents($SSPKeyPath."saml.crt.tmp");
        //see: http://php.net/manual/en/function.openssl-pkey-new.php and http://phpseclib.sourceforge.net/
   
        $privKey->loadKey($privtest);

        $privatekeyTest = $PEMprivatekey;
        $publickeyTest = $PEMcert;
        
        if ($publickeyTest!=$publickeyTmp || $pubtest!=$publickeyTmp){
            // Add a message to the message queue
           // $app->enqueueMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), 'error');
            SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
            SAMLoginControllerAjax::enqueueAjaxMessage('Public key is '.$publickeyTest.' certificate file path is '.$SSPKeyPath.'. Written to file: <pre>'.$publickeyTmp."</pre>"
                    , SAMLoginControllerAjax::$AJAX_MESSAGE_WARNING);

            unlink($SSPKeyPath."saml.key.tmp");
            unlink($SSPKeyPath."saml.crt.tmp");
            
            //TODO:
            //try exec openssl one line command without prompt:
            //E.g. openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=US/ST=Test/L=Springfield/O=Dis/CN=www.example.com" -keyout www.example.com.key  -out www.example.com.cer
            
            return false;
        }
        
        $privBackup=  file_get_contents($SSPKeyPath."saml.key");
        $pubBackup=  file_get_contents($SSPKeyPath."saml.crt");
        $datetimestring = date('j_M_y_H_i_s', time());
        $privkeybackupname="saml.backup_until_$datetimestring.key";
        file_put_contents($SSPKeyPath.$privkeybackupname, $privBackup);
        $pubkeybackupname="saml.backup_until_$datetimestring.crt";
        file_put_contents($SSPKeyPath.$pubkeybackupname, $pubBackup );
        
        $config = SSPConfManager::getAuthsourcesConf();

        $config["default-sp"]["new_privatekey"] = "saml.key";
        $config["default-sp"]["new_certificate"] = "saml.crt";
        $config["default-sp"]["privatekey"] = $privkeybackupname;
        $config["default-sp"]["certificate"] = $pubkeybackupname;
        
        SSPConfManager::saveAuthsourcesConf($config, $app);
        
        
        file_put_contents($SSPKeyPath."saml.key", $privatekeyTest);
        file_put_contents($SSPKeyPath."saml.crt", $publickeyTest );
        
     //   $app->enqueueMessage(JText::_('SAMLOGIN_GENKEY_OK'));
        SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_GENKEY_OK'), SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
        
        unlink($SSPKeyPath."saml.key.tmp");
        unlink($SSPKeyPath."saml.crt.tmp");
        return true;
    }
    
    static function keyrotateEndPeriod($app){
        $config = SSPConfManager::getAuthsourcesConf();
       
        $config["default-sp"]["privatekey"] = "saml.key";
        $config["default-sp"]["certificate"] = "saml.crt";
        unset($config["default-sp"]["new_privatekey"]);
        unset($config["default-sp"]["new_certificate"]);

        SSPConfManager::saveAuthsourcesConf($config, $app);
    }
    
    static  function _depr_genkey($app){
 
 
// see samples: http://phpseclib.sourceforge.net/x509/compare.html
        $rsa = new Crypt_RSA();

        $rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
        $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);
       
        //$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);    
        
        //define('CRYPT_RSA_EXPONENT', 65537);
        //define('CRYPT_RSA_SMALLEST_PRIME', 64); // makes it so multi-prime RSA is used
        
        extract($rsa->createKey(2048)); // == $rsa->createKey(1024) where 1024 is the key size
        $SSPKeyPath=  SSPConfManager::getCertDirPath();
        
        $privatekeyTmp = $privatekey; //see extract() php doc to learn how this var is created
        $publickeyTmp = $publickey; //see extract() php doc to learn how this var is created
        
        file_put_contents($SSPKeyPath."saml.key.tmp", $privatekeyTmp);
        file_put_contents($SSPKeyPath."saml.crt.tmp", $publickeyTmp );
        
        $privtest=  file_get_contents($SSPKeyPath."saml.key.tmp");
        $pubtest=  file_get_contents($SSPKeyPath."saml.crt.tmp");
        //see: http://php.net/manual/en/function.openssl-pkey-new.php and http://phpseclib.sourceforge.net/
   
        $rsa->loadKey($privtest);

        $privatekeyTest = $rsa->getPrivateKey();
        $publickeyTest = $rsa->getPublicKey();
        
        if ($publickeyTest!=$publickeyTmp || $pubtest!=$publickeyTmp){
            // Add a message to the message queue
            //$app->enqueueMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), 'error');
                  SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
            unlink($SSPKeyPath."saml.key.tmp");
            unlink($SSPKeyPath."saml.crt.tmp");
            return false;
        }
        
        $privBackup=  file_get_contents($SSPKeyPath."saml.key");
        $pubBackup=  file_get_contents($SSPKeyPath."saml.crt");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPKeyPath."saml.backup_until_$datetimestring.key", $privBackup);
        file_put_contents($SSPKeyPath."saml.backup_until_$datetimestring.crt", $pubBackup );
        
        
        file_put_contents($SSPKeyPath."saml.key", $privatekeyTest);
        file_put_contents($SSPKeyPath."saml.crt", $publickeyTest );
        
      //  $app->enqueueMessage(JText::_('SAMLOGIN_GENKEY_OK'));
        SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_GENKEY_OK'), SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
        
        unlink($SSPKeyPath."saml.key.tmp");
        unlink($SSPKeyPath."saml.crt.tmp");
        return true;
       
    }
}
?>
