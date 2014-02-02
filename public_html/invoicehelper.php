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
?>
<!doctype html>
<html>
<head>
<title>CWorkLog Invoice</title>
<style>
iframe {
	width: 100%;
	height: 100%;
}

.invoice_helper {
	width: 100%;
	height: 60px;
	border: 1px solid silver;
	text-align: center;
}

.invoice {
		border-top: 1px dotted black;
		height: 800px;
}

</style>
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


	<button type="submit">Go</button>
	</form>
	</div>

	<div class="invoice">
	<iframe name="invoice" src="invoice.php?invoice_wid=<?=$OPTS['wid']?>&format=<?=$OPTS['format']?>">
	</iframe>
	</div>

	</div>

</body>
</html>
