<?php
defined('_JEXEC') or die; ?>

<div class="modSAMLogin">
   <?php
   $greeting=$params->get('greeting',1)==1;
   if ($greeting){ ?>
    <div class="SAMLLoggedin">
        Logged in as <strong>    
        <?php 
         $user=JFactory::getUser();
         echo $user->name;// print_r($user,true);
         if (isset($user->LastName)){ //for tunedms
             echo " ".$user->LastName;
         }
        ?>
        </strong>
    </div>
   <?php } ?>
	<div class="SAMLogoutPre"><?php echo $params->get('preLogoutMessage'); ?></div>


	<div  class="SAMLogut">
		<form action="<?php echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSLO', true, $params->get('usesecure')); ?>" method="get">
			<div class="SAMLoginFormCont">
				<button class="button uk-button" type="submit">
					<span><?php echo $params->get('logoutButtonLabel','SSO Logout'); ?></span>
				</button>	
			</div>
                        <input type="hidden" name="return" value="<?php echo $returnURL; ?>" />
	  		<?php echo JHTML::_('form.token'); ?>
		</form>
	</div>
	
</div>