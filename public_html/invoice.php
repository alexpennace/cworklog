<?PHP
require_once('lib/db.inc.php');
require_once('lib/Members.class.php');
Members::SessionForceLogin();

if (!empty($_REQUEST['invoice_wid'])){
   $wid = $_REQUEST['invoice_wid'];
}else{
	die('&invoice_wid required.');
}

if (!empty($_REQUEST['invoice_template'])){
    $template = $_REQUEST['invoice_template'];
}else{
	$template = null;
}

require_once(dirname(__FILE__).'/lib/CWLInvoice.class.php');

$inv = new CWLInvoice($_GET);
$inv->generate($wid, $template);
$inv->output();
