<?PHP
require_once('lib/Members.class.php');
require_once('lib/misc.inc.php');
require_once('lib/Site.class.php');
require_once('lib/work_log.class.php');

Members::SessionAllowLogin();

$error = false;
$error_field = false;
$error_field2 = false;
$registration_complete = false;

if (isset($_GET['ajax'])){
   if (isset($_GET['username_check'])){
		$error = username_check($_GET['username_check']);
		if ($error){
		 die(json_encode(array('error'=>$error)));
		}
		if (Members::GetUserByUsername($_GET['username_check']))
		{
			die(json_encode(array('error'=>'This username is not available')));
		}
		else
		{
			die(json_encode(array('success'=>1)));
		}
	  
   }
   exit;
}

if (isset($_POST['username']) && isset($_POST['email']))
{
   if ($error = username_check($_POST['username'])){
      $error_field = 'username';
   }
   else if (Members::GetUserByUsername($_POST['username']))
   {
	  $error = 'This username is not available';
      $error_field = 'username';
   }
   else if ($_POST['email'] != $_POST['email_confirm']){
      $error = 'Emails do not match';
	  $error_field = 'email';
	  $error_field2 = 'email_confirm';
   }
   else if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
      $error = 'Email is not valid';
	  $error_field = 'email';
   }
   else if (Members::GetUserByEmail($_POST['email'])){
      $error = 'Email is already taken';
	  $error_field = 'email';
   }
   else if ($_POST['password'] != $_POST['password_confirm']){
      $error = 'Passwords do not match';
	  $error_field = 'password';
	  $error_field2 = 'password_confirm';
   }
   else if (strlen($_POST['password']) < 4){
      $error = 'Password is too short (must be 4 to 15 characters)';
	  $error_field = 'password';
   }
   else if (strlen($_POST['password']) > 15){
      $error = 'Password is too long (must be 4 to 15 characters)';
	  $error_field = 'password';
   }
   else if (empty($_POST['iagree_tc'])){
      $error = 'You must agree to the terms and conditions';
	  $error_field = 'iagree_tc';
   }
   else
   {
		$sql = "INSERT INTO user (id,	username,	password,	email,	phone,	name,	street,	street2,	city,	state,	zip,	country,	status,	verify_code)
		        VALUES (NULL, '%s', MD5('%s'), 
				'%s', '%s', 
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', 
				%d, '%s');";
		$verify_code = random_string(25);
        

        if (Site::$use_php_mail)
        {
            $mailed = mail($_POST['email'], 'Work Log Registration', 
              "Please verify your account by clicking the link below\r\n".
              Site::$base_url.'verify.php?code='.$verify_code.'&email='.urlencode($_POST['email']), 
              Site::$email_from_header);
            
            if (!$mailed){
                $error = 'There was an error with your email address, please try again';
                $error_field = 'email';
                return false;
            }
        }else{
           //we really have no way of verifying the email address except that the user entered it twice,
           //whatever
        }
          
		$result = mysql_query(sprintf($sql, $_POST['username'], $_POST['password'], 
		                    $_POST['email'], $_POST['phone'], 
							$_POST['fullname'], $_POST['street1'], $_POST['street2'], $_POST['city'], $_POST['state'], $_POST['zip'], $_POST['country'],
							0, $verify_code));
		if (!$result){
		   $error = 'There was a server error, please try again later';
		}
        
        //now a new feature is to insert a mock-up 
        if (Site::$insert_mock_company_upon_registration){
            $ins_work_log = work_log::Add(array('title'=>'My First Work Log','description'=>'Learning the ins and outs of '.Site::$title,
                'user_id'=> mysql_insert_id(),
                'company_id'=>'new',
                'name'=>'My First Company',
                'default_hourly_rate'=>21.5,
                'street'=>'1st Company Lane',
                'street2'=>'',
                'city'=>'New York',
                'state'=>'NY',
                'zip'=>'10027',
                'country'=>'USA',
                'phone'=>'555-1212',
                'email'=>'firstcompany@example.com',
                'notes'=>''));
            if (!$ins_work_log){
               $error = 'Mock Work-Log could not be added';
            }
        }
        
		$registration_complete = !!$result;
   }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?=Site::$title?> - Registration</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <link rel="stylesheet" type="text/css" href="css/register.css"/>
		<script src="js/jquery-1.8.2.js"></script>
    </head>
    <body>

    <?PHP
		if ($registration_complete)
		{
		  ?>
		  <div style="border: 1px solid green; color: green">
		  Thank you for registering, please check your email to confirm your account.
          <a href="index.php?username_or_email=<?=urlencode($_POST['username'])?>">Login</a>
		  </div>
		  <?PHP
		}
		else //display registration form
		{
    ?>	
	<script>
	function username_check(username)
	{
		$('#username_check').css('color', 'yellow').html('Checking...');
		var querystr = '';
		querystr += 'ajax=1' + 
					'&username_check='+encodeURIComponent(username);
		
		$.ajax({
		  type: "GET",
		  url: "register.php",
		  dataType: "json",
		  data: querystr,
		}).done(function( msg ) {
		   if (msg.error){
		      $('#username_check').css('color', 'red').html(msg.error);
			  document.frmRegister['username'].style.border = '1px solid red';
		   }else{
		      $('#username_check').css('color', 'green').html('This username is available');
			  //document.frmRegister['username'].removeAttribute("style");
			  document.frmRegister['username'].style.border = '1px solid green';
		   }
		});
	}
	</script>
        <form name="frmRegister" method="POST" class="register">
            <h1>Registration</h1>
		    <?PHP if ($error){
				?><div style="border: 1px solid red; color: red; background-color: pink; padding: 3px;">
				Error: <?=$error?>
				</div><?PHP
			}?>
            <fieldset class="row1">
                <legend>Account Details
                </legend>
				<p><label>Username *
				</label><input name="username" type="text" value="<?=isset($_POST['username']) ? htmlentities($_POST['username']):''?>"
				onchange="username_check(this.value);"
				/>
				<span id="username_check">
				
				</span>
				</p>
                <p>
                    <label>Email *
                    </label>
                    <input name="email" type="text" value="<?=isset($_POST['email']) ? htmlentities($_POST['email']):''?>"/>
                    <label>Repeat email *
                    </label>
                    <input name="email_confirm" type="text" value="<?=isset($_POST['email_confirm']) ? htmlentities($_POST['email_confirm']):''?>"/>
                </p>
                <p>
                    <label>Password*
                    </label>
                    <input name="password" type="password" value=""/>
                    <label>Repeat Password*
                    </label>
                    <input name="password_confirm" type="password" value=""/>
                    <label class="obinfo">* obligatory fields
                    </label>
                </p>
            </fieldset>
            <fieldset class="row2">
                <legend title="Personal Details (used for invoicing)">Invoicing Details
                </legend>
                <p>
                    <label class="optional">Name
                    </label>
                    <input  name="fullname" type="text" class="long" value="<?=isset($_POST['fullname']) ? htmlentities($_POST['fullname']):''?>"/>
                </p>
                <p>
                    <label class="optional">Phone
                    </label>
                    <input  name="phone" type="text" maxlength="25" value="<?=isset($_POST['phone']) ? htmlentities($_POST['phone']):''?>"/>
                </p>
                <p>
                    <label class="optional">Street 1
                    </label>
                    <input name="street1" type="text" class="long" value="<?=isset($_POST['street1']) ? htmlentities($_POST['street1']):''?>"/>
                </p>
                <p>
                    <label class="optional">Street 2
                    </label>
                    <input name="street2" type="text" class="long" value="<?=isset($_POST['street2']) ? htmlentities($_POST['street2']):''?>"/>
                </p>
                <p>
                    <label class="optional">City
                    </label>
                    <input name="city" type="text" class="long" value="<?=isset($_POST['city']) ? htmlentities($_POST['city']):''?>"/>
                </p>
                <p>
                    <label class="optional">State
                    </label>
                    <input name="state"  type="text" class="long" value="<?=isset($_POST['state']) ? htmlentities($_POST['state']):''?>"/>
                </p>
                <p>
                    <label class="optional">Zip
                    </label>
                    <input name="zip" type="text" maxlength="15" value="<?=isset($_POST['zip']) ? htmlentities($_POST['zip']):''?>"/>
                </p>
                <p>
                    <label class="optional">Country
                    </label>
                    <input name="country" type="text" class="long" value="<?=isset($_POST['country']) ? htmlentities($_POST['country']):''?>"/>
                </p>
            </fieldset>
            <fieldset class="row3">
				<legend>Other Information</legend>
                <div class="infobox"><h4>Helpful Information</h4>
                    <p>The fields to the left are only used privately to personalize your <a target="_blank" href="showcase_invoice.php">invoices</a>. If you do not use the <a target="_blank" href="showcase_invoice.php">invoice</a> feature, you may leave it blank.</p>
                </div>
            </fieldset>
            <fieldset class="row4">
                <legend>Terms and Mailing
                </legend>
                <p class="agreement">
                    <input type="checkbox" name="iagree_tc" value="1" <?=isset($_POST['iagree_tc']) ? 'checked ':''?>/>
                    <label>*  I accept the <a target="_blank" href="terms.php">Terms and Conditions</a></label>
                </p>
            </fieldset>
            <div><button class="button" type="submit">Register &raquo;</button></div>
        </form>
		<?PHP if ($error && $error_field){
		  ?>
		  <script>
		  if (document.frmRegister['<?=$error_field?>']){
		     document.frmRegister['<?=$error_field?>'].style.border = '1px solid red';
			 document.frmRegister['<?=$error_field?>'].focus();
		   }
		  if (document.frmRegister['<?=$error_field2?>']){
		     document.frmRegister['<?=$error_field2?>'].style.border = '1px solid red';
		   }
		  </script>
		  <?PHP
		}?>
		<?PHP
		}//end if showing registration form
		?>
    </body>
</html>





