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

 error_reporting(E_ALL);
 ini_set('display_errors', 1);

require_once(__DIR__.'/lib/Members.class.php');
require_once(__DIR__.'/lib/misc.inc.php');
require_once(__DIR__.'/lib/Site.class.php');
require_once(__DIR__.'/lib/work_log.class.php');
require_once(__DIR__.'/lib/cwl_email.class.php');

Members::SessionAllowLogin();

$error = false;
$error_field = false;
$error_field2 = false;
$registration_complete = false;

if (isset($_GET['ajax'])){
   if (isset($_GET['username_check'])){
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
   
   if (Members::GetUserByUsername($_POST['username']))
   {
	  $error = 'This username is not available';
      $error_field = 'username';
   }
   /*else if ($_POST['email'] != $_POST['email_confirm']){
      $error = 'Emails do not match';
	  $error_field = 'email';
	  $error_field2 = 'email_confirm';
   }*/
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
   }
   else if (empty($_POST['iagree_tc'])){
      $error = 'You must agree to the terms and conditions';
	  $error_field = 'iagree_tc';
   }
   else
   {
		  $sql = "INSERT INTO user (id,	username,	password,	email,	phone,	name,	street,	street2,	city,	state,	zip,	country,	status,	verify_code, date_created)
		        VALUES (NULL, :username, MD5(:password), 
				:email, :phone, 
				:name, :street1, :street2, :city, :state, :zip, :country, 
				:status, :verify_code, NOW());";
	   	
      $verify_code = random_string(25);
        

        if (Site::cfg('use_php_mail'))
        {
            
           list($mailer, $message, $logger) = cwl_email::setup(false);

            $message->setSubject('Contractor\'s Work Log Registration');
            $message->setBody("Please verify your account by clicking the link below\r\n".
                Site::cfg('base_url').'verify.php?code='.$verify_code.'&email='.urlencode($_POST['email']), 'text/html');
             
            $message->setTo(array($_POST['email']));
        
            $mailed = $mailer->send($message);

            if (!$mailed){
                $error = 'There was an error with your email address, please try again';
                $error_field = 'email';
                return false;
            }
        }else{
           //we really have no way of verifying the email address except that the user entered it twice,
           //whatever
        }
          
          $exec_ary = array('username'=>$_POST['username'], 'password'=>$_POST['password'], 'email'=>$_POST['email'], 'phone'=>$_POST['phone'], 'name'=>$_POST['fullname'], 'street1'=>$_POST['street1'], 'street2'=>$_POST['street2'], 'city'=>$_POST['city'], 'state'=>$_POST['state'], 'zip'=>$_POST['zip'],'country'=>$_POST['country'], 'status'=>0, 'verify_code'=>$verify_code);

		      $prep = $DBH->prepare($sql); 
          $result =  $prep->execute($exec_ary);
      		if (!$result){
      		   $error = 'There was a server error, please try again later';
      		}
        
        //now a new feature is to insert a mock-up 
        if (Site::cfg('insert_mock_company_upon_registration')){
            $ins_work_log = work_log::Add(array('title'=>'My First Work Log','description'=>'Learning the ins and outs of '.Site::cfg('title'),
                'user_id'=> $DBH->lastInsertId(),
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title><?=Site::cfg('title')?> - Registration</title>
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
		if ($registration_complete)
		{
		  ?>
		  <div style="border: 1px solid green; color: green; padding: 10px;">
		  Thank you for registering, please check your email to confirm your account.
          <a href="index.php?username_or_email=<?=urlencode($_POST['username'])?>">Login</a>
		  </div>
		  <?PHP
		}
		else //display registration form
		{
    ?>	
<script type="text/javascript">
    function toggle5(showHideDiv, switchImgTag) {
            var ele = document.getElementById(showHideDiv);
            var imageEle = document.getElementById(switchImgTag);
            if(ele.style.display == "block") {
                    ele.style.display = "none";
            imageEle.innerHTML = '<img src="images/plus.png">';
            }
            else {
                    ele.style.display = "block";
                    imageEle.innerHTML = '<img src="images/minus.png">';
            }
    }

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
		  data: querystr
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
      
        
        <form id="RegForm" name="frmRegister" method="POST" class="register">
        <div class="Row">
        <h2 style="border-bottom:1px solid #c7c7c7; padding-bottom:5px; margin-bottom:15px;">Registration</h2>
		    <?PHP if ($error){
				?><div style="border: 1px solid red; color: red; background-color: pink; padding: 3px; margin-bottom: 10px;">
				Error: <?=$error?>
				</div><?PHP
			}?>
            <h3>Account Details</h3>
             <div class="Col1" style="background:none;">
				<h5><sup>*</sup> Username &nbsp; <span id="username_check">
				
				</span></h5>
				<input name="username" type="text" value="<?=isset($_POST['username']) ? htmlentities($_POST['username']):''?>"
				onchange="username_check(this.value);"
				/>
				
                <h5><sup>*</sup> Choose a password</h5>
                <input name="password" type="password"  />
             </div>
             <div class="Col2">

            <h5><sup>*</sup> What's your email address?</h5>
            <input type="text" name="email" value="<?=isset($_POST['email']) ? htmlentities($_POST['email']):''?>" />
            <h5><sup>*</sup> Re-type password:</h5>
            <input name="password_confirm" type="password" />
            </div>


            </div>

            <h3 id="headerDivImg"><a style="color: black" href="javascript:toggle5('contentDivImg', 'imageDivLink');">Invoicing Details</a>  <a id="imageDivLink" href="javascript:toggle5('contentDivImg', 'imageDivLink');"> <img src="images/plus.png"></a></h3>  

            
            <div class="Row ContPopup" id="contentDivImg" style="display: none;">
            <p>
            Feel free to enter your invoicing details. These will show up when you invoice your client.
            </p>
            <div class="Col1" style="background:none;" >
            <h5>Name</h5>
            <input name="fullname" type="text" class="long" value="<?=isset($_POST['fullname']) ? htmlentities($_POST['fullname']):''?>"/>
            <h5>Street 1</h5>
            <input name="street1" type="text" class="long" value="<?=isset($_POST['street1']) ? htmlentities($_POST['street1']):''?>"/>
            <h5>City</h5>
            <input name="city" type="text" class="long" value="<?=isset($_POST['city']) ? htmlentities($_POST['city']):''?>"/>
            <h5>Zip/Postal Code</h5>
            <input name="zip" type="text" maxlength="15" value="<?=isset($_POST['zip']) ? htmlentities($_POST['zip']):''?>"/>
            </div>

            <div class="Col2">
            <h5>Phone:</h5>
            <input name="phone" type="text" maxlength="25" value="<?=isset($_POST['phone']) ? htmlentities($_POST['phone']):''?>"/>
            <h5> Street 2</h5>
            <input name="street2" type="text" class="long" value="<?=isset($_POST['street2']) ? htmlentities($_POST['street2']):''?>"/>
            <h5> State/Province</h5>
            <input name="state" type="text" class="long" value="<?=isset($_POST['state']) ? htmlentities($_POST['state']):''?>"/>
            <h5> Country</h5>
            <input name="country" type="text" class="long" value="<?=isset($_POST['country']) ? htmlentities($_POST['country']):''?>"/>
            </div>

            </div>

            <div class="Row">
            <div class="Col1" style="background:none;">
            <input name="iagree_tc" type="checkbox" <?=!empty($_POST['iagree_tc']) ? 'checked=checked' : ''?>/> <sup>*</sup> I accept the <a href="terms.php" target="_blank" class="termcond">Terms and Conditions</a>. </div>
            <div class="Col2" style="float:right;">
            <em style="float:right;"><sup>*</sup>obligatory fields.</em>
            </div>
            </div>
            <input type="submit" />
        </form>
        
        <script>
        $(function() {
             <?PHP
             //show the invoice details if we posted a non-empty value
             if (!empty($_POST['fullname']) || 
                 !empty($_POST['phone']) || 
                 !empty($_POST['street1']) || 
                 !empty($_POST['street2']) || 
                 !empty($_POST['city']) || 
                 !empty($_POST['state']) || 
                 !empty($_POST['zip']) || 
                 !empty($_POST['country']))
             {
                 ?>
                 toggle5('contentDivImg', 'imageDivLink');        
                 <?PHP
             }
             ?> 
               var tabindexize = function(index, Element, evenorodd){
                   console.log(index+' ' + Element + evenorodd);
                   $('input[type=text],input[type=password],input[type=checkbox]', Element).each(function(i, elm){
                        var odd = (evenorodd == 'odd');
                        var startindex;
                        if (index === 0){
                           startindex = odd ? 1 : 2;
                        }else if (index === 1){
                           startindex = odd ? 5 : 6;
                        }else if (index == 2){
                           startindex = 13;
                        }
                        var tabindex = i*2 + startindex;
                        $(this).attr('tabindex', tabindex);
                  });              
               }
               $('.Col1').each(function(index, Element){ tabindexize(index, Element, 'odd'); });
               $('.Col2').each(function(index, Element){ tabindexize(index, Element, 'even'); });
          });
		  </script>        
        
        
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





