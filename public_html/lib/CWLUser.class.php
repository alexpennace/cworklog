<?PHP
require_once(dirname(__FILE__).'/db.inc.php');
require_once(dirname(__FILE__).'/work_log.class.php');

class CWLUser{
   private $id = false;
   private $user_plan_row = false;
   public function __construct($uid){
       global $DBH;
       $prep = $DBH->prepare('SELECT user.id AS user_id, user.*, plan.* FROM user LEFT JOIN plan ON plan.id= user.plan_id WHERE user.id = :uid LIMIT 1');
       $prep->execute(array(':uid'=>$uid));
       $this->user_plan_row = $prep->fetch(PDO::FETCH_ASSOC);
       if ($this->user_plan_row){
         $this->id = $this->user_plan_row['user_id'];
       }else{
          $this->id = false;
          $this->user_plan_row = false;
       }
   }
   public function isValid(){ return $this->id && is_array($this->user_plan_row); }
   public function getUser(){ return $this->user_plan_row; }
   public function getPlan(){ return $this->user_plan_row; }
   
   public function setPlan($plan_id_or_shortname){
       global $DBH;
       if (!is_numeric($plan_id_or_shortname)){
          require_once(dirname(__FILE__).'/CWLPlans.class.php');
          $plan = CWLPlans::PlanFromShortname($plan_id_or_shortname);
          if ($plan){
             $plan_id = $plan['id'];
          }else{
             return false; //invalid plan shortname
          }
       }else{
          $plan_id = $plan_id_or_shortname;
       }
       if ($this->user_plan_row['plan_id'] == $plan_id){ return true; }
       
       $prep = $DBH->prepare('UPDATE user SET plan_id = :plan_id WHERE id = :user_id');
       return $prep->execute(array(':plan_id'=>$plan_id, ':user_id'=>$this->id));
   }

   public function planExceedsActiveWorkLogs($almost_exceeds = false){
     $num = $this->countUnlockedWorkLogs();
     $max = $this->user_plan_row['max_active_worklogs'];
     if ($almost_exceeds){
         return ($num >= $max);
     }else {
        return ($num > $max);
     }   
   }
   
   public function planExceedsClients($almost_exceeds = false){
     $num = $this->countClients();
     $max = $this->user_plan_row['max_clients'];
     if ($almost_exceeds){
         return ($num >= $max);
     }else {
        return ($num > $max);
     }        
   }
   
   public function countUnlockedWorkLogs(){
     global $DBH;
     $prep = $DBH->prepare('SELECT COUNT(*) FROM work_log WHERE locked = 0 AND user_id = :uid');
     $prep->execute(array(':uid'=> $this->id));
     return $prep->fetchColumn();
   }
   
   public function countSubscribedReferrals(){
     global $DBH;
     $prep = $DBH->prepare('SELECT COUNT(*) FROM user JOIN plan ON user.plan_id = plan.id WHERE referred_by_id = :uid AND plan.cost_monthly > 0 AND stripe_id IS NOT NULL');
     $prep->execute(array(':uid'=> $this->id));
     return $prep->fetchColumn();  
   }
   
   public function countClients(){
     global $DBH;
     $prep = $DBH->prepare('SELECT COUNT(*) FROM company WHERE user_id = :uid');
     $prep->execute(array(':uid'=> $this->id));
     return $prep->fetchColumn();    
   }
   
}
