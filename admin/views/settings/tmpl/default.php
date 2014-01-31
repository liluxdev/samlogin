<?php

defined('_JEXEC') or die;
?>
<form action="index.php" method="post" name="adminForm">
  <fieldset>
  	<div style="float:right;">
  	        <button onclick="submitbutton('save');window.top.setTimeout('window.parent.location.reload();window.parent.document.getElementById(\'sbox-window\').close();', 700);" type="button"><?php echo JText::_('Save'); ?></button>
  		<button onclick="window.parent.document.getElementById('sbox-window').close();" type="button"><?php echo JText::_('Cancel'); ?></button>
  	</div>
  </fieldset>
  <?php echo $this->pane->startPane('settings'); ?>
  <?php foreach($this->params->getGroups() as $group=>$value):?>
  	<?php echo $this->pane->startPanel(JText::_($group), $group.'-tab'); ?>
  		<?php echo $this->params->render('params', $group); ?>
  	<?php echo $this->pane->endPanel(); ?>
  <?php endforeach; ?>
  <input type="hidden" name="option" value="com_samlogin" />
  <input type="hidden" name="view" value="settings" />
  <input type="hidden" id="task" name="task" value="" />
  <?php echo JHTML::_('form.token'); ?>
</form>
