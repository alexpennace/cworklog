<?PHP
require_once(dirname(__FILE__).'/db.inc.php');

class CWLPlans{
   public static function GetActivePlans(){
       global $DBH;
       $prep = $DBH->prepare('SELECT * FROM plan WHERE active=1 ORDER BY cost_monthly ASC');
       $prep->execute();
       return $prep->fetchAll(PDO::FETCH_ASSOC);
   }
   
   public static function PlanFromShortname($plan_shortname){
       global $DBH;
       $prep = $DBH->prepare('SELECT * FROM plan WHERE active = 1 AND LOWER(shortname) = LOWER(:shortname) LIMIT 1');
       $prep->execute(array(':shortname'=>$plan_shortname));
       return $prep->fetch();
   }
   
}