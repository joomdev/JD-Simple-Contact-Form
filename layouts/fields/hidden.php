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
$attrs = [];
?>
<input type="hidden" name="jdscf[<?php echo $field->name; ?>][<?php echo $field->type; ?>]" value="<?php echo $field->value; ?>" <?php echo implode(' ', $attrs); ?> />