<?php
defined('_JEXEC') or die; 
?>
<div class="modSAMLogin">
	<span class="SAMLoginPre"><?php echo $params->get('preMessage',''); ?></span>
	<div class="SAMLogin" style='text-align:center;'>
			<form action="<?php echo $ssoLink; ?>" method="POST"> 
						<button type="submit" class="<?php echo $params->get('loginButtonClasses',"btn btn-primary uk-button uk-button-primary");?>">
							<i></i>
							<span> <?php echo $params->get('loginButtonLabel','SSO Login');?> </span>
						</button>
			</form>
	</div>
	
</div>