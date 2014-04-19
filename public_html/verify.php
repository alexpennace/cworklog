<?PHP
 error_reporting(E_ALL);
 ini_set('display_errors', 1);

   require_once('lib/db.inc.php');
   require_once('lib/Members.class.php');
   require_once('lib/Site.class.php');
   Members::SessionAllowLogin();
   $error = false;
   $success = false;
   if (isset($_GET['code']))
   {
        if (isset($_GET['email'])){
            $sql = "SELECT * FROM user WHERE verify_code = :verify_code AND LOWER(email) = LOWER(:email)";
            $exec_ary = array('verify_code'=>$_GET['code'], 'email'=>$_GET['email']); 
            $prep = $DBH->prepare($sql);
            $result = $prep->execute($exec_ary);
            if ($result && $row = $prep->fetch()){
                $verify_user = $row;
                $sql2 = "UPDATE user SET status = 1, verify_code = '', verify_param = '' WHERE id = :id";
                $prep = $DBH->prepare($sql2);
                $exec_ary = array('id'=>$verify_user['id']);
                $result2 = $prep->execute($exec_ary);
                if ($result2){
                   $success = 'Thank you for verifying your email address, your account has been updated';
                }else{
                   $error = 'There was a problem verifying your account, please try again later';
                }
            }else{
                $error = 'Invalid verification code, please try again';
            }
       }else if (isset($_GET['new_email'])){
            $sql = "SELECT * FROM user WHERE verify_code = :verify_code AND LOWER(verify_param) = LOWER(:verify_param)";
            $prep = $DBH->prepare($sql);
            $result = $prep->execute(array('verify_code'=>$_GET['code'], 'verify_param'=>$_GET['new_email']));         
            if ($result && $row = $prep->fetch()){
                $verify_user = $row;
                $sql2 = "UPDATE user SET status = 1, email = :email, verify_code = '', verify_param = '' WHERE id = :id";
                $prep = $DBH->prepare($sql2);
                $exec_ary = array('email'=>$_GET['new_email'], 'id'=> $verify_user['id']);
                $result2 = $prep->execute($exec_ary);
                if ($result2){
                   $success = 'Thank you for verifying your new email address, your account has been updated';
                }else{
                   $error = 'There was a problem verifying your account, please try again later';
                }
            }       
       }else{
          $error = 'Invalid verification action';
       }
   }else if (isset($_GET['resetpwcode'])){
            $sql = "SELECT * FROM user WHERE verify_code = :verify_code AND LOWER(email) = LOWER(:email)";
            $prep = $DBH->prepare($sql); 
            $exec_ary = array('verify_code'=> $_GET['resetpwcode'], 'email'=> $_GET['email']);
            $result = $prep->execute($exec_ary);
            if ($result && $row = $prep->fetch()){
                $resetpw_user = $row;
                if (!empty($_POST)){
                   if (!empty($_POST['password']) && !empty($_POST['password_confirm'])){
                       if ($_POST['password'] != $_POST['password_confirm']){
                          $error = 'Passwords do not match';
                          $error_field = 'password';
                          $error_field2 = 'password_confirm';
                       }
                       else if (strpos($_POST['password'],' ') !== false){
                          $error = 'Password can not contain spaces';
                          $error_field = 'password';
                       }
                       else if (strlen($_POST['password']) < 4){
                          $error = 'Password is too short (must be 4 to 15 characters)';
                          $error_field = 'password';
                       }
                       else if (strlen($_POST['password']) > 15){
                          $error = 'Password is too long (must be 4 to 15 characters)';
                          $error_field = 'password';
                       }else{
                            $sql2 = "UPDATE user SET status = 1, password = MD5(:password), verify_code = '', verify_param = '' WHERE id = :id";
                            $prep = $DBH->prepare($sql2);
                            $exec_ary = array('password'=>$_POST['password'], 'id'=>$resetpw_user['id']);
                            $result2 = $prep->execute($exec_ary);
                            if ($result2){
                               $success = 'Thank you for verifying your new email address, your account has been updated';
                            }else{
                               $error = 'There was a problem verifying your account, please try again later';
                            }                         
                          
                          $success = 'Your new password has been changed. <a href="index.php?username_or_email='.urlencode($resetpw_user['username']).'">Login</a>';
                       }                       
                   
                   }else{
                       $error = 'Password cannot be empty';
                   }
                }
            }else{
                $error = 'Invalid verification code, please try again';
            }   
   }
   else
   {
      $error = 'No code provided, try again';
   }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title><?=Site::cfg('title')?> - <?=isset($_GET['resetpwcode']) ? 'Reset Password' : 'Verify Code'?></title>
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
     echo $error;
  }
  if ($success){
     echo $success;
  }
  if (empty($success) && !empty($_GET['resetpwcode']) && !empty($resetpw_user)){
    ?><form name="frmResetPassword" id="frmResetPassword" method="POST">
        <div class="Row">
        <h2 style="border-bottom:1px solid #c7c7c7; padding-bottom:5px; margin-bottom:15px;">Reset Password</h2>
             <div class="Col1" style="background:none;">
				<h5><sup>*</sup> Password &nbsp; </h5>
				<input name="password" type="password" value=""/>
             </div>
             <div class="Col2">
                <h5><sup>*</sup> Confirm Password</h5>
                <input type="password" name="password_confirm" value="" />
            </div>
        </div>  
        <input type="submit" />        
    </form>
    <?PHP
  }
?>
</div>
</body>
</html>