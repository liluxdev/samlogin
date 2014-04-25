<div class='uk-grid'>
    <!-- uk-tab-left uk-width-medium-1-3  -->
    <ul   class="settingsFieldsetTabs settingsFieldsetTabs_<?php echo $fromHTMLUniqueIdSuffix; ?> uk-tab uk-tab-left uk-width-medium-1-4  " data-uk-tab="{connect:'#tab-settings-content_<?php echo $fromHTMLUniqueIdSuffix; ?>'}">         
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
    
     <div  class='uk-width-medium-3-4'> 
        <ul id="tab-settings-content_<?php echo $fromHTMLUniqueIdSuffix; ?>" class="uk-switcher uk-margin ">
         
        <?php foreach ($this->fields->_xml as $fieldset): ?>
                    <li class="settings_tab_<?php echo $fieldset->attributes('group'); ?>">
                        <div class="uk-form-row" <?php echo $rel; ?>>
                        <?php if ($fieldset->attributes('description')) : ?>
                            <p><?php echo JText::_($fieldset->attributes('description')); ?></p>
            <?php endif; ?>

                        <div class="<?php echo $fieldset->attributes('group'); ?>Fieldset">
            <?php echo $this->fields->render('params', $fieldset->attributes('group')); ?>
                        </div>

                        </div>
                    </li>
        <?php endforeach; ?>
            </ul>
    </div>
</div>
