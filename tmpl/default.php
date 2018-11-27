<?php
/**
 * @package   JD Simple Contact Form
 * @author    JoomDev https://www.joomdev.com
 * @copyright Copyright (C) 2009 - 2018 JoomDev.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
// no direct access
defined('_JEXEC') or die;
$title = $params->get('title', '');
$description = $params->get('description', '');
$session = JFactory::getSession();
$message = $session->get('jdscf-message-' . $module->id, '');
$captcha = $params->get('captcha', 0);
?>
<?php
if (!empty($message)) {
   echo '<div class="jd-simple-contact-form">' . $message . '</div>';
   $session->set('jdscf-message-' . $module->id, '');
} else {
   ?>
   <div class="jd-simple-contact-form <?php echo $moduleclass_sfx; ?>">
      <div id="jdscf-message-<?php echo $module->id; ?>"></div>
      <div class="simple-contact-form-loader module-<?php echo $module->id; ?> d-none">
         <div class="loading"></div>
      </div>
      <?php if (!empty($title)) { ?>
         <h5 class="card-title"><?php echo JText::_($title); ?></h5>
      <?php } ?>
      <?php if (!empty($description)) { ?>
         <p class="card-subtitle mb-2 text-muted"><?php echo JText::_($description); ?></p>
      <?php } ?>
      <form method="POST" action="<?php echo JURI::root(); ?>index.php?option=com_ajax&module=jdsimplecontactform&format=json&method=submit" data-parsley-validate data-parsley-errors-wrapper="<ul class='text-danger list-unstyled mt-2 small'></ul>" data-parsley-error-class="border-danger" data-parsley-success-class="border-success" id="simple-contact-form-<?php echo $module->id; ?>">
         <div class="jdscf-row">
            <?php
            ModJDSimpleContactFormHelper::renderForm($params);
            ?>
         </div>

         <?php
         if ($captcha) {
            JPluginHelper::importPlugin('captcha');
            $dispatcher = JEventDispatcher::getInstance();
            $dispatcher->trigger('onInit', 'jdscf_recaptcha_' . $module->id);
            $plugin = JPluginHelper::getPlugin('captcha', 'recaptcha');
            if (!empty($plugin)) {
               $plugin_params = new JRegistry($plugin->params);
               $attributes = [];
               $attributes['data-theme'] = $plugin_params->get('theme2', '');
               $attributes['data-size'] = $plugin_params->get('size', '');
               $attributeArray = [];
               foreach ($attributes as $attributeKey => $attributeValue) {
                  $attributeArray[] = $attributeKey . '="' . $attributeValue . '"';
               }
               ?>
               <div class="jdscf-row">
                  <div class="jdscf-col">
                     <div class="form-group">
                        <div id="jdscf_recaptcha_<?php echo $module->id; ?>" class="g-recaptcha" data-sitekey="<?php echo $plugin_params->get('public_key', ''); ?>" <?php echo implode(' ', $attributeArray); ?>></div>
                     </div>
                  </div>
               </div>
               <?php
            }
         }
         ?>

         <div class="jdscf-row">
            <?php
            $submit = new JLayoutFile('fields.submit', JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
            echo $submit->render(['params' => $params]);
            ?>
         </div>
         <input type="hidden" name="returnurl" value="<?php echo urlencode(JUri::getInstance()); ?>"/>
         <input type="hidden" name="id" value="<?php echo $module->id; ?>" />
         <?php echo JHtml::_('form.token'); ?>
      </form>
   </div>
   <script src="//code.jquery.com/jquery-3.3.1.min.js"></script>
   <script src="//parsleyjs.org/dist/parsley.min.js"></script>
   <script> var jQuery_3_3_1 = $.noConflict(true);</script>
   <?php if ($params->get('ajaxsubmit', 0)) { ?>
      <script>
         (function ($) {
            $(function () {
               var showMessage = function (type, message) {
                  type = type == 'error' ? 'danger' : type;
                  var _alert = '<div class="alert alert-' + type + '"><div>' + message + '</div></div>';
                  $('#jdscf-message-<?php echo $module->id; ?>').html(_alert);
                  setTimeout(function () {
                     $('#jdscf-message-<?php echo $module->id; ?>').html('');
                  }, 3000);
               }
               $('#simple-contact-form-<?php echo $module->id; ?>').on('submit', function (e) {
                  e.preventDefault();
                  var _form = $(this);
                  var _id = 'simple-contact-form-<?php echo $module->id; ?>';
                  var _loading = $('.simple-contact-form-loader.module-<?php echo $module->id; ?>');
                  if (_form.parsley().isValid()) {
                     $.ajax({
                        url: '<?php echo JURI::root(); ?>index.php?option=com_ajax&module=jdsimplecontactform&format=json&method=submitForm',
                        data: $(this).serialize(),
                        type: 'POST',
                        beforeSend: function () {
                           _loading.removeClass('d-none');
                        },
                        success: function (response) {

                           if (response.status == 'success') {
                              $('.jd-simple-contact-form').html(response.data.message);
                              _loading.addClass('d-none');
                              if (response.data.redirect != '') {
                                 setTimeout(function () {
                                    window.location = response.data.redirect;
                                 }, 2000);
                              }
                           } else {
                              _loading.addClass('d-none');
                              showMessage("error", response.message);
                           }
                        },
                        error: function (response) {
                           _loading.addClass('d-none');
                           showMessage("error", response.message);
                        }
                     });
                  }
               });
            });
         })(jQuery_3_3_1);
      </script>
   <?php } ?>
   <?php
}?>