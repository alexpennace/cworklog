<?PHP
require_once(dirname(__FILE__).'/db.inc.php');
require_once(dirname(__FILE__).'/work_log.class.php');
require_once(dirname(__FILE__).'/CWLUser.class.php');

class CWLTimeDetails{
   private $user_id = null;
   private $cwl_user = null;
   public function __construct($user_id){
      $this->cwl_user = new CWLUser($user_id);
      if ($this->cwl_user->isValid()){
         $this->user_id = $user_id;
      }
   }
   
   
   public function getAllTimeLogsBetween($start_day, $stop_day, $start_time = '00:00:00', $stop_time = '23:59:59'){
       global $DBH;
       
       $start = date("Y-m-d", strtotime($start_day));
       $start .= ' '.date("H:i:s", strtotime($start_time));       
       
       
       $stop = date("Y-m-d", strtotime($stop_day));
       $stop .= ' '.date("H:i:s", strtotime($stop_time));
       
       
       
       
       $prep = $DBH->prepare(
           "SELECT * 
            FROM time_log
            JOIN work_log ON time_log.work_log_id = work_log.id
            WHERE work_log.user_id = :user_id
            AND start_time >=  :start
            AND stop_time <=  :stop
            ORDER BY stop_time ASC");
       $prep->execute(array(':user_id'=>$this->user_id, ':start'=>$start, ':stop'=>$stop)));
       $time_logs = $prep->fetch(PDO::FETCH_ASSOC);
       return $time_logs;
   }
   
   
}//end CWLTimeDetails class
