<?php
define( 'WP_USE_THEMES', false );
require_once '../../../wp-load.php';

header( 'Expires: Tue, 01 Jan 2013 00:00:00 GMT' );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-store, no-cache, must-revalidate' );
header( 'Cache-Control: post-check=0, pre-check=0', false );
header( 'Pragma: no-cache' );

$font_path  = __DIR__ . '/fonts/times_new_yorker.ttf';
$image_path = 'image/45-degree-fabric.png';

$random_string = '';
for ( $i = 0; $i < 5; $i++ ) {
	$random_string .= wp_rand( 0, 9 );
}

set_transient( 'wp2sl_captcha', $random_string, 300 );

$im        = @imagecreatefrompng( $image_path );
$font_color = imagecolorallocate( $im, 0, 0, 0 );
imagettftext( $im, 30, 0, 10, 38, $font_color, $font_path, $random_string );

header( 'Content-type: image/png' );
imagepng( $im, null, 0 );
imagedestroy( $im );
