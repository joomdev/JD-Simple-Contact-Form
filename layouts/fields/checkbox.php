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
$options = ModJDSimpleContactFormHelper::getOptions($field->options);
$attrs = [];
if ($field->required) {
   $attrs[] = 'required';
   $attrs[] = 'data-parsley-required-message="' . JText::sprintf('MOD_JDSCF_REQUIRED_ERROR', strip_tags($label)) . '"';
}
?>
<div class="form-check form-check-inline">
   <input class="form-check-input" type="checkbox" name="jdscf[<?php echo $field->name; ?>][]" value="1" id="<?php echo $field->name; ?>" <?php echo implode(' ', $attrs); ?> />
   <label class="form-check-label" for="<?php echo $field->name; ?>">
      <?php echo $label; ?>
   </label>
</div>