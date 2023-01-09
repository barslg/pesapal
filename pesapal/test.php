<?php 




function dbg($msg,$fileName='debug.log') {
	$funcFull = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2);
	if (isset($funcFull[1])) {
		$func=$funcFull[1]['function'];
		$line=$funcFull[0]['line'];
	} else {
		$func='';
		$line=$funcFull[0]['line'];
	}


	if(is_array($msg) OR is_object($msg))  {
		if (array_key_exists('pid',$msg)) {
			$msg=(array) $msg;
			$data = base64_decode($msg['pid']);
			$msg = unserialize($data);
		}	
		$msg=var_export($msg,true);
	}


	if(is_bool($msg)) {
		ob_start();
		var_dump($msg);
		$msg=ob_get_clean();				
	}
	// $msg=var_export($msg,true);
	date_default_timezone_set('Europe/Istanbul');
	$currentDate = new \DateTime();
	$fn=__FILE__;
	$out="[{$currentDate->format('Y-m-d H:i:s.u')}]\t {$func} {$fn}  (line: {$line}): {$msg}";
	$toLog=$out.PHP_EOL;
	file_put_contents(realpath(dirname(__FILE__)).'/'.$fileName, $toLog,FILE_APPEND);
	if (PHP_SAPI != 'cli') {
		echo "<pre>{$out}</pre>";
	} else {
		echo $out.PHP_EOL;
	}
}


// $msg['pid'] = 'YToyOntzOjk6Imludm9pY2VpZCI7czoxMDoiREVWXy0xNTU3MiI7czoxMzoidHJhbnNhY3Rpb25pZCI7czozNjoiMmZkOTZmYmYtMDIxZi00OWIzLTg2ZDQtZGYzOWRiZWM2NWIzIjt9';

$msg=new stdClass();
$msg->pid='YToyOntzOjk6Imludm9pY2VpZCI7czoxMDoiREVWXy0xNTU3MiI7czoxMzoidHJhbnNhY3Rpb25pZCI7czozNjoiMmZkOTZmYmYtMDIxZi00OWIzLTg2ZDQtZGYzOWRiZWM2NWIzIjt9';


dbg($msg);



?>