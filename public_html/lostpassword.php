<?PHP
/**
 *  This file helps manage Membership and logins
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
require_once('lib/Members.class.php');
require_once('lib/misc.inc.php');
require_once('lib/Site.class.php');
require_once('lib/work_log.class.php');

Members::SessionAllowLogin();

$error = false;
$error_field = false;
$lostyourpw_done = false;

if (!empty($_POST)){
   $user_by_username = Members::GetUserByUsername($_POST['username']);
   $user_by_email = Members::GetUserByEmail($_POST['email']);
   
   if ($user_by_username && $user_by_email && $user_by_username['id'] == $user_by_email['id']){
      $exec_ary = array();
      $code = random_string(25);
      $sql = "UPDATE user SET verify_command = 'reset_password', verify_code = :verify_code, verify_param = '' 
              WHERE id = :id LIMIT 1";
      
      $exec_ary['verify_code'] = $code;
      $exec_ary['id'] = $user_by_username['id'];

      $prep = $DBH->prepare($sql);
      $result = $prep->execute($exec_ary);
      if ($result){
           if ($prep->rowCount() == 1){
              if (Site::cfg('use_php_mail')){
                  require_once(__DIR__.'/lib/cwl_email.class.php');

                    list($mailer, $message, $logger) = cwl_email::setup(false);

                    $message->setSubject('Contractor\'s Work Log Reset Password');
                    $message->setBody("Please reset your password by going to the link below:\r\n".
                      Site::cfg('base_url').'/verify.php?resetpwcode='.$code.'&email='.urlencode($_POST['email']), 'text/html');
                    //verify.php?resetpwcode=xYnMUSupKmflhhCcfX53QgCvN&email=jakinsman@gmail.com
                    $message->setTo(array($_POST['email']));
                
                    $result = $mailer->send($message);
                  
                  if ($result){
                    $lostyourpw_done = true;                  
                 }else{
                    $error = 'There was an error with your email address, please contact an adminstrator for a link to reset your passowrd.'.$failures;
                 }
              }else{
                 $error = ('This site does not support sending emails, please contact the administrator for your reset your password link.');
                 $lostyourpw_done = true;
              }
           }else{ //update failed, this could be because there is already a pending account change
              
              $error = 'Password reset failed. Please contact an administrator';  
           }
           
      }else{
          $error = 'There was a database error, please try again later and/or contact an administrator.';
      }   
   }else{
      $error = 'There was an error with your username or email address, please try again.';
   }

}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?=Site::cfg('title')?> - Lost your password?</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <?PHP
          Site::CssJsYuiIncludes();
          Site::CssJsJqueryIncludes();
          Site::Css();
        ?>
    </head>
    <body>
    <div id="Wrapper">
    <div class="logost" style="margin-bottom:20px"><a href="index.php"><img border=0 src="images/logo.jpg" width="412" height="136" /></a></div>
    <?PHP
	    if ($error){
				?><div style="border: 1px solid red; color: red; background-color: pink; padding: 3px; margin-bottom: 10px;">
				Error: <?=$error?>
				</div><?PHP
		}
        
		if ($lostyourpw_done)
		{
		  if (!$error){
              ?>
              <div style="border: 1px solid green; color: green; padding: 10px;">
              Thank you for resetting your password, please check your email for a link to reset your password.
              <a href="index.php?username_or_email=<?=urlencode($_POST['username'])?>">Login</a>
              </div>
              <?PHP
          }
		}
		else //display lost password form
		{
    ?>	
        <form id="LostPasswordForm" name="frmLostPassword" method="POST">
        <div class="Row">
        <h2 style="border-bottom:1px solid #c7c7c7; padding-bottom:5px; margin-bottom:15px;">Lost your password?</h2>
            <h3>Account Details</h3>
             <div class="Col1" style="background:none;">
				<h5><sup>*</sup> Username &nbsp; </h5>
				<input name="username" type="text" value="<?=isset($_POST['username']) ? htmlentities($_POST['username']):''?>"/>
             </div>
             <div class="Col2">
               <h5><sup>*</sup> Email Address</h5>
               <input type="text" name="email" value="<?=isset($_POST['email']) ? htmlentities($_POST['email']):''?>" />
             </div>
        </div>
        <div class="Row">
            <div class="Col1" style="background:none;">
            <em style="float:right;"><sup>*</sup>obligatory fields.</em>
            </div>
        </div>
        <input type="submit" />
        </form>
		<?PHP if ($error && $error_field){
		  ?>
		  <script>
		  if (document.frmLostPassword['<?=$error_field?>']){
		     document.frmLostPassword['<?=$error_field?>'].style.border = '1px solid red';
			 document.frmLostPassword['<?=$error_field?>'].focus();
		   }
		  if (document.frmLostPassword['<?=$error_field2?>']){
		     document.frmLostPassword['<?=$error_field2?>'].style.border = '1px solid red';
		   }
          </script> 
		  <?PHP
		}?>
		<?PHP
		}//end if showing lost password form
		?>
     </div>
    </body>
</html>





