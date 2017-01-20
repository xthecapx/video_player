<?php

/////////////////////////////////////////////////////////////////
// die if magic_quotes_runtime or magic_quotes_gpc are set
if (function_exists('get_magic_quotes_runtime') && get_magic_quotes_runtime()) {
    die('magic_quotes_runtime is enabled, getID3 will not run.');
}
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    die('magic_quotes_gpc is enabled, getID3 will not run.');
}
if (!defined('ENT_SUBSTITUTE')) { // defined in PHP v5.4.0
    define('ENT_SUBSTITUTE', ENT_QUOTES);
}
/////////////////////////////////////////////////////////////////



require_once('getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;
$getID3->setOption(array('encoding' => 'UTF-8'));

// load the user playlist
$playlist = json_decode( $_REQUEST['json'] );

// process the playlist and retrieve missing info from mp3 files
$audioItems = array();
$urls = array();
$index = 0;

foreach( $playlist as &$item ) {

    // get info for audio items
    if( $item->type === 'audio' ) {
        $file = parse_url(urldecode($item->mp3), PHP_URL_PATH);
        // $file = utf8_decode($file);
        $file = $_SERVER['DOCUMENT_ROOT'] . $file;
        // $item->mp3 = urldecode($item->mp3);

        if( file_exists($file) ) {
            $fileInfo = $getID3->analyze($file);
            getid3_lib::CopyTagsToComments($fileInfo);

            if( !isset($item->artist) || $item->artist === '' ) {
                if( isset( $fileInfo['comments_html']['artist'][0] ) ) {
                    $item->artist = $fileInfo['comments_html']['artist'][0];
                }
            }

            if( !isset($item->album) || $item->album === '' ) {
                if( isset( $fileInfo['comments_html']['album'][0] ) ) {
                    $item->album = $fileInfo['comments_html']['album'][0];
                }
            }

            if( !isset($item->track) || $item->track === '' ) {
                if( isset( $fileInfo['comments_html']['title'][0] ) ) {
                    $item->track = $fileInfo['comments_html']['title'][0];
                }
            }

            if( !isset($item->poster) || $item->poster === '' ) {
                if( isset( $fileInfo['comments']['picture'][0] ) ) {
                     $item->poster = 'data:'.$fileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($fileInfo['comments']['picture'][0]['data']);
                }
                else {
                    $album = $item->album;
                    $album = preg_replace('/\([^)]*\)/', '', $album);  // remove parentheses and any content inside them

                    $album = str_replace(array('OST', 'Disc'), array('', ''), $album);  // remove 'OST' and 'Disc' from album name to help with search

                    $term = $item->artist . ' - ' . $album;
                    $item->searchTerm = $term;
                    $audioItems[] = $index;
                }
            }

            if( !isset($item->duration) || $item->duration === '' ) {
                $item->duration = $fileInfo['playtime_string'];
            }
        }
    }
    // get info for video items
    else if( $item->type === 'video' ) {
        $file = parse_url(urldecode($item->mp4), PHP_URL_PATH);
        $file = $_SERVER['DOCUMENT_ROOT'] . $file;

        if( file_exists($file) ) {
            $fileInfo = $getID3->analyze($file);

            if( !isset($item->duration) || $item->duration === '' ) {
                $item->duration = $fileInfo['playtime_string'];
            }
        }
    }

    $index++;
}



$playlist = json_encode($playlist);

header('Content-type: application/json; charset=UTF-8');
echo $playlist;