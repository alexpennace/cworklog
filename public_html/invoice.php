<?PHP
/**
 *  This file is responsible for generating an invoice, based on the work log id
 *  and the invoice_template in $_REQUEST string.
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
ini_set('display_errors',1);

require_once(dirname(__FILE__).'/lib/db.inc.php');
require_once(dirname(__FILE__).'/lib/Members.class.php');
Members::SessionForceLogin();

if (!empty($_REQUEST['invoice_wid'])){
   $wid = $_REQUEST['invoice_wid'];
}else{
	if (!empty($_REQUEST['wid'])){
		$wid = $_REQUEST['wid'];
	}else{
	   die('&invoice_wid required.');
	}
}

if (!empty($_REQUEST['invoice_template'])){
    $template = $_REQUEST['invoice_template'];
}else{
	$template = null;
}

require_once(dirname(__FILE__).'/lib/CWLInvoice.class.php');
$ARY = array_merge($_GET, $_POST);
$inv = new CWLInvoice($ARY);
$inv->generate($wid, $template);

if (!empty($_REQUEST['send_email_instead'])){
   list($format, $output) = $inv->grab_contents();
   $rand = md5(time()).mt_rand();
   $filename = 'Invoice-'.date('Y-m-d').".$format";

   switch($format){
   		case 'pdf': $mime = 'application/pdf'; break;
   		case 'html': $mime = 'text/html'; break;
   		default: 
   		  $mime = 'text';
   }

    require_once(__DIR__.'/lib/cwl_email.class.php');

    list($mailer, $message, $logger) = cwl_email::setup(false);

	$message->setSubject('Invoice from '.Members::LoggedInEmail());
	$message->setBody($_REQUEST['emailinvoice']['inline_message'], 'text/html');

	$message->setFrom(Members::LoggedInEmail());
   
    $message->setTo(array($_REQUEST['emailinvoice']['email_to']=>''));
	$message->setBcc(array(Members::LoggedInEmail()=>Members::LoggedInShortName()));
     
    $attachment = Swift_Attachment::newInstance($output, $filename, $mime);

	$message->attach($attachment);
	$mailed = $mailer->send($message, $failures);
	
	if (!$mailed){
	  echo "Failure Sending to:";
	  print_r($failures);
	}else{
	   echo 'Message sent.';
	}

}else{
  $inv->output();
}
