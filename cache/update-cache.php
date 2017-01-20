<?php
/*
* Caches API calls to a local file which is updated on a
* given time interval.
*/

$cache_file = $_REQUEST['cacheFile'];

if( get_magic_quotes_gpc() ) {
  $playlist = stripcslashes( $_REQUEST['playlist'] );
}
else {
  $playlist = $_REQUEST['playlist'];
}

// $playlist = json_encode($playlist);


// update the cache if past interval time
$fp = fopen($cache_file, 'w+'); // open or create cache

if ($fp) {
    if (flock($fp, LOCK_EX)) {
        fwrite($fp, $playlist);
        flock($fp, LOCK_UN);
    }

    fclose($fp);
}