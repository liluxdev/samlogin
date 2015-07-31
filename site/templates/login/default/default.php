<?php

defined('_JEXEC') or die; ?>

<div id="samloginLoginView" class="samlogin-loginview">

	<?php if ($this->params->get('show_page_heading', 1)) : ?>
	<h1><?php echo $this->escape($this->params->get('page_title')); ?></h1>
	<?php endif; ?>
	
	<?php if($this->params->get('introductionMessage')):?>
	<div class="samloginIntroMessage"><?php echo $this->introductionMessage; ?></div>
	<?php endif; ?>

		<div class="samloginBlock">
	      <?php if ($this->params->get('enable_samlogin', 1)) { ?>   
               <div class="samloginSAMLLoginBlock">
					<h2 class="samloginServicesMessage"><?php echo $this->params->get('ssoLoginTitle',"Federated Login");?></h2>
					<div class="samloginBlock">
					
						<a class="<?php echo $this->params->get('loginButtonClasses',"btn btn-primary uk-button uk-button-primary");?>" href="<?php echo $this->ssoLink; ?>">
							<i></i>
							<span> <?php echo $this->params->get('loginButtonLabel',JText::_('SAMLOGIN_SSO'));?> </span>
						</a>
						
					</div>
	        </div>
              <?php }?>
             <?php if ($this->params->get('enable_fbconnect', 0)) { ?>
            	      <?php if ($this->params->get('enable_samlogin', 1)) { ?>   
            <div class="SamloginOrSpacer"><h4> <?php echo JText::_('SAMLOGIN_OR') ?> </h4></div>
                      <?php }?>
            <div class="samloginFacebookBlock">
                <a class="<?php echo $this->params->get('loginButtonClasses',"btn btn-primary uk-button uk-button-primary");?>" href="<?php echo $this->facebookSSOLink; ?>">
							<i></i>
							<span> <?php echo $this->params->get('facebookLoginButtonLabel',JText::_('SAMLOGIN_FACEBOOK_SSO'));?> </span>
		</a>
            </div>
            <?php }?>
            
	
            <?php if ($this->params->get('showClassic', 1)) : ?>
            
                <div class="SamloginOrSpacer"><h4> <?php echo JText::_('SAMLOGIN_OR') ?> </h4></div>

		<div class="samloginLoginBlock">
			<div class="samloginBlock">
				<h3 class=""><?php echo JText::_('SAMLOGIN_SIGN_IN_PRE')?></h3>
				<!--<h3 class=""><?php echo JText::_('SAMLOGIN_SIGN_IN') ?></h3> -->
				<?php if($this->params->get('signInMessage')):?>
				<div class=""><?php echo $this->signInMessage; ?></div>
				<?php endif; ?>	
				<form action="<?php echo JRoute::_('index.php', true, $this->params->get('usesecure')); ?>" method="post">
					<label class="" for="samloginUsername"><?php echo JText::_('SAMLOGIN_USERNAME') ?></label>
					<div class="samloginRow">
						<input id="samloginUsername"  type="text" name="username" />
						<a class="samloginLink" href="<?php echo $this->remindUsernameLink; ?>"><?php echo JText::_('SAMLOGIN_FORGOT_USERNAME'); ?></a>
					</div>
					<label class="samloginRow" for="samloginPassword"><?php echo JText::_('SAMLOGIN_PASSWORD') ?></label>
					<div class="samloginRow">
						<input id="samloginPassword" type="password" name="<?php echo $this->passwordFieldName; ?>" />
						<a class="samloginLink" href="<?php echo $this->resetPasswordLink; ?>"><?php echo JText::_('SAMLOGIN_FORGOT_PASSWORD'); ?></a>
					</div>
                                        <div class="samloginClassicLoginBttBlock">
					<button class="uk-button btn samloginUserpassLoginButton" type="submit">
						<i></i>
						<span><?php echo JText::_('SAMLOGIN_USERPASS_LOGIN_BUTTON') ?></span>
					</button>
                                        </div>
					<?php if($this->rememberMe): ?>
					<div class="samloginRememberBlock">
						<label  for="samloginRemember"><?php echo JText::_('SAMLOGIN_REMEMBER') ?></label>
						<input id="samloginRemember" type="checkbox" name="remember" value="yes" />
					</div>
					<?php endif; ?>
					<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
					<input type="hidden" name="task" value="<?php echo $this->task; ?>" />
					<input type="hidden" name="return" value="<?php echo $this->returnURL; ?>" />
					<?php echo JHTML::_('form.token'); ?>
				</form>
				
			
			
		
                                
                <?php if ($this->params->get('allowUserRegistration')) : ?>
		<div class="samloginRegistrationBlock">
			<div class="samloginRegister">
				<h4 class="samloginNMY"><?php echo JText::_('SAMLOGIN_NOT_MEMBER_CAN_SIGN_UP')?></h4>
				<?php if($this->params->get('registrationMessage')):?>
				<div class="samloginRegistrationMessage"><?php echo $this->registrationMessage; ?></div>
				<?php endif; ?>	
				<a class="btn uk-button" href="<?php echo $this->registrationLink; ?>">
					<i></i>
					<span><?php echo JText::_('SAMLOGIN_REGISTER'); ?></span>
				</a>
			</div>
		</div>
		<?php endif; ?>
                                
                <?php endif; ?>
                                
			</div>
		</div>
	</div>
</div>