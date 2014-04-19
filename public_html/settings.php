<?PHP
/**
 *  This file helps manage Membership and logins
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim) 
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License.
 * 
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *  
 *   You should have received a copy of the GNU General Public License
 *   along with this program (gpl.txt).  If not, see <http://www.gnu.org/licenses/>.
 */
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   
require_once(dirname(__FILE__).'/lib/Members.class.php');
require_once(dirname(__FILE__).'/lib/misc.inc.php');
require_once(dirname(__FILE__).'/lib/Site.class.php');
require_once(dirname(__FILE__).'/lib/work_log.class.php');
require_once(dirname(__FILE__).'/lib/CWLUser.class.php');
require_once(dirname(__FILE__).'/lib/CWLPlans.class.php');
require_once(dirname(__FILE__).'/lib/stripelib/Stripe.php');

if (Site::cfg('stripe_apikey')){
   Stripe::setApiKey(Site::cfg('stripe_apikey'));
}

Members::SessionForceLogin();
$cwluser = new CWLUser($_SESSION['user_id']);
$user = $cwluser->getUser();

if (isset($_POST)){
    $success = false;
    $error = false;
    $error_field = false;
    
    if (Site::cfg('stripe_apikey') && isset($_POST['plan'])){
        
         $payable_plans = array('betastarter','betapro');
         if (in_array($_POST['plan'], $payable_plans)){
            $newplan = CWLPlans::PlanFromShortname($_POST['plan']);
            
            if ($cwluser->countUnlockedWorkLogs() > $newplan['max_active_worklogs'] || 
                $cwluser->countClients() > $newplan['max_clients']){
                
                 $error = 'You exceed '.$newplan['shortname'].' plan, and therefore must choose a bigger plan';
                 
             }else{ //plan works
            
               // Set your secret key: remember to change this to your live secret key in production
               // See your keys here https://manage.stripe.com/account
               

               // Get the credit card details submitted by the form
               $token = $_POST['stripeToken'];

             
                 $customer = Stripe_Customer::create(array(
                   "card" => $token,
                   "plan" => $_POST['plan'],
                   "email" => $user['email'])
                 );
               
               $sql = "UPDATE  `user` SET  
                    `stripe_id` =  :stripe_id 
                     WHERE  `user`.`id` = :user_id LIMIT 1";
               $prep = $DBH->prepare($sql);
               $set_stripe_id = $prep->execute(array('stripe_id'=>$customer['id'], 'user_id'=>$user['user_id']));
    
               if ($set_stripe_id && $cwluser->setPlan($_POST['plan'])){
                  $success = 'Success changing plans. Happy freelancing :)';
                  $cwluser = new CWLUser($_SESSION['user_id']);
               }else{
                  $error = 'There was an error changing plans.';
               } 
            
            }
         }else{ //free plan
           $oldplan = $cwluser->getPlan();
           if ($cwluser->setPlan($_POST['plan'])){
           
              if (!empty($user['stripe_id'])){
                $cu = Stripe_Customer::retrieve($user['stripe_id']);
                $cu->cancelSubscription();
                $success = 'Success cancelling '.$oldplan['shortname'].' plan. Cheers. :)';
              }else{
                 $success = 'Success changing plans. Happy freelancing :)';
              }
              
              $cwluser = new CWLUser($_SESSION['user_id']);
              //TODO: unsubscribe the user
           }else{
              $error = 'There was an error changing plans. Is it still available?';
           }
         }
         
    }
    //are we changing the password?
    else if (isset($_POST['pw_new']) && isset($_POST['pw_new_confirm'])){
      if (strlen($_POST['pw_new']) < 4){
              $error = 'New Password is too short (must be 4 to 15 characters)';
              $error_field = 'pw_new';
      }
      else if (strlen($_POST['pw_new']) > 15){
              $error = 'New Password is too long (must be 4 to 15 characters)';
              $error_field = 'pw_new';
      } 
      if ($_POST['pw_new'] != $_POST['pw_new_confirm']){
         $error = 'New password does not match with confirmed password';
         $error_field ='pw_new';
      }else{ //passwords match, do more error checking

          $sql = "SELECT * FROM user WHERE id = :id AND MD5(:password) = password";
          $exec_ary = array('id'=>$_SESSION['user_id'], 'password'=> $_POST['pw_current']);
          $prep = $DBH->prepare($sql);
          $result = $prep->execute($exec_ary);
          if ($row = $prep->fetch()){
             //current password matches
             $sql = "UPDATE user SET password = MD5(:password) WHERE id = :id";
             $exec_ary = array('id'=>$_SESSION['user_id'], 'password'=> $_POST['pw_new']);
             $prep = $DBH->prepare($sql);
             $result = $prep->execute($exec_ary);
             if (!$result){
                $error = 'Your password could not be changed';
             }else{
                $success = 'Your password has been changed!';
             }
          }else{
             $error = 'Your current password is incorrect';
             $error_field = 'pw_current';
          }
      }
   }else if (isset($_POST['email_new'])){ 
          $sql = "SELECT * FROM user WHERE id = :id AND MD5(:password) = password";
          $exec_ary = array('id'=>$_SESSION['user_id'], 'password'=> $_POST['pw_current']);
          $prep = $DBH->prepare($sql);
          $result = $prep->execute($exec_ary);
          if ($row = $prep->fetch()){
             if (!filter_var($_POST['email_new'], FILTER_VALIDATE_EMAIL)){
                  $error = 'Email is not valid';
                  $error_field = 'email';
             }else{
                 $verify_code = random_string(25);
                 $sql = "UPDATE user SET verify_command = 'change_email', 
                                         verify_code = :verify_code, 
                                         verify_param = :email_new 
                         WHERE id = :id";
                 $prep = $DBH->prepare($sql);
                 $exec_ary = array('id'=>$_SESSION['user_id'], 'verify_code'=> $verify_code, 'email_new'=>$_POST['email_new']);
                 $result = $prep->execute($exec_ary);
                 if (!$result){
                    $error = 'Error performing email address change, try again later.';
                 }else{ //everything so far so good
                     list($mailer, $message, $logger) = cwl_email::setup(false);

                      $message->setSubject('New email confirmation - '.Site::cfg('title'));
                      $message->setBody("Please verify your new email address by clicking the link below\r\n".
                      Site::cfg('base_url').'/verify.php?code='.$verify_code.'&new_email='.urlencode($_POST['email_new']), 'text/html');
                       
                      $message->setTo(array($_POST['email_new']));
                  
                      $mailed = $mailer->send($message);
                      
                    if (!$mailed){
                       $error = 'Error sending mail to new email address';
                       $error_field = 'email_new';
                    }else{
                        $success = 'Your email address is pending a change';
                    }
                 }
             }//valid email
          }else{
             $error = 'Current password does not match';
             $error_field = 'pw_current';
          }
   
   }else if (isset($_POST['street'])){
      $stmt = $DBH->prepare('UPDATE user SET name = :name, phone = :phone, street = :street, street2 = :street2, city = :city, state = :state, zip = :zip, country = :country WHERE id = :id');
      
      $result = $stmt->execute(array('name'=>$_POST['name'], 
            'phone'=>$_POST['phone'], 'street'=>$_POST['street'], 
            'street2'=>$_POST['street2'], 
            'city'=>$_POST['city'], 
            'state'=>$_POST['state'], 
            'zip'=>$_POST['zip'], 
            'country'=>$_POST['country'],
            'id' => $_SESSION['user_id']));
      if ($result){
        $success = 'Successfully updated your address information, now try to generate an invoice';
      }else{
        $error = 'Error updating address information';
      }
   }
}//end if POSTing updates
?>

