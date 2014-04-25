<style type="text/css">
    .j25ClearFixFieldsets fieldset input, 
    .j25ClearFixFieldsets fieldset textarea, 
    .j25ClearFixFieldsets fieldset select, 
    .j25ClearFixFieldsets fieldset img, 
    .j25ClearFixFieldsets fieldset button {
float: inherit !important;
width: auto;
margin: inherit !important;
}

.j25ClearFixFieldsets fieldset{
    border: none !important;
}

element.style {
}
.j25ClearFixFieldsets fieldset label, .j25ClearFixFieldsets fieldset span.faux-label {
float: none;
clear: none;
display: inline-block;
margin: 6px;
}
</style>

<div class="j25ClearFixFieldsets">
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