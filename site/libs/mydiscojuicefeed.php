<?php
die("TODO: this file is a proof of concept, need some code to produce lat lng from ips");
//see: https://github.com/andreassolberg/DiscoJuice/blob/1656109be46c50b29474f80f9e218c4141d9b9f8/www/feeds.php
//and http://discojuice.org/advanced/ 
// in particular: djc.metadata.push('https://example.org/additional-metadata.js');
//and http://cdn.discojuice.org/feeds/
$rawfile = "../simplesamlphp/metadata/federations/saml20-idp-remote.php";
require($rawfile);
foreach ($metadata as $federation){
    
}

if (!file_exists($feedfile)) {
        throw new Exception('Feed not found');
}

$data = file_get_contents($feedfile);


ob_start("ob_gzhandler");

header("Vary: Content-Language");
header("Last-Modified: ". $last_modified_string);
header("Etag: ". $etag);


$expires = 60*60*24*3; // Cache for three day
header("Pragma: public");



if ($_REQUEST['callback']) {
        if(!preg_match('/^[a-z0-9A-Z\-_]+$/', $_REQUEST['callback'])) throw new Exception('Invalid characters in callback.');

        header('Content-Type: application/javascript; charset=utf-8');
        echo $_REQUEST['callback'] . '(' . $data . ')';
} else {
        header('Content-Type: application/json; charset=utf-8');
        echo $data;
}
?>