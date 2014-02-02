<?PHP
$base = 'http://www.jonathantneal.com/examples/invoice/';
$allowed_vars = 
	array(
		'FULLNAME'=>'',
		'STREET'=>'',
		'CITYSTATEZIP'=>'',
		'PHONE'=>'',
		'LOGOSRC'=>'',
		'TOCOMPANY'=>'',
		'INVOICENUM'=>'',
		'DATE'=>'',
		'ITEM1NAME'=>'',
		'ITEM1DESCRIP'=>'',
		'ITEM1PRICE'=>'',
		'ITEM1QUANTITY'=>'',
		'ITEM1TOTAL'=>'',
		'ITEMS'=>array(
			'NAME'=>'',
			'DESCRIP'=>'',
			'PRICE'=>'',
			'QUANTITY'=>'',
			'TOTAL'=>'',
			),
		'ADDITIONAL NOTES'=>'',

	);
//Transform 
	$vars = array();

 //SET ALL VARIABLES
	$vars['FULLNAME'] = $from_user_row['name'];
	$vars['STREET'] = $from_user_row['street'];
	$vars['CITYSTATEZIP'] = $from_user_row['city'];
	$vars['CITYSTATEZIP'] .= ' '.$from_user_row['state'];
	$vars['CITYSTATEZIP'] .= ', '.$from_user_row['zip'];
	if (!empty($from_user_row['country'])){
		$vars['CITYSTATEZIP'] .= '<br>'.$from_user_row['country'];
	}
	$vars['PHONE'] = ', '.$from_user_row['phone'];

    $vars['TOCOMPANY'] = $company_row['name'];
    $vars['INVOICENUM'] = rand(10000, 99999);
    $vars['DATE'] = date('M j, Y');

	ob_start();
?>
<html>
<head><style>article,aside,details,figcaption,figure,footer,header,hgroup,nav,section{display:block}audio[controls],canvas,video{display:inline-block}[hidden],audio{display:none}mark{background:#FF0;color:#000}</style>
		<base href="<?=$base?>">
		<meta charset="utf-8">
		<title>Invoice</title>
		<link rel="stylesheet" href="style.css">
		<link rel="license" href="http://www.opensource.org/licenses/mit-license/">
		<script src="script.js"></script>
	</head>
	<body style="">
		<header>
			<h1>Invoice</h1>
			<address contenteditable="">
				<p><?=$vars['FULLNAME']?></p>
				<p><?=$vars['STREET']?><br><?=$vars['CITYSTATEZIP']?></p>
				<p><?=$vars['PHONE']?></p>
			</address>
			<span><img alt="" src="<?=$vars['LOGOSRC']?>" class=""><input type="file" accept="image/*"></span>
		</header>
		<article>
			<h1>Recipient</h1>
			<address contenteditable=""><?=$vars['TOCOMPANY']?></address>
			<table class="meta">
				<tbody><tr>
					<th><span contenteditable="">Invoice #</span></th>
					<td><span contenteditable=""><?=$vars['INVOICENUM']?></span></td>
				</tr>
				<tr>
					<th><span contenteditable="">Date</span></th>
					<td><span contenteditable=""><?=$vars['DATE']?><br></span></td>
				</tr>
				<tr>
					<th><span contenteditable="">Amount Due</span></th>
					<td><span id="prefix" contenteditable="">$</span><span>1.00</span></td>
				</tr>
			</tbody></table>
			<table class="inventory">
				<thead>
					<tr>
						<th><span contenteditable="">Item</span></th>
						<th><span contenteditable="">Description</span></th>
						<th><span contenteditable="">Rate</span></th>
						<th><span contenteditable="">Quantity</span></th>
						<th><span contenteditable="">Price</span></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><a class="cut">-</a><span contenteditable=""><?=$vars['ITEM1NAME']?><br></span></td>
						<td><span contenteditable=""><?=$vars['ITEM1DESCRIP']?><br></span></td>
						<td><span data-prefix="">$</span><span contenteditable=""><?=$vars['ITEM1PRICE']?></span></td>
						<td><span contenteditable=""><?=$vars['ITEM1QUANTITY']?></span></td>
						<td><span data-prefix="">$</span><span><?=$vars['ITEM1TOTAL']?></span></td>
					</tr>
				</tbody>
			</table>
			<a class="add">+</a>
			<table class="balance">
				<tbody><tr>
					<th><span contenteditable="">Total</span></th>
					<td><span data-prefix="">$</span><span>1.00</span></td>
				</tr>
				<tr>
					<th><span contenteditable="">Amount Paid</span></th>
					<td><span data-prefix="">$</span><span contenteditable="">0.00</span></td>
				</tr>
				<tr>
					<th><span contenteditable="">Balance Due</span></th>
					<td><span data-prefix="">$</span><span>1.00</span></td>
				</tr>
			</tbody></table>
		</article>
		<aside>
			<h1><span contenteditable="">Additional Notes</span></h1>
			<div contenteditable="">
				<p><?=$vars['ADDITIONAL NOTES']?></p>
			</div>
		</aside>
	
</body>
</html>
