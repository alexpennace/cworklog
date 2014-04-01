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
   
   
   function time2str($ts)
   {
       if(!ctype_digit($ts))
           $ts = strtotime($ts);

       $diff = time() - $ts;
       if($diff == 0)
           return 'now';
       elseif($diff > 0)
       {
           $day_diff = floor($diff / 86400);
           if($day_diff == 0)
           {
               if($diff < 60) return 'just now';
               if($diff < 120) return '1 minute ago';
               if($diff < 3600) return floor($diff / 60) . ' minutes ago';
               if($diff < 7200) return '1 hour ago';
               if($diff < 86400) return floor($diff / 3600) . ' hours ago';
           }
           if($day_diff == 1) return 'Yesterday';
           if($day_diff < 7) return $day_diff . ' days ago';
           if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
           if($day_diff < 60) return 'last month';
           return date('F Y', $ts);
       }
       else
       {
           $diff = abs($diff);
           $day_diff = floor($diff / 86400);
           if($day_diff == 0)
           {
               if($diff < 120) return 'in a minute';
               if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
               if($diff < 7200) return 'in an hour';
               if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
           }
           if($day_diff == 1) return 'Tomorrow';
           if($day_diff < 4) return date('l', $ts);
           if($day_diff < 7 + (7 - date('w'))) return 'next week';
           if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
           if(date('n', $ts) == date('n') + 1) return 'next month';
           return date('F Y', $ts);
       }
   }   

  /**
   * Quickly grab a $_GET variable returning a default value if not set
   */
  function GT($key, $default = null){
    return isset($_GET[$key]) ? $_GET[$key] : $default;
  }

 
 /**
  * Quickly die with a json encoded message
  * @example
  * jsdie('Error logging in');
  */
  function jsdie($msg, $key='error', $a = array()){
    $a[$key] = $msg;
    $js = json_encode($a);
    die($js);
  }
