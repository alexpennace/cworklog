<?php
/**
 * This file is responsible for dealing with the generation of invoices.
 * @example 
 *  $inv = new CWLInvoice(array('template'=>'leblancbasic_invoice.tpl'));
 *  $inv->generate($list_of_wids);
 *  $inv->output('pdf');
 */
class CWLInvoice{
	public static function GetTemplates(){
		$templates = array();
		foreach(glob(dirname(__FILE__).'/cwlinvoice_templates/'."*.tpl.php") as $tpl){
			$templates[] = basename($tpl);
		}
		return $templates;
	}


	//default opts, can be overridden in constructor
	private $opts = array(
		   'template'=>'leblancbasic_invoice.tpl', 
		   'templates_dir'=>false,
		   'format'=>'html',
		   'currency_symbol'=>'$',
		   'thankyou_message'=>'THANK YOU FOR YOUR BUSINESS!',
		   'final_descrip_override'=>false,
		   'invoice_number'=>false, //just dont put one if we dont need it
		   'extra_css'=>false,
    );
    //populated in generate() method
	private $final_html = '';
	public function getFinalHtml(){ return $this->final_html; }

	public function __construct($opts){
		$this->opts = array_merge($this->opts, (array)$opts);
		if (empty($opts['templates_dir'])){
			$this->opts['templates_dir'] = dirname(__FILE__).'/cwlinvoice_templates';
		}
	}


    public static function parse_wids($wid_or_widlist){
    	 $wl_ids = array();
    	 if (is_numeric($wid_or_widlist) ){
				$wid = (int)$wid_or_widlist;
				$wl_ids = array($wid);
			}else if (is_array($wid_or_widlist)){
				$wid = false;
				$wl_ids = $wid_or_widlist;
			}else if (is_string($wid_or_widlist) && strpos($wid_or_widlist, ',') !== false){
			   //calculating multiple work logs
				$wid = false;
			    $wl_ids = explode(',', $wid_or_widlist);
			}
		  return $wl_ids;
    }

    /**
     *  Ensure the wids in this list @see parse_wids() are valid
     */
    public static function validate_wids($wl_ids){

    }


