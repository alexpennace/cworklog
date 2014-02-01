<?PHP
   header('Content-type: application/json');
   require_once('lib/db.inc.php');
   require_once('lib/Members.class.php');
   require_once('lib/work_log.class.php');
   
   $entityBody = file_get_contents('php://input');
   $json = json_decode($entityBody);
   
   if (!Members::Login($json['u'], $json['p'])){
      die(json_encode(array('error'=> true, 'response'=>array('code'=>1, 'message'=>'Error logging in'))));
   }
   if (!empty($json['entries']) && is_array($json['entries'])){
      $successes = array();
      $fails = array();
      foreach($json['entries'] as $timelog){ 
         $wl = new work_log($json['work_log_id']);
         $wlrow = $wl->getRow();
         if ($wlrow['user_id'] != $_SESSION['user_id']){
            $fails[] = array('error'=> true, 'code'=> 3, 
                            'message'=>'You do not have permission to edit this work log',
                            'time_log'=> $timelog);
         }else if ($wlrow['locked']){
            $fails[] = array('error'=> true, 'code'=> 4, 
                            'message'=>'This work log is locked',
                            'time_log'=> $timelog);        
         }else{
            //potential success, lets insert the time log
            $sql = "INSERT INTO time_log (work_log_id, start_time, stop_time, notes) 
            VALUES (:wid, :start_time, :stop_time, :notes) 
            ON DUPLICATE KEY 
               UPDATE notes = VALUES(:notes2), 
                      stop_time = VALUES(:stop_time2)";
            $prep = $DBH->prepare($sql);
            $prep->bindValue(':wid', $json['work_log_id'], PDO::PARAM_INT);
            $prep->bindValue(':start_time', date('Y-m-d H:i:s', strtotime($timelog['start_time'])), PDO::PARAM_STRING);
            $prep->bindValue(':stop_time', date('Y-m-d H:i:s', strtotime($timelog['stop_time'])), PDO::PARAM_STRING);
            $prep->bindValue(':stop_time2', date('Y-m-d H:i:s', strtotime($timelog['stop_time'])), PDO::PARAM_STRING);
            $prep->bindValue(':notes', $timelog['notes'], PDO::PARAM_STRING);
            $prep->bindValue(':notes2', $timelog['notes'], PDO::PARAM_STRING);
            
            if ($prep->execute()){
               $successes[] = array('error'=>false, 'time_log'=>$timelog);
            }else{
               $fails[] = array('error'=>true, 'code'=>5, 'message'=>'Database server error, could not insert/update', 'time_log'=>$timelog);
            }
            
         }
      }
      
      die(json_encode(array('success_count'=> count($successes), 'successes'=>$successes,
                            'fail_count'=> count($fails), 'fails'=>$fails)));
   }else{
      die(json_encode(array('error'=> true, 
         'response'=> array('code'=>3, 'message'=>'Time log entries are empty or invalid'))));
   }