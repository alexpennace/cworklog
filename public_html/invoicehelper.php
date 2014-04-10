<?php
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


function forcecheck($key, $default=null, $ARRAY = null){
	if (is_null($ARRAY)){ $ARRAY = $_GET; }
	if (!isset($ARRAY[$key])){
		if (is_null($default)){
		   die('&'.$key.' required.');
		}else{
			return $default;
		}
	}else{
		return $ARRAY[$key];
	}
}

$OPTS = $_GET;

forcecheck('wid');
$OPTS['format'] = forcecheck('format', 'pdf');


   include_once(dirname(__FILE__).'/lib/Site.class.php');
   include_once(__DIR__.'/lib/Members.class.php');
   Members::SessionForceLogin();
?>
<!doctype html>
<html>
<head>
<title>CWorkLog Invoice</title>
<?php
Site::CssJsJqueryIncludes();
?>
<link href="css/invoicehelper.css" rel="stylesheet" type="text/css"/>
<script src="js/invoicehelper.js"></script>
</head>
<body>

<div class="container">
	<div class="invoice_helper">
	<form target="invoice" action="invoice.php" method="GET">

	<input type="hidden" name="invoice_wid" value="<?=$OPTS['wid']?>">
	<label>Format
		<select name="format">
		<option value="pdf">pdf</option>
		<option value="html">html</option>
		</select>
	</label>

	<label>Template
	<?php
		require_once(dirname(__FILE__).'/lib/CWLInvoice.class.php');
		$templates = CWLInvoice::GetTemplates();
	?>
		<select id="sel_invoice_template" name="invoice_template">
		<?php
			foreach($templates as $t){
				$bn = basename($t, '_invoice.tpl.php');
				?><option value="<?=htmlentities($t);?>"><?=htmlentities($bn)?></option><?php 
			}
		?>
		</select>
	</label>
   <script>
    (function(){ 
		document.getElementById('sel_invoice_template').value = <?=json_encode('leblancbasic_invoice.tpl.php')?>;
	})(); 
   </script>

	<button type="submit">Update Invoice</button> <button id="btnEmailInvoice" type="button">Email Invoice</button>

	<div id="moreinvoiceoptions">
	<div class="head">
	More Invoice Options
	</div>
	<div class="body flexcroll">
		<label>Custom Invoice Number: <input type="text" name="invoice_number" value=""></label>
		<label>Description Override<br>
		<textarea name="final_descrip_override"></textarea>
		</label>

		<label>Thank you message at the bottom: <br>
		<textarea name="thankyou_message">THANK YOU FOR YOUR BUSINESS!</textarea>
		</label>

		<label>Extra CSS for the invoice<br>
		<textarea name="extra_css"></textarea>
		</label>
	</div>
	</div>


		<div id="emailinvoice" class="emailinvoice">
		  <label>Email to: <input type="text" name="emailinvoice[email_to]" value=""></label><br>
		  <label>Email From: <input type="text" name="emailinvoice[email_from]" value="<?=Members::LoggedInEmail()?>" readonly="readonly"></label><br>
		  <label>Message In Email <br>
			<textarea name="emailinvoice[inline_message]">Attached is my invoice. Thank you</textarea>
		  </label>
		  <br>
		  <p>A copy of the message will be sent to <?=Members::LoggedInEmail()?></p>
	      <!--
		  <label><input type="radio" name="emailinvoice[how_to_show]" value="attachpdf" checked="checked">Attach PDF</label>
		  Additional:  <label><input type="checkbox" name="emailinvoice[mark_locked]" value="1" checked="checked">Mark Locked</label><label><input type="checkbox" name="emailinvoice[copy_hours_to_actual]" value="1" checked="checked">Copy Logged Hours to Actual Hours</label><label><input type="checkbox" name="emailinvoice[date_billed_today]" value="1" checked="checked">Set Date-Billed to Today</label><label><input type="checkbox" name="emailinvoice[amount_billed_invoice_total]" value="1" checked="checked">Set amount billed to invoice total</label>
		  -->
	      <button type="submit" name="send_email_instead" value="1" title="You may want to click Update first to preview the invoice">Update and Send now</button>
		</div>
	</form>

	</div>
	<div class="invoice">
	<iframe name="invoice" src="invoice.php?invoice_wid=<?=$OPTS['wid']?>&format=<?=$OPTS['format']?>">
	</iframe>
	</div>

	</div>
</body>
</html>
