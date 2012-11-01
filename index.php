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

?><!DOCTYPE html>
<html>
<head>
<title>Contractor's Work Log</title>
<?PHP if (!empty($_GET['mobile'])){ ?>
<style>
form label input { width: 100%; }
img { width: 16px; }
body { font-size: 10px; }
input { font-size: 10px; }
</style>
<?PHP } ?>
</head>
<body>
<?PHP
	//Members::MenuBar();
?>
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
</body>
</html>
