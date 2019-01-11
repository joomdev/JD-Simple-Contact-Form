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
if (isset($field->placeholder) && !empty($field->placeholder)) {
   $attrs[] = 'placeholder="' . $field->placeholder . '"';
}

if (!empty($field->id)) {
   $attrs[] = 'id="' . $field->id . '"';
}

if ($field->required) {
   $attrs[] = 'required';
   $attrs[] = 'data-parsley-required-message="' . JText::sprintf('MOD_JDSCF_REQUIRED_ERROR', strip_tags($label)) . '"';
}
?>
<input type="text" name="jdscf[<?php echo $field->name; ?>]" class="form-control" <?php echo implode(' ', $attrs); ?> />

<?php
$js = 'var picker = new Pikaday({'
        . 'field: document.getElementById("' . $field->id . '")';
if (isset($field->calendar_min) && !empty($field->calendar_min)) {
   $js .= ',minDate: new Date("' . $field->calendar_min . '")';
}
if (isset($field->calendar_max) && !empty($field->calendar_max)) {
   $js .= ',maxDate: new Date("' . $field->calendar_max . '")';
}
$js .= '});';
ModJDSimpleContactFormHelper::addJS($js, $module->id);
?>