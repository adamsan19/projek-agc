<?php
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}
function get_option( $key = null, $default = null ) {
  global $config, $site_config;

  if ( ! is_null( $key ) && isset( $site_config[$key] ) ) {
    return $site_config[$key];
  } elseif ( ! is_null( $key ) && isset( $config[$key] ) ) {
    return $config[$key];
  } else {
    if ( $default ) {
      return $default;
    }
  }
}
