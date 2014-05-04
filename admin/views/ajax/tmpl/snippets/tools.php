<div class='uk-grid'>
    <!-- uk-tab-left uk-width-medium-1-3  -->
    <ul   class="toolTabs settingsFieldsetTabs uk-tab uk-tab-left uk-width-medium-1-4  " 
          data-uk-tab="{connect:'#tab-tools-content'}">         

        <li class="uk-active"><a href="#tools_keygen"><i class='uk-icon-certificate'></i> Certificate Generator</a></li>
        <li class=""><a href="#tools_keyupload"><i class='uk-icon-upload'></i> Custom Certificate Upload</a></li>


    </ul>
    <div  class='uk-width-medium-3-4'> 
        <ul id="tab-tools-content" style='width: 500px;' class="uk-switcher uk-margin ">
            <li class="tools_keygen">


                <div class="uk-form-row">
                    <label>
                        Generate new self-signed keypair and rollover old one
                    </label>
                    <button class="uk-button uk-button-danger" onClick="samlogin_regenkeys();">
                        Generate & Rollover
                    </button>
                </div>

                <div class="uk-form-row">
                    <label>
                        Stop key rollover period
                    </label>
                    <button class="uk-button uk-button-primary" onClick="samlogin_keyRotateEndPeriod();">Stop</button>

                </div>


            </li>

            <li class="tools_keyupload">
                <p><em>Please ensure you are browsing your Joomla admin via a secure HTTPS while uploading the private key. 
                       Also please use a modern and up-to-date browser to do this operation as there's some javascript keypair validation
                       that may fail in old borwsers</em></p>
                <table>
                    <tr style="vertical-align: top;">
                        <td style="margin-right: 5px;">
                            <fieldset>
                                <legend>Private key </legend>
                                <em>Usually is a file with <code>.key</code> extension. Supported encodings: (DER,PEM)</em>
                                <div class="uk-form-row">
                                    <label for="privkey_file">Private key file</label>
                                    <input onchange="keyfileRead(this.id, 'priv')" type="file" name="privkey_file" id="privkey_file"></input>
                                </div>
                                <!--   <div class="uk-form-row">
                                       <label for="privkey_enc">Private key encoding</label>
                                       <select name="privkey_enc" id="privkey_enc">
                                           <option selected value="pem">PEM</option>
                                           <option  value="der">DER</option>
                                       </select>
                                   </div> -->
                                <div class="uk-form-row privkeyInspect uk-hidden">
                                    <button class="uk-button" data-uk-toggle="{target:'#privkey_content'}"><i class='uk-icon-eye'></i> Private key</button>
                                    <pre class="uk-hidden" style="width: 90%; font-size: 80%;" name="privkey_content" id="privkey_content"></pre>
                                </div>
                            </fieldset>
                        </td>
                        <td>
                            <fieldset>
                                <legend>Public key</legend>
                                <em>Usually is a file with <code>.crt</code> or <code>.cer</code> extension. Supported encodings: (DER,PEM)</em>
                                <div class="uk-form-row">
                                    <label for="pubkey_file">Public key file</label>
                                    <input  onchange="keyfileRead(this.id, 'pub')" type="file" name="pubkey_file" id="pubkey_file"></input>
                                </div>
                                <!--  <div class="uk-form-row">
                                      <label for="pubkey_enc">Public key encoding</label>
                                      <select name="pubkey_enc" id="pubkey_enc">
                                          <option selected value="pem">PEM</option>
                                          <option  value="der">DER</option>
                                      </select>
                                  </div> -->
                                <div class="uk-form-row pubkeyInspect uk-hidden">
                                    <button class="uk-button" data-uk-toggle="{target:'#pubkey_content'}"><i class='uk-icon-eye'></i> Public key</button>
                                    <pre class="uk-hidden" style="width: 90%; font-size: 80%;" name="pubkey_content" id="pubkey_content"></pre>
                                </div>
                            </fieldset>
                        </td>
                    </tr>
                </table>


                <hr/>
                <div class="uk-form-row">
                    <button class="uk-button uk-button-success uk-hidden keypairCustomSavebutton" onClick="samlogin_uploadCustomKeypair();">
                        Save and start using this keypair in production
                    </button>
                    <hr/>
                    <div class='customKeyCheck' class='uk-button'></div>

                </div>

            </li>

        </ul>
    </div>


