<?PHP
require_once('lib/db.inc.php');
require_once('lib/Members.class.php');
Members::SessionForceLogin();

$wid = isset($_GET['wid']) ? $_GET['wid'] : false;

if ($wid === false){
   die('Work log id needed');
}

require_once('lib/work_log.class.php');
//try
{
 $work_log = new work_log($wid);
}
//catch(Exception $e)
{
  //die('Invalid work log');
}

$wl_row = $work_log->getRow();
if (!$wl_row['locked']){
   //die('This work log is unlocked, must lock before invoicing');
}

if (!empty($wl_row['_in_progress_'])){
   die('This work log is in progress, cannot invoice.');
}

//name	street	street2	city	state	zip	country	phone	email	notes
$result = mysql_query("SELECT name, street, street2, city, state, zip, country, phone, email FROM company WHERE id = ".(int)$wl_row['company_id']);
if ($result){
  $company_row = mysql_fetch_assoc($result);
}

if (empty($company_row) || !$result){
  die('No company associated with this work_log');
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
			<?PHP $date = !empty($wl_row['date_billed']) ? $wl_row['date_billed'] : 'now'; ?>
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
			<tr> <th class="first">Service</th> <th>Hours</th> <th>Rate</th> <th>Total</th> </tr>
			<tr class="billable_item"> <td class="first"><?=htmlentities($wl_row['description'])?></td> <td><?=!empty($wl_row['hours']) ? $wl_row['hours'] : number_format($wl_row['_calc_hours_'], 3)?></td> <td><?=$currency_symbol?><?=number_format($wl_row['rate'], 2)?></td> <td><?=$currency_symbol?><?=!empty($wl_row['amount_billed']) ? number_format($wl_row['amount_billed'], 2) : number_format($wl_row['_calc_amount_'], 2)?></td> </tr>
			<?PHP
			   $total_amount = !empty($wl_row['amount_billed']) ? $wl_row['amount_billed'] : $wl_row['_calc_amount_'];
			   $paid_amount = isset($_GET['paid_amount']) ? $_GET['paid_amount'] : 0.0;
			   if ($paid_amount === 0.0 && !empty($wl_row['date_paid'])){
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
