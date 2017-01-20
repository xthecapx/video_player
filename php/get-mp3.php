<?php

$folder = urldecode( $_REQUEST['folder'] );
$path = parse_url($folder, PHP_URL_PATH);
$path = $_SERVER['DOCUMENT_ROOT'] . $path;

$audiofiles = glob( $path . '/*.mp3');

$json = array();

foreach($audiofiles as $file) {
	$filename = basename($file);
	$covername = basename($filename, '.mp3');
	$coverpath = $path . '/covers/' . $covername . '.jpg';

	if( file_exists($coverpath) ) {
		$cover = $folder .'/covers/' . $covername . '.jpg';
	}
	else {
		$cover = '';
	}

	if( strpos($filename, 'not free') === false ) {
		$free = true;
	}
	else {
		$free = false;
	}

	$json[] = array('type' => 'audio', 'mp3' => $folder . '/' . $filename, 'poster' => $cover, 'free' => $free);
}

$json = json_encode($json);

header('Content-type: application/json; charset=UTF-8');
echo $json;