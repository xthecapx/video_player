<?php

$root_url = urldecode($_REQUEST['protocol']) . '//'. $_REQUEST['rootUrl'];
$stream_url = strpos($_REQUEST['path'], '/') === 0 ? $root_url . $_REQUEST['path'] : $root_url . '/' . $_REQUEST['path']; // IE does not include slash in url pathname
$radio_type = $_REQUEST['type'];
$old_track = $_REQUEST['track'];
$pull_cover = $_REQUEST['pullCover'];

$ua = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:34.0) Gecko/20100101 Firefox/34.0';
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERAGENT, $ua);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$track = false;


// get info from Shoutcast stream
if( $radio_type === 'shoutcast' ) {
    // try the first url variety to get stream info (works for Shoutcast 2 servers)
    $info_url = $root_url . '/stats?sid=1';

    curl_setopt($ch, CURLOPT_URL, $info_url);

    $xml_response = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // check if data has been returned by looking for HTTP 200 header
    if( $http === 200 && strpos($type, 'xml') !== false ) {
        // We get the content
        $info = simplexml_load_string($xml_response);
        $track = (string) $info->SONGTITLE;
        $track = html_entity_decode( trim($track) );
    }
    else {  // load data from another url variety (works for Shoutcast 1 servers)
        $info_url = $root_url . '/7.html';
        curl_setopt($ch, CURLOPT_URL, $info_url);
        $html = curl_exec($ch);
        $html = strip_tags($html);
        $info = explode(',', $html);
        $track = html_entity_decode( trim($info[6]) );
    }
}
// get info from Icecast stream
else if( $radio_type === 'icecast' ) {
    $icy_metaint = -1;
    $needle = 'StreamTitle=';

    $opts = array(
        'http' => array(
            'method' => 'GET',
            'header' => 'Icy-MetaData: 1',
            'user_agent' => $ua
        )
    );

    $default = stream_context_set_default($opts);

    $stream = fopen($stream_url, 'r');

    if( $stream && ($meta_data = stream_get_meta_data($stream)) && isset( $meta_data['wrapper_data'] ) ) {
        foreach ($meta_data['wrapper_data'] as $header) {
            if (strpos(strtolower($header), 'icy-metaint') !== false) {
                $tmp = explode(":", $header);
                $icy_metaint = trim($tmp[1]);
                break;
            }
        }
    }

    if( $icy_metaint != -1 ) {
        $buffer = stream_get_contents($stream, 300, $icy_metaint);

        if( strpos($buffer, $needle) !== false ) {
            $title = explode($needle, $buffer);
            $title = trim($title[1]);
            $track = trim( substr($title, 1, strpos($title, ';') - 2) );
        }
    }

    if($stream) {
        fclose($stream);
    }
}



// now process the obtained track info
if( empty($track) ) {  // no information could be obtained
    curl_close($ch);
    $result = array('status' => 'locked');
}
else if( $track == $old_track ) {  // the same song is playing
    curl_close($ch);
    $result = array('status' => 'same');
}
else {  // new song is playing so get cover art
    if( $pull_cover ) {
        $url = 'https://itunes.apple.com/search?term='.urlencode($track).'&media=music&entity=musicTrack,album&limit=1';
        curl_setopt($ch, CURLOPT_URL, $url);
        $cover_response = curl_exec($ch);
        $cover = json_decode($cover_response, true);
        $cover = $cover['results'][0]['artworkUrl100'];
        $cover = str_replace('100x100', '600x600', $cover);
        curl_close($ch);

        $result = array('status' => 'new', 'track' => $track, 'cover' => $cover);
    }
    else {
        curl_close($ch);

        $result = array('status' => 'new', 'track' => $track);
    }

}


$result = json_encode($result);

header('Content-type: application/json; charset=UTF-8');
echo $result;