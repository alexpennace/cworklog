<?PHP
	function random_string( $length ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	
		$size = strlen( $chars );
		$str = '';
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}
		return $str;
	}
	
	function username_check($username) {
		   $error = false;
		   if (!preg_match('/\\A(?:[a-z]([a-z]|\\d)+)\\z/i', $username)){
			  $error = 'Username must start with a character and only contain characters and numbers between 4 and 15 characters';
			  $error_field = 'username';   
		   }   
		   else if (strlen($username) < 4){
			  $error = 'Username is too short (must be between 4 and 15 characters)';
			  $error_field = 'username';   
		   }
		   else if (strlen($username) > 15){
			  $error = 'Username is too long (must be between 4 and 15 characters)';
			  $error_field = 'username';
		   }
		   return $error;
	}
?>