<?php
date_default_timezone_set( 'Asia/Jakarta' );
 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define( 'IDC', dirname( __FILE__ ) );

$base = str_replace( "\\", "/", IDC );
$path = str_replace( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ), '', $base );
if ( $base == $path ) {
	$base = str_replace( array( "public_html", "\\" ), array( "www", "/" ), IDC );
	$path = str_replace( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ), '', $base );
}

define( 'BASE', $base );
define( 'PATH', $path );

require 'config/init.php';

require 'core/functions/general.php';
require 'core/uri.php';
require 'core/router.php';

if ( $found ) {
  $file = BASE . '/' . $route['file'];
  if ( $route['file'] && file_exists( $file ) ) {
    require $file;
    exit();
  } else {
    print_error( "File <strong>$vars[file]</strong> is not found" );
  }
} else {
  print_error( "Page is not found" );
}
