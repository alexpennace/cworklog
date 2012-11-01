<?PHP
$usetables = false;
ob_start();
?>
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
</head>
<body>
	<div id="header">
		<div id="invoice">
			INVOICE
		</div>
		<div id="date" style="position: absolute; top: 70px; right: 0px;">
			Date: July 7th, 2009
		</div>
	</div>
	
	<div id="addresses">
	  <?PHP if ($usetables){ ?>
	   <table border=0 style="width: 100%" cellpadding="0" cellspacing="0" width="100%">
	   <tr><td align="left" width="50%">
    <?PHP } ?>
    
		<div id="from_business" style="position: absolute; left: 0px; top: 125px;">
			<div>
				Smart Company<br />
				123 Main Street<br />
				Suite 101<br />
				Washington, DC 20001<br />
				USA
			</div>
			<div class="contact">
				202-555-1212<br />
				user@domain.com
			</div>
		</div>
		<?PHP if ($usetables){ ?></td><td align="right" width="50%"><?PHP } ?>
		<div id="to_business" style="position: absolute; right: 0px; top: 125px;">
			<div id="to">
				TO:
			</div>
			<div>
				Client<br />
				321 Elm Street<br />
				Suite 201<br />
				Washington, DC 20007<br />
				USA
			</div>
			<div class="contact">
				202-555-2121<br />
				client@site.com
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
	<div id="main">
		<table id="tabulation">
			<tr> <th class="first">Service</th> <th>Hours</th> <th>Rate</th> <th>Total</th> </tr>
			<tr class="billable_item"> <td class="first">Consulting</td> <td>8.0</td> <td>$150</td> <td>1200.00</td> </tr>
			<tr class="billable_item"> <td class="first">Programming</td> <td>6.0</td> <td>$100</td> <td>600.00</td> </tr>
			<tr id="total"><td colspan="2">&nbsp;</td><td class="totalLabel">Total</td><td class="totals">$1800.00</td></tr>
			<tr id="paid"><td colspan="2">&nbsp;</td><td class="totalLabel">Paid</td><td class="totals">0.00</td></tr>
			<tr id="due"><td colspan="2">&nbsp;</td><td class="totalLabel">Due</td><td class="totals">$1800.00</td></tr>
		</table>
	</div>
	
	<div id="footer">
		<div id="company">
			Smart Company
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
if ($_GET['format'] == 'html'){
  die($contents);
}

require_once("lib/dompdf/dompdf_config.inc.php");

set_time_limit (0);
$dompdf = new DOMPDF();
$dompdf->load_html($contents);
//28 mm x 89 mm - DYMO White Address Labels
//$dompdf->set_paper(array(0,0, 105.826771654, 336.377952756), 'landscape');
//$dompdf->set_paper('paper', 'landscape');
$dompdf->render();

$dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
exit(0);
?>
