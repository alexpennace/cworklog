<?PHP
   header('Content-type: application/json');
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   if (!Members::Login($_REQUEST['u'], $_REQUEST['p'])){
      die(json_encode(array('error'=> true, 'response'=>array('code'=>1, 'message'=>'Error logging in'))));
   }
   require_once(dirname(__FILE__).'/lib/work_log.class.php');
   //allow the user to get a specific work log from his organization
   if (isset($_REQUEST['wid'])){
      $wl = new work_log($_REQUEST['wid']);
      $wlrow = $wl->getRow();
      if ($wlrow['user_id'] != $_SESSION['user_id']){
         die(json_encode(array('error'=>true, 
                            'response'=>array('code'=>3, 'message'=>'You do not have permission to view this work log'))));
      }else{     
         //also fetch the time log
         $wlrow['_time_log_'] = $wl->fetchTimeLog();
         die(json_encode(array('error'=>false, 'response'=>$wlrow)));
      }
   }
     //get a list of work logs that are not locked and unpaid so we can keep working on it
  $sql = "SELECT work_log.id, work_log.rate, work_log.title, work_log.description, 
               company.id AS company_id, company.name AS company_name 
            FROM work_log JOIN company WHERE work_log.locked = 0 
             AND (work_log.date_billed IS NULL OR work_log.date_billed = '0000-00-00') 
             AND (work_log.date_paid IS NULL OR work_log.date_paid = '0000-00-00') 
             AND work_log.company_id = company.id AND work_log.user_id = ".(int)$_SESSION['user_id'].
	  " ORDER BY name ASC";
   $prep = $DBH->prepare($sql);
   $prep->execute();
   if (!$result){
      die(json_encode(array('error'=> true, 'response'=>array('code'=>2,'message'=>'Server error, try again later')))); 
   }
   $work_log_rows = array();
   while($row = $prep->fetch($result)){ 
      $wl = new work_log($row['id']);
      $work_log_rows[] = $wl->getRow();
   }

 die(json_encode(array('error'=>false, 'response'=> array(
     'user'=>array('api_key'=>$_SESSION['user_row']['api_key'],'id'=>$_SESSION['user_id']), 
     'work_logs'=>$work_log_rows)))
    );
