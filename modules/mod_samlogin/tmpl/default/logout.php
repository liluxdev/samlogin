<?php
defined('_JEXEC') or die; ?>

<div class="modSAMLogin">
    <div class="SAMLLoggedin">
        Logged in as <strong>    
        <?php 
         $user=JFactory::getUser();
         echo $user->name;// print_r($user,true);
        ?>
        </strong>
    </div>
	<div class="SAMLogoutPre"><?php echo $params->get('preLogoutMessage'); ?></div>


	<div class="SAMLogut">
		<form action="<?php echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSLO', true, $params->get('usesecure')); ?>" method="get">
			<div class="SAMLoginFormCont">
				<button class="button uk-button" type="submit">
					<span>SSO LogOut</span>
				</button>	
			</div>
                        <input type="hidden" name="return" value="<?php echo base64_encode(JURI::current()); ?>" />
	  		<?php echo JHTML::_('form.token'); ?>
		</form>
	</div>
	
</div>