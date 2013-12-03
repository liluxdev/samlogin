<?php
die("for development only");
$arr=  json_decode('["feide","edugain","kalmar","switch","arnes","arnes-test","uk","incommon","edugate","surfnet","surfnet2","surfnet-uwap","surfnet-foodl","rctsaai-test","rctsaai","garr","garr-test","rediris","gakunin","aconet","aaf","caf","carsi","cesnet","dfn","renater","grnet","niif","laife","swamid","sweden-eid","skolfederation","skolfederation2","haka","poland","redclara","gridp"]',true);
print_r($arr);
echo "view souce to view options";
sort($arr);
foreach($arr as $i){
    echo '<option value="'.$i.'">'.$i.'</option>';
}
?>