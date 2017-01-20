<?php
/*
* Caches API calls to a local file which is updated in a
* given time interval.
*/

$update_interval = $_REQUEST['interval'] * 60; // 10 minutes
$cache_file = $_REQUEST['cacheFile'];


/*
 * Checks the cache file and if the last modified time is lesser than
 * update interval then returns cache contents else returns a "expired" status
 */
if ( !file_exists($cache_file) || (time() - filemtime($cache_file) > $update_interval) ) {
    header('Content-Type: application/json; charset=UTF-8');
    echo '{"expired": true}';
}
else {
    header('Content-Type: application/json; charset=UTF-8');
    echo file_get_contents($cache_file);
}


