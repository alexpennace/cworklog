<?PHP
 require_once('lib/Members.class.php');
 require_once('lib/Site.class.php');
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Contractor's Work Log</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<?PHP if (!empty($_REQUEST['mobile'])){ ?>
<style>
form label input { width: 90%; }
img { width: 16px; }
body { font-size: 10px; }
a { color: green; }
input { font-size: 10px; }
</style>
<?PHP } ?>
</head>
<body>
<?PHP if (!empty($_REQUEST['mobile'])){ ?>
<img src="images/time_log_clipboard.png" />Login below or <a href="register.php">Register free</a>
<form name="frmLogin" action="index.php" method="POST">
<input type="hidden" name="mobile" value="1" />
<?PHP
  if (isset($_REQUEST['goto'])){ ?><input type="hidden" name="goto" value="<?=htmlentities($_REQUEST['goto'])?>"/><?PHP }
  if (!empty($ERROR_MSG)){ 
    echo '<div style="padding: 2px; margin: 3px; border: 1px dashed black;">'.$ERROR_MSG.'</div>';
  }
?>
<label>Username/Email <input type="text" name="username_or_email" value="<?=isset($_REQUEST['username_or_email']) ? htmlentities($_REQUEST['username_or_email']) : ''?>"/></label>
<br />
<label>Password <input type="password" name="password" value="<?=isset($_POST['pw'])?htmlentities($_POST['pw']):''?>"/>
<div style="text-align: center">
<input type="submit" value="Login" />
</div>
</form>
<?PHP 
//automatically log in if username and password is provided
if (!empty($_REQUEST['username_or_email']) && !empty($_POST['pw'])){ ?>
<script>
document.frmLogin.submit();
</script>
<?PHP } ?>

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
</p>
<p>
Look at the main screen showing all your work logs
<a href="images/screenshots/ss2013-01-03.png" target=_blank><img border=0 src="images/screenshots/ss2013-01-03_thumb.png" /></a>
<a href="images/screenshots/maccworklog_lg.png" target=_blank><img border=0 src="images/screenshots/maccworklog_sm.png" /></a>
</p>

<p>
<h4>Watch an example of how to edit your tracked time: </h4>
<br>
<iframe width="420" height="280" src="https://www.youtube.com/embed/Hl9zjlxUhT4?feature=player_detailpage" frameborder="0" allowfullscreen></iframe>
</p>
<p>
<h4>Why Contractor's Work Log?</h4>
    <ul>
    <li> <b>Features Include: </b></li>
    <li> - Keep track of all your clients</li>
    <li> - Easily click Start/Stop to track billable time</li>
    <li> - Pad your invoice if needed, despite the time worked</li>
    <li> - One-click PDF generated invoice</li>
    </ul>
<br>
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
<input type="text" name="username_or_email" tabindex=1/>
<h5>Password: <a align=right class="littletext link" href="lostpassword.php">Lost your password?</a> </h5>
<input type="password" name="password" tabindex=2/>
<input type="submit" value=""/>
</form>
<a href="register.php"><img border=0 src="images/register_btn.jpg" width="341" height="59" alt="Register" class="MrgnLft25" /></a>
</div>

</div>

</div>


<?PHP } ?>
<br>
<br>
<br>
<a href="https://github.com/relipse/cworklog"><img style="position: absolute; top: 0; left: 0; border: 0;" src="https://camo.githubusercontent.com/567c3a48d796e2fc06ea80409cc9dd82bf714434/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f6c6566745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_left_darkblue_121621.png"></a>
</body>
</html>
