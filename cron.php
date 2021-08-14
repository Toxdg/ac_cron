#!/usr/bin/php -q
<?php
echo '<h1>Cron PHP</h1>';
ac_make_log_file('** cron execute - start **');
define("ROOT_PATH",$_SERVER['DOCUMENT_ROOT']);
/**
*
* core main cron
*
**/

// function make log raport
function ac_make_log_file($log_msg, $dir = 'log'){
    $log_dirname = __DIR__.'/'.$dir;
	$log_msg = date('H:i:s').' '.$log_msg;
    if (!file_exists($log_dirname)) {
        // create directory/folder uploads.
        mkdir($log_dirname, 0777, true);
    }
    $log_file_data = $log_dirname.'/log_' . date('d-m-Y') . '.log';
    file_put_contents($log_file_data, $log_msg . "\r\n", FILE_APPEND);
}

// function load modules
 function ac_load_modules($dir = null, $extensions = array(), $exclude = array()){
	$dir_scan = __DIR__.$dir;
	$files = scandir($dir_scan);
	$extensions[] = "php";
	$exclude[] = basename(__FILE__);
	$tab_plikow = array();
	unset($files[0]); 
	unset($files[1]); 
	foreach ($files as $filename) {		
		$filepath = $dir_scan.'/'.$filename;
		if(is_file($filepath)) {
			$ext = ac_getFileExtension($filename);			
			if (in_array($ext,$extensions) && !in_array($filename,$exclude)) {
				include($dir_scan.'/'.$filename);
			}
		}
	}
}

// clear logs
function ac_remove_old_logs(){   
    $base_dir = __DIR__;
    $upload_dir = "/log";
    $now = time();
    $dir = $base_dir.$upload_dir;
    if(is_dir($dir)) {
        if($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if(ac_getFileExtension($file) == 'zip' || ac_getFileExtension($file) == 'gz' || ac_getFileExtension($file) == 'txt' || ac_getFileExtension($file) == 'log'){
                    if ($now - filemtime($dir.'/'.$file) >= 60 * 60 * 24 * 5) { // 5 days
                        unlink($dir.'/'.$file);
                    }
                }
            }
            closedir($dh);
        }
    }
}
// file type extension
function ac_getFileExtension($filename){
    $path_info = pathinfo($filename);
    return $path_info['extension'];
}


// loading main modules
ac_load_modules('/cron_tasks');

ac_make_log_file('** cron execute - end **');

ac_remove_old_logs();