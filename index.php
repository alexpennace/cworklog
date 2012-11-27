<?PHP
 require_once('lib/Members.class.php');
 Members::SessionAllowLogin();
 $ERROR_MSG = '';
 if (isset($_POST['username_or_email'])){
    $logged_in = Members::Login($_POST['username_or_email'], $_POST['password']);
	$ERROR_MSG = 'Failure logging in, check your username or password, and try again';
 }
 else{
    $logged_in = false;
 }
 
 if (!empty($_GET['logout'])){
	Members::Logout();
 }
 
 if (Members::IsLoggedIn())
 {
	if (!empty($_REQUEST['goto'])){
	  $goto = $_REQUEST['goto'];
	}else{
	  $goto = 'work_log.php';
	}
	header('Location: '.$goto);
	exit;
 }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Contractor's Work Log</title>
<?PHP if (!empty($_REQUEST['mobile'])){ ?>
<style>
form label input { width: 100%; }
img { width: 16px; }
body { font-size: 10px; }
input { font-size: 10px; }
</style>
<?PHP }else{ ?>
<link rel="stylesheet" type="text/css" href="css/stylesheet.css" />
<?PHP } ?>
</head>
<body>
<?PHP if (!empty($_REQUEST['mobile'])){ ?>
<img src="images/time_log_clipboard.png" />
<?PHP
   if ($logged_in){
      echo 'Welcome '.$logged_in['first_name'];
   }else{
      echo 'Not logged in';
   }
?>
<form name="frmLogin" action="index.php" method="POST">
<input type="hidden" name="mobile" value="1" />
<?PHP
  if (isset($_REQUEST['goto'])){ ?><input type="hidden" name="goto" value="<?=htmlentities($_REQUEST['goto'])?>"/><?PHP }
  if (!empty($ERROR_MSG)){ 
    ?><div class="error">
    <?=$ERROR_MSG?>
    </div><?PHP
  }
?>
<label>Username/Email <input type="text" name="username_or_email" value="<?=isset($_REQUEST['username_or_email']) ? htmlentities($_REQUEST['username_or_email']) : ''?>"/></label>
<br />
<label>Password <input type="password" name="password" value="<?=isset($_REQUEST['pw'])?htmlentities($_REQUEST['pw']):''?>"/>
<br />
<input type="submit" value="Login" />
</form>
<?PHP 
//automatically log in if username and password is provided
if (!empty($_REQUEST['username_or_email']) && !empty($_REQUEST['pw'])){ ?>
<script>
document.frmLogin.submit();
</script>
<?PHP } ?>
Not registered? <a href="register.php">Register free</a>
<?PHP }else{ ?>
<div id="Wrapper">
<div class="logost"><img src="images/logo.jpg" width="412" height="136" /></div>
<div class="Row">
<div class="Col1">
<h2>Welcome  to <span class="OrangeColor">Contractor's</span> <span class="GreenColor">Work Log</span></h2>
<p>
Contractor's Work Log is a tool to track billable time for clients, and then send them an invoice.
It is easy: 
<br><b>1. </b>Add a client work log, <b>2. </b>Log your time
, and <b>3. </b>Send a bill. 
<br>
<b>Simple</b>, <b>Easy</b>, and <b>Free</b>.
</p>
<p>
<h4>What is the catch?</h4>
<p>
Right now, nothing.
In the future, CWorkLog may turn into a business endeavor charging monthly or allowing a private download to a php/mysql server. If you have any input on this or would like to help test, please post a message on the <a href="http://www.donationcoder.com/forum/index.php?topic=32772.0" target="_blank" style="color: blue">donation coder forum</a>
Contractor's Work Log started as a private endeavor on one local machine, and was later made available to the public after 37 weeks of testing.
</p>
</div>

<div class="Col2">

<form id="LoginForm" action="index.php" method="POST">
<?PHP
  if (isset($_GET['goto'])){ ?><input type="hidden" name="goto" value="<?=htmlentities($_GET['goto'])?>"/><?PHP }
  if (!empty($ERROR_MSG)){ 
    ?><div class="error">
    <?=$ERROR_MSG?>
    </div><?PHP
  }
?>
<h5>Username:</h5>
<input type="text" name="username_or_email"  />
<h5>Password:</h5>
<input type="password" name="password"/>
<input type="submit" value=""/>
</form>
<a href="register.php"><img border=0 src="images/register_btn.jpg" width="341" height="59" alt="Register" class="MrgnLft25" /></a>
</div>

</div>

</div>


<?PHP } ?></body>
</html>
