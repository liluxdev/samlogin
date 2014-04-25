<style type="text/css">
 .j3ClearFixFieldsets .radio input[type="radio"], 
 .j3ClearFixFieldsets .checkbox input[type="checkbox"] {
float: inherit !important;
margin: inherit !important;
}

 .j3ClearFixFieldsets .radio,
 .j3ClearFixFieldsets .checkbox {
	min-height: 18px;
	padding-left: 0px;
}


.j3ClearFixFieldsets label {
/* display: block; */
/* margin-bottom: 5px; */
display: inline-block;
margin: 5px;
}

.j3ClearFixFieldsets input[type="text"],
.j3ClearFixFieldsets textarea,
.j3ClearFixFieldsets .uneditable-input {
	width: 90%;
}

.j3ClearFixFieldsets .chzn-container{
    width: 90% !important;

}

</style>

<div class="j3ClearFixFieldsets">
<div class='uk-grid'>
    <!-- uk-tab-left uk-width-medium-1-3  -->
    <ul   class="settingsFieldsetTabs settingsFieldsetTabs_<?php echo $fromHTMLUniqueIdSuffix; ?> uk-tab uk-tab-left uk-width-medium-1-4  " data-uk-tab="{connect:'#tab-settings-content_<?php echo $fromHTMLUniqueIdSuffix; ?>'}">         
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

                                <?php
                                }
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
</div>