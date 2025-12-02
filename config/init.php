<?php
if (!defined('BASE')) {
    define('BASE', dirname(__DIR__));
}

// Define the PATH constant - this should be the base URL path
if (!defined('PATH')) {
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    // Pastikan hanya path, tanpa protokol/host
    $scriptName = str_replace(['http://', 'https://', $_SERVER['HTTP_HOST']], '', $scriptName);
    define('PATH', $scriptName === '\\' ? '' : $scriptName);
}

$config = require 'default/common.php';

if ( file_exists( BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/common.php' ) ) {
  $site_config = require $_SERVER['HTTP_HOST'] . '/common.php';

  if (
    isset( $config['use_default_store'] ) && $config['use_default_store'] ||
    isset( $site_config['use_default_store'] ) && $site_config['use_default_store']
  ) {
    $use_default_store = true;
  } else {
    $use_default_store = false;
  }

  if ( ! $use_default_store && ! is_dir( BASE . '/store/' . $_SERVER['HTTP_HOST'] ) && ! @mkdir( BASE . '/store/' . $_SERVER['HTTP_HOST'], 0755, true ) ) {
    exit( 'Can\'t create store directory. Please check your folder permission.' );
  }
}

$routes = require 'default/routes.php';

if ( file_exists( BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/routes.php' ) ) {
  $site_routes = require $_SERVER['HTTP_HOST'] . '/routes.php';
  $routes = array_merge( $routes, $site_routes );
}
