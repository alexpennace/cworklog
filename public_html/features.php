<?PHP
   require_once('lib/db.inc.php');
   require_once('lib/Members.class.php');
   require_once('lib/Site.class.php');
   Members::SessionForceLogin();
   require_once(dirname(__FILE__).'/lib/Moment.php');
   require_once(dirname(__FILE__).'/lib/misc.inc.php');


   $cid = isset($_GET['cid']) ? $_GET['cid'] : false;
   if ($cid === false){
      die('Company id needed');
   }
   $sql = 'SElECT * FROM Company WHERE user_id = :user_id AND id = :cid';
   $prep = $DBH->prepare($sql);
   $prep->execute(array(':user_id'=>$_SESSION['user_id'], 'cid'=>$cid));
   
   $company = $prep->fetch(PDO::FETCH_ASSOC);
   
   if (!$company){
       die('Client not found');
   }
   
   
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Features</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<link rel="stylesheet" type="text/css" href="css/theme.css" />
<style>
th {
	font: bold 11px  Arial, Helvetica,sans-serif;
	color: #fff;
	border-right: 1px solid #c7c7c7; border-bottom: 1px solid #c7c7c7;
	font-weight:bold;
	
	border-top: 1px solid #c7c7c7;
	letter-spacing: 2px;
	text-transform: uppercase;
	text-align: left;
	padding: 6px 6px 6px 12px;
	background: url(images/th_hd_bg.jpg) repeat-x left top!important ;
}
th.nobg {
	border-top: 0;
	border-left: 0;
	border-right: 1px solid #c7c7c7;
	background: none;
}
th.spec {	
	border-left: 1px solid #c7c7c7;
	border-top: 0;
	background: #fff url(images/bullet1.gif) no-repeat;
	font: bold 14px  Arial, Helvetica,sans-serif;
	color:#121212;
}
th.specalt {
	border-left: 1px solid #c7c7c7;
	border-top: 0;
	background: #f5fafa url(images/bullet2.gif) no-repeat;
	font: bold 14px  Arial, Helvetica,	sans-serif;
	color: #121212;
}
td {
	border-right: 1px solid #c7c7c7;
	border-bottom: 1px solid #c7c7c7;
	background: #fff;
	padding: 6px 6px 6px 12px;
	color: #121212;
	font-size: 14px;
}
td.alt {
	background: #F5FAFA;
	color: #B4AA9D;
}
td input {
  font-size: 14px;
  width: 100%;
  border: 1px solid silver;
}
table td.editable:hover{
  background-color: #FCB279;
  cursor: pointer;
}
table{ border-left: 1px solid #c7c7c7; } 
</style>
<script type="text/javascript" src="js/date.js"></script>
</head>
<body>
<?PHP Members::MenuBarCompact(); ?>
<div class="dataBlk" >
<?PHP if (!empty($error)){ ?>
<div class="error"><?=$error?></div>
<?PHP } ?>

<?PHP if (!empty($success)){ ?>
<div class="success"><?=$success?></div>
<?PHP } ?>
<?PHP

//select all features that this user worked on for this particular client
$prep = $DBH->prepare('SELECT feature, date_modified FROM files_log 
                      WHERE work_log_id IN 
                             (SELECT id FROM work_log WHERE company_id = :company_id) 
                      GROUP BY feature
                      ORDER BY date_modified DESC');
$prep->execute(array(':company_id'=>$cid));
$features = $prep->fetchAll(PDO::FETCH_ASSOC);


$total_files = 0;
foreach($features as &$feature){
   $prep2 = $DBH->prepare('SELECT file, change_type, date_modified, work_log_id, in_production 
                      FROM files_log 
                     WHERE feature = :feature 
                       AND work_log_id IN (SELECT id FROM work_log WHERE company_id = :company_id)');
   $prep2->execute(array(':feature'=>$feature['feature'], ':company_id'=>$cid)); 
   $files = $prep2->fetchAll(PDO::FETCH_ASSOC);
   
   $feature['_files_'] = $files; 
   $total_files += count($files);
}
unset($feature);

?>
<h1><?=$company['name']?> - <?=count($features)?> features, <?=$total_files?> files</h1><?PHP
  if (count($features) > 0){
     $m = new Moment\Moment('now');
     foreach($features as $i => $feature){
       ?><br><h3><?=$feature['feature']?></h3><?PHP
       ?><table border=0><tr><th>Type</th><th>File</th><th>Last modified</th><th>Work Log</th><th>&nbsp;</th></tr><?PHP
       
       foreach($feature['_files_'] as $j => $file_log){
          $worklog = new work_log($file_log['work_log_id']);
          $wl_row = $worklog->getRow();
          
          $diff = $m->from(date('r',strtotime($file_log['date_modified'])));
          ?><tr><td><?=$file_log['change_type']?></td>
                <td><?=$file_log['file']?></td>
                <td title="<?=$file_log['date_modified']?>"><?=time2str($file_log['date_modified'])?></td>
                <td><a href="work_log.php?wid=<?=$file_log['work_log_id']?>"><?=htmlentities($wl_row['title'])?></a></td>
                <td><label style="font-size: 10px"><input type="checkbox" name="<?=$file_log['file']?>" value="production" <?=$file_log['in_production'] ? 'checked="checked"':''?> >in production</label></td>
                </tr><?PHP
       }
       ?></table><br><?PHP       
     }
  }
?>
</div>
</body>
</html>
