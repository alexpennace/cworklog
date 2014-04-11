<?PHP
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   Members::SessionForceLogin();
   require_once(dirname(__FILE__).'/lib/CWLUser.class.php');
   $cwluser = new CWLUser($_SESSION['user_id']);
   if (!$cwluser->isValid()){
      die(json_encode(array('error'=>'User not found error')));
   }
   require_once(dirname(__FILE__).'/lib/work_log.class.php');

   function update_worklogs($field, $value, $csv_worklog_ids){
      global $DBH;
      $ids = explode(',', $csv_worklog_ids);
      
      $wl_rows = array();   
      foreach($ids as $id){
         try{
           $wl = new work_log($id);
           $prep = $DBH->prepare('UPDATE work_log SET '.$field.' = :value WHERE id = :wid');
           if (!$prep->execute(array(':value'=>$value, ':wid'=>$id))){
              continue;
           }
           $wl = new work_log($id);
           $wl_rows[] = $wl->getRow();
         }catch(Exception $e){
           continue;
         }
      }   
      return $wl_rows;
   }
   
   $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
   $csv_worklog_ids = isset($_REQUEST['csv_worklog_ids']) ? $_REQUEST['csv_worklog_ids'] : false;
   $csv_timelog_ids = isset($_REQUEST['csv_timelog_ids']) ? $_REQUEST['csv_timelog_ids'] : false;
   
if ($action == 'lock-worklogs' && !empty($csv_worklog_ids)){
   $wl_rows = update_worklogs('locked', true, $csv_worklog_ids);
   die(json_encode(array('error'=>empty($wl_rows), 'work_logs'=>$wl_rows)));
}else if ($action == 'unlock-worklogs' && !empty($csv_worklog_ids)){
   $wl_rows = update_worklogs('locked', false, $csv_worklog_ids);
   die(json_encode(array('error'=>empty($wl_rows), 'work_logs'=>$wl_rows)));
}else if ($action == 'invoice-date-today' && !empty($csv_worklog_ids)){
   $wl_rows = update_worklogs('date_billed', date('Y-m-d h:i:s', strtotime('now')), $csv_worklog_ids);
   die(json_encode(array('error'=>empty($wl_rows), 'work_logs'=>$wl_rows)));
}else if ($action == 'mark-paid-today' && !empty($csv_worklog_ids)){
   $wl_rows = update_worklogs('date_paid', date('Y-m-d h:i:s', strtotime('now')), $csv_worklog_ids);
   die(json_encode(array('error'=>empty($wl_rows), 'work_logs'=>$wl_rows)));
}else if ($action == 'move_timelogs_to_worklog' && !empty($csv_timelog_ids) && !empty($_REQUEST['worklog_id'])){
   try{
       $wl = new work_log($_REQUEST['worklog_id']);
       $wlto_row = $wl->getRow();
       if ($wlto_row['locked']){
          die(json_encode(array('error'=>'This work log is locked. Unlock and then try move again')));
       }
       
       $timelog_ids = explode(',',$csv_timelog_ids);
       $work_logs_verified = array($wlto_row['id']=>true);
       
       $success_count = 0;
       $fail_count = 0;
       $failed = array();
       foreach($timelog_ids as $tid){
             $prep = $DBH->prepare('SELECT * FROM time_log WHERE id = :tid');
             $prep->execute(array(':tid'=>$tid));
             $timelog = $prep->fetch(PDO::FETCH_ASSOC);
             
             //CHECK IF THE WORKLOG MOVING THIS TIME LOG FROM IS LOCKED OR NOT
             if (empty($work_logs_verified[$timelog['work_log_id']])){
                try{ 
                   $wlfrom = new work_log($timelog['work_log_id']);
                   $wlfrom_row = $wlfrom->getRow();
                   
                   if ($wlfrom_row['locked']){
                      throw new Exception('Cannot move from a locked work log');
                   }else{
                      $work_logs_verified[$timelog['work_log_id']] = true;
                   }
                }
                catch(Exception $e){
                   $fail_count++;
                   $failed[] = array('tid'=>$tid,'message'=>$e->getMessage(), 'time_log_id'=>$tid, 'time_log'=>$timelog, 'from_work_log_id'=>$timelog['work_log_id'], 'to_work_log_id'=>$wlto_row['id']);
                   continue; //move on to next timelog
                }
             }//end if work log has not been checked yet
             
             //FINALLY MOVE THE TIME LOG
             $prep = $DBH->prepare('UPDATE time_log SET work_log_id = :wl_id 
                                    WHERE id = :time_log_id');
             if ($prep->execute(array(':wl_id'=>$wlto_row['id'], ':time_log_id'=> $timelog['id']))){
                $success_count++;
             }else{
                   $fail_count++;
                   $failed[] = array('time_log_id'=>$tid, 'time_log'=>$timelog, 'from_work_log_id'=>$timelog['work_log_id'], 'to_work_log_id'=>$wlto_row['id']);
             }
       }
       
       die(json_encode(array('error'=> $success_count == 0, 'warning'=> $fail_count > 0, 'errors'=>$failed)));
    
   }catch(Exception $e){
     die(json_encode(array('error'=>'Invalid Permissions')));
   }

}

die(json_encode(array('error'=>'Invalid Action')));