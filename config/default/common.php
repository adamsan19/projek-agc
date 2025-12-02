<?php
if (!defined('BASE')) {
    define('BASE', dirname(dirname(__DIR__)));
}

if (file_exists(BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/config.json')) {
    $cache_file = BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/config.json';
} else {
    $cache_file = BASE . '/config/default/config.json';
}

$cache = json_decode(file_get_contents($cache_file), true);
if (!$cache) {
    die('Error loading configuration file: ' . $cache_file);
}

return [
    'site_name' => isset($cache['site_name']) ? $cache['site_name'] : '',
    'site_tagline' => isset($cache['site_tagline']) ? $cache['site_tagline'] : '',
    'google_verify' => isset($cache['google_verify']) ? $cache['google_verify'] : '',
    'home_title' => isset($cache['home_title']) ? $cache['home_title'] : '',
    'home_description' => isset($cache['home_description']) ? $cache['home_description'] : '',
    'home_robots' => isset($cache['home_robots']) ? $cache['home_robots'] : '',
    'search_title' => isset($cache['search_title']) ? $cache['search_title'] : '',
    'search_description' => isset($cache['search_description']) ? $cache['search_description'] : '',
    'search_robots' => isset($cache['search_robots']) ? $cache['search_robots'] : '',
    'download_title' => isset($cache['download_title']) ? $cache['download_title'] : '',
    'download_description' => isset($cache['download_description']) ? $cache['download_description'] : '',
    'download_robots' => isset($cache['download_robots']) ? $cache['download_robots'] : '',
    'playlist_title' => isset($cache['playlist_title']) ? $cache['playlist_title'] : '',
    'playlist_description' => isset($cache['playlist_description']) ? $cache['playlist_description'] : '',
    'playlist_robots' => isset($cache['playlist_robots']) ? $cache['playlist_robots'] : '',
    'page_title' => isset($cache['page_title']) ? $cache['page_title'] : '',
    'page_description' => isset($cache['page_description']) ? $cache['page_description'] : '',
    'page_robots' => isset($cache['page_robots']) ? $cache['page_robots'] : '',
    'search_page_title' => isset($cache['search_page_title']) ? $cache['search_page_title'] : '',
    'download_page_title' => isset($cache['download_page_title']) ? $cache['download_page_title'] : '',
    'theme' => isset($cache['theme']) ? $cache['theme'] : '',
    'search_permalink' => isset($cache['search_permalink']) ? $cache['search_permalink'] : '',
    'download_permalink' => isset($cache['download_permalink']) ? $cache['download_permalink'] : '',
    'playlist_permalink' => isset($cache['playlist_permalink']) ? $cache['playlist_permalink'] : '',
    'page_permalink' => isset($cache['page_permalink']) ? $cache['page_permalink'] : '',
    'sitemap_searches_permalink' => isset($cache['sitemap_searches_permalink']) ? $cache['sitemap_searches_permalink'] : '',
    'sitemap_keywords_permalink' => isset($cache['sitemap_keywords_permalink']) ? $cache['sitemap_keywords_permalink'] : '',
    'sitemap_index_permalink' => isset($cache['sitemap_index_permalink']) ? $cache['sitemap_index_permalink'] : '',
    'itunes_country' => isset($cache['itunes_country']) ? $cache['itunes_country'] : '',
    'itunes_count' => isset($cache['itunes_count']) ? $cache['itunes_count'] : 0,
    'youtube_playlist_count' => isset($cache['youtube_playlist_count']) ? $cache['youtube_playlist_count'] : 0,
    'youtube_playlist_id' => isset($cache['youtube_playlist_id']) ? $cache['youtube_playlist_id'] : '',
    'playlist_count' => isset($cache['playlist_count']) ? $cache['playlist_count'] : 0,
    'youtube_top_videos_count' => isset($cache['youtube_top_videos_count']) ? $cache['youtube_top_videos_count'] : 0,
    'youtube_top_videos_country' => isset($cache['youtube_top_videos_country']) ? $cache['youtube_top_videos_country'] : '',
    'youtube_search_count' => isset($cache['youtube_search_count']) ? $cache['youtube_search_count'] : 0,
    'youtube_related_count' => isset($cache['youtube_related_count']) ? $cache['youtube_related_count'] : 0,
    'youtube_api_keys' => isset($cache['youtube_api_keys']) ? $cache['youtube_api_keys'] : '',
    'sitemap_limit' => isset($cache['sitemap_limit']) ? $cache['sitemap_limit'] : 0,
    'use_default_store' => isset($cache['use_default_store']) ? $cache['use_default_store'] : 0,
    'enable_cache' => isset($cache['enable_cache']) ? $cache['enable_cache'] : 0,
    'enable_cache_download' => isset($cache['enable_cache_download']) ? $cache['enable_cache_download'] : 0,
    'enable_cache_unique' => isset($cache['enable_cache_unique']) ? $cache['enable_cache_unique'] : 0,
    'cache_time' => isset($cache['cache_time']) ? $cache['cache_time'] : 0,
    'save_recent_searches' => isset($cache['save_recent_searches']) ? $cache['save_recent_searches'] : 0,
    'recent_searches_limit' => isset($cache['recent_searches_limit']) ? $cache['recent_searches_limit'] : 0,
    'recent_searches_count' => isset($cache['recent_searches_count']) ? $cache['recent_searches_count'] : 0,
    'use_default_keyword_files' => isset($cache['use_default_keyword_files']) ? $cache['use_default_keyword_files'] : 0,
    'footer_copyright' => isset($cache['footer_copyright']) ? $cache['footer_copyright'] : ''
,  'cache_duration_home' => $cache['cache_duration_home'] ?? 3600,
'photon_cdn' => $cache['photon_cdn'] ?? '0',
'cache_duration_search' => $cache['cache_duration_search'] ?? 1800,
'cache_duration_mobile' => $cache['cache_duration_mobile'] ?? 43200,
'ad_code_custom' => $cache['ad_code_custom'] ?? '',
'ad_code_header' => $cache['ad_code_header'] ?? '',
'ad_code_player' => $cache['ad_code_player'] ?? '',
'ad_code_footer' => $cache['ad_code_footer'] ?? '',
'ad_code_sticky' => $cache['ad_code_sticky'] ?? '',
'enable_redis_cache' => $cache['enable_redis_cache'] ?? '0',
'redis_host' => $cache['redis_host'] ?? '127.0.0.1',
'redis_port' => $cache['redis_port'] ?? '6379',
'redis_auth' => $cache['redis_auth'] ?? '',
'redis_timeout' => $cache['redis_timeout'] ?? '1',
'redis_pool_size' => $cache['redis_pool_size'] ?? '10',
'redis_idle_timeout' => $cache['redis_idle_timeout'] ?? '60'
];
