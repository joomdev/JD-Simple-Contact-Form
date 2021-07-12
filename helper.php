<?php

/**
 * @package   JD Simple Contact Form
 * @author    JoomDev https://www.joomdev.com
 * @copyright Copyright (C) 2021 Joomdev, Inc. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or Later
 */
// no direct access
defined('_JEXEC') or die;

class ModJDSimpleContactFormHelper {

   public static function renderForm($params, $module) {
      $fields = $params->get('fields', []);
      foreach ($fields as $field) {
         $field->id = \JFilterOutput::stringURLSafe('jdscf-' . $module->id . '-' . $field->name);
         self::renderField($field, $module, $params);
      }
   }

   public static function renderField($field, $module, $params) {
      $label = new JLayoutFile('label', JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
      $field_layout = self::getFieldLayout($field->type);
      $input = new JLayoutFile('fields.' . $field_layout, JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
      $layout = new JLayoutFile('field', JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
      if ($field->type == 'checkbox' || $field->type == 'hidden') {
         $field->show_label = 0;
      }
      echo $layout->render(['field' => $field, 'label' => $label->render(['field' => $field]), 'input' => $input->render(['field' => $field, 'label' => self::getLabelText($field), 'module' => $module, 'params' => $params]), 'module' => $module]);
   }

   public static function getOptions($options) {
      $options = explode("\n", $options);
      $array = [];
      foreach ($options as $option) {
         if (!empty($option)) {
            $array[] = ['text' => $option, 'value' => trim( $option )];
         }
      }
      return $array;
   }

   public static function getLabelText($field) {
      $label = $field->label;
      if (empty($label)) {
         $label = ucfirst($field->name);
      } else {
         $label = JText::_($label);
      }
      return $label;
   }

   public static function getFieldLayout($type) {
      $return = '';
      if (file_exists(JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts/fields/' . $type . '-custom.php')) {
         // For adding custom files
         $return = $type . '-custom';
      } else if (file_exists(JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts/fields/' . $type . '.php')) {
         $return = $type;
      } else {
         $return = 'text';
      }
      return $return;
   }

   public static function submitForm($ajax = false) {
      if (!JSession::checkToken()) {
         throw new \Exception(JText::_("JINVALID_TOKEN"));
      }
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
         throw new \Exception(JText::_('MOD_JDSCF_BAD_REQUEST'), 400);
      }
      $app = JFactory::getApplication();
      $jinput = $app->input->post;

      $jdscf = $jinput->get('jdscf', [], 'ARRAY');
      $id = $jinput->get('id', [], 'INT');
      $params = self::getModuleParams();

      if ($params->get('captcha', 0)) {

         $captchaType = $params->get('captchaPlugins') == "" ? JFactory::getConfig()->get('captcha') : $params->get('captchaPlugins');
         JPluginHelper::importPlugin('captcha', $captchaType);
         $dispatcher = JEventDispatcher::getInstance();

         if ( $captchaType == "recaptcha" ) {
            $check_captcha = $dispatcher->trigger('onCheckAnswer', $jinput->get('recaptcha_response_field'));
            if (!$check_captcha[0]) {
               throw new \Exception(JText::_('Invalid Captcha'), 0);
            }
         } elseif ( $captchaType == "recaptcha_invisible" ) {
            $check_captcha = $dispatcher->trigger('onCheckAnswer', $jinput->get('g-recaptcha-response'));
         } elseif (!empty($captchaType)) {
            $check_captcha = $dispatcher->trigger('onCheckAnswer');
         }
      }

      $labels = [];
      foreach ($params->get('fields', []) as $field) {
         $labels[$field->name] = ['label' => self::getLabelText($field), 'type' => $field->type];
      }

      $singleSendCopyMailAddress = [];
      $values = [];
      foreach ($jdscf as $name => $value) {
         if(is_array($value)) {

            // Type email values
            if(isset($value['email'])) {
               $values[$name] = $value['email'];
               
               //single cc
               if(isset($value['single_cc']) && $value['single_cc'] == 1) {
                  $singleSendCopyMailAddress[] = $value['email'];
               }
            }
			
            // Type text values
            ( isset($value['text'] ) ? $values[$name] = $value['text'] : '');
            
            // Type number values
            ( isset($value['number'] ) ? $values[$name] = $value['number'] : '');

            // Type url values
            ( isset($value['url'] ) ? $values[$name] = $value['url'] : '');

            // Type Hidden Value
            ( isset($value['hidden'] ) ? $values[$name] = $value['hidden'] : '');

         } else {
            $values[$name] = $value;
         }
      }

      $contents = [];
      $attachments = [];
      $errors = [];
      // Get all error messages and add them to $errors variable
      $messages = $app->getMessageQueue();
      if (!empty($messages)) {
         for ($i=0; $i < count($messages); $i++) { 
            $errors[] = $messages[$i]["message"];
         }
      }
      foreach ($labels as $name => $fld) {
         $value = isset($values[$name]) ? $values[$name] : '';

         if ($fld['type'] == 'checkboxes') {
            if ( isset ($_POST['jdscf'][$name]['cbs'] ) ) {
               $value = $_POST['jdscf'][$name]['cbs'];
            }
            
            if (is_array($value)) {
               $value = implode(', ', $value);
            } else {
               $value = $value;
            }
         }        
         if ($fld['type'] == 'checkbox') {
            if (isset($_POST['jdscf'][$name]['cb'])){
               $value = $_POST['jdscf'][$name]['cb'];
            }            
            if (is_array($value)) {
               $value = implode(',', $value);
            } else {
               $value = $value;
            }
            $value = empty($value) ? 'unchecked' : 'checked';
         }

         if ($fld['type'] == 'file') {
            if(isset($_FILES['jdscf']['name'][$name])) {
               $value = $_FILES['jdscf']['name'][$name];
               $uploaded = self::uploadFile($_FILES['jdscf']['name'][$name], $_FILES['jdscf']['tmp_name'][$name]);
               //filetype error
               if(!empty($value)) {
                  if(!$uploaded) {
                     $errors[] = JText::_('MOD_JDSCF_UNSUPPORTED_FILE_ERROR');
                  }
               }               
               if(!empty($uploaded)) {
                  $attachments[] = $uploaded;
               }
            }
         }
         if ($fld['type'] == 'textarea') {
            if ($value) {
               $value = nl2br($value);
            }
         }

         $contents[$name] = [
             "value" => $value,
             "label" => $fld['label'],
             "name" => $name,
         ];
      }

      // Fetches IP Address of Client
      if ( $params->get('ip_info' ) ) {
         if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
         }
         elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
         }
         else {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
         }

         $contents["jdsimple_ip"] = array( 
            "value" => "<a href='http://whois.domaintools.com/$ipAddress'>$ipAddress</a>",  
            "label" => "IP Address", 
            "name" => "ip"
         );
      }

      // Send mail to backend
      $mailParamsBackend = self::getMailParams($params, 'email', $singleSendCopyMailAddress, $contents);
      $send = self::sendMail($mailParamsBackend, $contents, $attachments, $errors, $app);  

      if ($send === true) {
         // Send mail to visitor
         $mailParamsVisitor = self::getMailParams($params, 'singleSendCopyEmail', $singleSendCopyMailAddress, $contents);
         $send = self::sendMail($mailParamsVisitor, $contents, $attachments, $errors, $app);
      }

      if ($send !== true) {
         switch($params->get('ajaxsubmit'))
         {
            case 0: throw new \Exception(JText::_('MOD_JDSCFEMAIL_SEND_ERROR'));
            break;
            case 1: throw new \Exception(json_encode($errors));
            break;
         }         
      }
      $message = $params->get('thankyou_message', '');
      if (empty($message)) {
         $message = JText::_('MOD_JDSCF_THANKYOU_DEFAULT');
      } else {
         $template = $params->get('email_custom', '');
         $message = self::renderVariables($contents, $message);
      }
      $redirect_url = $params->get('redirect_url', '');
      $redirect_url = self::renderVariables($contents, $redirect_url);
      if (!$ajax) {
         $return = !empty($redirect_url) ? $redirect_url : urldecode($jinput->get('returnurl', '', 'RAW'));
         $session = JFactory::getSession();
         if (empty($redirect_url)) {
            $session->set('jdscf-message-' . $id, $message);
         } else {
            $session->set('jdscf-message-' . $id, '');
         }
         $app->redirect($return);
      }
      return ['message' => $message, 'redirect' => $redirect_url, 'errors' => json_encode($errors)];
   }

   public static function renderVariables($variables, $source) {
      foreach ($variables as $content) {
         $value = is_array($content['value']) ? implode(', ', $content['value']) : $content['value'];
         $value = empty($value) ? '' : $value;
         $label = empty($content['label']) ? '' : $content['label'];
         $source = str_replace('{' . $content['name'] . ':label}', $label, $source);
         $source = str_replace('{' . $content['name'] . ':value}', $value, $source);
      }
      return $source;
   }

   /**
    * Checks if string has one or more variables in it
    *
    * @param string $content String to check if it contains one or more variables
    * @return boolean
    */
   private static function hasVariable($content) {
      if (preg_match('/\{.+?:((label)|(value))\}/', $content)) {
         return true;
      }
      return false;
   }

   public static function getModuleParams() {
      $app = JFactory::getApplication();
      $jinput = $app->input->post;
      $id = $jinput->get('id', 0);
      $params = new JRegistry();

      $db = JFactory::getDbo();
      $query = "SELECT * FROM `#__modules` WHERE `id`='$id'";
      $db->setQuery($query);
      $result = $db->loadObject();
      if (!empty($result)) {
         $params->loadString($result->params, 'JSON');
      } else {
         throw new \Exception(JText::_('MOD_JDSCF_MODULE_NOT_FOUND'), 404);
      }
      return $params;
   }

   public static function submitAjax() {
      try {
         self::submitForm();
      } catch (\Exception $e) {
         $app = JFactory::getApplication();
         $params = self::getModuleParams();
         $jinput = $app->input->post;
         $app->enqueueMessage($e->getMessage(), 'error');
         $redirect_url = $params->get('redirect_url', '');
         $return = !empty($redirect_url) ? $redirect_url : urldecode($jinput->get('returnurl', '', 'RAW'));
         $app->redirect($return);
      }
   }

   public static function submitFormAjax() {
      header('Content-Type: application/json');
      header('Access-Control-Allow-Origin: *');
      $return = array();
      try {
         $data = self::submitForm(true);
         $return['status'] = "success";
         $return['code'] = 200;
         $return['data'] = $data;
      } catch (\Exception $e) {
         $return['status'] = "error";
         $return['code'] = $e->getCode();
         $return['message'] = $e->getMessage();
         $return['line'] = $e->getLine();
         $return['file'] = $e->getFile();
      }
      echo \json_encode($return);
      exit;
   }

   public static function addJS($js, $moduleid) {
      if (!isset($GLOBALS['mod_jdscf_js_' . $moduleid])) {
         $GLOBALS['mod_jdscf_js_' . $moduleid] = [];
      }
      $GLOBALS['mod_jdscf_js_' . $moduleid][] = $js;
   }

   public static function getJS($moduleid) {
      if (!isset($GLOBALS['mod_jdscf_js_' . $moduleid])) {
         return [];
      }
      return $GLOBALS['mod_jdscf_js_' . $moduleid];
   }


   /**
    * Is Single CC enabled. For single email field (at bottom)
    *
    * @param \Joomla\Registry\Registry $params Parameters of the module
    * @return boolean
    */
   public static function isSingleCCMail($params) {      
      $singlesendcopy_email = $params->get('single_sendcopy_email', 0);
      $singlesendcopyemail_field = $params->get('singleSendCopyEmail_field', '');      
      if($singlesendcopy_email && $singlesendcopy_email === "1" && !empty($singlesendcopyemail_field)){
         return true;
      } else {
         return false;
      }
   }

   /**
    * Upload a file and return it's full path
    *
    * @param [type] $name
    * @param [type] $src
    * @return string Full path file name
    */
   public static function uploadFile($name, $src) {
      jimport('joomla.filesystem.file');
      jimport('joomla.application.component.helper');

      $fullFileName = JFile::stripExt($name);
      $filetype = JFile::getExt($name);
      $filename = JFile::makeSafe($fullFileName."_".mt_rand(10000000,99999999).".".$filetype);

      $params = JComponentHelper::getParams('com_media');
      $allowable = array_map('trim', explode(',', $params->get('upload_extensions')));

      if ($filetype == '' || $filetype == false || (!in_array($filetype, $allowable) ))
      {
         return false;
      }
      else
      {
         $tmppath = JPATH_SITE . '/tmp';
         if (!file_exists($tmppath.'/jdscf')) {
            mkdir($tmppath.'/jdscf',0777);
         }
         $folder = md5(time().'-'.$filename.rand(0,99999));
         if (!file_exists($tmppath.'/jdscf/'.$folder)) {
            mkdir($tmppath.'/jdscf/'.$folder,0777);
         }
         $dest = $tmppath.'/jdscf/'.$folder.'/'.$filename;

         $return = null;
         if (JFile::upload($src, $dest)) {
            $return = $dest;
         }
         return $return;
      }
   }

   /**
    * Returns the value for a specific field
    *
    * @param string $fieldname Name of the field for which the value should be returned
    * @param array[] $contents Collections of fields and there values
    * @return string
    */
   private static function getContentsValue($fieldname,$contents){
      $contentValue = $contents[$fieldname];
      if (!empty($contentValue)) {
         return $contentValue['value'];
      }
      return '';
   }

   /**
    * Replaces variables with field label/field value, if token is present 
    *
    * @param string $fieldname Name of the field
    * @param \Joomla\Registry\Registry $params Contains the parameters of the module
    * @param array[string] $contents Values filled in to the form
    * @return string
    */
   private static function getRenderedValue($fieldname, $params, $contents){
      $returnValue = $params->get($fieldname);
      if (!empty($returnValue) && self::hasVariable($returnValue)) {
         $returnValue = self::renderVariables($contents, $returnValue);
      }
      return $returnValue;
   }

   /**
    * Get the mail paramaters from the params object
    *
    * @param \Joomla\Registry\Registry $params Contains the parameters of the module
    * @param string $fieldPrefix Prefix with fields of the email
    * @param string[] $singleSendCopyMailAddress Mailaddress to be used to sent confirmation mail to visitor
    * @param array[string] $contents Values filled in to the form
    * @return string[] Parameter values of the mail
    */
   private static function getMailParams($params, $fieldPrefix, $singleSendCopyMailAddress, $contents){
      $mailParams = [];
      $mailParams['template'] = $params->get($fieldPrefix . '_template', '');
      $mailParams['custom'] = $params->get($fieldPrefix . '_custom', '');
      $mailParams['subject'] = $params->get($fieldPrefix . '_subject', '');
      $mailParams['cc'] = $params->get($fieldPrefix . '_cc', '');
      $mailParams['bcc'] = $params->get($fieldPrefix . '_bcc', '');
      $mailParams['title'] = $params->get('title', '');
      $mailParams['from'] = self::getRenderedValue($fieldPrefix . '_from', $params, $contents);
      $mailParams['name'] = self::getRenderedValue($fieldPrefix . '_name', $params, $contents);

      if ($fieldPrefix === 'email') {
         $mailParams['singleSendCopyMailAddress'] = $singleSendCopyMailAddress;
      }

      // For custom visitor mail, we need the mailaddress of the visitor.
      if ($fieldPrefix === 'singleSendCopyEmail') {
         $mailField = $params->get('singleSendCopyEmail_field', '');
         $mailValue = self::getContentsValue($mailField, $contents);
         if (!empty($mailValue)) {
            if (filter_var($mailValue, FILTER_VALIDATE_EMAIL)) {
               $mailParams['to'] = $mailValue;
            }
         }
      } else {
         $mailParams['to'] = $params->get($fieldPrefix . '_to', '');
      }
 
      // Compensate for inconsistent naming. In future maybe update the field name.
      $replyToFieldName = $fieldPrefix === 'email' ? 'reply_to' : $fieldPrefix . '_reply_to';
      $mailParams['reply_to'] = self::getRenderedValue($replyToFieldName, $params, $contents);

      return $mailParams;
      
   }

   /**
    * Send mail with configured content to configured mail address
    *
    * @param string[] $mailParams Parameter values needed for sending the mail
    * @param array $contents
    * @param string[] $attachments
    * @param string[] $errors
    * @param \Joomla\CMS\Application\CMSApplication $app
    * @return boolean
    */
   private static function sendMail($mailParams, $contents, $attachments, $errors, $app) {
      
      if ($mailParams['template'] == 'custom') {
         $html = $mailParams['custom'];
         if ( empty( $html ) ) {
            $layout = new JLayoutFile('emails.default', JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
            $html = $layout->render(['contents' => $contents]);
         } else {
            $html = self::renderVariables($contents, $html);  
         }
      } else {
         $layout = new JLayoutFile('emails.default', JPATH_SITE . '/modules/mod_jdsimplecontactform/layouts');
         $html = $layout->render(['contents' => $contents]);
      }

      // sending mail
      $mailer = JFactory::getMailer();
      $config = JFactory::getConfig();
      $title = $mailParams['title'];
      if (!empty($title)) {
         $title = ' : ' . $title;
      }
      // Sender
      if (!empty($mailParams['from'])) {
         $email_from = $mailParams['from'];
         $email_from = self::renderVariables($contents, $email_from);
         if (!filter_var($email_from, FILTER_VALIDATE_EMAIL)) {
            $email_from = $config->get('mailfrom');
         }
      } else {
         $email_from = $config->get('mailfrom');
      }

      if (!empty($mailParams['name'])) {
         $email_name = $mailParams['name'];
         $email_name = self::renderVariables($contents, $email_name);
         if (empty($email_name)) {
            $email_name = $config->get('fromname');
         }
      } else {
         $email_name = $config->get('fromname');
      }

      $sender = array($email_from, $email_name);
      $mailer->setSender($sender);

      // Subject
      $email_subject = !empty($mailParams['subject']) ? $mailParams['subject'] : JText::_('MOD_JDSCF_DEFAULT_SUBJECT', $title);
      $email_subject = self::renderVariables($contents, $email_subject);
      $mailer->setSubject($email_subject);

      // Recipient
      $recipients = !empty($mailParams['to']) ? $mailParams['to'] : $config->get('mailfrom');
      $recipients = explode(',', $recipients);
      if (!empty($recipients)) {
         $mailer->addRecipient($recipients);
      }

      // Reply-To
      if (!empty($mailParams['reply_to'])) {
         $reply_to = $mailParams['reply_to'];
         $reply_to = self::renderVariables($contents, $reply_to);
         if (!filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
            $reply_to = '';
         }
         $mailer->addReplyTo($reply_to);
      } else {
         $reply_to = '';
      }

      // CC
      $cc = !empty($mailParams['cc']) ? $mailParams['cc'] : '';
      $cc = empty($cc) ? [] : explode(",", $cc);
      if(!empty($mailParams['singleSendCopyMailAddress'])){
         $cc = array_merge($cc, $mailParams['singleSendCopyMailAddress']);
         $cc = array_unique($cc);
      }

      if (!empty($cc)) {
         $mailer->addCc($cc);
      }
      // BCC
      $bcc = !empty($mailParams['bcc']) ? $mailParams['bcc'] : '';
      $bcc = empty($bcc) ? [] : explode(',', $bcc);
      if (!empty($bcc)) {
         $mailer->addBcc($bcc);
      }
      $mailer->isHtml(true);
      $mailer->Encoding = 'base64';
      $mailer->setBody($html);
      foreach($attachments as $attachment){
         $mailer->addAttachment($attachment);
      }
      if(!empty($errors)) {
         $app = JFactory::getApplication();
         $send = false;
         // showing all the validation errors
         foreach ($errors as $error) {
            $app->enqueueMessage(\JText::_($error), 'error');
         }
         return $send;
      }
      else {
          return $mailer->Send();
      }
   }
}
