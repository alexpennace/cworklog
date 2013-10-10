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
   
   private static function GetPublicSQLTimeFields(){
       return ' work_log.id AS work_log_id, work_log.user_id, company_id, company.name, work_log.title, rate, start_time, stop_time, date_billed, date_paid, locked ';
   }
   
    public function getAllTimeLogs(){
       global $DBH;
       
       

       $prep = $DBH->prepare(
           "SELECT ".self::GetPublicSQLTimeFields()."
            FROM time_log
            JOIN work_log ON time_log.work_log_id = work_log.id
            JOIN company ON work_log.company_id = company.id
            WHERE work_log.user_id = :user_id
            ORDER BY stop_time ASC");
       $prep->execute(array(':user_id'=>$this->user_id));
       $time_logs = $prep->fetchAll(PDO::FETCH_ASSOC);
       return $time_logs;
   }
   

   
   public function getAllTimeLogsBetween($start_day, $stop_day, $start_time = '00:00:00', $stop_time = '23:59:59'){
       global $DBH;
       
       $start = date("Y-m-d", strtotime($start_day));
       $start .= ' '.date("H:i:s", strtotime($start_time));       
       
       
       $stop = date("Y-m-d", strtotime($stop_day));
       $stop .= ' '.date("H:i:s", strtotime($stop_time));
       
       //echo $start.' - '.$stop;
       
       $sql = "SELECT ".self::GetPublicSQLTimeFields()." 
            FROM time_log
            JOIN work_log ON time_log.work_log_id = work_log.id
            JOIN company ON work_log.company_id = company.id
            WHERE work_log.user_id = :user_id
            AND start_time >=  :start
            AND stop_time <=  :stop
            ORDER BY stop_time ASC";
       //echo $sql;
       
       $prep = $DBH->prepare($sql);
       $prep->execute(array(':user_id'=>$this->user_id, ':start'=>$start, ':stop'=>$stop));
       $time_logs = $prep->fetchAll(PDO::FETCH_ASSOC);
       return $time_logs;
   }
   
   
}//end CWLTimeDetails class
