<?PHP
 require_once('lib/Members.class.php');
 Members::SessionAllowLogin();
 
 if (isset($_POST['username_or_email'])){
    $logged_in = Members::Login($_POST['username_or_email'], $_POST['password']);
	echo mysql_error();
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
<?PHP if (!empty($_GET['mobile'])){ ?>
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
<?PHP if (!empty($_GET['mobile'])){ ?>
<img src="images/time_log_clipboard.png" />
<?PHP
   if ($logged_in){
      echo 'Welcome '.$logged_in['first_name'];
   }else{
      echo 'Not logged in';
   }
?>
<form name="frmLogin" action="index.php" method="POST">
<?PHP
  if (isset($_GET['goto'])){ ?><input type="hidden" name="goto" value="<?=htmlentities($_GET['goto'])?>"/><?PHP }
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
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean ut purus at elit malesuada euismod. Quisque quis lacus a nulla gravida sagittis. Praesent dapibus purus a lacus placerat et feugiat felis consequat. Quisque felis mi, egestas at rutrum vitae, lacinia ac nibh. Aliquam sollicitudin sapien quis mauris consequat ac consequat tortor ornare. </p><p>Praesent justo dolor, ornare sed ornare at, laoreet at sem. Donec mollis, ligula vitae varius placerat, odio ante consequat ante, ut euismod dolor enim at erat. Duis dignissim ultricies risus id tristique. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nullam a quam ac risus tristique fringilla iaculis ut velit.
</p>
</div>

<div class="Col2">

<form id="LoginForm" action="index.php" method="POST">
<h5>Username:</h5>
<input type="text" name="username_or_email"  />
<h5>Password:</h5>
<input type="password" name="password"/>
<input type="submit" />
</form>
<a href="register.php"><img src="images/register_btn.jpg" width="341" height="59" alt="Register" class="MrgnLft25" /></a>
</div>

</div>

</div>


<?PHP } ?></body>
</html>
