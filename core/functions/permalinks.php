<?php
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}
function search_permalink( $query ) {
  if (empty($query)) {
    return site_url();
  }

  // Decode URL first, then normalize spacing and duplicated words
  $query = urldecode( $query );
  $query = trim( $query );
  if ($query === '') {
    return site_url();
  }

  // Treat dashes/underscores as separators when normalizing
  $query = str_replace( [ '-', '_' ], ' ', $query );
  $query = preg_replace( '/\s+/u', ' ', $query );

  if ($query === '') {
    return site_url();
  }

  if (function_exists('normalize_keyword_phrase')) {
    $query = normalize_keyword_phrase( $query );
  } else {
    // Fallback normalization: strip duplicate tokens while preserving order
    $words = preg_split('/\s+/u', $query);
    $unique_map = [];
    $normalized_words = [];

    foreach ($words as $word) {
      $key = mb_strtolower($word, 'UTF-8');
      if (!isset($unique_map[$key])) {
        $unique_map[$key] = true;
        $normalized_words[] = $word;
      }
    }

    $query = implode(' ', $normalized_words);
  }

  $query = trim($query);
  if ($query === '') {
    return site_url();
  }

  // Convert to lowercase before processing to ensure consistent URLs
  $query = mb_strtolower( $query, 'UTF-8' );
  $query = permalink( $query );
  // Ensure final result is lowercase (double check)
  $query = strtolower( $query );
  $permalink_option = get_option( 'search_permalink' );
  $permalink_option = $permalink_option ?: '%query%';
  $slug = str_replace( '%query%', $query, $permalink_option );
  return site_url() . '/' . $slug;
}

function search_pagination_permalink( $query, $page = 1 ) {
  if (empty($query)) {
    return site_url();
  }
  $base_url = search_permalink($query);
  if ($page <= 1) {
    return $base_url;
  }
  // Add /page/ prefix if not already present
  if (substr($base_url, -1) !== '/') {
    $base_url .= '/';
  }
  return $base_url . 'page/' . $page;
}

function download_permalink( $id ) {
  if (empty($id)) {
    return site_url();
  }
  $permalink_option = get_option( 'download_permalink' );
  $permalink_option = $permalink_option ?: '%id%';
  $full_slug = str_replace( '%id%', $id, $permalink_option );
  return site_url() . '/' . $full_slug;
}

function playlist_permalink( $slug ) {
  if (empty($slug)) {
    return site_url();
  }
  $permalink_option = get_option( 'playlist_permalink' );
  $permalink_option = $permalink_option ?: '%slug%';
  $full_slug = str_replace( '%slug%', $slug, $permalink_option );
  return site_url() . '/' . $full_slug;
}

function page_permalink( $slug ) {
  if (empty($slug)) {
    return site_url();
  }
  $permalink_option = get_option( 'page_permalink' );
  $permalink_option = $permalink_option ?: '%slug%';
  $full_slug = str_replace( '%slug%', $slug, $permalink_option );
  return site_url() . '/' . $full_slug;
}

function sitemap_searches_permalink() {
  $permalink_option = get_option( 'sitemap_searches_permalink' );
  $permalink_option = $permalink_option ?: 'sitemap/searches.xml';
  return site_url() . '/' . $permalink_option;
}

function sitemap_keywords_permalink( $num ) {
  if (empty($num)) {
    return site_url();
  }
  $permalink_option = get_option( 'sitemap_keywords_permalink' );
  $permalink_option = $permalink_option ?: '%num%.xml';
  $slug = str_replace( '%num%', $num, $permalink_option );
  return site_url() . '/' . $slug;
}

function sitemap_permalink() {
  $permalink_option = get_option( 'sitemap_permalink' );
  $permalink_option = $permalink_option ?: 'sitemap.xml';
  return site_url() . '/' . $permalink_option;
}

/**
 * Generate canonical URL for specific content
 */
function get_canonical_url($type = '', $params = []) {
  switch ($type) {
    case 'search':
      $query = $params['query'] ?? '';
      return search_permalink($query);
      
    case 'download':
      $id = $params['id'] ?? '';
      return download_permalink($id);
      
    case 'playlist':
      $slug = $params['slug'] ?? '';
      return playlist_permalink($slug);
      
    case 'page':
      $page = $params['page'] ?? 1;
      if ($page == 1) {
        return site_url();
      } else {
        return site_url() . '/page/' . $page;
      }
      
    case 'home':
    default:
      return site_url();
  }
}

