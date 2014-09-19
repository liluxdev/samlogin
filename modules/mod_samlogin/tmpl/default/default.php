<?php
defined('_JEXEC') or die; 
?>
<div class="modSAMLogin">
	<span class="SAMLoginPre"><?php echo $params->get('preMessage',''); ?></span>
	<div class="SAMLogin">
            <a  class="<?php echo $params->get("loginButtonClasses",'btn btn-success');?>" 
                href="<?php echo $ssoLink;?>" 
            >
		<?php echo $params->get('loginButtonLabel','SSO Login'); ?>
            </a>
	</div>
	
</div>