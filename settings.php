<?PHP
require_once('lib/Members.class.php');
require_once('lib/misc.inc.php');
require_once('lib/Site.class.php');
require_once('lib/work_log.class.php');

Members::SessionForceLogin();

if (isset($_POST)){
    $success = false;
    $error = false;
    $error_field = false;
    //are we changing the password?
    if (isset($_POST['pw_new']) && isset($_POST['pw_new_confirm'])){
      if (strlen($_POST['pw_new']) < 4){
              $error = 'New Password is too short (must be 4 to 15 characters)';
              $error_field = 'pw_new';
      }
      else if (strlen($_POST['pw_new']) > 15){
              $error = 'New Password is too long (must be 4 to 15 characters)';
              $error_field = 'pw_new';
      } 
      if ($_POST['pw_new'] != $_POST['pw_new_confirm']){
         $error = 'New password does not match with confirmed password';
         $error_field ='pw_new';
      }else{ //passwords match, do more error checking

          $sql = "SELECT * FROM user WHERE id = %d AND MD5('%s') = password";
          $sql = sprintf($sql, $_SESSION['user_id'], $_POST['pw_current']);
          $result = mysql_query($sql);
          if ($row = mysql_fetch_assoc($result)){
             //current password matches
             $sql = "UPDATE user SET password = MD5('%s') WHERE id = %d";
             $sql = sprintf($sql, $_POST['pw_new'], $_SESSION['user_id']);
             $result = mysql_query($sql);
             if (!$result){
                $error = 'Your password could not be changed';
             }else{
                $success = 'Your password has been changed!';
             }
          }else{
             $error = 'Your current password is incorrect';
             $error_field = 'pw_current';
          }
      }
   }else if (isset($_POST['email_new'])){ 
          $sql = "SELECT * FROM user WHERE id = %d AND MD5('%s') = password";
          $sql = sprintf($sql, $_SESSION['user_id'], $_POST['pw_current']);
          $result = mysql_query($sql);
          if ($row = mysql_fetch_assoc($result)){
             if (!filter_var($_POST['email_new'], FILTER_VALIDATE_EMAIL)){
                  $error = 'Email is not valid';
                  $error_field = 'email';
             }else{
                 $verify_code = random_string(25);
                 $sql = "UPDATE user SET verify_command = 'change_email', 
                                         verify_code = '".$verify_code."', 
                                         verify_param = '".$_POST['email_new']."' 
                         WHERE id = ".(int)$_SESSION['user_id'];
                 $result = mysql_query($sql);
                 if (!$result){
                    $error = 'Error performing email address change, try again later.';
                 }else{ //everything so far so good
                     $mailed = mail($_POST['email_new'], 'New email address confirmation - '.Site::$title, 
                      "Please verify your new email address by clicking the link below\r\n".
                      Site::$base_url.'verify.php?code='.$verify_code.'&new_email='.urlencode($_POST['email_new']), 
                      Site::$email_from_header);
                      
                    if (!$mailed){
                       $error = 'Error sending mail to new email address';
                       $error_field = 'email_new';
                    }else{
                        $success = 'Your email address is pending a change';
                    }
                 }
             }//valid email
          }else{
             $error = 'Current password does not match';
             $error_field = 'pw_current';
          }
   
   }else if (isset($_POST['street'])){
      $stmt = $DBH->prepare('UPDATE user SET name = :name, phone = :phone, street = :street, street2 = :street2, city = :city, state = :state, zip = :zip, country = :country WHERE id = :id');
      
      $result = $stmt->execute(array('name'=>$_POST['name'], 
            'phone'=>$_POST['phone'], 'street'=>$_POST['street'], 
            'street2'=>$_POST['street2'], 
            'city'=>$_POST['city'], 
            'state'=>$_POST['state'], 
            'zip'=>$_POST['zip'], 
            'country'=>$_POST['country'],
            'id' => $_SESSION['user_id']));
      if ($result){
        $success = 'Successfully updated your address information, now try to generate an invoice';
      }else{
        $error = 'Error updating address information';
      }
   }
}//end if POSTing updates
?>

<!DOCTYPE html>

<html>
<head>
<title>Settings - <?=Site::$title?></title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<script type="text/javascript" src="js/work_log_shared.js"></script>
</head>
<body class="yui-skin-sam">
<?PHP Members::MenuBar(); ?>

<?PHP
   //grab latest user row from database
   $sql = "SELECT * FROM user WHERE id = ".(int)$_SESSION['user_id'];
   $result = mysql_query($sql);
   $user_row = mysql_fetch_assoc($result);
   //update the session variables
   $_SESSION['user_row'] = $user_row;
?>
<script>
    $(function() {
        $( "#accordion" ).accordion({ active: false, collapsible: true });
    });
</script>

<?PHP
  if (!empty($success)){
     echo '<div class="success">'.$success.'</div>';
  }
  if (!empty($error)){
     echo '<div class="error">'.$error.'</div>';
  }
?>

<div id="accordion"  style="padding:0 1%;">

  <h3><strong>Change Password</strong></h3>

    <div>

        <form method="POST" id="formsetting">

        <label >Current Password: </label><input type="password" name="pw_current"/><br>

        <label>New Password: </label><input type="password" name="pw_new"/><br>

        <label>Confirm Password:</label><input type="password" name="pw_new_confirm"/><br>

        <input type="submit" value="Change Password"/>
        </form>
    </div>

    <h3><strong>Change Email </strong>

    <?PHP 
    echo '('.$_SESSION['user_row']['email'].') ';
    
    if ($_SESSION['user_row']['verify_command'] == 'change_email'){ 
        echo '*Pending change to '.$_SESSION['user_row']['verify_param']; 
    }?></h3>
    <div>

        <form method="POST" id="formsetting">

        <label>Current Password: </label><input type="password" name="pw_current"/><br>

        <label>New Email: </label><input type="text" name="email_new"/><br>

        <input type="submit" value="Change Email"/>
        </form>
    </div>

    <h3><strong>Change Address</strong></h3>

    <div>

        <form method="POST" id="formsetting">

        <b>This address will be used in generating a pdf invoice</b><br><br>

        <label>Name</label><input type="text" name="name" value="<?=$user_row['name']?>"/><br>

        <label>Street</label><input type="text" name="street" value="<?=$user_row['street']?>"/><br>

        <label>Street2</label><input type="text" name="street2" value="<?=$user_row['street2']?>"/><br>

        <label>City</label><input type="text" name="city" value="<?=$user_row['city']?>"/><br>

        <label>State</label><input type="text" name="state" value="<?=$user_row['state']?>"/><br>

        <label>Zip</label><input type="text" name="zip" value="<?=$user_row['zip']?>"/><br>

        <label>Country</label><input type="text" name="country" value="<?=$user_row['country']?>"/><br>

        <label>Phone</label><input type="text" name="phone" value="<?=$user_row['phone']?>"/><br>

        <input type="submit" value="Change Address"/>
        </form>
    </div>
</div>
</body>
</html>
