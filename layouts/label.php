<?php
/**
 * @package   JD Simple Contact Form
 * @author    JoomDev https://www.joomdev.com
 * @copyright Copyright (C) 2009 - 2020 JoomDev.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
// no direct access
defined('_JEXEC') or die;
extract($displayData);
$label = ModJDSimpleContactFormHelper::getLabelText($field);
?>
<label class="d-block">
   <?php echo $label; ?>
   <?php if ($field->required) { ?>
      <small class="text-danger">*</small>
   <?php } ?>
</label>