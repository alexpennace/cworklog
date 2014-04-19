<?php
/**
 *  This file is an admin emailing page used to send mass email to subscribers
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim) 
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License.
 * 
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *  
 *   You should have received a copy of the GNU General Public License
 *   along with this program (gpl.txt).  If not, see <http://www.gnu.org/licenses/>.
 */
   error_reporting(E_ALL);
   ini_set('display_errors', 1);

   require_once(dirname(__FILE__).'/../lib/db.inc.php');
   require_once(dirname(__FILE__).'/../lib/Members.class.php');
   require_once(dirname(__FILE__).'/lib/CWLAdminEmailTemplate.class.php');

   Members::SessionForceLogin();

   $ALLOW_LOGIN_FROM = Site::cfg('admin_email');
   if (empty($ALLOW_LOGIN_FROM) || 
       strtolower($ALLOW_LOGIN_FROM) !== strtolower(Members::LoggedInEmail())){
       header('Location: 404.php');
       die();
   }

   function unstrip_array($array){
      foreach($array as &$val){
         if(is_array($val)){
            $val = unstrip_array($val);
         }else{
            $val = stripslashes($val);
         }
      }
      return $array;
   }
                    
      
    $_GET = unstrip_array($_GET);
    $_POST = unstrip_array($_POST);
    $_REQUEST = unstrip_array($_REQUEST);
  
                   
   $dirs = array_filter(glob(dirname(__FILE__).'/send_email_templates/*'), 'is_dir'); 
   $valid_templates = array();
   foreach($dirs as $dir){
      $pathinfo = pathinfo($dir);
      $valid_templates[$pathinfo['filename']] = array_merge(array('path'=>$dir),pathinfo($dir));
   }
   $chosen_template = null;
   if (isset($_GET['t'])){
      if (empty($valid_templates[$_GET['t']])){
         die('invalid template '.$_GET['t']);
      }
      $chosen_template = isset($_GET['t']) ? $_GET['t'] : '';
      $chosen_template_info = $valid_templates[$chosen_template];
      
      $full_template_dir = dirname(__FILE__)."/send_email_templates/".$chosen_template;
      $dir = $full_template_dir."/attachments/";
      $files = array();
      foreach(glob($dir."*") as $file){ 
         if (!is_dir($file)){
            $files[]=$file;
         }
      }
      $chosen_template_info['attachments'] = $files;
      if (file_exists($full_template_dir.'/from.txt')){
         $chosen_template_info['from'] = file_get_contents($full_template_dir.'/from.txt');
      }
      if (file_exists($full_template_dir.'/subject.txt')){
         $chosen_template_info['subject'] = file_get_contents($full_template_dir.'/subject.txt');
      }  
      if (file_exists($full_template_dir.'/body.txt')){
         $chosen_template_info['body'] = file_get_contents($full_template_dir.'/body.txt');
      }      
   }
   
   
   if ($_SERVER['REQUEST_METHOD'] == 'POST'){
      if (!empty($_POST['to'])){
         $attachments = array();
         if (!empty($_POST['attachments'])){
            foreach($_POST['attachments'] as $atch){
               $fn = $full_template_dir.'/attachments/'.$atch;
               if (file_exists($fn)){
                  $attachments[] = $fn;
               }
            }
         }
         list($mailer, $message, $logger) = cwl_email::setup(false);
         $message->setFrom($_POST['from']);

            if (isset($_REQUEST['to'])){
              if (is_numeric($_REQUEST['to'])){
                $prep = pdo()->prepare('SELECT * FROM user WHERE user.id = :id');
                $prep->execute(array('id'=>$_REQUEST['to']));
                $rows = $prep->fetchAll();
              }else{

                $users = new cwl_user_group($_REQUEST['to']);
                $rows = $users->fetch();

               }
            }else{
              $rows = array();
            }


            foreach($rows as $i => $user_row) {
                try{
                  $message->setSubject($_POST['subject']);

                  $body = $_POST['body'];

                  $template = new CWLAdminEmailTemplate($body);
                  $nwbody = $template->replaceAll($user_row);

                  $message->setBody(strip_tags($nwbody, 'a'));
                  $message->addPart($nwbody, 'text/html');
                   
                  $message->setTo(array($user_row['email']));
              
                  if (!empty($_POST['actually_send_email'])){
                    $mailed = $mailer->send($message);
                    $msg = '';
                  }else{
                    $mailed = false;
                    $msg = 'Uncheck the "Actually Send Emails" box';
                  }
                }catch(Exception $e){
                  $mailed = false;
                  $msg = $e->getMessage();
                }
                //echo $body;
                echo ($i+1).'. ';
                if (!$mailed){
                    $error = 'There was an error '.$msg.' with the email address '.$user_row['email'].', please try again';
                    $error_field = 'to';
                    echo 'Email not sent: '.$error.'<br> body:<br>'.$nwbody.'<br>ENDBODY';
                }else{
                  echo 'Email sent to '.$user_row['email'].' body:<br>'.$nwbody.'<br>ENDBODY';
                }
                echo '<br>';
            }
            exit;
      }
   }    
?>

