<form class="uk-form uk-form-horizontal" style="background: white;" action="index.php" method="post" id="paramsForm_<?php echo $fromHTMLUniqueIdSuffix; ?>">
    <?php if (version_compare(JVERSION, '3.0', 'ge')) { ?>

        <?php
        //see also com_config view and template
        $app = JFactory::getApplication();
        $template = $app->getTemplate();

// Load the tooltip behavior.
        JHtml::_('behavior.tooltip');
        JHtml::_('behavior.formvalidation');
        JHtml::_('formbehavior.chosen', 'select');
        ?>
        <div class='uk-grid'>
            <!-- uk-tab-left uk-width-medium-1-3  -->
            <ul  class="settingsFieldsetTabs settingsFieldsetTabs_<?php echo $fromHTMLUniqueIdSuffix; ?> uk-tab uk-tab-left uk-width-medium-1-4 " data-uk-tab="{connect:'#tab-settings-content_<?php echo $fromHTMLUniqueIdSuffix; ?>'}">         
                <?php
                $firstClass = "uk-active";
                foreach ($this->fields->getFieldsets() as $name => $fieldset):
                    ?>
                    <li class="<?php
                    echo $firstClass;
                    $firstClass = "";
                    ?>"><a href="#settings_<?php echo $name; ?>"><?php echo JText::_($fieldset->label); ?></a></li>


                <?php endforeach; ?>
            </ul>
            <div  class='uk-width-medium-3-4'> 
                <ul id="tab-settings-content_<?php echo $fromHTMLUniqueIdSuffix; ?>" class="uk-switcher uk-margin ">
                    <?php foreach ($this->fields->getFieldsets() as $name => $fieldset): ?>
                        <li class="settings_tab_<?php echo $name; ?>">
                            <?php foreach ($this->fields->getFieldset($name) as $field) : ?>
                                <?php
                                $class = '';
                                $rel = '';
                                if ($showon = $field->getAttribute('showon')) {
                                    JHtml::_('jquery.framework');
                                    JHtml::_('script', 'jui/cms.js', false, true);
                                    $id = $this->fields->getFormControl();
                                    $showon = explode(':', $showon, 2);
                                    $class = ' showon_' . implode(' showon_', explode(',', $showon[1]));
                                    $rel = ' rel="showon_' . $id . '[' . $showon[0] . ']"';
                                }
                                ?>


                                <div class="uk-form-row" <?php echo $rel; ?>>
                                    
                                    <?php if (!$field->hidden && $name != "permissions") : ?>
                                        <?php if ($field->type != "Spacer") {
                                            ?>
                                            <label class="uk-form-label form_<?php echo $field->type; ?>" for="<?php echo $field->name; ?>">
                                                <?php echo $field->label; ?>
                                            </label>
                                            <div class="uk-form-controls">

                                                <?php echo $field->input; ?>

                                            </div>
                                        <?php
                                        } else {
                                            ?>
                                            <!-- is spacer -->
                                            <div data-field-type="<? echo $field->type; ?>" class="samlogin-j-form-spacer"> <?php echo $field->label; ?> </div>

                                        <?php }
                                    endif;
                                    ?>


                                </div>
                                <!--
                                                                <div class="uk-form control-group<?php echo $class; ?>"<?php echo $rel; ?>>
                                <?php if (!$field->hidden && $name != "permissions") : ?>
                                                                                <label class="uk-label" for="<?php echo $field->name; ?>">
                                    <?php echo $field->label; ?>
                                                                                </label>
                                <?php endif; ?>
                                                                    <span class="<?php if ($name != "permissions") : ?>controls<?php endif; ?>">
            <?php echo $field->input; ?>
                                                                    </span>
                                                                </div>
                                -->

                        <?php endforeach; ?>
                        </li>
    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php } else { ?>
    <?php if (version_compare(JVERSION, '2.5.0', 'ge')): ?>
            <!-- uk-tab-left uk-width-medium-1-2 -->
            <ul class="settingsFieldsetTabs uk-tab uk-tab-left uk-width-medium-1-3 " data-uk-tab="{connect:'#tab-settings-content'}">


                <?php
                $firstClass = "uk-active";
                foreach ($this->fields->getFieldsets() as $name => $fieldset):
                    ?>
                    <li class="<?php
                    echo $firstClass;
                    $firstClass = "";
                    ?>"><a href="#settings_<?php echo $name; ?>"><?php echo JText::_($fieldset->label); ?></a></li>


        <?php endforeach; ?>
            </ul>


            <ul id="tab-settings-content" class="uk-switcher uk-margin">
        <?php foreach ($this->fields->getFieldsets() as $name => $fieldset): ?>
                    <li class="settings_tab_<?php echo $name; ?>">

                            <?php foreach ($this->fields->getFieldset($name) as $field): ?>
                            <div class="uk-form field_<?php echo $field->type; ?>">
                                <?php echo $field->label; ?>
                <?php echo $field->input; ?>
                            </div>
                            <div class="clr"></div>
                    <?php endforeach; ?>
                    </li>
            <?php endforeach; ?>
            </ul>
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