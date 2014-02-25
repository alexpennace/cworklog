<?PHP           

  function makeFilterLink($text, $key, $value)
  {
    ?><a <?PHP if (isset($_GET[$key]) && $_GET[$key] == $value){ 
        ?>href="<?=modQS('',array($key))?>" class="unfilter" title="Unfilter <?=htmlentities($text)?>"<?PHP 
    }else{
        ?>href="<?=modQS(array($key=>$value))?>"<?PHP 
    }?>><?=$text?></a><?PHP
  }
          
  function modQS($ary_or_qs, $ary_unset = array()){
     if (is_string($ary_or_qs)){
       $ary_or_qs = parse_str($ary_or_qs);
     }
     if (!is_array($ary_or_qs)) {
        $ary_or_qs = array();
     }
     
     if (!is_array($ary_unset)){
        $ary_unset = array($ary_unset);
     }
     
    
     $CURQS = array_merge($_GET, $ary_or_qs);
     
     //actually generate the query string
     $qs = '';
     foreach($CURQS as $key => $val){
     
         if (in_array($key, $ary_unset)){
            continue; //ignore this key
         }

         if (isset($ary_or_qs[$key])) {
            $CURQS[$key] = $ary_or_qs[$key];
         }
         
         if (is_string($key) && is_string($CURQS[$key])){
            if ($qs == ''){ $qs = '?'; }
            else { $qs .= '&'; }
            $qs .= urlencode($key).'='.urlencode($CURQS[$key]);
         }
     }
     //if the query string is empty, we need to specify the page
     if (empty($qs)){
         return $_SERVER['PHP_SELF'];
     }else{
        return $qs;
     }
   }
