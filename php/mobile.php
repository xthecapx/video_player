<?php

//This file returns 'true' if a mobile device is detected and 'false' otherwise

require_once('mdetect.php');

$mobile = new uagent_info();

if( $mobile->DetectTierTablet() || $mobile->DetectTierIphone() || $mobile->DetectTierRichCss() ) {
	echo 'true';
}
else {
	echo 'false';
}

?>