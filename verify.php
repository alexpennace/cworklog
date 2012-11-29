<?PHP
   require_once('lib/db.inc.php');
   require_once('lib/Members.class.php');
   require_once('lib/Site.class.php');
   Members::SessionAllowLogin();
   $error = false;
   $success = false;
   if (isset($_GET['code']))
   {
        if (isset($_GET['email'])){
            $sql = "SELECT * FROM user WHERE verify_code = '%s' AND LOWER(email) = LOWER('%s')";
            $result = mysql_query(sprintf($sql, $_GET['code'], $_GET['email']));
            if ($result && $row = mysql_fetch_assoc($result)){
                $verify_user = $row;
                $sql2 = "UPDATE user SET status = 1, verify_code = '', verify_param = '' WHERE id = %d";
                $result2 = mysql_query(sprintf($sql2, $verify_user['id']));
                if ($result2){
                   $success = 'Thank you for verifying your email address, your account has been updated';
                }else{
                   $error = 'There was a problem verifying your account, please try again later';
                }
            }else{
                $error = 'Invalid verification code, please try again';
            }
       }else if (isset($_GET['new_email'])){
            $sql = "SELECT * FROM user WHERE verify_code = '%s' AND LOWER(verify_param) = LOWER('%s')";
            $result = mysql_query(sprintf($sql, mysql_real_escape_string($_GET['code']), mysql_real_escape_string($_GET['new_email'])));         
            if ($result && $row = mysql_fetch_assoc($result)){
                $verify_user = $row;
                $sql2 = "UPDATE user SET status = 1, email = '%s', verify_code = '', verify_param = '' WHERE id = %d";
                $result2 = mysql_query(sprintf($sql2, mysql_real_escape_string($_GET['code']), $verify_user['id']));
                if ($result2){
                   $success = 'Thank you for verifying your new email address, your account has been updated';
                }else{
                   $error = 'There was a problem verifying your account, please try again later';
                }
            }       
       }else{
          $error = 'Invalid verification action';
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
<title>Verify Code</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
</head>
<body>
<?PHP Members::MenuBar(); ?>
<?PHP
  if ($error){
     echo $error;
  }
  if ($success){
     echo $success;
  }
?>
</body>
</html>