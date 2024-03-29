<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

$this->css()
     ->js('curation.js');

Toolbar::title(Lang::txt('COM_PUBLICATIONS_PUBLICATION') . ' ' . Lang::txt('COM_PUBLICATIONS_MASTER_TYPE') . ' - ' . $this->row->type . ': ' . Lang::txt('COM_PUBLICATIONS_EDIT_BLOCK_ELEMENTS'), 'publications');
Toolbar::save('saveelements');
Toolbar::cancel();

$params = new \Hubzero\Config\Registry($this->row->params);
$manifest  = $this->curation->_manifest;
$curParams = $manifest->params;
$blocks    = $manifest->blocks;
$blockId   = $this->blockId;
$block     = $blocks->$blockId;
?>

<p class="backto"><a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $this->row->id); ?>"><?php echo Lang::txt('COM_PUBLICATIONS_MTYPE_BACK') . ' ' . $this->row->type . ' ' . Lang::txt('COM_PUBLICATIONS_MASTER_TYPE'); ?></a></p>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" id="item-form" name="adminForm">
	<fieldset class="adminform">
		<legend><span><?php echo Lang::txt('COM_PUBLICATIONS_EDIT_BLOCK_ELEMENTS'); ?></span></legend>

		<input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
		<input type="hidden" name="bid" value="<?php echo $this->blockId; ?>" />
		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
		<input type="hidden" name="task" value="saveelements" />

		<p class="warning"><?php echo Lang::txt('COM_PUBLICATIONS_EDIT_BLOCK_ELEMENTS_WARNING'); ?></p>

		<?php foreach ($block->elements as $elementId => $element) { ?>
			<fieldset class="adminform">
				<legend><span class="block-id"><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_ID') . ': ' . $elementId; ?> - <?php echo $element->name; ?> - <?php echo $element->name == 'metadata' ? $element->params->input : $element->params->type; ?></span></legend>
				<div class="input-wrap">
					<label for="field-el-<?php echo $elementId; ?>-label"><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_LABEL'); ?>:</label>
					<input type="text" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][label]" id="field-el-<?php echo $elementId; ?>-label" maxlength="255" value="<?php echo $element->label;  ?>" />
				</div>
				<div class="input-wrap">
					<label for="field-el-<?php echo $elementId; ?>-about"><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_ELEMENT_ABOUT'); ?>:</label>
					<textarea name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][about]" id="field-el-<?php echo $elementId; ?>-about"><?php echo htmlspecialchars($element->about); ?></textarea>
				</div>
				<div class="input-wrap">
					<label for="field-el-<?php echo $elementId; ?>-adminTips"><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_ELEMENT_ADMIN_TIPS'); ?>:</label>
					<textarea name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][adminTips]" id="field-el-<?php echo $elementId; ?>-adminTips"><?php echo htmlspecialchars($element->adminTips); ?></textarea>
				</div>

				<?php foreach ($element->params as $paramname => $paramvalue) { ?>
				<div class="input-wrap">
					<label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_PARAMS_' . strtoupper($paramname)); ?></label>
					<?php
						if ($element->type == 'attachment' && $paramname == 'type') {
						?>
						<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>]">
							<option value="file" <?php echo $paramvalue == 'file' ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_TYPE_FILE'); ?></option>
							<option value="link" <?php echo $paramvalue == 'link' ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_TYPE_LINK'); ?></option>
							<option value="data" <?php echo $paramvalue == 'data' ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_TYPE_DATA'); ?></option>
							<option value="publication" <?php echo $paramvalue == 'publication' ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_TYPE_PUBLICATION'); ?></option>
						</select>
						<?php }
						elseif ($paramname == 'required') {
						?>
						<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>]">
							<option value="1" <?php echo $paramvalue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JYES'); ?></option>
							<option value="0" <?php echo $paramvalue == 0 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JNO'); ?></option>
						</select>
						<?php }
						elseif ($element->type == 'attachment' && $paramname == 'role')
						{ ?>
						<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>]">
							<option value="1" <?php echo $paramvalue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_ROLE_PRIMARY'); ?></option>
							<option value="2" <?php echo ($paramvalue == 2 || $paramvalue == 0) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_ROLE_SUPPORTING'); ?></option>
							<option value="3" <?php echo $paramvalue == 3 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_ROLE_GALLERY'); ?></option>
						</select>
						<?php }
						elseif ($paramname == 'typeParams') {
							foreach ($paramvalue as $tpName => $tpValue) { ?>
							<div class="input-wrap">
								<?php if ($tpName !== 'view') { ?>
								<label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_PARAMS_' . strtoupper($tpName)); ?></label>
								<?php } ?>
							<?php
								if ($tpName == 'handler') { ?>
								<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
									<option value="" <?php echo !$tpValue ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JNONE'); ?></option>
									<option value="imageviewer" <?php echo $tpValue == 'imageviewer' ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_HANDLER_IMAGE'); ?></option>
								</select>
							<?php }
							elseif ($tpName == 'reuse') {  ?>
							<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
								<option value="1" <?php echo $tpValue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JYES'); ?></option>
								<option value="0" <?php echo ($tpValue == 0) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JNO'); ?></option>
							</select>
						<?php }
							elseif ($tpName == 'dirHierarchy') {  ?>
							<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
								<option value="1" <?php echo $tpValue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_DIRHIERARCHY_PRESERVE'); ?></option>
								<option value="0" <?php echo ($tpValue == 0) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_DIRHIERARCHY_NOT_PRESERVE_APPEND_ID'); ?></option>
								<option value="2" <?php echo ($tpValue == 2) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_DIRHIERARCHY_NOT_PRESERVE_APPEND_NUMBER'); ?></option>
							</select>
						<?php } elseif ($tpName == 'includeInPackage') {  ?>
							<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
								<option value="1" <?php echo $tpValue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JYES'); ?></option>
								<option value="0" <?php echo $tpValue != 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('JNO'); ?></option>
							</select>
						<?php } elseif ($tpName == 'bundleDirHierarchy') {  ?>
							<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
								<option value="1" <?php echo $tpValue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_DIRHIERARCHY_PRESERVE'); ?></option>
								<option value="0" <?php echo ($tpValue == 0) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_DIRHIERARCHY_NOT_PRESERVE_APPEND_NUMBER'); ?></option>
							</select>
						<?php }
							elseif ($tpName == 'multiZip') {  ?>
							<select name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]">
								<option value="1" <?php echo $tpValue == 1 ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_MULTIZIP_ONE'); ?></option>
								<option value="0" <?php echo ($tpValue == 0) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_MULTIZIP_ZERO'); ?></option>
								<option value="2" <?php echo ($tpValue == 2) ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_PUBLICATIONS_CURATION_ELEMENT_PARAMS_MULTIZIP_TWO'); ?></option>
							</select>
						<?php }
							elseif ($tpName == 'view') { ?>
								<input type="hidden" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][view]" value="<?php echo $tpValue;  ?>" />
								<?php
								$view = new \Hubzero\Component\View(array(
									'base_path' => $this->_basePath,
									'name'      => $this->_name,
									'layout'    => '_select' . $tpValue,
								));	
								$view->row = $this->row;
								$view->blockId = $blockId;
								$view->elementId = $elementId;
								echo $view->loadTemplate(); ?>
							<?php }
								elseif (is_array($tpValue)) {
								$tpVal = implode(',', $tpValue); ?>
								<input type="text" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]" value="<?php echo $tpVal;  ?>" />
							<?php	}
							else {
								?>
							<input type="text" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>][<?php echo $tpName; ?>]" value="<?php echo $tpValue;  ?>" />
							<?php } ?>
							</div>
						<?php }
						}
						elseif (is_array($paramvalue)) {
						$val = implode(',', $paramvalue);
					?>
					<input type="text" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>]" value="<?php echo $val;  ?>" />
					<?php } else { ?>
						<input type="<?php echo ($paramname == 'min' || $paramname == 'max') ? 'number' : 'text'; ?>" name="curation[blocks][<?php echo $blockId; ?>][elements][<?php echo $elementId; ?>][params][<?php echo $paramname; ?>]" value="<?php echo $paramvalue;  ?>" <?php if ($paramname == 'min' || $paramname == 'max') { echo ' min="0" '; } ?> />
					<?php } ?>
				</div>
				<?php } ?>
			</fieldset>
		<?php } ?>
	</fieldset>
	<?php echo Html::input('token'); ?>
</form>