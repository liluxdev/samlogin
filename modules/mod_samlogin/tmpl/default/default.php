<?php
defined('_JEXEC') or die; 
?>
<div class="modSAMLogin">
	<span class="SAMLoginPre"><?php echo $params->get('preMessage',''); ?></span>
	<div class="SAMLogin">
		<form action="<?php echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSSO', true, $params->get('usesecure')); ?>" method="get">
			<div class="SAMLoginFormCont">
				<button class="button uk-button" type="submit">
					<span>SSO Login</span>
				</button>	
			</div>
                        <input type="hidden" name="return" value="<?php echo base64_encode(JURI::current()); ?>" />
	  		<?php echo JHTML::_('form.token'); ?>
		</form>
	</div>
	
</div>