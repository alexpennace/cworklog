<?php
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

?>
<!doctype html>
<html>
<head>
<title>CWorkLog Invoice</title>
<?php
Site::CssJsJqueryIncludes();
?>
<link href="css/invoicehelper.css" rel="stylesheet" type="text/css"/>
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

	<button type="submit">Update Invoice</button>

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
	</form>
	</div>
	<div class="invoice">
	<iframe name="invoice" src="invoice.php?invoice_wid=<?=$OPTS['wid']?>&format=<?=$OPTS['format']?>">
	</iframe>
	</div>

	</div>
<script>
$(function(){
		$('#moreinvoiceoptions .head').click(function(){
				var $body = $(this).parent().find('.body');
				$body.toggle();
				var invoice_top_helper_height;
				
				if ($body.is(":visible")){
						invoice_top_helper_height = '140px';
				}else{
						invoice_top_helper_height = '70px';
				}

				$('.invoice_helper').css('height', invoice_top_helper_height);
				$('.invoice').css('padding-top', invoice_top_helper_height);
		}).click();
});
</script>
</body>
</html>
