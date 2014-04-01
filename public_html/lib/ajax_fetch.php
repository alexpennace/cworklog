<?php

require_once(dirname(__FILE__).'/db.inc.php');
require_once(dirname(__FILE__).'/Members.class.php');
require_once(dirname(__FILE__).'/misc.inc.php');
require_once(__DIR__.'/CWLTimeDetails.class.php');

Members::SessionForceLogin(true) or die(json_encode(array('error'=>'Not logged in.')));

$timeDetails = new CWLTimeDetails($_SESSION['user_id']);

if (!($f = GT('f'))){
	jsdie('No function f= given');
}

if ($f == 'running_time_log'){
   $time_log = $timeDetails->getMostRecentRunningTimeLog();
   jsdie($time_log, 'time_log');
}

jsdie($f.' invalid for function');
