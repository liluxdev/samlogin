<?php

// no direct access
defined('_JEXEC') or die ;
?>
<div id="samlogin-dash" style="font-size: 1.2em;">
	<!--<div class="samlogin-logo" >
		<img style="display: block; /* margin: 0 auto;*/" src="<?php echo JURI::base(true); ?>/components/com_samlogin/images/samlogin-logo300.png" alt="SAMLogin" />
	</div>-->
        <h2><?php echo JText::_('SAMLOGIN_FEDERATION_INFO'); ?></h2>
        <div id="samlogin-fedinfo">
            Self XML Metadata url: <a href="<?php echo $this->checks["metadataURL"];?>"><?php echo $this->checks["metadataURL"];?></a>
        </div>
    	<h2><?php echo JText::_('SAMLOGIN_CONFIGURATION_CHECKS'); ?></h2>
	<div class="samlogin-dash-table-cont" id="samlogin-dash-content">
		<table class="samlogin-dash-table">
			<thead>
				<tr>
					<td class="samlogin-col1-head"><span>Check</span></td>
					<td class="samlogin-col2-head"><span>Status</span></td>
                                        <td class="samlogin-col3-head"><span>Action / Help</span></td>
				</tr>
			</thead>
			<tbody>
                            <tr></tr>
                                <tr>
					<td class="samlogin-generic-check">Linked SimpleSAMLphp installation</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['sspCheck'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['sspCheck'] ? "<span style='color:green;'>Version: ".$this->checks['sspCheck']."</span>" : JText::_('SAMLOGIN_TEST_FAIL'). " <a onClick='samlogin_installSSP()' href='#installSSP'>install now</a>";?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['sspCheck'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['sspCheck'] ? JText::_('SAMLOGIN_SSP_REINSTALL') : JText::_('SAMLOGIN_SSP_GUIDELINK'); ?></span></td>
			        </tr>
                                <?php if ($this->checks['sspCheck']){ ?>
                                 <tr>
					<td class="samlogin-generic-check">Key Rotation Status</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['keyrotation_msg'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['keyrotation_msg'];?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['keyrotation_msg'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['keyrotation_msg'] ? JText::_('SAMLOGIN_KEYROTATE_GUIDELINK') : JText::_('SAMLOGIN_KEYROTATE_GUIDELINK'); ?></span></td>
			        </tr>
                                <tr>
					<td class="samlogin-spare-check">SimpleSAML BaseURL Path</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['baseurlpath'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['baseurlpath'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['baseurlpath'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['baseurlpath'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_BASEURLPATH_GUIDELINK'); ?></span></td>
			        </tr>
                                 <tr>
					<td class="samlogin-auth-plugin">Auth Plugin Enabled</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['authPlugin'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['authPlugin'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['authPlugin'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['authPlugin'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_AUTHPLUGIN_GUIDELINK'); ?></span></td>
			        </tr>
				<tr>
					<td class="samlogin-cert-protected">SSP Secret Salt Changed</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['secretsaltChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['secretsaltChanged'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['secretsaltChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['secretsaltChanged'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_SECRETSALT_GUIDELINK'); ?></span></td>
			        </tr>
                                <tr>
					<td class="samlogin-adminpass">SSP AdminPass is disabled or changed from default</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['adminpassChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['adminpassChanged'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['adminpassChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['adminpassChanged'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_ADMINPASS_GUIDELINK'); ?></span></td>
			        </tr>
                                  <tr>
					<td class="samlogin-cert-protected">Site is reachable on HTTPS, and metadata URL is published</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['sslEnabled'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['sslEnabled'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['sslEnabled'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['sslEnabled'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_SSL_GUIDELINK'); ?></span></td>
			        </tr>        
                                <tr>
					<td class="samlogin-cert-changed">SAML endpoint's default certificate was changed</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['privKeyChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['privKeyChanged'] ? JText::_('SAMLOGIN_TEST_PASS')."" : JText::_('SAMLOGIN_TEST_FAIL') ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['privKeyChanged'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['privKeyChanged'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_PRIVATEKEY_GENERATE_GUIDELINK'); ?></span></td>
			        </tr>
                                <tr>
					<td class="samlogin-cert-protected">SAML endpoint's private key is protected form www access</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['privatekey'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['privatekey'];?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['privatekey'] ? 'pass' : 'fail'; ?>"><?php echo !stristr($this->checks['privatekey'],"FAIL") ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_PRIVATEKEY_PROTECT_GUIDELINK'); ?></span></td>
			        </tr>
                                <tr>
					<td class="samlogin-cert-protected">SAML endpoint's private key is protected form www access (https)</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['privatekeySSL'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['privatekeySSL'];?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['privatekeySSL'] ? 'pass' : 'fail'; ?>"><?php echo !stristr($this->checks['privatekeySSL'],"FAIL") ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_PRIVATEKEY_PROTECT_GUIDELINK'); ?></span></td>
			        </tr>
                                 <tr>
					<td class="samlogin-cert-protected">SSP logs are protected form www access</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['logswww'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['logswww'] ? JText::_('SAMLOGIN_TEST_PASS')." (".$this->checks['logswww'].")" : JText::_('SAMLOGIN_TEST_FAIL').$this->checks['logswww']; ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['logswww'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['logswww'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_PRIVATEKEY_PROTECT_GUIDELINK'); ?></span></td>
			        </tr>
                                <tr>
					<td class="samlogin-cert-protected">SSP logs are protected form www access (https)</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['logswwws'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['logswwws'] ? JText::_('SAMLOGIN_TEST_PASS')." (".$this->checks['logswwws'].")"  : JText::_('SAMLOGIN_TEST_FAIL').$this->checks['logswwws']; ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['logswwws'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['logswwws'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_PRIVATEKEY_PROTECT_GUIDELINK'); ?></span></td>
			        </tr>
                                
                                 <tr>
					<td class="samlogin-generic-check">Using Metarefresh</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['metarefresh'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['metarefresh'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['metarefresh'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['metarefresh'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_METAREFRESH_GUIDELINK'); ?></span></td>
			         </tr>
                                 
                                    <tr>
					<td class="samlogin-generic-check">Last metarefresh cronjob update</td>
					<td class="samlogin-checkresult"><span class="<?php echo $this->checks['metarefreshSAML2IdpLastUpdate'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['metarefreshSAML2IdpLastUpdate']; ?></span></td>
                                        <td class="samlogin-checkresult-todo"><span class="<?php echo $this->checks['metarefreshSAML2IdpLastUpdate'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['metarefreshSAML2IdpLastUpdate'] ? JText::_('SAMLOGIN_TEST_ALLDONE') : JText::_('SAMLOGIN_METAREFRESH_GUIDELINK'); ?></span></td>
			         </tr>
                                <?php }?>
			</tbody>
		</table>
	</div>
        
        <br/>
            
        <div id="samlogin-croninfo">
            Cronjob URL: 
            <a target="_blank" href="<?php echo $this->checks["cronLink"];?>">
                <?php echo $this->checks["cronLink"];?></a> <br/>
                Crontab scheduling:<br/><textarea style="font-size:10px; width: 100em; height: 3em;"><?php echo $this->checks["cronSuggestion"];?></textarea> 
            
           
        </div>
        
	<div id="samlogin-system-checks">
		<h2><?php echo JText::_('SAMLOGIN_SYSTEM_CHECKS'); ?></h2>
		<table class="samlogin-system-table">
			<tbody>
				<tr>
					<td class="samlogin-system-label">PHP</td>
					<td class="samlogin-system-value"><?php echo $this->checks['php']; ?></td>
				</tr>
				<tr>
					<td class="samlogin-system-label">PHP cURL</td>
					<td class="samlogin-system-value"><span class="<?php echo $this->checks['curl'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['curl'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
				</tr>
				<tr>
					<td class="samlogin-system-label">PHP mCrypt</td>
					<td class="samlogin-system-value"><span class="<?php echo $this->checks['mcrypt'] ? 'pass' : 'fail'; ?>"><?php echo $this->checks['mcrypt'] ? JText::_('SAMLOGIN_TEST_PASS') : JText::_('SAMLOGIN_TEST_FAIL'); ?></span></td>
				</tr>
			</tbody>
		</table>
	</div>
 
        <div id="ssp-conf-debug">
            <h2><?php echo JText::_('SAMLOGIN_SSP_CONF_DEBUG'); ?></h2>
            <?php //echo $this->checks['sspConfDebug']; ?>
        </div>
        
       
	
	<div id="samlogin-footer">
		<a target="_blank" href="http://creativeprogramming.it/samlogin">SAMLogin</a> | Copyright &copy; 2013-<?php echo date('Y'); ?> <a href="http://www.creativeprogramming.it" target="_blank">creativeprogramming.it.</a>
	</div>
</div>