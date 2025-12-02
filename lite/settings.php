<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Sites Setting';
require 'includes/header.php';
require '../libraries/ua.class.php';
require '../libraries/simple_html_dom.php';
require '../core/functions/options.php';
require '../core/functions/cache.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Sites Setting</h1>
</div>
<?php
// Load config: prefer domain-specific, fallback to default
if ( file_exists( '../config/' . $_SERVER['HTTP_HOST'] . '/config.json' ) ) {
$cache_file = '../config/' . $_SERVER['HTTP_HOST'] . '/config.json';
}else{
$cache_file = '../config/default/config.json';
}

// Always save to default config.json
$save_file = '../config/default/config.json';
$folder = '../';
if(!($buka_folder = opendir($folder)));

$file_array = array(); 
while($baca_folder = readdir($buka_folder))
 {
  if(substr($baca_folder,0,1) != '.')
   {
     $file_array[] =  $baca_folder;
    }
 }
if(isset($_POST['change'])){
$cache['site_name'] = $_POST['site_name'];
$cache['site_tagline'] = $_POST['site_tagline'];
$cache['google_verify'] = $_POST['google_verify'];
$cache['home_title'] = $_POST['home_title'];
$cache['home_description'] = $_POST['home_description'];
$cache['home_robots'] = $_POST['home_robots'];
$cache['search_title'] = $_POST['search_title'];
$cache['search_description'] = $_POST['search_description'];
$cache['search_robots'] = $_POST['search_robots'];
$cache['download_title'] = $_POST['download_title'];
$cache['download_description'] = $_POST['download_description'];
$cache['download_robots'] = $_POST['download_robots'];
$cache['playlist_title'] = $_POST['playlist_title'];
$cache['playlist_description'] = $_POST['playlist_description'];
$cache['playlist_robots'] = $_POST['playlist_robots'];
$cache['page_title'] = $_POST['page_title'];
$cache['page_description'] = $_POST['page_description'];
$cache['page_robots'] = $_POST['page_robots'];
$cache['search_page_title'] = $_POST['search_page_title'];
$cache['download_page_title'] = $_POST['download_page_title'];
$cache['theme'] = $_POST['theme'];
$cache['search_permalink'] = $_POST['search_permalink'];
$cache['download_permalink'] = $_POST['download_permalink'];
$cache['playlist_permalink'] = $_POST['playlist_permalink'];
$cache['page_permalink'] = $_POST['page_permalink'];
$cache['sitemap_searches_permalink'] = $_POST['sitemap_searches_permalink'];
$cache['sitemap_keywords_permalink'] = $_POST['sitemap_keywords_permalink'];
$cache['sitemap_index_permalink'] = $_POST['sitemap_index_permalink'];
$cache['itunes_country'] = $_POST['itunes_country'];
$cache['itunes_count'] = $_POST['itunes_count'];
$cache['youtube_playlist_count'] = $_POST['youtube_playlist_count'];
$cache['youtube_playlist_id'] = $_POST['youtube_playlist_id'];
$cache['youtube_top_videos_count'] = $_POST['youtube_top_videos_count'];
$cache['youtube_top_videos_country'] = $_POST['youtube_top_videos_country'];
$cache['youtube_search_count'] = $_POST['youtube_search_count'];
$cache['playlist_count'] = $_POST['playlist_count'];
$cache['youtube_related_count'] = $_POST['youtube_related_count'];
$cache['youtube_api_keys'] = $_POST['youtube_api_keys'];
$cache['sitemap_limit'] = $_POST['sitemap_limit'];
$cache['use_default_store'] = $_POST['use_default_store'];
$cache['enable_cache'] = $_POST['enable_cache'];
$cache['enable_cache_download'] = $_POST['enable_cache_download'];
$cache['enable_cache_unique'] = $_POST['enable_cache_unique'];
$cache['cache_time'] = $_POST['cache_time'];
$cache['save_recent_searches'] = $_POST['save_recent_searches'];
$cache['recent_searches_limit'] = $_POST['recent_searches_limit'];
$cache['recent_searches_count'] = $_POST['recent_searches_count'];
$cache['use_default_keyword_files'] = $_POST['use_default_keyword_files'];
$cache['footer_copyright'] = $_POST['footer_copyright'];
$cache['photon_cdn'] = $_POST['photon_cdn'];
$cache['cache_duration_home'] = $_POST['cache_duration_home'];
$cache['cache_duration_search'] = $_POST['cache_duration_search'];
$cache['cache_duration_mobile'] = $_POST['cache_duration_mobile'];
$cache['ad_code_custom'] = $_POST['ad_code_custom'];
$cache['ad_code_header'] = $_POST['ad_code_header'];
$cache['ad_code_player'] = $_POST['ad_code_player'];
$cache['ad_code_footer'] = $_POST['ad_code_footer'];
$cache['ad_code_sticky'] = $_POST['ad_code_sticky'];
$cache['enable_redis_cache'] = $_POST['enable_redis_cache'];
$cache['redis_host'] = $_POST['redis_host'];
$cache['redis_port'] = $_POST['redis_port'];
$cache['redis_auth'] = $_POST['redis_auth'];
$cache['redis_timeout'] = $_POST['redis_timeout'];
$cache['redis_pool_size'] = $_POST['redis_pool_size'];
$cache['redis_idle_timeout'] = $_POST['redis_idle_timeout'];

if ( isset( $cache ) ) {
  // Ensure directory exists
  $save_dir = dirname($save_file);
  if (!is_dir($save_dir)) {
    @mkdir($save_dir, 0755, true);
  }
  
  // Save to config/default/config.json with pretty print
  $json_content = json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  if (file_put_contents($save_file, $json_content) !== false) {
    // Success message will be shown
  } else {
    echo '<div class="card bg-danger text-white shadow mb-2"><div class="card-body"><b>Error:</b> Failed to save configuration file. Please check folder permissions.</div></div>';
  }
}
?>
<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
<b>Sites Setting</b> successfully updated
</div>
</div>
<?php
}
$cache = json_decode( file_get_contents( $cache_file ), true );
?>
<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-header">
<ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
<li class="nav-item">
<a class="nav-link active" id="pills-general-tab" data-toggle="pill" href="#pills-general" role="tab" aria-controls="pills-general" aria-selected="true">General</a>
</li>
<li class="nav-item">
<a class="nav-link" id="pills-seo-tab" data-toggle="pill" href="#pills-seo" role="tab" aria-controls="pills-seo" aria-selected="false">SEO</a>
</li>
<li class="nav-item">
<a class="nav-link" id="pills-agc-tab" data-toggle="pill" href="#pills-agc" role="tab" aria-controls="pills-agc" aria-selected="false">AGC</a>
</li>
<li class="nav-item">
<a class="nav-link" id="pills-slug-tab" data-toggle="pill" href="#pills-slug" role="tab" aria-controls="pills-slug" aria-selected="false">Slug</a>
</li>
<li class="nav-item">
<a class="nav-link" id="pills-caching-tab" data-toggle="pill" href="#pills-caching" role="tab" aria-controls="pills-caching" aria-selected="false">Caching</a>
</li>
<li class="nav-item">
<a class="nav-link" id="pills-adcode-tab" data-toggle="pill" href="#pills-adcode" role="tab" aria-controls="pills-adcode" aria-selected="false">Ad Code</a>
</li>
</ul>
</div>
<form method="post">
<div class="tab-content" id="pills-tabContent">
<div class="tab-pane fade show active" id="pills-general" role="tabpanel" aria-labelledby="pills-general-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Sitename</label>
<div class="col-md-6">
<input class="form-control" type="text" name="site_name" value="<?php echo $cache['site_name']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Tagline</label>
<div class="col-md-6">
<input class="form-control" type="text" name="site_tagline" value="<?php echo $cache['site_tagline']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Google Verify</label>
<div class="col-md-6">
<input class="form-control" type="text" name="google_verify" value="<?php echo $cache['google_verify']; ?>">
<div class="card-sub">Example: 5jEbBD1ityNmh38Ekd0YfXM_fyfCS8wS2</div>
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
<div class="tab-pane fade" id="pills-seo" role="tabpanel" aria-labelledby="pills-seo-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Home Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="home_title" value="<?php echo $cache['home_title']; ?>">
<div class="card-sub">%site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Home Description</label>
<div class="col-md-6">
<textarea class="form-control" name="home_description"><?php echo $cache['home_description']; ?></textarea>
<div class="card-sub">%site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Home Robots</label>
<div class="col-md-6">
<input class="form-control" type="text" name="home_robots" value="<?php echo $cache['home_robots']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Search Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="search_title" value="<?php echo $cache['search_title']; ?>">
<div class="card-sub">%query%, %size%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Search Description</label>
<div class="col-md-6">
<textarea class="form-control" name="search_description"><?php echo $cache['search_description']; ?></textarea>
<div class="card-sub">%query%, %size%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Search Robots</label>
<div class="col-md-6">
<input class="form-control" type="text" name="search_robots" value="<?php echo $cache['search_robots']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Download Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="download_title" value="<?php echo $cache['download_title']; ?>">
<div class="card-sub">%title%, %size%, %duration%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Download Description</label>
<div class="col-md-6">
<textarea class="form-control" name="download_description"><?php echo $cache['download_description']; ?></textarea>
<div class="card-sub">%title%, %size%, %duration%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Download Robots</label>
<div class="col-md-6">
<input class="form-control" type="text" name="download_robots" value="<?php echo $cache['download_robots']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Playlist Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="playlist_title" value="<?php echo $cache['playlist_title']; ?>">
<div class="card-sub">%title%, %size%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Playlist Description</label>
<div class="col-md-6">
<textarea class="form-control" name="playlist_description"><?php echo $cache['playlist_description']; ?></textarea>
<div class="card-sub">%title%, %size%, %description%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Playlist Robots</label>
<div class="col-md-6">
<input class="form-control" type="text" name="playlist_robots" value="<?php echo $cache['playlist_robots']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Pages Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="page_title" value="<?php echo $cache['page_title']; ?>">
<div class="card-sub">%title%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Pages Description</label>
<div class="col-md-6">
<textarea class="form-control" name="page_description"><?php echo $cache['page_description']; ?></textarea>
<div class="card-sub">%title%, %site_name%, %domain%</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Pages Robots</label>
<div class="col-md-6">
<input class="form-control" type="text" name="page_robots" value="<?php echo $cache['page_robots']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Search Page Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="search_page_title" value="<?php echo $cache['search_page_title']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Download Page Title</label>
<div class="col-md-6">
<input class="form-control" type="text" name="download_page_title" value="<?php echo $cache['download_page_title']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Footer Copyright</label>
<div class="col-md-6">
<input class="form-control" type="text" name="footer_copyright" value="<?php echo $cache['footer_copyright']; ?>">
<div class="card-sub">%year%, %site_name%</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Theme</label>
<div class="col-md-6">
<select class="form-control form-control" name="theme">
<?php foreach($file_array as $index => $nama_file){?>
	<option value="<?php echo $nama_file; ?>"  <?php if($cache['theme'] == "".$nama_file."") echo "selected"; ?>><?php echo $nama_file; ?></option>
<?php } ?>
<option value="none" <?php if($cache['theme'] == "none") echo "selected"; ?>>Tanpa Theme</option>
</select>
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
<div class="tab-pane fade" id="pills-slug" role="tabpanel" aria-labelledby="pills-slug-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Search Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="search_permalink" value="<?php echo $cache['search_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Download Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="download_permalink" value="<?php echo $cache['download_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Playlist Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="playlist_permalink" value="<?php echo $cache['playlist_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Pages Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="page_permalink" value="<?php echo $cache['page_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Sitemap Searches Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="sitemap_searches_permalink" value="<?php echo $cache['sitemap_searches_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Sitemap Keywords Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="sitemap_keywords_permalink" value="<?php echo $cache['sitemap_keywords_permalink']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Sitemap Index Slug</label>
<div class="col-md-6">
<input class="form-control" type="text" name="sitemap_index_permalink" value="<?php echo $cache['sitemap_index_permalink']; ?>">
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
<div class="tab-pane fade" id="pills-agc" role="tabpanel" aria-labelledby="pills-agc-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube API</label>
<div class="col-md-6">
<textarea class="form-control" name="youtube_api_keys"><?php echo $cache['youtube_api_keys']; ?></textarea>
<div class="card-sub">You can add more Youtube API key, separate with commas.</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Playlist ID</label>
<div class="col-md-6">
<input class="form-control" type="text" name="youtube_playlist_id" value="<?php echo $cache['youtube_playlist_id']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Playlist Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="youtube_playlist_count" value="<?php echo $cache['youtube_playlist_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Playlist Page Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="playlist_count" value="<?php echo $cache['playlist_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Top Videos Country</label>
<div class="col-md-6">
<input class="form-control" type="text" name="youtube_top_videos_country" value="<?php echo $cache['youtube_top_videos_country']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Top Videos Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="youtube_top_videos_count" value="<?php echo $cache['youtube_top_videos_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Search Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="youtube_search_count" value="<?php echo $cache['youtube_search_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Youtube Related Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="youtube_related_count" value="<?php echo $cache['youtube_related_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">iTunes Country</label>
<div class="col-md-6">
<input class="form-control" type="text" name="itunes_country" value="<?php echo $cache['itunes_country']; ?>">
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">iTunes Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="itunes_count" value="<?php echo $cache['itunes_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Sitemap Limit</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="sitemap_limit" value="<?php echo $cache['sitemap_limit']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Recent Searches Limit</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="recent_searches_limit" value="<?php echo $cache['recent_searches_limit']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Recent Searches Count</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="recent_searches_count" value="<?php echo $cache['recent_searches_count']; ?>">
<div class="input-group-append">
<span class="input-group-text">items</span>
</div>
</div>
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
<div class="tab-pane fade" id="pills-caching" role="tabpanel" aria-labelledby="pills-caching-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Use Default Store</label>
<div class="col-md-6">
<select class="form-control form-control" name="use_default_store">
	<option value="" <?php if($cache['use_default_store'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['use_default_store'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to use a directory based on the domain</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Save Recent Searches</label>
<div class="col-md-6">
<select class="form-control form-control" name="save_recent_searches">
	<option value="" <?php if($cache['save_recent_searches'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['save_recent_searches'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to save recent search to sitemap</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Use Default Keyword Files</label>
<div class="col-md-6">
<select class="form-control form-control" name="use_default_keyword_files">
	<option value="" <?php if($cache['use_default_keyword_files'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['use_default_keyword_files'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to use a keywords directory based on the default directory</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Home</label>
<div class="col-md-6">
<select class="form-control form-control" name="enable_cache">
	<option value="" <?php if($cache['enable_cache'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['enable_cache'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to save the data in json files</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Download</label>
<div class="col-md-6">
<select class="form-control form-control" name="enable_cache_download">
	<option value="" <?php if($cache['enable_cache_download'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['enable_cache_download'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to save the data in json files</div>
</div>
</div>
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Unique</label>
<div class="col-md-6">
<select class="form-control form-control" name="enable_cache_unique" disabled>
	<option value="" <?php if($cache['enable_cache_unique'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['enable_cache_unique'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to save the data in json files</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Expiration Time</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="cache_time" value="<?php echo $cache['cache_time']; ?>">
<div class="input-group-append">
<span class="input-group-text">second</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Duration Home</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="cache_duration_home" value="<?php echo $cache['cache_duration_home']; ?>">
<div class="input-group-append">
<span class="input-group-text">second</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Duration Search</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="cache_duration_search" value="<?php echo $cache['cache_duration_search']; ?>">
<div class="input-group-append">
<span class="input-group-text">second</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Cache Duration Mobile</label>
<div class="col-md-6">
<div class="input-group mb-3">
<input class="form-control" type="number" name="cache_duration_mobile" value="<?php echo $cache['cache_duration_mobile']; ?>">
<div class="input-group-append">
<span class="input-group-text">second</span>
</div>
</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Photon CDN</label>
<div class="col-md-6">
<select class="form-control form-control" name="photon_cdn">
	<option value="" <?php if($cache['photon_cdn'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['photon_cdn'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to use Photon CDN</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Enable Redis Cache</label>
<div class="col-md-6">
<select class="form-control form-control" name="enable_redis_cache">
	<option value="" <?php if($cache['enable_redis_cache'] == "") echo "selected"; ?>>No</option>
	<option value="1"  <?php if($cache['enable_redis_cache'] == "1") echo "selected"; ?>>Yes</option>
</select>
<div class="card-sub">Select "<b>yes</b>" if you want to use Redis cache</div>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Host</label>
<div class="col-md-6">
<input class="form-control" type="text" name="redis_host" value="<?php echo $cache['redis_host']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Port</label>
<div class="col-md-6">
<input class="form-control" type="number" name="redis_port" value="<?php echo $cache['redis_port']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Auth</label>
<div class="col-md-6">
<input class="form-control" type="text" name="redis_auth" value="<?php echo $cache['redis_auth']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Timeout</label>
<div class="col-md-6">
<input class="form-control" type="number" name="redis_timeout" value="<?php echo $cache['redis_timeout']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Pool Size</label>
<div class="col-md-6">
<input class="form-control" type="number" name="redis_pool_size" value="<?php echo $cache['redis_pool_size']; ?>">
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Redis Idle Timeout</label>
<div class="col-md-6">
<input class="form-control" type="number" name="redis_idle_timeout" value="<?php echo $cache['redis_idle_timeout']; ?>">
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
<div class="tab-pane fade" id="pills-adcode" role="tabpanel" aria-labelledby="pills-adcode-tab">
<div class="card-body">
<div class="form-group row">
<label class="col-md-2 col-form-label">Ad Code Custom</label>
<div class="col-md-6">
<textarea class="form-control" name="ad_code_custom" rows="4"><?php echo $cache['ad_code_custom']; ?></textarea>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Ad Code Header</label>
<div class="col-md-6">
<textarea class="form-control" name="ad_code_header" rows="4"><?php echo $cache['ad_code_header']; ?></textarea>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Ad Code Player</label>
<div class="col-md-6">
<textarea class="form-control" name="ad_code_player" rows="4"><?php echo $cache['ad_code_player']; ?></textarea>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Ad Code Footer</label>
<div class="col-md-6">
<textarea class="form-control" name="ad_code_footer" rows="4"><?php echo $cache['ad_code_footer']; ?></textarea>
</div>
</div>
<hr style="margin-top:15px;margin-bottom:15px;">
<div class="form-group row">
<label class="col-md-2 col-form-label">Ad Code Sticky</label>
<div class="col-md-6">
<textarea class="form-control" name="ad_code_sticky" rows="4"><?php echo $cache['ad_code_sticky']; ?></textarea>
</div>
</div>
</div>
<div class="card-footer">
<input type="submit" class="btn btn-primary" name="change" value="Update">
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</div>
</div>
</form>
</div>
</div>
</div>

</div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>