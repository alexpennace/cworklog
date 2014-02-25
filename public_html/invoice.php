<?PHP
/**
 *  This file is responsible for generating an invoice, based on the work log id
 *  and the invoice_template in $_REQUEST string.
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim 
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
$inv->output();
