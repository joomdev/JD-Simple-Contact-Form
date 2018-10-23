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
switch ($field->type) {
   case 'email':
      $attrs[] = 'data-parsley-type="email"';
      break;
   case 'number':
      $attrs[] = 'data-parsley-type="number"';
      break;
   case 'url':
      $attrs[] = 'data-parsley-type="url"';
      break;
}
if ($field->type == 'text' || $field->type == 'number') {
   if (!empty($field->min_length)) {
      $attrs[] = 'data-parsley-minlength="' . $field->min_length . '"';
      $attrs[] = 'data-parsley-minlength-message="' . JText::sprintf('MOD_JDSCF_NUMBER_MIN_LENGTH_ERROR', strip_tags($label), $field->min_length) . '"';
   }
   if (!empty($field->max_length)) {
      $attrs[] = 'data-parsley-maxlength="' . $field->max_length . '"';
      $attrs[] = 'data-parsley-maxlength-message="' . JText::sprintf('MOD_JDSCF_NUMBER_MAX_LENGTH_ERROR', strip_tags($label), $field->max_length) . '"';
   }
   if ($field->type == 'number') {
      if (!empty($field->min)) {
         $attrs[] = 'data-parsley-min="' . $field->min . '"';
         $attrs[] = 'data-parsley-min-message="' . JText::sprintf('MOD_JDSCF_NUMBER_MIN_ERROR', strip_tags($label), $field->min) . '"';
      }
      if (!empty($field->max)) {
         $attrs[] = 'data-parsley-max="' . $field->max . '"';
         $attrs[] = 'data-parsley-max-message="' . JText::sprintf('MOD_JDSCF_NUMBER_MAX_ERROR', strip_tags($label), $field->max) . '"';
      }
   }
}

if ($field->required) {
   $attrs[] = 'required';
   $attrs[] = 'data-parsley-required-message="' . JText::sprintf('MOD_JDSCF_REQUIRED_ERROR', strip_tags($label)) . '"';
}
?>
<input type="text" name="jdscf[<?php echo $field->name; ?>]" class="form-control" <?php echo implode(' ', $attrs); ?> />