<!DOCTYPE html>

<html>
<head>
<title>Settings - <?=Site::cfg('title')?></title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<script type="text/javascript" src="js/work_log_shared.js"></script>
<link rel="stylesheet" type="text/css" href="css/progressbars.css" />
<link rel="stylesheet" type="text/css" href="css/settings.css" />
</head>
<body class="yui-skin-sam">
<?PHP Members::MenuBar(); ?>

<?PHP
   //grab latest user row from database
   $sql = "SELECT * FROM user WHERE id = ".(int)$_SESSION['user_id'];
   $prep = $DBH->prepare($sql);
$result = $prep->execute();
   $user_row = $prep->fetch();
   //update the session variables
   $_SESSION['user_row'] = $user_row;
?>
<script>
    $(function() {
        var $acc = $( "#accordion" );
        $acc.accordion({ active: 0, collapsible: true, height: '100px' });
        $('.ui-accordion-content').css('height', '');
    });
</script>

<?PHP
  if (!empty($success)){
     echo '<div class="success">'.$success.'</div>';
  }
  if (!empty($error)){
     echo '<div class="error">'.$error.'</div>';
  }
?>
<style>
#curplan td
{
  text-align:right;
}
#select_plan td{
   padding: 5px;
}

#tbl_curplan{ left: auto; right: auto; }
</style>
  <?PHP
    $curplan = $cwluser->getPlan();
    $num_clients = $cwluser->countClients();
    $num_worklogs = $cwluser->countUnlockedWorkLogs();
    if (Site::cfg('stripe_apikey') && !empty($user_row['stripe_id'])){
      try{
        $stripe_customer = Stripe_Customer::retrieve($user_row['stripe_id']);
         //echo $stripe_customer['subscription']['plan']['id'];
         $SHOW_CHANGE_PLANS = true;
      }catch(Stripe_ApiConnectionError $e){
         $SHOW_CHANGE_PLANS = false;
      }
    }
    else{
      $stripe_customer = false;
      $SHOW_CHANGE_PLANS = true;
    }
    
    
  ?>
