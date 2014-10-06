<?php

defined('_JEXEC') or die;
?>

<div id="samloginLoggedInView">
    <div class="samlogintUserBlock">

        <div class="samloginUserInfo">
            <div class="samloginClearFix">
                <?php if ($this->user->samloginImage): ?>
                    <img class="samloginAvatar" title="Image provided by GrAvatar.com" src="<?php echo $this->user->samloginImage; ?>" alt="<?php echo $this->user->name; ?>" />
                <?php endif; ?>
                <span class="samloginGreeting"><?php echo JText::_('SAMLOGIN_WELCOME'); ?></span>
                <span class="samloginUsername"><?php echo $this->user->name; ?></span>
                <br/>
         <!--       <a class="samloginAccountLink" href="<?php echo $this->accountLink; ?>"><?php echo JText::_('SAMLOGIN_MY_ACCOUNT'); ?></a> -->
            </div>
            <?php if ($this->user->samloginIdPName) { ?>
            <div id="SAMLoginIdp"><i><?php echo JText::_('SAMLOGIN_AUTH_BY');?>:</i>  <?php echo($this->user->samloginIdPName) ?></div>
                <div class="SAMLogut">
                    <form action="<?php echo JRoute::_('index.php?option=com_samlogin&view=login&task=initSLO', true); ?>" method="get">
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

                <form action="<?php echo JRoute::_('index.php'); ?>" method="post" class="samloginSignoutClassic">
                    <input type="hidden" name="option" value="<?php echo $this->option; ?>" />
                    <input type="hidden" name="task" value="<?php echo $this->task; ?>" />
                    <input type="hidden" name="return" value="<?php echo $this->returnURL; ?>" />
                    <?php echo JHTML::_('form.token'); ?>
                    <button type="submit" class="samloginButton btn uk-btn">
                        <i></i>
                        <span><?php echo JText::_('SAMLOGIN_SIGNOUT_CLASSIC'); ?></span>
                    </button>
                </form>
            <?php } ?>
        </div>




        <?php if (count($this->K2Menu) || count($this->menu)): ?>
            <ul class="samloginUserMenu">

                <?php if (count($this->K2Menu)): ?>
                    <li>
                        <a class="samloginUserLink" href="<?php echo $this->K2Menu['user']; ?>"><?php echo JText::_('SAMLOGIN__MY_PAGE'); ?></a>
                    </li>
                    <?php if (isset($this->K2Menu['add'])): ?>
                        <li>
                            <a class="modal samloginAddLink" rel="{handler:'iframe',size:{x:990,y:550}}" href="<?php echo $this->K2Menu['add']; ?>"><?php echo JText::_('SAMLOGIN__ADD_NEW_ITEM'); ?></a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a class="samloginCommentsLink modal" rel="{handler:'iframe',size:{x:990,y:550}}" href="<?php echo $this->K2Menu['comments']; ?>"><?php echo JText::_('SAMLOGIN__MY_COMMENTS'); ?></a>
                    </li>
                <?php endif; ?>

                <?php if (count($this->menu)): ?>
                    <?php $level = 1;
                    foreach ($this->menu as $key => $link): $level++; ?>
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