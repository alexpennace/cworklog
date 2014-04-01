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

	   <?php
	   		if (!empty($extra_css)){
	   			?>
	   			<?=$extra_css?>
	   			<?php
	   	    }
	   ?>
	</style>
</head>
<body>
	<div id="header">
		<div id="invoice">
			INVOICE
		</div>
		<?php if (!empty($invoice_number)){ ?>
			<div id="invoice_number" class="invoice_number" 
			      style="position: absolute; top: 10px; left: 180px;">
			Inv. Number: <?=$invoice_number?>
			</span><?php 
		 } ?>

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
               $str .= $company_row['city'];
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
			<tr class="billable_item"> <td class="first"><?=($wl_final['description'])?></td> <td><?=isset($wl_final['hours']) ? $wl_final['hours'] : number_format($wl_final['_calc_hours_'], 3)?></td> <td><?=$currency_symbol?><?=number_format($wl_final['rate'], 2)?></td> <td><?=$currency_symbol?><?=isset($wl_final['amount_billed']) ? number_format($wl_final['amount_billed'], 2) : number_format($wl_final['_calc_amount_'], 2)?></td> </tr>
			<?PHP

			   $total_amount = isset($wl_final['amount_billed']) ? $wl_final['amount_billed'] : $wl_final['_calc_amount_'];
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
			<?=$THANKSMSG?>
		</div>
	</div>
	
</body>
</html>