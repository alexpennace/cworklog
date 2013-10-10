<?PHP 
  require_once(dirname(__FILE__).'/lib/db.inc.php');
  require_once(dirname(__FILE__).'/lib/Members.class.php');
  require_once(dirname(__FILE__).'/lib/Site.class.php');
  require_once(dirname(__FILE__).'/lib/CWLTimeDetails.class.php');
  Members::SessionForceLogin();

?>
<style>
.curday{
  font-size: 2em;
  font-weight: bold;
}

</style>

<?PHP
  
  $timeDetails = new CWLTimeDetails($_SESSION['user_id']);
  
  
  $month = $_GET['month'];
  $year = isset($_GET['year']) ? $_GET['year'] : '2013';
  
  $first_of_month = $month.' 1, '.$year;
  
  $last_day_of_month = date('t', strtotime($first_of_month));
  
  $last_date_of_month = $month.' '.$last_day_of_month.', '.$year;
  
  echo $month.' '.$year.' ';
  echo '<br>';
  echo $first_of_month.' - '.$last_date_of_month;
  echo '<br>';
  $month_timelogs = $timeDetails->getAllTimeLogsBetween($first_of_month, $last_date_of_month);
   
   

   
   function performExtraTimeLogCalculations(&$timelogs, &$total_rate, &$total_seconds, &$total_dollars){
      foreach($timelogs as &$timelog){
            $total_rate += $timelog['rate'];
            
            $timelog['_seconds_'] = strtotime($timelog['stop_time']) - strtotime($timelog['start_time']);
            
            $timelog['_HH:MM:SS_'] = work_log::sec2hms($timelog['_seconds_']);
            $total_seconds += $timelog['_seconds_'];
            
            $timelog['_hours_'] = $timelog['_seconds_'] / 60 / 60;
            $timelog['_hours_rnd_'] = round($timelog['_hours_'], 1);
            $timelog['_dollars_'] = round($timelog['rate']*$timelog['_hours_'], 3);
            $total_dollars += $timelog['_dollars_'];
      }unset($timelog);   
   }
   
   $total_seconds = 0;
   $total_dollars = 0;
   $total_rate = 0;  
   performExtraTimeLogCalculations($month_timelogs, $total_rate, $total_seconds, $total_dollars);
   if (count($month_timelogs) == 0){
      $avg_rate = 0;
   }else{
      $avg_rate = $total_rate / count($month_timelogs);
   }
   
   echo 'Total Hours (hh:mm:ss) '.work_log::sec2hms($total_seconds).' @ avg $'.number_format($avg_rate, 2).'/hr =&gt; $'.number_format($total_dollars, 2);
 
   $total_month_seconds = 0;
   $total_month_dollars = 0;
   $total_month_rate = 0; 
   $total_month_timelogs = 0;   
   for ($m = 1; $m <= $last_day_of_month; ++$m){
      $curday = $month." $m, ".$year;
      echo '<div class="curday">'.date('l M j, Y', strtotime($curday)).'</div>';
      $day_timelogs = $timeDetails->getAllTimeLogsBetween($curday, $curday);
      
      
      $total_seconds = 0;
      $total_dollars = 0;
      $total_rate = 0;  
      performExtraTimeLogCalculations($day_timelogs, $total_rate, $total_seconds, $total_dollars);
      $avg_rate = count($day_timelogs) > 0 ? $total_rate / count($day_timelogs) : 0;
      
      
      $total_month_seconds += $total_seconds;
      $total_month_dollars += $total_dollars;
      $total_month_rate += $total_rate;
      $total_month_timelogs += count($day_timelogs);
      
      
      echo array2table($day_timelogs);
      echo 'Total Hours (hh:mm:ss) '.work_log::sec2hms($total_seconds).' @ avg $'.number_format($avg_rate, 2).'/hr =&gt; $'.number_format($total_dollars, 2);
      echo '<br>';
   }
   
   $total_month_avg_rate = $total_month_timelogs > 0 ? $total_month_rate / $total_month_timelogs : 0;
   echo '<h2>Monthly Summary</h2>';
   echo 'Total Hours (hh:mm:ss) '.work_log::sec2hms($total_month_seconds).' @ avg $'.number_format($total_month_avg_rate, 2).'/hr =&gt; $'.number_format($total_month_dollars, 2);

   
   //array2table($month_timelogs);
  
  /**
 * Translate a result array into a HTML table
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.3.2
 * @link        http://aidanlister.com/2004/04/converting-arrays-to-human-readable-tables/
 * @param       array  $array      The result (numericaly keyed, associative inner) array.
 * @param       bool   $recursive  Recursively generate tables for multi-dimensional arrays
 * @param       string $null       String to output for blank cells
 */
function array2table($array, $recursive = false, $null = '&nbsp;')
{
    // Sanity check
    if (empty($array) || !is_array($array)) {
        return false;
    }
 
    if (!isset($array[0]) || !is_array($array[0])) {
        $array = array($array);
    }
 
    // Start the table
    $table = "<table>\n";
 
    // The header
    $table .= "\t<tr>";
    // Take the keys from the first row as the headings
    foreach (array_keys($array[0]) as $heading) {
        $table .= '<th>' . $heading . '</th>';
    }
    $table .= "</tr>\n";
 
    // The body
    foreach ($array as $row) {
        $table .= "\t<tr>" ;
        foreach ($row as $cell) {
            $table .= '<td>';
 
            // Cast objects
            if (is_object($cell)) { $cell = (array) $cell; }
             
            if ($recursive === true && is_array($cell) && !empty($cell)) {
                // Recursive mode
                $table .= "\n" . array2table($cell, true, true) . "\n";
            } else {
                $table .= (strlen($cell) > 0) ?
                    htmlspecialchars((string) $cell) :
                    $null;
            }
 
            $table .= '</td>';
        }
 
        $table .= "</tr>\n";
    }
 
    $table .= '</table>';
    return $table;
}
?>