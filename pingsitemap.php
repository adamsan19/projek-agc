<?php
define( 'IDC', dirname( __FILE__ ) );
$base = str_replace( "\\", "/", IDC );
$path = str_replace( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ), '', $base );
if ( $base == $path ) {
	$base = str_replace( array( "public_html", "\\" ), array( "www", "/" ), IDC );
	$path = str_replace( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ), '', $base );
}
define( 'BASE', $base, TRUE );
define( 'PATH', $path, TRUE );
require 'config/init.php';
require 'core/functions/options.php';

$root = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
$sitemap_url = $root. ''.get_option( 'sitemap_index_permalink' );
$send_email = false;
$email = isset($_GET['email']) ? $_GET['email'] : '';
function SendSiteMapUpdateIndicationPing($sitemap_url){
	$curl_req = array();
	$urls = array();
	$urls[] = "http://www.google.com/webmasters/tools/ping?sitemap=".urlencode($sitemap_url);
	$urls[] = "http://www.bing.com/webmaster/ping.aspx?siteMap=".urlencode($sitemap_url);

	foreach ($urls as $url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURL_HTTP_VERSION_1_1, 1);
		$curl_req[] = $curl;
	}

	$multiHandle = curl_multi_init();

	foreach($curl_req as $key => $curl)
	{
		curl_multi_add_handle($multiHandle,$curl);
	}
	do
	{
		$multi_curl = curl_multi_exec($multiHandle, $isactive);
	}
	while ($isactive || $multi_curl == CURLM_CALL_MULTI_PERFORM );
	
	$success = true;
	foreach($curl_req as $curlO)
	{
		if(curl_errno($curlO) != CURLE_OK)
		{
			$success = false;
		}
	}

	curl_multi_close($multiHandle);
	return $success;
}

$success = SendSiteMapUpdateIndicationPing($sitemap_url) ? 'Sukses' : 'Gagal';

$headers = 'From: '. $email . "\r\n" .
    'Reply-To: ' . $email . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

$title = $message = $success . ' ping ' . $sitemap_url;

if($send_email){
	// Send ping result to email
	echo $title;
	mail($email, $title , $message . ' ke Google dan Bing' . "\r\n\r\n Thanks ", $headers);
}
echo $title;