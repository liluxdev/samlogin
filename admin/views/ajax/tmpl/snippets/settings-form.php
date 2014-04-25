<form class="uk-form uk-form-horizontal samloginParamForm samloginParamForm_<?php echo $fromHTMLUniqueIdSuffix; ?>" style="background: white;" action="index.php" method="post" id="paramsForm_<?php echo $fromHTMLUniqueIdSuffix; ?>">
    <?php if (version_compare(JVERSION, '3.0', 'ge')) { ?>

        <?php
        //see also com_config view and template
        $app = JFactory::getApplication();
        $template = $app->getTemplate();

// Load the tooltip behavior.
        JHtml::_('behavior.tooltip');
        JHtml::_('behavior.formvalidation');
        JHtml::_('formbehavior.chosen', 'select');
        
        include dirname(__FILE__)."/fieldsets-j3.php"
        
        ?>
        
    <?php } else { ?>
    <?php if (version_compare(JVERSION, '2.5.0', 'ge')): 
    
            include dirname(__FILE__)."/fieldsets-j25.php"
            ?>
           
    <?php else : ?>
            <ul class="settingsFieldsetTabs uk-tab uk-tab-left uk-width-medium-1-3" data-uk-tab="{connect:'#tab-settings-content'}">

                <?php
                $firstClass = "uk-active";
                foreach ($this->fields->_xml as $fieldset):
                    ?>
                    <li class="<?php
                    echo $firstClass;
                    $firstClass = "";
                    ?>"><a href="#settings_<?php echo $fieldset->attributes('group'); ?>"><?php echo JText::_($fieldset->attributes('label')); ?></a></li>


        <?php endforeach; ?>
            </ul>

            <ul id="tab-settings-content" class="uk-switcher uk-margin">
        <?php foreach ($this->fields->_xml as $fieldset): ?>
                    <li class="settings_tab_<?php echo $fieldset->attributes('group'); ?>">

                        <?php if ($fieldset->attributes('description')) : ?>
                            <p><?php echo JText::_($fieldset->attributes('description')); ?></p>
            <?php endif; ?>

                        <div class="<?php echo $fieldset->attributes('group'); ?>Fieldset">
            <?php echo $this->fields->render('params', $fieldset->attributes('group')); ?>
                        </div>

                        <div class="clr"></div>
                    </li>
        <?php endforeach; ?>
            </ul>


        <?php
        endif;
    }
    ?>
</form>