<div id="accordion"  style="padding:0 1%;">
  <?PHP if ($SHOW_CHANGE_PLANS){ ?>
  <h3><strong>View/Upgrade Plan</strong></h3>
  <div>
  <table border=0 id="tbl_curplan">
  <tr><td><h3>Your Plan: </h3></td><td><h3><?=ucfirst($curplan['shortname'])?> Plan</h3></td></tr>
  <tr><td>Monthly Cost: </td><td><b>$<?=$curplan['cost_monthly']?></b>/mo</td></tr>
  
  <?PHP if ($stripe_customer && !empty($stripe_customer['subscription']['plan']['id'])){ ?>
  <tr><td>Subscription: </td><td>
     <b><?=$stripe_customer['subscription']['plan']['id'];?></b> , 
          Started: <b><?=date('M j, Y', $stripe_customer['subscription']['start'])?></b>  
          , Status: <b><?=$stripe_customer['subscription']['status']?></b>
          <?PHP if ($stripe_customer['subscription']['status'] == 'trialing'){ ?>
          , Trial Ends: <b><?=date('M j, Y', $stripe_customer['subscription']['trial_end'])?></b>
          <?PHP } ?>
      </td></tr>
  <tr><td>Card: </td><td><b><?=$stripe_customer['active_card']['last4']?> <?=$stripe_customer['active_card']['type']?></b></td></tr>
  <?PHP } ?>
  
  <tr><td valign=top>Clients: </td><td><b><?=$num_clients?></b>/<?=$curplan['max_clients']?><br>
  <?PHP 
  $client_percent = $num_clients/$curplan['max_clients']; 
  $client_percent *= 100;
  //echo $client_percent;
  if ($client_percent > 100){
     $client_percent = 100;
  }
  ?><div class="meter<?=$client_percent > 75 ? ' red' : $client_percent > 50 ? ' orange' : ''?>" style="width: 200px">
			<span style="width: <?=$client_percent?>%"></span>
   </div></td>
  </tr>
  <tr><td valign=top>Active Work Logs: </td><td><b><?=$num_worklogs?></b>/<?=$curplan['max_active_worklogs']?><br>
  <?PHP
  $activewl_percent = $num_worklogs/$curplan['max_active_worklogs']; 
  $activewl_percent *= 100;
  //echo $activewl_percent;
  if ($activewl_percent > 100){
     $activewl_percent = 100;
  }
  if ($activewl_percent > 75){
     $color = ' red';
  }else if ($activewl_percent > 50){
     $color = ' orange';
  }else{
     $color = '';
  }
  ?><div class="meter<?=$color?>" style="width: 200px">
			<span style="width: <?=$activewl_percent?>%"></span>
   </div>
  </td></tr>
  <tr><td>
  30 day free-trial: 
  </td><td><b><?=$curplan['trial_expired'] ? 'Completed' : 'In-progress'?></b></td>
  </tr>
  <tr><td valign=top>Plan Expires: </td><td><?PHP
   if (is_null($curplan['date_plan_expires'])){
      
       $str = 'Never';
       $expire_percent = 0; //76;
   }else{
      $stt_expires = strtotime($curplan['date_plan_expires']);
      
      
      $diff_expires = $stt_expires - time();
      
      $days_between = ($datediff/(60*60*24));
      
      $expire_percent  = $days_between / 365;
      
      $expire_percent *= 100;
      $expire_percent = 100 - $expire_percent;
      
      $str = date('D F j, Y, g:i a',$stt_expires).' (in '.$days_between.' days)';
   }
  ?>
  <b><?=$str?></b>
  <div class="meter<?=$expire_percent > 75 ? ' red' : $expire_percent > 50 ? ' orange' : ''?>" style="width: 200px">
			<span style="width: <?=$expire_percent?>%"></span>
  </div>
  </td></tr>
  </table>
  <?PHP 
  $WARNING_MSG = '';
  $exceeded = '';
  $need_to_upgrade = false;
  if ($num_clients >= $curplan['max_clients']){ 
    $exceeded = ($num_clients == $curplan['max_clients'] ? 'tipped' : 'exceeded');
    $WARNING_MSG .= 'You have <b>'.$exceeded.'</b> your <b>client</b> threshold. ';
  }
  if ($exceeded == 'exceeded'){
     $need_to_upgrade = true;
  }
  if ($num_worklogs > $curplan['max_active_worklogs']){ 
     $exceeded = ($num_worklogs == $curplan['max_active_worklogs'] ? 'tipped' : 'exceeded');
     if (!empty($WARNING_MSG)){ $WARNING_MSG .= ' and you '; }else{
       $WARNING_MSG .= ' You ';
     }
     $WARNING_MSG .= 'have <b>'.$exceeded.'</b> your <b>active work log</b> threshold.'; 
  } 
  if ($exceeded == 'exceeded'){
     $need_to_upgrade = true;
  }  
  if (!empty($WARNING_MSG)){
  ?>
  <div class="error">
   <?=$WARNING_MSG?>
   
   <?PHP if ($need_to_upgrade){ ?>
   <b>Please choose an upgrade plan.</b>
   <?PHP }else{ ?>
   It might be a good time to upgrade.
   <?PHP } ?>
  </div><?PHP 
  } 
  ?>
  <?PHP
     $plans = CWLPlans::GetActivePlans();
  ?>
  <br><br>
  
  <script type="text/javascript" src="https://js.stripe.com/v1/"></script>
 <script type="text/javascript">
            // this identifies your website in the createToken call below
            Stripe.setPublishableKey('pk_test_ycvuBYiFki458hklivGa6EQx');
 
            function stripeResponseHandler(status, response) {
                if (response.error) {
                    // re-enable the submit button
                    $('.submit-button').removeAttr("disabled");
                    // show the errors on the form
                    $(".payment-errors").html(response.error.message);
                } else {
                    var form$ = $("#frmChangeToPayPlan");
                    // token contains id, last4, and card type
                    var token = response['id'];
                    // insert the token into the form so it gets submitted to the server
                    form$.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
                    // and submit
                    form$.get(0).submit();
                }
            }
 
            $(document).ready(function() {
                $("#frmChangeToPayPlan").submit(function(event) {
                    // disable the submit button to prevent repeated clicks
                    $('.submit-button').attr("disabled", "disabled");
 
                    // createToken returns immediately - the supplied callback submits the form if there are no errors
                    Stripe.createToken({
                        number: $('.card-number').val(),
                        cvc: $('.card-cvc').val(),
                        exp_month: $('.card-expiry-month').val(),
                        exp_year: $('.card-expiry-year').val()
                    }, stripeResponseHandler);
                    return false; // submit from callback
                });
            });
        </script>  
  
   
  
  <table border=0 cellspacing=13 cellpadding=18 id="select_plan">
  <tr><?PHP
  foreach($plans as $p){
    ?><td align=center valign=top class="<?=$p['shortname']==$curplan['shortname']?'selected_plan':'nonselected_plan'?> <?=$p['shortname']?>">
       <label>
       <?PHP if ($p['shortname']==$curplan['shortname']){ ?>
          <div class="current_plan">current plan</div>
       <?PHP }else{ ?>
          <div class="change_plan">&nbsp;</div>
       <?PHP } ?>
       <div class="plan_name"><h2><?=$p['name']?><h2></div>
       <div class="plan_price"><b>$<?=$p['cost_monthly']?>/mo</b></div>
       <div class="plan_max_clients"><b><?=$p['max_clients']?></b> clients</div>
       <div class="plan_max_active_worklogs"><b><?=$p['max_active_worklogs']?></b> active work logs</div>   
       <div class="plan_developer_api"><b><?=$p['allow_api_key'] ? 'Developer API' : ''?></b></div>
       <div class="plan_radio">
       <input type="radio" name="plan" value="<?=$p['shortname']?>" <?PHP if ($curplan['shortname'] == $p['shortname']){ echo 'checked=checked'; } ?>>
       </label>
       </div>
  </td><?PHP
  }
  ?>
  </tr>
  </table>
  
  <script>
  $('input[type=radio][name=plan]').click(function(){
     if (this.value == 'free'){
        $('#frmChangeToPayPlan').hide();
        $('#frmChangeToPayPlan').get(0).plan.value = '';
        <?PHP if ($curplan['shortname'] != 'free') { ?>
        $('#frmFreePlan').show();
        <?PHP } ?>
     }else{
        $('#frmFreePlan').hide();
        if (this.value != <?=json_encode($curplan['shortname'])?>){
          $('#frmChangeToPayPlan').show();
          $('#frmChangeToPayPlan').get(0).plan.value = this.value;
          $('#plan_cc_info').show();
        }else{
          $('#frmChangeToPayPlan').hide();
          $('#frmChangeToPayPlan').get(0).plan.value = '';        
        }
     }
  });
  </script>
  <form class="settings" method="POST" id="frmFreePlan" style="display: none">
     <input type="hidden" name="plan" value="free" />
     <input type="submit" name="change_plan" value="Downgrade Plan"/>
  </form>
  <?php if (Site::cfg('stripe_apikey')){ ?>
  <form class="settings" method="POST" id="frmChangeToPayPlan" style="display: none">
  <input type="hidden" name="plan" value="" />
  
    <div id="plan_cc_info" style="display:none">
        <span class="payment-errors"><?= $error ?></span>
        <span class="payment-success"><?= $success ?></span>
        
        Please enter your credit card information to change plans.
        <h3>Credit Card Information</h3>
            <div class="form-row">
                <label>Card Number</label>
                <input type="text" size="20" autocomplete="off" class="card-number" />
            </div>
            <div class="form-row">
                <label>CVC</label>
                <input type="text" size="4" autocomplete="off" class="card-cvc" />
            </div>
            <div class="form-row">
                <label>Expiration (MM/YYYY)</label>
                <input type="text" size="2" class="card-expiry-month"/>
                <span> / </span>
                <input type="text" size="4" class="card-expiry-year"/>
            </div>
        
       
  </div>
  <input type="submit" name="change_plan" value="Change Plan"/>
  </form> 
  <?php }else{ ?>
          You may not upgrade plans at this time because the credit card system is disabled, please contact the administrator.
  <?php } ?>
  </div>
  <?PHP } ?>

  <?PHP 
  //DISABLE TIMEZONES FOR NOW UNTIL WE GET WORKING
  if (false){ 
  ?>
  <h3><strong>Update Time Zone</strong></h3>
  <div style="height: 55px;">
    <form class="settings" method="POST">
    <?PHP
       $timezones = array("Africa/Abidjan","Africa/Accra","Africa/Addis_Ababa","Africa/Algiers","Africa/Asmara","Africa/Asmera","Africa/Bamako","Africa/Bangui","Africa/Banjul","Africa/Bissau","Africa/Blantyre","Africa/Brazzaville","Africa/Bujumbura","Africa/Cairo","Africa/Casablanca","Africa/Ceuta","Africa/Conakry","Africa/Dakar","Africa/Dar_es_Salaam","Africa/Djibouti","Africa/Douala","Africa/El_Aaiun","Africa/Freetown","Africa/Gaborone","Africa/Harare","Africa/Johannesburg","Africa/Juba","Africa/Kampala","Africa/Khartoum","Africa/Kigali","Africa/Kinshasa","Africa/Lagos","Africa/Libreville","Africa/Lome","Africa/Luanda","Africa/Lubumbashi","Africa/Lusaka","Africa/Malabo","Africa/Maputo","Africa/Maseru","Africa/Mbabane","Africa/Mogadishu","Africa/Monrovia","Africa/Nairobi","Africa/Ndjamena","Africa/Niamey","Africa/Nouakchott","Africa/Ouagadougou","Africa/Porto-Novo","Africa/Sao_Tome","Africa/Timbuktu","Africa/Tripoli","Africa/Tunis","Africa/Windhoek","America/Adak","America/Anchorage","America/Anguilla","America/Antigua","America/Araguaina","America/Argentina/Buenos_Aires","America/Argentina/Catamarca","America/Argentina/ComodRivadavia","America/Argentina/Cordoba","America/Argentina/Jujuy","America/Argentina/La_Rioja","America/Argentina/Mendoza","America/Argentina/Rio_Gallegos","America/Argentina/Salta","America/Argentina/San_Juan","America/Argentina/San_Luis","America/Argentina/Tucuman","America/Argentina/Ushuaia","America/Aruba","America/Asuncion","America/Atikokan","America/Atka","America/Bahia","America/Bahia_Banderas","America/Barbados","America/Belem","America/Belize","America/Blanc-Sablon","America/Boa_Vista","America/Bogota","America/Boise","America/Buenos_Aires","America/Cambridge_Bay","America/Campo_Grande","America/Cancun","America/Caracas","America/Catamarca","America/Cayenne","America/Cayman","America/Chicago","America/Chihuahua","America/Coral_Harbour","America/Cordoba","America/Costa_Rica","America/Creston","America/Cuiaba","America/Curacao","America/Danmarkshavn","America/Dawson","America/Dawson_Creek","America/Denver","America/Detroit","America/Dominica","America/Edmonton","America/Eirunepe","America/El_Salvador","America/Ensenada","America/Fort_Wayne","America/Fortaleza","America/Glace_Bay","America/Godthab","America/Goose_Bay","America/Grand_Turk","America/Grenada","America/Guadeloupe","America/Guatemala","America/Guayaquil","America/Guyana","America/Halifax","America/Havana","America/Hermosillo","America/Indiana/Indianapolis","America/Indiana/Knox","America/Indiana/Marengo","America/Indiana/Petersburg","America/Indiana/Tell_City","America/Indiana/Vevay","America/Indiana/Vincennes","America/Indiana/Winamac","America/Indianapolis","America/Inuvik","America/Iqaluit","America/Jamaica","America/Jujuy","America/Juneau","America/Kentucky/Louisville","America/Kentucky/Monticello","America/Knox_IN","America/Kralendijk","America/La_Paz","America/Lima","America/Los_Angeles","America/Louisville","America/Lower_Princes","America/Maceio","America/Managua","America/Manaus","America/Marigot","America/Martinique","America/Matamoros","America/Mazatlan","America/Mendoza","America/Menominee","America/Merida","America/Metlakatla","America/Mexico_City","America/Miquelon","America/Moncton","America/Monterrey","America/Montevideo","America/Montreal","America/Montserrat","America/Nassau","America/New_York","America/Nipigon","America/Nome","America/Noronha","America/North_Dakota/Beulah","America/North_Dakota/Center","America/North_Dakota/New_Salem","America/Ojinaga","America/Panama","America/Pangnirtung","America/Paramaribo","America/Phoenix","America/Port-au-Prince","America/Port_of_Spain","America/Porto_Acre","America/Porto_Velho","America/Puerto_Rico","America/Rainy_River","America/Rankin_Inlet","America/Recife","America/Regina","America/Resolute","America/Rio_Branco","America/Rosario","America/Santa_Isabel","America/Santarem","America/Santiago","America/Santo_Domingo","America/Sao_Paulo","America/Scoresbysund","America/Shiprock","America/Sitka","America/St_Barthelemy","America/St_Johns","America/St_Kitts","America/St_Lucia","America/St_Thomas","America/St_Vincent","America/Swift_Current","America/Tegucigalpa","America/Thule","America/Thunder_Bay","America/Tijuana","America/Toronto","America/Tortola","America/Vancouver","America/Virgin","America/Whitehorse","America/Winnipeg","America/Yakutat","America/Yellowknife","Antarctica/Casey","Antarctica/Davis","Antarctica/DumontDUrville","Antarctica/Macquarie","Antarctica/Mawson","Antarctica/McMurdo","Antarctica/Palmer","Antarctica/Rothera","Antarctica/South_Pole","Antarctica/Syowa","Antarctica/Vostok","Arctic/Longyearbyen","Asia/Aden","Asia/Almaty","Asia/Amman","Asia/Anadyr","Asia/Aqtau","Asia/Aqtobe","Asia/Ashgabat","Asia/Ashkhabad","Asia/Baghdad","Asia/Bahrain","Asia/Baku","Asia/Bangkok","Asia/Beirut","Asia/Bishkek","Asia/Brunei","Asia/Calcutta","Asia/Choibalsan","Asia/Chongqing","Asia/Chungking","Asia/Colombo","Asia/Dacca","Asia/Damascus","Asia/Dhaka","Asia/Dili","Asia/Dubai","Asia/Dushanbe","Asia/Gaza","Asia/Harbin","Asia/Hebron","Asia/Ho_Chi_Minh","Asia/Hong_Kong","Asia/Hovd","Asia/Irkutsk","Asia/Istanbul","Asia/Jakarta","Asia/Jayapura","Asia/Jerusalem","Asia/Kabul","Asia/Kamchatka","Asia/Karachi","Asia/Kashgar","Asia/Kathmandu","Asia/Katmandu","Asia/Khandyga","Asia/Kolkata","Asia/Krasnoyarsk","Asia/Kuala_Lumpur","Asia/Kuching","Asia/Kuwait","Asia/Macao","Asia/Macau","Asia/Magadan","Asia/Makassar","Asia/Manila","Asia/Muscat","Asia/Nicosia","Asia/Novokuznetsk","Asia/Novosibirsk","Asia/Omsk","Asia/Oral","Asia/Phnom_Penh","Asia/Pontianak","Asia/Pyongyang","Asia/Qatar","Asia/Qyzylorda","Asia/Rangoon","Asia/Riyadh","Asia/Saigon","Asia/Sakhalin","Asia/Samarkand","Asia/Seoul","Asia/Shanghai","Asia/Singapore","Asia/Taipei","Asia/Tashkent","Asia/Tbilisi","Asia/Tehran","Asia/Tel_Aviv","Asia/Thimbu","Asia/Thimphu","Asia/Tokyo","Asia/Ujung_Pandang","Asia/Ulaanbaatar","Asia/Ulan_Bator","Asia/Urumqi","Asia/Ust-Nera","Asia/Vientiane","Asia/Vladivostok","Asia/Yakutsk","Asia/Yekaterinburg","Asia/Yerevan","Atlantic/Azores","Atlantic/Bermuda","Atlantic/Canary","Atlantic/Cape_Verde","Atlantic/Faeroe","Atlantic/Faroe","Atlantic/Jan_Mayen","Atlantic/Madeira","Atlantic/Reykjavik","Atlantic/South_Georgia","Atlantic/St_Helena","Atlantic/Stanley","Australia/ACT","Australia/Adelaide","Australia/Brisbane","Australia/Broken_Hill","Australia/Canberra","Australia/Currie","Australia/Darwin","Australia/Eucla","Australia/Hobart","Australia/LHI","Australia/Lindeman","Australia/Lord_Howe","Australia/Melbourne","Australia/North","Australia/NSW","Australia/Perth","Australia/Queensland","Australia/South","Australia/Sydney","Australia/Tasmania","Australia/Victoria","Australia/West","Australia/Yancowinna","Brazil/Acre","Brazil/DeNoronha","Brazil/East","Brazil/West","Canada/Atlantic","Canada/Central","Canada/East-Saskatchewan","Canada/Eastern","Canada/Mountain","Canada/Newfoundland","Canada/Pacific","Canada/Saskatchewan","Canada/Yukon","CET","Chile/Continental","Chile/EasterIsland","CST6CDT","Cuba","EET","Egypt","Eire","EST","EST5EDT","Etc/GMT","Etc/GMT+0","Etc/GMT+1","Etc/GMT+10","Etc/GMT+11","Etc/GMT+12","Etc/GMT+2","Etc/GMT+3","Etc/GMT+4","Etc/GMT+5","Etc/GMT+6","Etc/GMT+7","Etc/GMT+8","Etc/GMT+9","Etc/GMT-0","Etc/GMT-1","Etc/GMT-10","Etc/GMT-11","Etc/GMT-12","Etc/GMT-13","Etc/GMT-14","Etc/GMT-2","Etc/GMT-3","Etc/GMT-4","Etc/GMT-5","Etc/GMT-6","Etc/GMT-7","Etc/GMT-8","Etc/GMT-9","Etc/GMT0","Etc/Greenwich","Etc/UCT","Etc/Universal","Etc/UTC","Etc/Zulu","Europe/Amsterdam","Europe/Andorra","Europe/Athens","Europe/Belfast","Europe/Belgrade","Europe/Berlin","Europe/Bratislava","Europe/Brussels","Europe/Bucharest","Europe/Budapest","Europe/Busingen","Europe/Chisinau","Europe/Copenhagen","Europe/Dublin","Europe/Gibraltar","Europe/Guernsey","Europe/Helsinki","Europe/Isle_of_Man","Europe/Istanbul","Europe/Jersey","Europe/Kaliningrad","Europe/Kiev","Europe/Lisbon","Europe/Ljubljana","Europe/London","Europe/Luxembourg","Europe/Madrid","Europe/Malta","Europe/Mariehamn","Europe/Minsk","Europe/Monaco","Europe/Moscow","Europe/Nicosia","Europe/Oslo","Europe/Paris","Europe/Podgorica","Europe/Prague","Europe/Riga","Europe/Rome","Europe/Samara","Europe/San_Marino","Europe/Sarajevo","Europe/Simferopol","Europe/Skopje","Europe/Sofia","Europe/Stockholm","Europe/Tallinn","Europe/Tirane","Europe/Tiraspol","Europe/Uzhgorod","Europe/Vaduz","Europe/Vatican","Europe/Vienna","Europe/Vilnius","Europe/Volgograd","Europe/Warsaw","Europe/Zagreb","Europe/Zaporozhye","Europe/Zurich","Factory","GB","GB-Eire","GMT","GMT+0","GMT-0","GMT0","Greenwich","Hongkong","HST","Iceland","Indian/Antananarivo","Indian/Chagos","Indian/Christmas","Indian/Cocos","Indian/Comoro","Indian/Kerguelen","Indian/Mahe","Indian/Maldives","Indian/Mauritius","Indian/Mayotte","Indian/Reunion","Iran","Israel","Jamaica","Japan","Kwajalein","Libya","MET","Mexico/BajaNorte","Mexico/BajaSur","Mexico/General","MST","MST7MDT","Navajo","NZ","NZ-CHAT","Pacific/Apia","Pacific/Auckland","Pacific/Chatham","Pacific/Chuuk","Pacific/Easter","Pacific/Efate","Pacific/Enderbury","Pacific/Fakaofo","Pacific/Fiji","Pacific/Funafuti","Pacific/Galapagos","Pacific/Gambier","Pacific/Guadalcanal","Pacific/Guam","Pacific/Honolulu","Pacific/Johnston","Pacific/Kiritimati","Pacific/Kosrae","Pacific/Kwajalein","Pacific/Majuro","Pacific/Marquesas","Pacific/Midway","Pacific/Nauru","Pacific/Niue","Pacific/Norfolk","Pacific/Noumea","Pacific/Pago_Pago","Pacific/Palau","Pacific/Pitcairn","Pacific/Pohnpei","Pacific/Ponape","Pacific/Port_Moresby","Pacific/Rarotonga","Pacific/Saipan","Pacific/Samoa","Pacific/Tahiti","Pacific/Tarawa","Pacific/Tongatapu","Pacific/Truk","Pacific/Wake","Pacific/Wallis","Pacific/Yap","Poland","Portugal","PRC","PST8PDT","ROC","ROK","Singapore","Turkey","UCT","Universal","US/Alaska","US/Aleutian","US/Arizona","US/Central","US/East-Indiana","US/Eastern","US/Hawaii","US/Indiana-Starke","US/Michigan","US/Mountain","US/Pacific","US/Pacific-New","US/Samoa","UTC","W-SU","WET","Zulu");
    ?>
    <select name="timezone">
    <option value="">-- Choose --</option>
    <?PHP
     foreach($timezones as $tz){
       ?><option value="<?=$tz?>"><?=$tz?></option><?PHP
     }
    ?>
    </select> &nbsp;
    <br>
    <input type="submit" value="Change Timezone"/>
    </form>
  </div>
  <?php } ?>
  <h3><strong>Change Password</strong></h3>
    <div>
        <form class="settings" method="POST" id="formsetting">
        <label >Current Password: </label><input type="password" name="pw_current"/><br>
        <label>New Password: </label><input type="password" name="pw_new"/><br>
        <label>Confirm Password:</label><input type="password" name="pw_new_confirm"/><br>
        <input type="submit" value="Change Password"/>
        </form>
    </div>

    <h3><strong>Change Email </strong>
    <?PHP 
    echo '('.$_SESSION['user_row']['email'].') ';
    
    if ($_SESSION['user_row']['verify_command'] == 'change_email'){ 
        echo '*Pending change to '.$_SESSION['user_row']['verify_param']; 
    }?></h3>
    <div>

        <form class="settings" method="POST" id="formsetting">

        <label>Current Password: </label><input type="password" name="pw_current"/><br>

        <label>New Email: </label><input type="text" name="email_new"/><br>

        <input type="submit" value="Change Email"/>
        </form>
    </div>

    <h3><strong>Change Address</strong></h3>

    <div>

        <form class="settings" method="POST" id="formsetting">

        <b>This address will be used in generating a pdf invoice</b><br><br>

        <label>Name</label><input type="text" name="name" value="<?=$user_row['name']?>"/><br>
        <label>Street</label><input type="text" name="street" value="<?=$user_row['street']?>"/><br>
        <label>Street2</label><input type="text" name="street2" value="<?=$user_row['street2']?>"/><br>
        <label>City</label><input type="text" name="city" value="<?=$user_row['city']?>"/><br>
        <label>State</label><input type="text" name="state" value="<?=$user_row['state']?>"/><br>
        <label>Zip</label><input type="text" name="zip" value="<?=$user_row['zip']?>"/><br>
        <label>Country</label><input type="text" name="country" value="<?=$user_row['country']?>"/><br>
        <label>Phone</label><input type="text" name="phone" value="<?=$user_row['phone']?>"/><br>
        <input type="submit" value="Change Address"/>
        </form>
    </div>
    
    <h3><strong>Remove Account</strong></h3>
    <div>
        <a href="delete.php?remove_my_account=1" onclick="if (confirm('Are you sure you want to delete your account? There is no going back')){ return true; }else{ return false; }">Permanently Delete Account and all attached information</a>
    </div>
</div>
</body>
</html>
