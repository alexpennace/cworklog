<?PHP
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

require_once('lib/db.inc.php');
require_once('lib/Members.class.php');
Members::SessionForceLogin();

$wid = isset($_GET['wid']) ? $_GET['wid'] : false;

if ($wid === false){
   die('Work log id needed');
}


if (strpos($wid, ',') !== false){
   //calculating multiple work logs
   $wl_ids = explode(',',$wid);
}else{
   $wl_ids = array($wid);
}

require_once('lib/work_log.class.php');

$companies = array();
$num = 0;
$wl_final['amount_billed'] = 0;
$wl_final['description'] = count($wl_ids) > 0 ? count($wl_ids).' work logs<br>' : '';
$wl_final['hours'] = 0;
$wl_final['rate'] = 0;
$wl_final['sum_rates'] = 0;

foreach($wl_ids as $wid){
   $num++;
   $work_log = new work_log($wid);
   $wl_row = $work_log->getRow();
   
   if (count($companies) > 1){
      die('You are attempting to bill multiple work logs with differing companies.
           Each invoice may have only one client');
   }
   
   if (!$wl_row['locked']){
      //die('This work log is unlocked, must lock before invoicing');
   }

   if (!empty($wl_row['_in_progress_'])){
      die('This work log is in progress, cannot invoice.');
   }

   //name	street	street2	city	state	zip	country	phone	email	notes
   $result = mysql_query("SELECT id, name, street, street2, city, state, zip, country, phone, email FROM company WHERE id = ".(int)$wl_row['company_id']);
   if ($result){
     $company_row = mysql_fetch_assoc($result);
     $companies[$wl_row['company_id']] = $company_row;
   }

   if (empty($company_row) || !$result){
     die('No company associated with this work_log');
   }
   
   if (!isset($wl_final['date_billed'])){ 
      $wl_final['date_billed'] = !empty($wl_row['date_billed']) ? $wl_row['date_billed'] : '0000-00-00'; 
   }
   
   //calculate most recent date
   $wl_final['date_billed'] = !empty($wl_row['date_billed']) && $wl_row['date_billed'] > $wl_final['date_billed'] ? $wl_row['date_billed'] : '0000-00-00';
   
   if (!empty($wl_row['description'])){
      $wl_final['description'] .= $wl_row['description'].'<br>';
   }
   $wl_final['hours'] += !empty($wl_row['hours']) ? $wl_row['hours'] : $wl_row['_calc_hours_'];
   $wl_final['amount_billed'] += !empty($wl_row['amount_billed']) ? $wl_row['amount_billed'] : $wl_row['_calc_amount_'];
   
   //calculate average rate
   $wl_final['sum_rates'] += $wl_row['rate'];
   
   $wl_final['rate'] = $wl_final['sum_rates'] / $num;
}
$currency_symbol = !empty($_GET['currency_symbol']) ? $_GET['currency_symbol'] : '$';
$usetables = false;
ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<!-- 

	Basic HTML/CSS Invoice
	 2009 Joseph L. LeBlanc

	Basic HTML/CSS Invoice by Joseph LeBlanc is licensed under a Creative Commons Attribution-Share Alike 3.0 United States License.
		http://creativecommons.org/licenses/by-sa/3.0/us/
	
	Permissions beyond the scope of this license may be available at http://www.designvsdevelop.com/basic-html-css-invoices.

-->
<html>
<head>
	<title>Invoice</title>
	<link rel="stylesheet" href="invoice_style.css" type="text/css">
<style>
               div.footer {
                  position: fixed;
                  overflow: hidden;
                  width: 100%;
                  padding: 0.1cm;
               }
             div.footer {
                 bottom: 0cm;
            	  left: 0cm;
                 border-top-width: 1px;
                 height: 2cm;
                 text-align: center;
               }
</style>
</head>
<body>
	<div id="header">
		<div id="invoice">
			INVOICE
		</div>
		<div id="date" style="position: absolute; top: 70px; right: 0px;">
			<?PHP $date = !empty($wl_final['date_billed']) && strtotime($wl_final['date_billed']) !== false ? $wl_final['date_billed'] : 'now'; ?>
         Date: <?=date('M j, Y', strtotime($date))?>
		</div>
	</div>
	
	<div id="addresses">
	  <?PHP if ($usetables){ ?>
	   <table border=0 style="width: 100%" cellpadding="0" cellspacing="0" width="100%">
	   <tr><td align="left" width="50%">
    <?PHP } ?>
    <?PHP
       //The information is now gathered from the user table which represents the bill-to information
       $result = mysql_query("SELECT * FROM user WHERE id = ".(int)$_SESSION['user_id']);
	   if ($result){
          $from_user_row = mysql_fetch_assoc($result);
	   }
    ?>
		<div id="from_business" style="position: absolute; left: 0px; top: 125px;">
			<div>
				<?PHP if (!empty($from_user_row['name'])){ ?><?=$from_user_row['name']?><br /><?PHP } ?>
				<?PHP if (!empty($from_user_row['street'])){ ?><?=$from_user_row['street']?><br /><?PHP } ?>
				<?PHP if (!empty($from_user_row['street2'])) { ?>
               <?=$from_user_row['street2']?><br />
				<?PHP } ?>
				<?PHP if (!empty($from_user_row['city'])){ ?><?=$from_user_row['city']?>, <?PHP } ?><?PHP if (!empty($from_user_row['state'])){ ?><?=$from_user_row['state']?><?PHP } ?> <?PHP if (!empty($from_user_row['zip'])){ ?><?=$from_user_row['zip']?><?PHP } ?>
            <?PHP if (!empty($from_user_row['country'])){ ?><br /><?=$from_user_row['country']?><?PHP } ?>
			</div>
         <?PHP if (!empty($from_user_row['email']) || !empty($from_user_row['phone'])){ 
            $contact_html = '';
            if (!empty($from_user_row['phone'])){
               $contact_html .= $from_user_row['phone']; 
            }
            if (!empty($contact_html)){
               $contact_html .= '<br>';
            }
            if (!empty($from_user_row['email'])){
               $contact_html .= $from_user_row['email']; 
            }
         ?>
			<div class="contact">
				<?=$contact_html?>
			</div>
         <?PHP } ?>
		</div>
		<?PHP if ($usetables){ ?></td><td align="right" width="50%"><?PHP } ?>
		<div id="to_business" style="position: absolute; right: 0px; top: 125px;">
			<div id="to">
				TO:
			</div>
			<div>
         <?PHP
            $str = $company_row['name'];
            
            if (!empty($company_row['street'])){
               if (!empty($str)){ $str .= '<br>'; }
               $str .= $company_row['street'];
            }
            if (!empty($company_row['street2'])){
               if (!empty($str)){ $str .= '<br>'; }
               $str .= $company_row['street2'];
            }
            if (!empty($company_row['city'])){ 
               if (!empty($str)){ $str .= '<br>'; }
               $str = $company_row['city'];
            }
            if (!empty($company_row['state'])){
               if (!empty($company_row['city'])){ $str .= ', '; }
               $str .= $company_row['state'];
            }
            if (!empty($company_row['zip'])){
               if (!empty($company_row['state'])){ $str .= ' '; }
               $str .= $company_row['zip'];
            }
         
				if (!empty($company_row['country'])){
               if (!empty($str)){ $str .= '<br>'; }
               $str .= $company_row['country'];
            }
           ?> 
           <?=$str?>
			</div>
			<div class="contact">
				<?=$company_row['phone']?>
				<?PHP if (!empty($company_row['email'])) { ?><br />
            <?=$company_row['email']?><?PHP } ?>
			</div>
		</div>
		<?PHP if ($usetables){ ?>
		</td>
		</tr>
		</table>
		<?PHP } ?>
	</div>
	<br>
	<br>
	<br>
	<?PHP
	  setlocale(LC_MONETARY, 'en_US');
	?>
	<div id="main">
		<table id="tabulation">
			<tr> <th class="first">Service<?=count($wl_ids) > 1 ? 's' : ''?></th> <th><?=count($wl_ids) > 1 ? 'Total ' : ''?>Hours</th> <th><?=count($wl_ids) > 1 ? 'Avg. ' : ''?>Rate</th> <th>Total</th> </tr>
			<tr class="billable_item"> <td class="first"><?=($wl_final['description'])?></td> <td><?=!empty($wl_final['hours']) ? $wl_final['hours'] : number_format($wl_final['_calc_hours_'], 3)?></td> <td><?=$currency_symbol?><?=number_format($wl_final['rate'], 2)?></td> <td><?=$currency_symbol?><?=!empty($wl_final['amount_billed']) ? number_format($wl_final['amount_billed'], 2) : number_format($wl_final['_calc_amount_'], 2)?></td> </tr>
			<?PHP
			   $total_amount = !empty($wl_final['amount_billed']) ? $wl_final['amount_billed'] : $wl_final['_calc_amount_'];
			   $paid_amount = isset($_GET['paid_amount']) ? $_GET['paid_amount'] : 0.0;
			   if ($paid_amount === 0.0 && !empty($wl_final['date_paid'])){
			      $paid_amount = $total_amount;
			   }
			?>
         <tr id="total"><td colspan="2">&nbsp;</td><td class="totalLabel">Total</td><td class="totals"><?=$currency_symbol?><?=number_format($total_amount, 2)?></td></tr>
			<tr id="paid"><td colspan="2">&nbsp;</td><td class="totalLabel">Paid</td><td class="totals"><?=$currency_symbol?><?=number_format($paid_amount, 2)?></td></tr>
			<tr id="due"><td colspan="2">&nbsp;</td><td class="totalLabel">Due</td><td class="totals"><?=$currency_symbol?><?=number_format($total_amount - $paid_amount, 2)?></td></tr>
		</table>
	</div>
	
	<div id="footer" class="footer">
		<div id="company">
			
		</div>
		<div id="thanks">
			THANK YOU FOR YOUR BUSINESS!
		</div>
	</div>
	
</body>
</html>
<?PHP
$contents = ob_get_contents();
ob_end_clean();

if (empty($_GET['format'])) {
	$_GET['format'] = 'pdf';
}

if ($_GET['format'] == 'html'){
  die($contents);
}
else if ($_GET['format'] == 'pdf')
{
   require_once("lib/dompdf/dompdf_config.inc.php");
   
   set_time_limit (0);
   $dompdf = new DOMPDF();
   $dompdf->load_html($contents);
   //$dompdf->set_paper('paper', 'landscape');
   $dompdf->render();
   
   $dompdf->stream("Invoice", array("Attachment" => false));
   exit(0);
}
?>
