<?php

// No direct access to this file
defined('_JEXEC') or die;
$phpseclibPath = JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpseclibPath);
include_once(JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/Crypt/RSA.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/Crypt/Hash.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/File/X509.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/Math/BigInteger.php");
include_once(JPATH_COMPONENT_ADMINISTRATOR . "/libs/phpseclib/phpseclib0.3.5/Crypt/AES.php"); //mcrypt is used
include_once("sspconfmanager.php");

class KeyManager {

    private static function der2pem($der_data) {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    private static function pem2der($pem_data) {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

    //converts PEM cert info to DER cert info
    function pem2der_info($pem_data) {
        $begin = "CERTIFICATE REQUEST-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

// unpacks certificates from myproxy server response and
// converts them to PEM format
// Arg : -string containing the myproxy server response
// Returns : -array of strings each containing a certificate
    function der2pems($der_data) {
        $pems = array();
        $num_array = unpack('C', substr($der_data, 0, 1)); //C* converts to unsigned char
        $num_certs = $num_array[1]; //why does unpack start at index 1? why!?!?!

        $der_data = substr($der_data, 1); //trim off the number of certs from first byte
        //now bytes 1 and 2 mark the beginning of the cert
        for ($i = 0; $i < $num_certs; $i++) {
            $pem = "";
            $index = 0;
            //bytes 3 and 4 tell how long the cert is
            $l1 = ord(substr($der_data, $index + 2, $index + 3));
            $l2 = ord(substr($der_data, $index + 3, $index + 4));
            $len = (256 * $l1) + $l2;

            $thisCertData = substr($der_data, $index, $index + $len + 4);
            $pem = $pem . "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($thisCertData), 64, "\n")
                    . "-----END CERTIFICATE-----\n";
            $der_data = substr($der_data, $index + $len + 4);
            array_push($pems, $pem);
        }
        return $pems;
    }

    static function uploadKey($app, $publicKey, $privateKey, $format_pub = "pem", $format_priv = "pem") {
        if (SAMLoginControllerAjax::aquireLock("nosimulate")) {
            $SSPKeyPath = SSPConfManager::getCertDirPath();
            $privBackup = file_get_contents($SSPKeyPath . "saml.key");
            $pubBackup = file_get_contents($SSPKeyPath . "saml.crt");
            $datetimestring = date('j_M_y_H_i_s', time());
            $privkeybackupname = "saml.backup_until_$datetimestring.key";
            file_put_contents($SSPKeyPath . $privkeybackupname, $privBackup);
            $pubkeybackupname = "saml.backup_until_$datetimestring.crt";
            file_put_contents($SSPKeyPath . $pubkeybackupname, $pubBackup);

            $message = "ciao";
            $rsa = new Crypt_RSA();
            $rsa->loadKey($privateKey);


            /* Sign message */
            $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
            $rsa->setHash('sha1');
            $signature = $rsa->sign($message);

            /* not working
            $rsa2 = new Crypt_RSA();
            $x509 = new File_X509;
            $x509->loadX509($publicKey);
            $rsa2->loadKey($x509->getPublicKey());
            $isVerified = $rsa2->verify($message, $signature);

            if ($isVerified) {
                SAMLoginControllerAjax::enqueueAjaxMessage("Signature test passed (php server side)", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
            }
            */
            $isVerified=false;
            $noOpenSSL=false;
            if (function_exists("openssl_x509_export")){
                openssl_x509_export($publicKey, $str_cert);
                $res_pubkey = openssl_get_publickey($str_cert);
                $isVerified = openssl_verify($message, $signature, $res_pubkey);
            }else{
                $isVerified = false;
                $noOpenSSL = true;
            }
            // function doVerify() {

            /* X509
              //http://stackoverflow.com/questions/14757678/how-to-encrypt-decrypt-text-using-a-x509certificate-aes-256-algorithm

              $aes = new Crypt_AES();
              //    $x509->setPublicKey();
              $plaintext = 'ciaociao';
              $randomKey=$privateKey;

              $aes = new Crypt_AES();

              $aes->setKey($publicKey);
              $cryptakey = $aes->encrypt($plaintext);
              echo $chipertext."\n";
              SAMLoginControllerAjax::enqueueAjaxMessage($chipertext, SAMLoginControllerAjax::$AJAX_MESSAGE_INFO);
              $aes2 = new Crypt_AES();
              $aes2->setKey($privateKey);
              $aes2->setKeyLength(256);
              $plaintext_back = $aes2->decrypt($chipertext);

              echo "back;".$plaintext_back;
              SAMLoginControllerAjax::enqueueAjaxMessage($plaintext_back, SAMLoginControllerAjax::$AJAX_MESSAGE_INFO);
             * 
             */


            if ($isVerified || $noOpenSSL) {
                if ($noOpenSSL===false){
                    SAMLoginControllerAjax::enqueueAjaxMessage("Signature test passed (server side)", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
                }else{
                    SAMLoginControllerAjax::enqueueAjaxMessage("Warning: php-openssl bindings not available, cannot verify keypair server side, anyway we consider it valid", SAMLoginControllerAjax::$AJAX_MESSAGE_WARNING);
              
                }
                
                if ($privBackup == $privateKey && $pubBackup == $publicKey) {
                    SAMLoginControllerAjax::enqueueAjaxMessage("Already using this keypair. Operation aborted.", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
                    SAMLoginControllerAjax::releaseLock("nosimulate");
                    return false;
                } else {
                    file_put_contents($SSPKeyPath . "saml.key", $privateKey);
                    file_put_contents($SSPKeyPath . "saml.crt", $publicKey);
                    SAMLoginControllerAjax::enqueueAjaxMessage("Keypair written to file", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);
                }
            } else {
                SAMLoginControllerAjax::enqueueAjaxMessage("Signature test failed (server side). Operation skipped.", SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
                SAMLoginControllerAjax::releaseLock("nosimulate");
                return false;
            }

            $config = SSPConfManager::getAuthsourcesConf();

            $config["default-sp"]["new_privatekey"] = "saml.key";
            $config["default-sp"]["new_certificate"] = "saml.crt";
            $config["default-sp"]["privatekey"] = $privkeybackupname;
            $config["default-sp"]["certificate"] = $pubkeybackupname;
            SSPConfManager::saveAuthsourcesConf($config, $app, true);





            if (strtolower($format_priv) == "der") {
                $privateKey = self::der2pem($privateKey);
            }

            if (strtolower($format_pub) == "der") {
                $publicKey = self::der2pem($publicKey);
            }




            file_put_contents($SSPKeyPath . "saml.key", $privateKey);
            file_put_contents($SSPKeyPath . "saml.crt", $publicKey);

            //   $app->enqueueMessage(JText::_('SAMLOGIN_GENKEY_OK'));
            SAMLoginControllerAjax::enqueueAjaxMessage("A New X.509 certificate was uploaded for the XML encryption & signing", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);

            unlink($SSPKeyPath . "saml.key.tmp");
            unlink($SSPKeyPath . "saml.crt.tmp");

            SAMLoginControllerAjax::releaseLock("nosimulate");
            return true;
        }
    }

    static function genkey($app) {
        if (SAMLoginControllerAjax::aquireLock("nosimulate")) {
            // create private key for CA cert
            // (you should probably print it out if you want to reuse it)
            $CAPrivKey = new Crypt_RSA();
            define('CRYPT_RSA_EXPONENT', 65537); 
            $bits=1024;
            $bits=2048;
            SAMLoginControllerAjax::enqueueAjaxMessage("Notice: Using '$bits' bits for the random CSR",SAMLoginControllerAjax::$AJAX_MESSAGE_WARNING);
            extract($CAPrivKey->createKey($bits));
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
            $subject->setDNProp('samlogin', 'SAMLogin Generated Cert');

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
            $subject->setDNProp('samlogin', 'SAMLogin Generated Cert');
            // $subject->setDomain('nomatter.nomatter');

            $domain = JURI::getInstance()->getHost();
            if (preg_match("/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $domain)){
                $domain="foobar.org";
            }

            $subject->setDomain($domain);

            SAMLoginControllerAjax::enqueueAjaxMessage("Notice: Using '$domain' as domain name in the CN of the XML Signing & Encryption certificate", SAMLoginControllerAjax::$AJAX_MESSAGE_WARNING);


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
            $PEMcert = $x509->saveX509($result);

            $SSPKeyPath = SSPConfManager::getCertDirPath();

            $privatekeyTmp = $PEMprivatekey; //see extract() php doc to learn how this var is created
            $publickeyTmp = $PEMcert; //see extract() php doc to learn how this var is created

            file_put_contents($SSPKeyPath . "saml.key.tmp", $privatekeyTmp);
            file_put_contents($SSPKeyPath . "saml.crt.tmp", $publickeyTmp);

            $privtest = file_get_contents($SSPKeyPath . "saml.key.tmp");
            $pubtest = file_get_contents($SSPKeyPath . "saml.crt.tmp");
            //see: http://php.net/manual/en/function.openssl-pkey-new.php and http://phpseclib.sourceforge.net/

            $privKey->loadKey($privtest);

            $privatekeyTest = $PEMprivatekey;
            $publickeyTest = $PEMcert;

            if ($publickeyTest != $publickeyTmp || $pubtest != $publickeyTmp) {
                // Add a message to the message queue
                // $app->enqueueMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), 'error');
                SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
                SAMLoginControllerAjax::enqueueAjaxMessage('Public key is ' . $publickeyTest . ' certificate file path is ' . $SSPKeyPath . '. Written to file: <pre>' . $publickeyTmp . "</pre>"
                        , SAMLoginControllerAjax::$AJAX_MESSAGE_WARNING);

                unlink($SSPKeyPath . "saml.key.tmp");
                unlink($SSPKeyPath . "saml.crt.tmp");

                //TODO:
                //try exec openssl one line command without prompt:
                //E.g. openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj "/C=US/ST=Test/L=Springfield/O=Dis/CN=www.example.com" -keyout www.example.com.key  -out www.example.com.cer

                return false;
            }

            $privBackup = file_get_contents($SSPKeyPath . "saml.key");
            $pubBackup = file_get_contents($SSPKeyPath . "saml.crt");
            $datetimestring = date('j_M_y_H_i_s', time());
            $privkeybackupname = "saml.backup_until_$datetimestring.key";
            file_put_contents($SSPKeyPath . $privkeybackupname, $privBackup);
            $pubkeybackupname = "saml.backup_until_$datetimestring.crt";
            file_put_contents($SSPKeyPath . $pubkeybackupname, $pubBackup);



            $config = SSPConfManager::getAuthsourcesConf();

            $config["default-sp"]["new_privatekey"] = "saml.key";
            $config["default-sp"]["new_certificate"] = "saml.crt";
            $config["default-sp"]["privatekey"] = $privkeybackupname;
            $config["default-sp"]["certificate"] = $pubkeybackupname;
            SSPConfManager::saveAuthsourcesConf($config, $app, true);









            file_put_contents($SSPKeyPath . "saml.key", $privatekeyTest);
            file_put_contents($SSPKeyPath . "saml.crt", $publickeyTest);

            //   $app->enqueueMessage(JText::_('SAMLOGIN_GENKEY_OK'));
            SAMLoginControllerAjax::enqueueAjaxMessage("A New x.509 certificate was generated for the XML encryption & signing needed by the SAML endpoints (N.B. this is not related and does not substitute the HTTPS certificate of your webserver)", SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);

            unlink($SSPKeyPath . "saml.key.tmp");
            unlink($SSPKeyPath . "saml.crt.tmp");

            SAMLoginControllerAjax::releaseLock("nosimulate");
            return true;
        } else {
            SAMLoginControllerAjax::enqueueAjaxMessage("Cuncurrency issue please retry", SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
        }
        return false;
    }

    static function keyrotateEndPeriod($app) {
        if (SAMLoginControllerAjax::aquireLock("nosimulate")) {

            $config = SSPConfManager::getAuthsourcesConf();
            $config["default-sp"]["privatekey"] = "saml.key";
            $config["default-sp"]["certificate"] = "saml.crt";
            unset($config["default-sp"]["new_privatekey"]);
            unset($config["default-sp"]["new_certificate"]);
            SSPConfManager::saveAuthsourcesConf($config, $app, true);

            SAMLoginControllerAjax::releaseLock("nosimulate");
        } else {
            SAMLoginControllerAjax::enqueueAjaxMessage("Cuncurrency issue please retry", SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
        }
    }

    static function _depr_genkey($app) {


// see samples: http://phpseclib.sourceforge.net/x509/compare.html
        $rsa = new Crypt_RSA();

        $rsa->setPrivateKeyFormat(CRYPT_RSA_PRIVATE_FORMAT_PKCS1);
        $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_PKCS1);

        //$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);    
        //define('CRYPT_RSA_EXPONENT', 65537);
        //define('CRYPT_RSA_SMALLEST_PRIME', 64); // makes it so multi-prime RSA is used
        define('CRYPT_RSA_EXPONENT', 65537); 
        extract($rsa->createKey(2048)); // == $rsa->createKey(1024) where 1024 is the key size
        $SSPKeyPath = SSPConfManager::getCertDirPath();

        $privatekeyTmp = $privatekey; //see extract() php doc to learn how this var is created
        $publickeyTmp = $publickey; //see extract() php doc to learn how this var is created

        file_put_contents($SSPKeyPath . "saml.key.tmp", $privatekeyTmp);
        file_put_contents($SSPKeyPath . "saml.crt.tmp", $publickeyTmp);

        $privtest = file_get_contents($SSPKeyPath . "saml.key.tmp");
        $pubtest = file_get_contents($SSPKeyPath . "saml.crt.tmp");
        //see: http://php.net/manual/en/function.openssl-pkey-new.php and http://phpseclib.sourceforge.net/

        $rsa->loadKey($privtest);

        $privatekeyTest = $rsa->getPrivateKey();
        $publickeyTest = $rsa->getPublicKey();

        if ($publickeyTest != $publickeyTmp || $pubtest != $publickeyTmp) {
            // Add a message to the message queue
            //$app->enqueueMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), 'error');
            SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_ERROR_PUBKEY_GENTEST'), SAMLoginControllerAjax::$AJAX_MESSAGE_DANGER);
            unlink($SSPKeyPath . "saml.key.tmp");
            unlink($SSPKeyPath . "saml.crt.tmp");
            return false;
        }

        $privBackup = file_get_contents($SSPKeyPath . "saml.key");
        $pubBackup = file_get_contents($SSPKeyPath . "saml.crt");
        $datetimestring = date('j_M_y_H_i_s', time());
        file_put_contents($SSPKeyPath . "saml.backup_until_$datetimestring.key", $privBackup);
        file_put_contents($SSPKeyPath . "saml.backup_until_$datetimestring.crt", $pubBackup);


        file_put_contents($SSPKeyPath . "saml.key", $privatekeyTest);
        file_put_contents($SSPKeyPath . "saml.crt", $publickeyTest);

        //  $app->enqueueMessage(JText::_('SAMLOGIN_GENKEY_OK'));
        SAMLoginControllerAjax::enqueueAjaxMessage(JText::_('SAMLOGIN_GENKEY_OK'), SAMLoginControllerAjax::$AJAX_MESSAGE_SUCCSS);

        unlink($SSPKeyPath . "saml.key.tmp");
        unlink($SSPKeyPath . "saml.crt.tmp");
        return true;
    }

}

?>
