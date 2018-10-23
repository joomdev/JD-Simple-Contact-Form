<?php
/**
 * @package   JD Simple Contact Form
 * @author    JoomDev https://www.joomdev.com
 * @copyright Copyright (C) 2009 - 2018 JoomDev.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
// no direct access
defined('_JEXEC') or die;
extract($displayData);
$attrs = [];
if ($field->required) {
   $attrs[] = 'required';
   $attrs[] = 'data-parsley-required-message="' . JText::sprintf('MOD_JDSCF_REQUIRED_ERROR', strip_tags($label)) . '"';
}
$attrs[] = 'id="' . $field->name . '-file-input"';
?>
<div class="custom-file">
   <input type="file" name="jdscf[<?php echo $field->name; ?>]" class="custom-file-input" <?php echo implode(' ', $attrs); ?>>
   <label class="custom-file-label" for="<?php echo $field->name; ?>-file-input"><?php echo JText::_('MOD_JDSCF_FILE_BTN_LBL'); ?></label>
</div>