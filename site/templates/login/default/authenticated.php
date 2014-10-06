<?php
defined('_JEXEC') or die;
?>

<div id="samloginLoggedInView">
    <div class="samlogintUserBlock">

        <div class="samloginUserInfo">
            <div class="samloginClearFix">
                <?php if ($this->params->get('showgravatar', 1) == 1 && $this->user->samloginImage): ?>
                    <img class="samloginAvatar" title="Image provided by GrAvatar.com" src="<?php echo $this->user->samloginImage; ?>" alt="<?php echo $this->user->name; ?>" />
                <?php endif; ?>
                <?php if ($this->params->get('showgreeting', 1) == 1) { ?>
                    <span class="samloginGreeting"><?php echo JText::_('SAMLOGIN_WELCOME'); ?></span>
                <?php } ?>
                <span class="samloginUsername"><?php echo $this->user->name; ?></span>
                <br/>
              <!--  <a class="samloginAccountLink" href="<?php echo $this->accountLink; ?>"><?php echo JText::_('SAMLOGIN_MY_ACCOUNT'); ?></a>-->
            </div>
            <?php if ($this->params->get('showauthvia', 1) == 1 && $this->user->samloginIdPName && !empty($this->user->samloginIdPName)) { ?>
                <div id="SAMLoginIdp"><i><?php echo JText::_('SAMLOGIN_AUTH_BY'); ?>:</i>  <?php echo($this->user->samloginIdPName) ?></div>
                <div class="SAMLogut">
                    <form action="<?php echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSLO', true, $this->params->get('usesecure')); ?>" method="get">
                        <div class="SAMLoginFormCont">
                            <button class="button uk-button" type="submit">
                                <span>SSO LogOut</span>
                            </button>	
                        </div>
                        <input type="hidden" name="return" value="<?php echo base64_encode(JURI::current()); ?>" />
                        <?php echo JHTML::_('form.token'); ?>
                    </form>
                </div>
            <?php } else { ?>
                <?php
                $enforceSSL = $this->params->get('usesecure', 0) == 1 ? 1 : 0; //it should be 1
                $formActionURL = JRoute::_('index.php', true, $enforceSSL);
                ?>	

                <form action="<?php echo $formActionURL; ?>" method="post" class="samloginSignoutClassic">
                    <input type="hidden" name="option" value="<?php echo $this->option; ?>" />
                    <input type="hidden" name="task" value="<?php echo $this->task; ?>" />
                    <input type="hidden" name="return" value="<?php echo $this->returnURL; ?>" />
                    <?php echo JHTML::_('form.token'); ?>
                    <button type="submit" class="<?php echo $this->params->get('logoutButtonClasses', "samloginButton btn uk-btn"); ?>">
                        <i></i>
                        <span><?php echo $this->params->get('logoutButtonLabel', JText::_('SAMLOGIN_SIGNOUT_CLASSIC')); ?></span>
                    </button>
                </form>
            <?php } ?>
        </div>




        <?php if (count($this->menu)): ?>
            <ul class="samloginUserMenu">
                <?php if (count($this->menu)): ?>
                    <?php
                    $level = 1;
                    foreach ($this->menu as $key => $link): $level++;
                        ?>
                        <li class="<?php echo $link->class; ?>">
                            <?php if ($link->type == 'url' && $link->browserNav == 0): ?>
                                <a href="<?php echo $link->href; ?>"><?php echo $link->title; ?></a>
                            <?php elseif (strpos($link->link, 'option=com_k2&view=item&layout=itemform') || $link->browserNav == 2): ?>
                                <a class="modal" rel="{handler:'iframe',size:{x:990,y:550}}" href="<?php echo $link->href; ?>"><?php echo $link->title; ?></a>
                            <?php else: ?>
                                <a href="<?php echo $link->href; ?>"<?php if ($link->browserNav == 1) echo ' target="_blank"'; ?>><?php echo $link->title; ?></a>
                            <?php endif; ?>

                                <?php if (isset($this->menu[$key + 1]) && $this->menu[$key]->level < $this->menu[$key + 1]->level): ?>
                                <ul>
                                <?php endif; ?>

                                <?php if (isset($this->menu[$key + 1]) && $this->menu[$key]->level > $this->menu[$key + 1]->level): ?>
                                    <?php echo str_repeat('</li></ul>', $this->menu[$key]->level - $this->menu[$key + 1]->level); ?>
                                <?php endif; ?>

                        <?php if (isset($this->menu[$key + 1]) && $this->menu[$key]->level == $this->menu[$key + 1]->level): ?>
                            </li>
                        <?php endif; ?>

                    <?php endforeach; ?>

            <?php endif; ?>
            </ul>
<?php endif; ?>
    </div>
</div>