<!DOCTYPE html>
<html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>Send an Email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <script src="../js/jquery-1.11.0.min.js"></script>
    <script src="../js/autosize-master/jquery.autosize-min.js"></script>
    <script src="../js/ckeditor/ckeditor.js"></script>

    <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <style type="text/css">
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      .form-sendemail {
        max-width: 1000px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-sendemail .form-sendemail-heading,
      .form-sendemail .checkbox {
        margin-bottom: 10px;
      }
      .form-sendemail input[type="text"],
      .form-sendemail input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
      .msg{
         margin: 0 auto 20px;
      }
      #checkuserbox{
        display: inline;
        float: left;
        margin-left: -22px;
        margin-top: 7px;
      }
      .bigdrop.select2-container .select2-results {max-height: 200px;}
      .bigdrop .select2-results {max-height: 200px;}
      .bigdrop .select2-choices {min-height: 150px; max-height: 150px; overflow-y: auto;}
      
    </style>
  </head>

  <body cz-shortcut-listen="true">

    <div class="container">
      <form name="frmSendEmail" class="form-sendemail" method="POST">
      <h5>Choose an Email Template</h5>
      <select name="template" onchange="var uri = window.location.href; uri = updateQueryStringParameter(uri,'t', $(this).val()); window.location.href = uri;">
      <option value="">[None]</option>
      <?PHP
         foreach($valid_templates as $template){
           ?><option <?=$chosen_template == $template['basename'] ? 'selected="selected"' : ''?> value="<?=$template['basename']?>"><?=$template['basename']?></option><?PHP
         }
      ?>
      </select>
               <h4>Send Email</h4>
               <table>
               <tr><td><b>From</b></td><td><input type="text" name="from" class="input-block-level" size="50" placeholder="From" value="<?=!empty($chosen_template_info['from']) ? htmlentities($chosen_template_info['from']) : ''?>" />
               </td></tr><tr>
               <td><b>To</b></td><td>
               <select name="to">
               <option>-- Choose Recipient(s)--</option>
               <option value="GREATERTHAN31">GREATERTHAN31</option>
               <option value="ALL">ALL</option>
               <option value="ZEROSTATUS">ZEROSTATUS</option>
               <?php
                 $prep = pdo()->prepare('SELECT * FROM user ORDER BY id ASC');
                 if ($prep->execute()){
                   while ($row = $prep->fetch(PDO::FETCH_ASSOC)) {
                         ?><option value="<?=$row['id']?>"><?=htmlentities($row['email'])?> (<?=$row['id']?>)</option>
                         <?php
                   }
                 }
               ?>
               </select>
               </tr>
               <tr><td>
               <b>Subject</b></td><td><input type="text" name="subject" class="input-block-level" placeholder="Subject" value="<?=!empty($chosen_template_info['subject']) ? htmlentities($chosen_template_info['subject']) : ''?>" size=100 />
               </td>
               </tr>
               </table>
               <h5>Body</h5>
               <textarea id="txtBody" class="input-block-level ckeditor" cols="15" rows="5" name="body" placeholder="Body"><?=!empty($chosen_template_info['body']) ? htmlentities($chosen_template_info['body']) : ''?></textarea>
          
               <br>
               <h5>Attachments</h5>
               <div>
               <?PHP
                  if (!empty($chosen_template_info['attachments'])){
                     foreach($chosen_template_info['attachments'] as $file){
                        $pi = pathinfo($file);
                       ?><a target=_blank href="send_email_templates/<?=$chosen_template?>/attachments/<?=htmlentities($pi['basename'])?>"><?=htmlentities($pi['basename'])?></a>
                       <input type="hidden" name="attachments[]" value="<?=htmlentities($pi['basename'])?>"/><br><?PHP
                     }
                  }else{
                    ?>[ No Attachments ]<?PHP
                  }
               ?>     
               </div>
 
               <label><input type="checkbox" name="actually_send_email" value="1">Actually send emails</label>
               <br>
               <button class="btn btn-large btn-primary" type="submit">Send Email</button>
      </form>
                       


    </div> <!-- /container -->

    <script>
         function updateQueryStringParameter(uri, key, value) {
           var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
           separator = uri.indexOf('?') !== -1 ? "&" : "?";
           if (uri.match(re)) {
             return uri.replace(re, '$1' + key + "=" + value + '$2');
           }
           else {
             return uri + separator + key + "=" + value;
           }
         }
      $(function(){
      glbSimpleHtmlEmailToolbar = [
            { name: 'document', items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
            { name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
            { name: 'editing', items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
            { name: 'forms', items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 
                 'HiddenField' ] },
            '/',
            { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
            { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv',
            '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
            { name: 'links', items : [ 'Link','Unlink'] },
            { name: 'insert', items : [ 'Image'] },
            '/',
            { name: 'styles', items : [ 'Styles','Format','Font','FontSize' ] },
            { name: 'colors', items : [ 'TextColor','BGColor' ] },
            { name: 'tools', items : [ 'Maximize', 'ShowBlocks' ] }
         ];
 
      CKEDITOR.replace( 'txtBody', {
            toolbar: glbSimpleHtmlEmailToolbar
         });  
                    
     });
    </script>
  

</body></html>