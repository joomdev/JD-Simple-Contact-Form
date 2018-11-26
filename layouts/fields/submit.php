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
$buttonText = $params->get('submittext', 'JSUBMIT');
$buttonClass = $params->get('submitclass', 'btn-primary');
?>
<div class="jdscf-col">
   <button type="submit" class="btn<?php echo!empty($buttonClass) ? ' ' . $buttonClass : ''; ?>"><?php echo JText::_($buttonText); ?></button>
</div>