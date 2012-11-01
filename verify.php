<?PHP
   $error = false;
   $success = false;
   if (isset($_GET['code']) && isset($_GET['email'])){
		$sql = "SELECT * FROM user WHERE verify_code = '%s' AND LOWER(email) = LOWER('%s')";
		$result = mysql_query(sprintf($sql, $_GET['code'], $_GET['email']));
		if ($result && $row = mysql_fetch_assoc($result)){
		    $verify_user = $row;
			$sql2 = "UPDATE user SET status = 1 WHERE id = %d";
			$result2 = mysql_query(sprintf($sql2, $verify_user['id']));
			if ($result2){
			   $success = 'Thank you for verifying your email address, your account has been updated';
			}else{
			   $error = 'There was a problem verifying your account, please try again later';
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
<html>
<head>
<title>Verify Code</title>
</head>
<body>
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