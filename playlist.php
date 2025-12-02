<?php
if (!defined('BASE')) {
    define('BASE', __DIR__);
}

require 'config/init.php';
require 'libraries/ua.class.php';
require 'libraries/simple_html_dom.php';

require 'core/functions/options.php';
require 'core/functions/cache.php';
require 'core/functions/permalinks.php';
require 'core/functions/common.php';
require 'core/functions/site.php';

require 'core/classes/agc.php';

$playlist_slug = isset($route['vars'][0]) ? $route['vars'][0] : null;
if ($playlist_slug) {
    // Check playlist cache
    $cache_file = get_playlist_path() . '/' . md5($playlist_slug) . '.json';
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < get_option('cache_time', 43200))) {
        $playlist_data = get_cache($cache_file);
    } else {
        $agc = new AGC();
        $playlist_data = $agc->get_playlist();
        
        if ($playlist_data) {
            create_cache($cache_file, $playlist_data);
        }
    }
}

if ( $redirect = badword_redirect() ) {
  redirect( $redirect );
}

dmca_redirect();

$result = agc()->get_playlist();
$data = agc()->get_playlist_data();
$playlist = agc()->get_youtube_playlist();
$searches = get_recent_user_access( get_option( 'recent_searches_count' ) );

$site_title = unique(str_replace( [ '%title%', '%size%', '%site_name%', '%domain%' ], [ $data['title'], $result[0]['size'], get_option( 'site_name' ), $_SERVER['HTTP_HOST'] ], get_option( 'playlist_title' ) ));
$meta_description = unique(str_replace( [ '%title%', '%size%', '%description%', '%site_name%', '%domain%' ], [ $data['title'], $result[0]['size'], $data['description'], get_option( 'site_name' ), $_SERVER['HTTP_HOST'] ], get_option( 'playlist_description' ) ));
$meta_robots = get_option( 'playlist_robots' );

$theme = get_option('theme');
function include_header() {
    $theme = get_option('theme');
    if ($theme == 'none') {
        require 'header.php';
    } else {
        require 'themes/' . $theme . '/header.php';
    }
}
function include_footer() {
    $theme = get_option('theme');
    if ($theme == 'none') {
        require 'footer.php';
    } else {
        require 'themes/' . $theme . '/footer.php';
    }
}

include_header();
require 'playlist.php';
include_footer();
?>