    /**
     * Prepare this invoice for output, by generating all the necessary items
     * based on the given work log id or work log id list
     */
	public function generate($wid_or_widlist, $template = null) {
			global $DBH;

		    $this->final_html = false;
		    $wl_ids = self::parse_wids($wid_or_widlist);
		    //echo json_encode($wl_ids);
			if (count($wl_ids) === 0){
				throw new Exception('Invalid $wid_or_widlist type, must be integer or array');
		    }

			if (is_null($template)){
				$template = $this->opts['template'];
			}

		    $template_file = $this->getTemplateFile($template);
		    if (empty($template_file)){
		    	throw new Exception('Template file not found from "'.$template.'"');
		    }

		    self::validate_wids($wl_ids);

			require_once(dirname(__FILE__).'/db.inc.php');
			require_once(dirname(__FILE__).'/work_log.class.php');

			$companies = array();
			$num = 0;
			$wl_final['amount_billed'] = 0;
			$wl_final['description'] = count($wl_ids) > 0 ? count($wl_ids).' work logs<br>' : '';
			if (!empty($this->opts['final_descrip_override'])){
				$wl_final['description'] = $this->opts['final_descrip_override'];
			}
			$wl_final['hours'] = 0;
			$wl_final['rate'] = 0;
			$wl_final['sum_rates'] = 0;

			$work_log_rows = array();

			foreach($wl_ids as $wid){
			   $num++;
			   $work_log = new work_log($wid);
			   $wl_row = $work_log->getRow();
			   
			   
			   if (count($companies) > 1){
			      die('You are attempting to bill multiple work logs with differing companies.
			           Each invoice may have only one client');
			   }
			   
			   if (!$wl_row['locked']){
			      //die('This work log is unlocked, must lock before invoicing');
			   }

			   if (!empty($wl_row['_in_progress_'])){
			      die('This work log is in progress, cannot invoice.');
			   }

			   //name	street	street2	city	state	zip	country	phone	email	notes
			   $prep = $DBH->prepare("SELECT id, name, street, street2, city, state, zip, country, phone, email FROM company WHERE id = ".(int)$wl_row['company_id']);
			   $result = $prep->execute();

			   if ($result){
			     $company_row = $prep->fetch($result);
			     $companies[$wl_row['company_id']] = $company_row;
			   }

			   if (empty($company_row) || !$result){
			     die('No company associated with this work_log');
			   }
			   
			   if (!isset($wl_final['date_billed'])){ 
			      $wl_final['date_billed'] = !empty($wl_row['date_billed']) ? $wl_row['date_billed'] : '0000-00-00'; 
			   }
			   
			   //calculate most recent date
			   $wl_final['date_billed'] = !empty($wl_row['date_billed']) && $wl_row['date_billed'] > $wl_final['date_billed'] ? $wl_row['date_billed'] : '0000-00-00';
			   
			   if (!empty($wl_row['description'])){
			      $wl_final['description'] .= $wl_row['description'].'<br>';
			   }
			   $wl_final['hours'] += !empty($wl_row['hours']) ? $wl_row['hours'] : $wl_row['_calc_hours_'];
			   $wl_final['amount_billed'] += !empty($wl_row['amount_billed']) ? $wl_row['amount_billed'] : $wl_row['_calc_amount_'];
			   
			   //calculate average rate
			   $wl_final['sum_rates'] += $wl_row['rate'];
			   
			   $wl_final['rate'] = $wl_final['sum_rates'] / $num;
			}
			$currency_symbol = !empty($this->opts['currency_symbol']) ? $this->opts['currency_symbol'] : '$';
			$usetables = false;


			$fullcompany_address = self::genhtml_adddress($company_row);


	       //The information is now gathered from the user table which represents the bill-to information
	       $prep = $DBH->prepare("SELECT * FROM user WHERE id = ".(int)$_SESSION['user_id']);
$result = $prep->execute();
		   if ($result){
	          $from_user_row = $prep->fetch();
		   }
			
			$THANKSMSG = $this->opts['thankyou_message']; 
			$invoice_number = $this->opts['invoice_number'];
			$extra_css = $this->opts['extra_css'];
			
			if ($template_file !== false){
				ob_start();
				include($template_file);
				$contents = ob_get_contents();
                ob_end_clean();
                $this->final_html = $contents;
                return $contents;
			}else{
				return false;
			}
	}

	public static function genhtml_adddress($company_row){
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
        return $str;
    }  


    public function getTemplateFile($template_file){
    	$base = basename($template_file);
    	$dir = $this->opts['templates_dir'];

    	$file = "$dir/$base";

    	if (!file_exists($file)){
    		$file = "$dir/$base.tpl.php";
    	}    
    	
    	if (!file_exists($file)){
    		$file = "$dir/$base.php";
    	}   

    	if (!file_exists($file)){
    		return false;
    	}
        return $file;
    }

    public function grab_contents($format = null){
			$contents = $this->final_html;

		    if (empty($format)){ $format = $this->opts['format']; }
			if (empty($format)) {
				$format = 'pdf';
			}

			if ($format == 'html'){
			   return array($format, $contents);
			}
			else if ($this->opts['format'] == 'pdf')
			{
			   require_once(dirname(__FILE__)."/dompdf/dompdf_config.inc.php");
			   
			   set_time_limit (0);
			   $dompdf = new DOMPDF();
			   $dompdf->load_html($contents);
			   //$dompdf->set_paper('paper', 'landscape');
			   $dompdf->render();
			   $output = $dompdf->output();
			   return array($format, $output);
			}
    }

	public function output($format = null){
		    $contents = $this->final_html;

		    if (empty($format)){ $format = $this->opts['format']; }
			if (empty($format)) {
				$format = 'pdf';
			}

			if ($format == 'html'){
			   die($contents);
			}
			else if ($this->opts['format'] == 'pdf')
			{
			   require_once(dirname(__FILE__)."/dompdf/dompdf_config.inc.php");
			   
			   set_time_limit (0);
			   $dompdf = new DOMPDF();
			   $dompdf->load_html($contents);
			   //$dompdf->set_paper('paper', 'landscape');
			   $dompdf->render();
			   
			   $dompdf->stream("Invoice", array("Attachment" => false));
			   exit(0);
			}
	}
}
