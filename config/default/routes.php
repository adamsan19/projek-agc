<?php
return [
  '/' => [ 'name' => 'home', 'file' => 'home.php' ],
  // Home pagination route - must be before other routes to match first
  'page/([0-9]+)' => [ 'name' => 'home-paged', 'file' => 'home.php' ],
  // Search pagination route - must be before search route to match first
  'f/([^/!#$&*()+={}\[\]|;,]+)/page/([0-9]+)' => [ 'name' => 'search-paged', 'file' => 'search.php' ],
  'search/([^/!#$&*()+={}\[\]|;,]+)/page/([0-9]+)' => [ 'name' => 'search-paged', 'file' => 'search.php' ],
  'search/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'search', 'file' => 'search.php' ],
  'download/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'download', 'file' => 'download.php' ],
  'dl/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'dl ', 'file' => 'dl.php' ],
  'p/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'player', 'file' => 'player.php' ],
  'playlist/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'playlist', 'file' => 'playlist.php' ],
  'sitemap/searches.xml' => [ 'name' => 'sitemap-searches', 'file' => 'sitemap.php' ],
  'sitemap/([0-9-]+).xml' => [ 'name' => 'sitemap-keywords', 'file' => 'sitemap.php' ],
  'sitemap.xml' => [ 'name' => 'sitemap-index', 'file' => 'sitemap.php' ],
  'api/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'api', 'file' => 'api.php' ],
  'api/list/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'api', 'file' => 'core/classes/api.php' ],
  'api/search/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'api', 'file' => 'core/classes/api.php' ],
  'api/info/([^/!#$&*()+={}\[\]|;,]+)' => [ 'name' => 'api', 'file' => 'core/classes/api.php' ],
  'api/fetch' => [ 'name' => 'api', 'file' => 'fetch_background.php' ],
];