</div>
<script>

    function samlogin_uploadCustomKeypair() {
        var pki = customKeyCheckStatus();
        if (pki) {
            if (confirm("Are you sure?")) {
                try {
                    window.clearTimeout(window.samlogin_configTestTimeout);
                } catch (ie) {
                }

                samlogin_showToaster("Uploading custom keypair", "warning");

                jQuery.ajax({
                    url: window.samloginBaseAjaxURL,
                    dataType: "json",
                    method: "POST",
                    data: {
                        priv: pki.priv,
                        pub: pki.pub,
                        task: "uploadkey",
                    }
                }).done(function(data) {
                    samlogin_processMessages(data);
                    setTimeout(function() {
                        samlogin_doConfigTests();
                    }, 1000);
                });
            }
        }
    }

    function chunk_split(body, chunklen, end) {
        //  discuss at: http://phpjs.org/functions/chunk_split/
        // original by: Paulo Freitas
        //    input by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Theriault
        //   example 1: chunk_split('Hello world!', 1, '*');
        //   returns 1: 'H*e*l*l*o* *w*o*r*l*d*!*'
        //   example 2: chunk_split('Hello world!', 10, '*');
        //   returns 2: 'Hello worl*d!*'

        chunklen = parseInt(chunklen, 10) || 76;
        end = end || '\r\n';

        if (chunklen < 1) {
            return false;
        }

        return body.match(new RegExp('.{0,' + chunklen + '}', 'g'))
                .join(end);
    }


    function base64_decode(data) {
        //  discuss at: http://phpjs.org/functions/base64_decode/
        // original by: Tyler Akins (http://rumkin.com)
        // improved by: Thunder.m
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        //    input by: Aman Gupta
        //    input by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Onno Marsman
        // bugfixed by: Pellentesque Malesuada
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        //   example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
        //   returns 1: 'Kevin van Zonneveld'
        //   example 2: base64_decode('YQ===');
        //   returns 2: 'a'

        var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
        var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
                ac = 0,
                dec = '',
                tmp_arr = [];

        if (!data) {
            return data;
        }

        data += '';

        do { // unpack four hexets into three octets using index points in b64
            h1 = b64.indexOf(data.charAt(i++));
            h2 = b64.indexOf(data.charAt(i++));
            h3 = b64.indexOf(data.charAt(i++));
            h4 = b64.indexOf(data.charAt(i++));

            bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

            o1 = bits >> 16 & 0xff;
            o2 = bits >> 8 & 0xff;
            o3 = bits & 0xff;

            if (h3 == 64) {
                tmp_arr[ac++] = String.fromCharCode(o1);
            } else if (h4 == 64) {
                tmp_arr[ac++] = String.fromCharCode(o1, o2);
            } else {
                tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
            }
        } while (i < data.length);

        dec = tmp_arr.join('');

        return dec.replace(/\0+$/, '');
    }

    function base64_encode(data) {
        //  discuss at: http://phpjs.org/functions/base64_encode/
        // original by: Tyler Akins (http://rumkin.com)
        // improved by: Bayron Guevara
        // improved by: Thunder.m
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Rafał Kukawski (http://kukawski.pl)
        // bugfixed by: Pellentesque Malesuada
        //   example 1: base64_encode('Kevin van Zonneveld');
        //   returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
        //   example 2: base64_encode('a');
        //   returns 2: 'YQ=='

        var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
        var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
                ac = 0,
                enc = '',
                tmp_arr = [];

        if (!data) {
            return data;
        }

        do { // pack three octets into four hexets
            o1 = data.charCodeAt(i++);
            o2 = data.charCodeAt(i++);
            o3 = data.charCodeAt(i++);

            bits = o1 << 16 | o2 << 8 | o3;

            h1 = bits >> 18 & 0x3f;
            h2 = bits >> 12 & 0x3f;
            h3 = bits >> 6 & 0x3f;
            h4 = bits & 0x3f;

            // use hexets to index into b64, and append result to encoded string
            tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
        } while (i < data.length);

        enc = tmp_arr.join('');

        var r = data.length % 3;

        return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
    }


    function strpos(haystack, needle, offset) {
        //  discuss at: http://phpjs.org/functions/strpos/
        // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Onno Marsman
        // improved by: Brett Zamir (http://brett-zamir.me)
        // bugfixed by: Daniel Esteban
        //   example 1: strpos('Kevin van Zonneveld', 'e', 5);
        //   returns 1: 14

        var i = (haystack + '')
                .indexOf(needle, (offset || 0));
        return i === -1 ? false : i;
    }

    function strlen(string) {
        //  discuss at: http://phpjs.org/functions/strlen/
        // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Sakimori
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        //    input by: Kirk Strobeck
        // bugfixed by: Onno Marsman
        //  revised by: Brett Zamir (http://brett-zamir.me)
        //        note: May look like overkill, but in order to be truly faithful to handling all Unicode
        //        note: characters and to this function in PHP which does not count the number of bytes
        //        note: but counts the number of characters, something like this is really necessary.
        //   example 1: strlen('Kevin van Zonneveld');
        //   returns 1: 19
        //   example 2: ini_set('unicode.semantics', 'on');
        //   example 2: strlen('A\ud87e\udc04Z');
        //   returns 2: 3

        var str = string + '';
        var i = 0,
                chr = '',
                lgth = 0;

        if (!this.php_js || !this.php_js.ini || !this.php_js.ini['unicode.semantics'] || this.php_js.ini[
                'unicode.semantics'].local_value.toLowerCase() !== 'on') {
            return string.length;
        }

        var getWholeChar = function(str, i) {
            var code = str.charCodeAt(i);
            var next = '',
                    prev = '';
            if (0xD800 <= code && code <= 0xDBFF) { // High surrogate (could change last hex to 0xDB7F to treat high private surrogates as single characters)
                if (str.length <= (i + 1)) {
                    throw 'High surrogate without following low surrogate';
                }
                next = str.charCodeAt(i + 1);
                if (0xDC00 > next || next > 0xDFFF) {
                    throw 'High surrogate without following low surrogate';
                }
                return str.charAt(i) + str.charAt(i + 1);
            } else if (0xDC00 <= code && code <= 0xDFFF) { // Low surrogate
                if (i === 0) {
                    throw 'Low surrogate without preceding high surrogate';
                }
                prev = str.charCodeAt(i - 1);
                if (0xD800 > prev || prev > 0xDBFF) { //(could change last hex to 0xDB7F to treat high private surrogates as single characters)
                    throw 'Low surrogate without preceding high surrogate';
                }
                return false; // We can pass over low surrogates now as the second component in a pair which we have already processed
            }
            return str.charAt(i);
        };

        for (i = 0, lgth = 0; i < str.length; i++) {
            if ((chr = getWholeChar(str, i)) === false) {
                continue;
            } // Adapt this line at the top of any loop, passing in the whole string and the current iteration and returning a variable to represent the individual character; purpose is to treat the first part of a surrogate pair as the whole character and then ignore the second part
            lgth++;
        }
        return lgth;
    }

    function substr(str, start, len) {
        //  discuss at: http://phpjs.org/functions/substr/
        //     version: 909.322
        // original by: Martijn Wieringa
        // bugfixed by: T.Wild
        // improved by: Onno Marsman
        // improved by: Brett Zamir (http://brett-zamir.me)
        //  revised by: Theriault
        //        note: Handles rare Unicode characters if 'unicode.semantics' ini (PHP6) is set to 'on'
        //   example 1: substr('abcdef', 0, -1);
        //   returns 1: 'abcde'
        //   example 2: substr(2, 0, -6);
        //   returns 2: false
        //   example 3: ini_set('unicode.semantics',  'on');
        //   example 3: substr('a\uD801\uDC00', 0, -1);
        //   returns 3: 'a'
        //   example 4: ini_set('unicode.semantics',  'on');
        //   example 4: substr('a\uD801\uDC00', 0, 2);
        //   returns 4: 'a\uD801\uDC00'
        //   example 5: ini_set('unicode.semantics',  'on');
        //   example 5: substr('a\uD801\uDC00', -1, 1);
        //   returns 5: '\uD801\uDC00'
        //   example 6: ini_set('unicode.semantics',  'on');
        //   example 6: substr('a\uD801\uDC00z\uD801\uDC00', -3, 2);
        //   returns 6: '\uD801\uDC00z'
        //   example 7: ini_set('unicode.semantics',  'on');
        //   example 7: substr('a\uD801\uDC00z\uD801\uDC00', -3, -1)
        //   returns 7: '\uD801\uDC00z'

        var i = 0,
                allBMP = true,
                es = 0,
                el = 0,
                se = 0,
                ret = '';
        str += '';
        var end = str.length;

        // BEGIN REDUNDANT
        this.php_js = this.php_js || {};
        this.php_js.ini = this.php_js.ini || {};
        // END REDUNDANT
        switch ((this.php_js.ini['unicode.semantics'] && this.php_js.ini['unicode.semantics'].local_value.toLowerCase())) {
            case 'on':
                // Full-blown Unicode including non-Basic-Multilingual-Plane characters
                // strlen()
                for (i = 0; i < str.length; i++) {
                    if (/[\uD800-\uDBFF]/.test(str.charAt(i)) && /[\uDC00-\uDFFF]/.test(str.charAt(i + 1))) {
                        allBMP = false;
                        break;
                    }
                }

                if (!allBMP) {
                    if (start < 0) {
                        for (i = end - 1, es = (start += end); i >= es; i--) {
                            if (/[\uDC00-\uDFFF]/.test(str.charAt(i)) && /[\uD800-\uDBFF]/.test(str.charAt(i - 1))) {
                                start--;
                                es--;
                            }
                        }
                    } else {
                        var surrogatePairs = /[\uD800-\uDBFF][\uDC00-\uDFFF]/g;
                        while ((surrogatePairs.exec(str)) != null) {
                            var li = surrogatePairs.lastIndex;
                            if (li - 2 < start) {
                                start++;
                            } else {
                                break;
                            }
                        }
                    }

                    if (start >= end || start < 0) {
                        return false;
                    }
                    if (len < 0) {
                        for (i = end - 1, el = (end += len); i >= el; i--) {
                            if (/[\uDC00-\uDFFF]/.test(str.charAt(i)) && /[\uD800-\uDBFF]/.test(str.charAt(i - 1))) {
                                end--;
                                el--;
                            }
                        }
                        if (start > end) {
                            return false;
                        }
                        return str.slice(start, end);
                    } else {
                        se = start + len;
                        for (i = start; i < se; i++) {
                            ret += str.charAt(i);
                            if (/[\uD800-\uDBFF]/.test(str.charAt(i)) && /[\uDC00-\uDFFF]/.test(str.charAt(i + 1))) {
                                se++; // Go one further, since one of the "characters" is part of a surrogate pair
                            }
                        }
                        return ret;
                    }
                    break;
                }
                // Fall-through
            case 'off':
                // assumes there are no non-BMP characters;
                //    if there may be such characters, then it is best to turn it on (critical in true XHTML/XML)
            default:
                if (start < 0) {
                    start += end;
                }
                end = typeof len === 'undefined' ? end : (len < 0 ? len + end : len + start);
                // PHP returns false if start does not fall within the string.
                // PHP returns false if the calculated end comes before the calculated start.
                // PHP returns an empty string if start and end are the same.
                // Otherwise, PHP returns the portion of the string from start to end.
                return start >= str.length || start < 0 || start > end ? !1 : str.slice(start, end);
        }
        return undefined; // Please Netbeans
    }

    function der2pem_crt(der_data) {

        var pem = chunk_split(base64_encode(der_data), 64, "\n");
        pem = "-----BEGIN CERTIFICATE-----\n" + pem + "-----END CERTIFICATE-----\n";
        return pem;
    }

    function der2pem_key(der_data) {

        var pem = chunk_split(base64_encode(der_data), 64, "\n");
        pem = "-----BEGIN RSA PRIVATE KEY-----\n" + pem + "-----END RSA PRIVATE KEY-----\n";
        return pem;
    }

    function pem2der_key(pem_data) {
        var begin = "PRIVATE KEY-----";
        var end = "-----END";
        //This javascript code removes all 3 types of line breaks
        pem_data = pem_data.replace(/(\r\n|\n|\r)/gm, "");
        pem_data = substr(pem_data, strpos(pem_data, begin) + strlen(begin));
        pem_data = substr(pem_data, 0, strpos(pem_data, end));
        var der = base64_decode(pem_data.trim());
        // return pem_data.trim();
        return der;
    }

    function pem2der_crt(pem_data) {
        var begin = "CERTIFICATE-----";
        var end = "-----END";

        pem_data = pem_data.replace(/(\r\n|\n|\r)/gm, "").trim();
        pem_data = substr(pem_data, strpos(pem_data, begin) + strlen(begin));
        pem_data = substr(pem_data, 0, strpos(pem_data, end));
        // alert(pem_data);
        var der = base64_decode(pem_data);
        return der;
    }


    function keyfileRead(fileinputid, type) {
        // obtain input element through DOM 
        var file = document.getElementById(fileinputid).files[0];

        if (file) {
            keyfileGetAsText(file, type);
        }
    }

    function keyfileGetAsText(readFile, type) {

        var reader = new FileReader();

        // Read file into memory as UTF-16      
        //  reader.readAsText(readFile, "UTF-8");
        reader.readAsBinaryString(readFile);
        // Handle progress, success, and errors
        reader.onprogress = keyfileUpdateProgress;
        if (type == "pub") {
            reader.onload = keyfileLoadedPub;
        } else {
            if (type == "priv") {
                reader.onload = keyfileLoadedPriv;
            }
        }
        reader.onerror = keyfileErrorHandler;
    }

    function keyfileUpdateProgress(evt) {
        if (evt.lengthComputable) {
            // evt.loaded and evt.total are ProgressEvent properties
            var loaded = (evt.loaded / evt.total);
            if (loaded < 1) {
                // Increase the prog bar length
                // style.width = (loaded * 200) + "px";
                //  document.getElementById('result').textContent = "caricato il " + loaded +"%";
            }
        }
    }

    function keyfileLoadedPriv(evt) {
        // Obtain the read file data    
        document.getElementById('privkey_content').innerHTML = "";
        var fileString = evt.target.result;
        var fileStringOrig = fileString;


        if (fileString.match("BEGIN CERTIFICATE")) {
            jQuery.UIkit.notify("Uhm... that seems a public key, but now we are supposed to load the private one", {status: "danger"});
            customKeyCheckStatus();
            return false;
        }

        if (!fileString.match("BEGIN RSA PRIVATE")) {
            fileString = der2pem_key(fileString);
            jQuery.UIkit.notify("Checking for DER to PEM RSA conversion...", {status: "warning"});
            var fileEncodedCheck = pem2der_key(fileString);
            if (fileEncodedCheck == fileStringOrig) {
                if (!fileStringOrig.match(/	\*H\÷/g)) { //that seems to identify public keys
                    //  if (!fileEncodedCheck.match("*ÜHÜ˜")){
                    jQuery.UIkit.notify("<i class='uk-icon-check'></i> Automatically converted DER to PEM encoded (RSA PRIVATE KEY)", {status: "success"});
                } else {
                    jQuery.UIkit.notify("Uhm... that seems a public key, but now we are supposed to load the private one", {status: "danger"});
                    //      document.getElementById('privkey_content').innerHTML = fileEncodedCheck + ":END \n\n equals? \n\nSTART:"+fileStringOrig+":END";
                    customKeyCheckStatus();
                    return false;
                }
            } else {
                //  document.getElementById('privkey_content').innerHTML = fileEncodedCheck + ":END \n\n equals? \n\nSTART:"+fileStringOrig+":END";
                jQuery.UIkit.notify("Not a valid PEM or DER RSA PRIVATE KEY", {status: "danger"});
                customKeyCheckStatus();
                return false;
            }
        }



        document.getElementById('privkey_content').innerHTML = fileString;
        customKeyCheckStatus();

    }


    function keyfileLoadedPub(evt) {
        // Obtain the read file data   
        document.getElementById('pubkey_content').innerHTML = "";
        var fileString = evt.target.result;
        var fileStringOrig = fileString;

        if (fileString.match("BEGIN RSA PRIVATE")) {
            jQuery.UIkit.notify("Uhm... that seems a private key, but now we are supposed to load the public one", {status: "danger"});
            customKeyCheckStatus();
            return false;
        }


        if (!fileString.match("BEGIN CERTIFICATE")) {
            fileString = der2pem_crt(fileString);
            jQuery.UIkit.notify("Checking for DER to PEM X.509 conversion...", {status: "warning"});
            var fileEncodedCheck = pem2der_crt(fileString);
            if (fileEncodedCheck == fileStringOrig) {
                if (fileStringOrig.match(/	\*H\÷/g)) { //that seems to identify public keys
                    //if (fileEncodedCheck.match("ÜHÜ˜")){
                    jQuery.UIkit.notify("<i class='uk-icon-check'></i> Automatically converted DER to PEM encoded (X.509 CERTIFICATE)", {status: "success"});
                } else {
                    jQuery.UIkit.notify("Uhm... that seems a private key, but now we are supposed to load the private one", {status: "danger"});
                    customKeyCheckStatus();
                    return false;
                }
            } else {
                //  document.getElementById('privkey_content').innerHTML = fileEncodedCheck + ":END \n\n equals? \n\nSTART:"+fileStringOrig+":END";
                jQuery.UIkit.notify("Not a valid PEM or DER x.509 CERTIFICATE or CHAIN", {status: "danger"});
                customKeyCheckStatus();
                return false;
            }
        }


        document.getElementById('pubkey_content').innerHTML = fileString;
        customKeyCheckStatus();
    }


    function customKeyCheckStatus() {
        jQuery(".privkeyInspect").addClass("uk-hidden");
        jQuery(".pubkeyInspect").addClass("uk-hidden");

        var noKey = false;
        jQuery(".keypairCustomSavebutton").addClass("uk-hidden");
        var testString = "ciao";

        var privkey = jQuery('#privkey_content').html();
        if (privkey.trim() == "") {
            jQuery(".customKeyCheck").addClass("uk-button-danger")
                    .removeClass("uk-button-success")
                    .html("<i class='uk-icon-warning'></i> Missing private key");
            noKey = true;
        } else {
            jQuery(".privkeyInspect").removeClass("uk-hidden");
        }

        var pubkey = jQuery('#pubkey_content').html();
        if (pubkey.trim() == "") {
            jQuery(".customKeyCheck").addClass("uk-button-danger")
                    .removeClass("uk-button-success")
                    .html("<i class='uk-icon-warning'></i> Missing public key");
            noKey = true;
        } else {
            jQuery(".pubkeyInspect").removeClass("uk-hidden");
        }


        if (noKey) {
            return false;
        }


        var rsa = new RSAKey();
        rsa.readPrivateKeyFromPEMString(privkey);
        var hashAlg = "sha1";
        var hSig = rsa.signString(testString, hashAlg);
        var hSignPrintable = linebrk(hSig, 64);

        // function doVerify() {
        var sMsg = testString;
        var hSig = hSignPrintable;

        var x509 = new X509();
        x509.readCertPEM(pubkey);
        var isValid = x509.subjectPublicKeyRSA.verifyString(sMsg, hSig);

        var certInfo = "Certificate info<br/>";
        var certInfo = certInfo + "<br/>Issuer: " + x509.getIssuerString();
        var certInfo = certInfo + "<br/>Subject: " + x509.getSubjectString();
        // display verification result
        if (isValid) {
            jQuery(".keypairCustomSavebutton").removeClass("uk-hidden");
            jQuery(".customKeyCheck").removeClass("uk-button-danger").addClass("uk-button-success")
                    .html("<i class='uk-icon-check'></i> Signature test passed, we ready to save and start using this keypair"
                            + " <pre>" + certInfo + "</pre>");
            return {priv: privkey, pub: pubkey};

        } else {

            jQuery(".customKeyCheck").addClass("uk-button-danger").removeClass("uk-button-success")
                    .html("<i class='uk-icon-warning'></i> Signature test failed, maybe you coupled a wrong keypair"
                            + " <pre>" + certInfo + "</pre>");
            return false;

        }

    }

    function old_customKeyCheckStatus() {
        // Encrypt with the public key...
        var testString = "ciao";
        var encrypt = new JSEncrypt();
        var pubkey = jQuery('#pubkey_content').html();
        if (pubkey.trim() == "") {
            jQuery(".customKeyCheck").addClass("uk-button-danger")
                    .removeClass("uk-button-success")
                    .html("<i class='uk-icon-warning'></i> Missing public key");
            return false;
        }
        encrypt.setPublicKey(pubkey);
        var encrypted = encrypt.encrypt(testString);

        // Decrypt with the private key...
        var decrypt = new JSEncrypt();
        var privkey = jQuery('#privkey_content').html();
        if (privkey.trim() == "") {
            jQuery(".customKeyCheck").addClass("uk-button-danger")
                    .removeClass("uk-button-success")
                    .html("<i class='uk-icon-warning'></i> Missing private key");
            return false;
        }
        decrypt.setPrivateKey(privkey);
        var uncrypted = decrypt.decrypt(encrypted);

        // Now a simple check to see if the round-trip worked.
        if (uncrypted == testString) {
            jQuery(".customKeyCheck").removeClass("uk-button-danger").addClass("uk-button-success").html("<i class='uk-icon-check'></i> Encryption test passed, ready to save");
        }
        else {
            alert(uncrypted);
            jQuery(".customKeyCheck").addClass("uk-button-danger").removeClass("uk-button-success").html("<i class='uk-icon-warning'></i> Encryption test failed, maybe you coupled a wrong keypair");

        }

    }

    function keyfileErrorHandler(evt) {
        jQuery.UIkit.notify(evt.target.error.name, {status: "danger"});
    }
</script>