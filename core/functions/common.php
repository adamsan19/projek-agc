<?php
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

// Polyfills for mbstring functions if extension is not loaded
if ( !function_exists( 'mb_strlen' ) ) {
    function mb_strlen( $str, $encoding = null ) {
        return preg_match_all( '/./us', $str );
    }
}

if ( !function_exists( 'mb_substr' ) ) {
    function mb_substr( $str, $start, $length = null, $encoding = null ) {
        preg_match_all( '/./us', $str, $matches );
        $chars = $matches[0] ?? [];
        if ( $length === null ) {
            return implode( '', array_slice( $chars, $start ) );
        }
        return implode( '', array_slice( $chars, $start, $length ) );
    }
}

if ( !function_exists( 'mb_strtolower' ) ) {
    function mb_strtolower( $str, $encoding = null ) {
        return strtolower( $str );
    }
}

if ( !function_exists( 'mb_strtoupper' ) ) {
    function mb_strtoupper( $str, $encoding = null ) {
        return strtoupper( $str );
    }
}

if ( !function_exists( 'mb_strrpos' ) ) {
    function mb_strrpos( $haystack, $needle, $offset = 0, $encoding = null ) {
        preg_match_all( '/./us', $haystack, $haystackChars );
        preg_match_all( '/./us', $needle, $needleChars );
        $haystackArr = $haystackChars[0] ?? [];
        $needleArr = $needleChars[0] ?? [];
        $needleStr = implode( '', $needleArr );
        $lastPos = false;
        for ( $i = $offset; $i <= count( $haystackArr ) - count( $needleArr ); $i++ ) {
            $segment = implode( '', array_slice( $haystackArr, $i, count( $needleArr ) ) );
            if ( $segment === $needleStr ) {
                $lastPos = $i;
            }
        }
        return $lastPos;
    }
}

if ( !function_exists( 'mb_ord' ) ) {
    function mb_ord( $char, $encoding = null ) {
        $bytes = unpack( 'C*', $char );
        if ( !$bytes ) return false;
        $len = count( $bytes );
        if ( $len === 1 ) {
            return $bytes[1];
        } elseif ( $len === 2 ) {
            return ( ( $bytes[1] & 0x1F ) << 6 ) | ( $bytes[2] & 0x3F );
        } elseif ( $len === 3 ) {
            return ( ( $bytes[1] & 0x0F ) << 12 ) | ( ( $bytes[2] & 0x3F ) << 6 ) | ( $bytes[3] & 0x3F );
        } elseif ( $len === 4 ) {
            return ( ( $bytes[1] & 0x07 ) << 18 ) | ( ( $bytes[2] & 0x3F ) << 12 ) | ( ( $bytes[3] & 0x3F ) << 6 ) | ( $bytes[4] & 0x3F );
        }
        return false;
    }
}

if ( !function_exists( 'mb_convert_encoding' ) ) {
    function mb_convert_encoding( $string, $to_encoding, $from_encoding = null ) {
        // Basic fallback - if iconv is available, use it
        if ( function_exists( 'iconv' ) ) {
            $from = is_array( $from_encoding ) ? 'UTF-8' : ( $from_encoding ?? 'UTF-8' );
            $result = @iconv( $from, $to_encoding . '//IGNORE', $string );
            return $result !== false ? $result : $string;
        }
        // Last resort - return string as-is
        return $string;
    }
}

if ( !function_exists( 'mb_list_encodings' ) ) {
    function mb_list_encodings() {
        return array( 'UTF-8', 'ASCII', 'ISO-8859-1', 'ISO-8859-15', 'CP1252', 'Windows-1252' );
    }
}

if ( !function_exists( 'mb_detect_encoding' ) ) {
    function mb_detect_encoding( $string, $encodings = null, $strict = false ) {
        // Check if valid UTF-8
        if ( preg_match( '//u', $string ) ) {
            return 'UTF-8';
        }
        return 'ISO-8859-1';
    }
}

if ( !function_exists( 'normalize_keyword_phrase' ) ) {
    function normalize_keyword_phrase( $string ) {
        // Trim, collapse whitespace, and drop duplicate tokens while keeping order
        $string = preg_replace( '/\s+/u', ' ', trim( (string) $string ) );
        if ( $string === '' ) {
            return '';
        }

        $words = preg_split( '/\s+/u', $string );
        $unique_map = [];
        $normalized_words = [];

        foreach ( $words as $word ) {
            $key = mb_strtolower( $word, 'UTF-8' );
            if ( !isset( $unique_map[ $key ] ) ) {
                $unique_map[ $key ] = true;
                $normalized_words[] = $word;
            }
        }

        return implode( ' ', $normalized_words );
    }
}

function get_search_query() {
    global $route;
  
    // Handle different route patterns
    if (isset($route["name"])) {
      switch ($route["name"]) {
        case 'search-paged':
          // For /f/query/page/2 or /search/query/page/2 pattern, search query is in vars[0]
          $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
          break;
        case 'carimobil-paged':
          // For /f/query/page/2 pattern, search query is in vars[0]
          $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
          break;
        case 'carimobil-search':
        case 'search':
          // For /f/query or /search/query pattern, search query is in vars[0]
          $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
          break;
        case 'carimobil':
          // For existing post/search/query pattern
          $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
          break;
        default:
          // Fallback to original logic
          $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
          break;
      }
    } else {
      $query = isset($route["vars"][0]) ? $route["vars"][0] : null;
    }
  
    $query = urldecode( ucwords( str_replace(
      [ "don-t", "can-t", "-" ],
      [ "don't", "can't", " " ],
      $query
    ) ) );

    return normalize_keyword_phrase( $query );
  }
  

/**
 * Paging Functions
 * Handles pagination logic for search results and other content
 */

/**
 * Get current page number from URL
 * Supports URL patterns like /f/query/page/2 or ?page=2
 * 
 * @return int Current page number (minimum 1)
 */
function get_current_page() {
    global $route;
    
    // Check if route is home-paged, page number is in vars[0]
    if (isset($route['name']) && $route['name'] === 'home-paged' && isset($route['vars'][0])) {
        $page = intval($route['vars'][0]);
        return max(1, $page);
    }
    
    // Check if route is search-paged, page number is in vars[1]
    if (isset($route['name']) && $route['name'] === 'search-paged' && isset($route['vars'][1])) {
        $page = intval($route['vars'][1]);
        return max(1, $page);
    }
    
    // Check for page parameter in URL path (e.g., /f/query/page/2 or /page/2)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path_parts = explode('/', trim($uri, '/'));
    
    // Look for 'page' in the path
    $page_index = array_search('page', $path_parts);
    if ($page_index !== false && isset($path_parts[$page_index + 1])) {
        $page = intval($path_parts[$page_index + 1]);
        return max(1, $page);
    }
    
    // Fallback to GET parameter
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    return max(1, $page);
}

/**
 * Get items per page setting
 * 
 * @param int $default Default items per page
 * @return int Items per page
 */
function get_per_page($default = 20) {
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : $default;
    return max(1, min(100, $per_page)); // Limit between 1-100
}

/**
 * Calculate pagination data
 * 
 * @param array $data Full dataset
 * @param int $current_page Current page number
 * @param int $per_page Items per page
 * @return array Pagination data
 */
function calculate_pagination($data, $current_page = null, $per_page = null) {
    if ($current_page === null) {
        $current_page = get_current_page();
    }
    if ($per_page === null) {
        $per_page = get_per_page();
    }
    
    $total_items = count($data);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($current_page - 1) * $per_page;
    
    // Ensure current page is within valid range
    $current_page = max(1, min($current_page, $total_pages));
    
    // Get items for current page
    $paged_data = array_slice($data, $offset, $per_page);
    
    return [
        'data' => $paged_data,
        'current_page' => $current_page,
        'per_page' => $per_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page > 1 ? $current_page - 1 : null,
        'next_page' => $current_page < $total_pages ? $current_page + 1 : null
    ];
}

/**
 * Generate pagination URL
 * 
 * @param int $page Page number
 * @param string $base_url Base URL (optional)
 * @return string Pagination URL
 */
function get_pagination_url($page, $base_url = null) {
    if ($base_url === null) {
        $base_url = $_SERVER['REQUEST_URI'];
    }
    
    // Remove existing page parameter from URL
    $base_url = preg_replace('/[?&]page=\d+/', '', $base_url);
    $base_url = preg_replace('/\/page\/\d+/', '', $base_url);
    
    // Add page parameter
    if ($page > 1) {
        if (strpos($base_url, '?') !== false) {
            $base_url .= '&page=' . $page;
        } else {
            $base_url .= '?page=' . $page;
        }
    }
    
    return $base_url;
}

/**
 * Generate pagination URL for search results
 * Supports URL pattern /f/query/page/2
 * 
 * @param int $page Page number
 * @param string $search_query Search query
 * @return string Pagination URL
 */
function get_search_pagination_url($page, $search_query) {
    return search_pagination_permalink($search_query, $page);
}

/**
 * Generate pagination URL for home page
 * Supports URL pattern /page/2
 * 
 * @param int $page Page number
 * @return string Pagination URL
 */
function get_home_pagination_url($page) {
    if ($page <= 1) {
        return site_url();
    }
    return site_url() . '/page/' . $page;
}

/**
 * Generate pagination HTML
 * 
 * @param array $pagination_data Pagination data from calculate_pagination()
 * @param string $search_query Search query for URL generation (empty for home page)
 * @param string $url_type Type of URL generation: 'search' or 'home'
 * @return string HTML pagination controls
 */
function generate_pagination_html($pagination_data, $search_query = '', $url_type = 'search') {
    $current_page = $pagination_data['current_page'];
    $total_pages = $pagination_data['total_pages'];
    $has_prev = $pagination_data['has_prev'];
    $has_next = $pagination_data['has_next'];
    $prev_page = $pagination_data['prev_page'];
    $next_page = $pagination_data['next_page'];
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav class="pagination-nav" aria-label="Pagination">';
    $html .= '<div class="flex items-center justify-center space-x-2 py-6">';
    
    // Previous button
    if ($has_prev) {
        if ($url_type === 'home') {
            $prev_url = get_home_pagination_url($prev_page);
        } elseif (!empty($search_query)) {
            $prev_url = get_search_pagination_url($prev_page, $search_query);
        } else {
            $prev_url = get_pagination_url($prev_page);
        }
        $html .= '<a href="' . htmlspecialchars($prev_url) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 transition-colors">';
        $html .= '&laquo; Previous';
        $html .= '</a>';
    } else {
        $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">';
        $html .= '&laquo; Previous';
        $html .= '</span>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    // Show first page if not in range
    if ($start_page > 1) {
        if ($url_type === 'home') {
            $first_url = get_home_pagination_url(1);
        } elseif (!empty($search_query)) {
            $first_url = get_search_pagination_url(1, $search_query);
        } else {
            $first_url = get_pagination_url(1);
        }
        $html .= '<a href="' . htmlspecialchars($first_url) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 transition-colors">1</a>';
        
        if ($start_page > 2) {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>';
        }
    }
    
    // Show page numbers in range
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-white bg-primary border border-primary rounded-md">' . $i . '</span>';
        } else {
            if ($url_type === 'home') {
                $page_url = get_home_pagination_url($i);
            } elseif (!empty($search_query)) {
                $page_url = get_search_pagination_url($i, $search_query);
            } else {
                $page_url = get_pagination_url($i);
            }
            $html .= '<a href="' . htmlspecialchars($page_url) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 transition-colors">' . $i . '</a>';
        }
    }
    
    // Show last page if not in range
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>';
        }
        
        if ($url_type === 'home') {
            $last_url = get_home_pagination_url($total_pages);
        } elseif (!empty($search_query)) {
            $last_url = get_search_pagination_url($total_pages, $search_query);
        } else {
            $last_url = get_pagination_url($total_pages);
        }
        $html .= '<a href="' . htmlspecialchars($last_url) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 transition-colors">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($has_next) {
        if ($url_type === 'home') {
            $next_url = get_home_pagination_url($next_page);
        } elseif (!empty($search_query)) {
            $next_url = get_search_pagination_url($next_page, $search_query);
        } else {
            $next_url = get_pagination_url($next_page);
        }
        $html .= '<a href="' . htmlspecialchars($next_url) . '" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-700 transition-colors">';
        $html .= 'Next &raquo;';
        $html .= '</a>';
    } else {
        $html .= '<span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">';
        $html .= 'Next &raquo;';
        $html .= '</span>';
    }
    
    $html .= '</div>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get pagination info text
 * 
 * @param array $pagination_data Pagination data
 * @return string Pagination info text
 */
function get_pagination_info($pagination_data) {
    $current_page = $pagination_data['current_page'];
    $per_page = $pagination_data['per_page'];
    $total_items = $pagination_data['total_items'];
    $total_pages = $pagination_data['total_pages'];
    
    $start_item = (($current_page - 1) * $per_page) + 1;
    $end_item = min($current_page * $per_page, $total_items);
    
    return "Showing {$start_item}-{$end_item} of {$total_items} results (Page {$current_page} of {$total_pages})";
}


function paging2($total) { 
    global $route;
    $page = isset($route["vars"][0]) ? intval($route["vars"][0]) : 1;
    $perPage = 10; // Jumlah item per halaman sesuai dengan API
    $totalPages = ceil($total / $perPage);

    $startPage = floor(($page - 1) / $perPage) * $perPage + 1;
    $endPage = min($startPage + $perPage - 1, $totalPages);

    $pagination = "<div style=\"display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 2rem; flex-wrap: wrap;\">";

    if ($startPage > 1) {
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"/page/" . ($startPage - 1) . "\">&#171; Sebelumnya</a> ";
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $page) {
            // Jika ini adalah halaman saat ini, tampilkan sebagai span
            $pagination .= "<span style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #3a3a3a; color: #f5f5f5; font-weight: 600;\">$i</span> ";
        } else {
            // Jika ini adalah halaman pertama, ganti dengan tautan ke /home
            if ($i == 1) {
                $pagination .= "<a style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"/\">1</a> ";
            } else {
                $pagination .= "<a style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"/page/" . $i . "\">$i</a> ";
            }
        }
    }

    if ($endPage < $totalPages) {
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"/page/" . ($endPage + 1) . "\">Berikutnya &#187;</a>";
    }

    $pagination .= "</div>";

    echo $pagination;
}

/**
 * Pagination function for search results with same styling as paging2()
 * 
 * @param int $total Total number of pages
 * @param string $search_query Search query for URL generation
 */
function paging2_search($total, $search_query = '') {
    // Use get_current_page() to get current page number
    $page = get_current_page();
    
    $perPage = 10; // Jumlah item per halaman sesuai dengan API
    $totalPages = max(1, (int)$total);

    $startPage = floor(($page - 1) / $perPage) * $perPage + 1;
    $endPage = min($startPage + $perPage - 1, $totalPages);

    $pagination = "<div style=\"display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 2rem; flex-wrap: wrap;\">";

    if ($startPage > 1) {
        $prevUrl = search_pagination_permalink($search_query, $startPage - 1);
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') . "\">&#171; Sebelumnya</a> ";
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $page) {
            // Jika ini adalah halaman saat ini, tampilkan sebagai span
            $pagination .= "<span style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #3a3a3a; color: #f5f5f5; font-weight: 600;\">$i</span> ";
        } else {
            // Generate URL untuk search pagination
            $pageUrl = search_pagination_permalink($search_query, $i);
            $pagination .= "<a style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') . "\">$i</a> ";
        }
    }

    if ($endPage < $totalPages) {
        $nextUrl = search_pagination_permalink($search_query, $endPage + 1);
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . "\">Berikutnya &#187;</a>";
    }

    $pagination .= "</div>";

    echo $pagination;
}

/**
 * Pagination function for home page with same styling as paging2_search()
 * 
 * @param int $total Total number of pages
 */
function paging2_home($total) {
    // Use get_current_page() to get current page number
    $page = get_current_page();
    
    $perPage = 10; // Jumlah item per halaman sesuai dengan API
    $totalPages = max(1, (int)$total);

    $startPage = floor(($page - 1) / $perPage) * $perPage + 1;
    $endPage = min($startPage + $perPage - 1, $totalPages);

    $pagination = "<div style=\"display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 2rem; flex-wrap: wrap;\">";

    if ($startPage > 1) {
        $prevUrl = get_home_pagination_url($startPage - 1);
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') . "\">&#171; Sebelumnya</a> ";
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $page) {
            // Jika ini adalah halaman saat ini, tampilkan sebagai span
            $pagination .= "<span style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #3a3a3a; color: #f5f5f5; font-weight: 600;\">$i</span> ";
        } else {
            // Generate URL untuk home pagination
            $pageUrl = get_home_pagination_url($i);
            $pagination .= "<a style=\"width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($pageUrl, ENT_QUOTES, 'UTF-8') . "\">$i</a> ";
        }
    }

    if ($endPage < $totalPages) {
        $nextUrl = get_home_pagination_url($endPage + 1);
        $pagination .= "<a style=\"width: 100px; height: 40px; display: flex; justify-content: center; align-items: center; border-radius: 8px; background-color: #1e1e1e; color: #f5f5f5; font-weight: 600; text-decoration: none; transition: all 0.3s ease; border: 1px solid #333;\" href=\"" . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . "\">Berikutnya &#187;</a>";
    }

    $pagination .= "</div>";

    echo $pagination;
}

/**
 * Enhanced pagination function with better styling and functionality
 * @param int $total Total number of items
 * @param int $per_page Items per page
 * @param int $current_page Current page number
 * @param string $base_url Base URL for pagination links
 */
function paging_enhanced($total, $per_page = 20, $current_page = 1, $base_url = '') {
    global $route;
    
    // Get current page from route if not provided
    if ($current_page === 1) {
        $current_page = isset($route["vars"][0]) ? intval($route["vars"][0]) : 1;
    }
    
    $total_pages = ceil($total / $per_page);
    
    if ($total_pages <= 1) {
        return; // No pagination needed
    }
    
    $pagination = '<div class="pagination-container">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = $current_page == 2 ? site_url() : site_url() . '/page/' . ($current_page - 1);
        $pagination .= '<a class="page-numbers" href="' . $prev_url . '">&#171; Previous</a>';
    }
    
    // Calculate range of pages to show
    $range = 2; // Number of pages to show on each side of current page
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // Show first page if not in range
    if ($start_page > 1) {
        $pagination .= '<a class="page-numbers" href="' . site_url() . '">1</a>';
        if ($start_page > 2) {
            $pagination .= '<span class="page-numbers dots">...</span>';
        }
    }
    
    // Show page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<span class="page-numbers current">' . $i . '</span>';
        } else {
            $page_url = $i == 1 ? site_url() : site_url() . '/page/' . $i;
            $pagination .= '<a class="page-numbers" href="' . $page_url . '">' . $i . '</a>';
        }
    }
    
    // Show last page if not in range
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<span class="page-numbers dots">...</span>';
        }
        $pagination .= '<a class="page-numbers" href="' . site_url() . '/page/' . $total_pages . '">' . $total_pages . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<a class="page-numbers" href="' . site_url() . '/page/' . ($current_page + 1) . '">Next &#187;</a>';
    }
    
    $pagination .= '</div>';
    
    echo $pagination;
}

if (!function_exists('store_dir')) {
    function store_dir() {
      if ( is_dir( BASE . '/store/' . $_SERVER['HTTP_HOST'] ) ) {
        return BASE . '/store/' . $_SERVER['HTTP_HOST'];
      } else {
        return BASE . '/store/default';
      }
    }
}

function unique( $text ) {
    return preg_replace_callback(
        '/\{(((?>[^\{\{\}\}]+)|(?R))*)\}/x',
        'do_unique',
        $text
    );
};

function do_unique( $text ) {
    $text = unique( $text[1] );
    $parts = explode( '|', $text );
    return $parts[array_rand( $parts )];
};

function get_badwords() {
	if ( file_exists( store_dir() . '/badwords.txt' ) ) {
		$bad_words = file_get_contents( store_dir() . '/badwords.txt' );
		return array_filter( explode( ',', $bad_words ) );
	} elseif ( file_exists( BASE . '/store/badwords.txt' ) ) {
		$bad_words = file_get_contents( BASE . '/store/badwords.txt' );
		return array_filter( explode( ',', $bad_words ) );
	} else {
		return [];
	}
}

function filter_badwords( $haystack, $needle, $offset = 0 ) {
	if ( is_array( $needle ) && ! empty( $needle ) ) {
		foreach( $needle as $word ) {
			if ( stristr( $haystack, trim( $word ) ) ) {
				return true;
			}
		}
	}

	return false;
}

function badword_redirect() {
	if ( filter_badwords( strtolower( get_search_query() ), get_badwords(), 0 ) ) {
		$exp_title = $static_exp_title = explode( ' ', strtolower( get_search_query() ) );

		if ( count( $exp_title ) > 1 ) {
			foreach( get_badwords() as $key => $value ) {
				$exp_value = explode( ' ', trim( $value ) );
				if ( count( $exp_value ) > 1 ) {
					foreach( $exp_value as $child_value ) {
						if ( ( $key = array_search( $child_value, $exp_title ) ) !== false ) {
							unset( $exp_title[$key] );
						}
					}
				} else {
					if ( ( $key = array_search( $value, $exp_title ) ) !== false ) {
						unset( $exp_title[$key] );
					}
				}
			}

			if ( count( $exp_title ) > 0 ) {
				if ( count( $exp_title ) != count( $static_exp_title ) ) {
					$redirect = search_permalink( implode( '-', $exp_title ) );
				}
			} else {
				$redirect = site_url();
			}
		} else {
			$redirect = site_url();
		}
	}

	return ( isset( $redirect ) ) ? $redirect : false;
}

function set_recent_user_access( $data, $key, $limit = 25000 ) {
  if ( ! get_option( 'save_recent_searches' ) ) {
    return;
  }

	// Validate data before saving
	if ( !isset( $data[$key] ) || empty( $data[$key] ) ) {
		return; // Don't save empty or missing data
	}

	$title = trim( $data[$key] );
	
	// Validate title: must be non-empty after trimming
	if ( empty( $title ) ) {
		return;
	}

	// Check if title contains only valid printable characters (excluding control characters)
	// Allow letters, numbers, spaces, and common punctuation
	if ( !preg_match( '/^[\p{L}\p{N}\s\-_.,!?()]+$/u', $title ) ) {
		return; // Skip invalid characters
	}

	// Additional check: ensure it contains at least one letter or number (not just punctuation)
	if ( !preg_match( '/[\p{L}\p{N}]/u', $title ) ) {
		return;
	}

	// Check for suspicious Unicode sequences (like \u6376\u6362\u6276\u6276)
	// These are often in unusual Unicode ranges
	$has_valid_chars = false;
	for ( $i = 0; $i < mb_strlen( $title, 'UTF-8' ); $i++ ) {
		$char = mb_substr( $title, $i, 1, 'UTF-8' );
		$code = mb_ord( $char, 'UTF-8' );
		
		// Reject if character is in suspicious ranges (like 0x6300-0x6400 which contains \u6376, etc.)
		if ( $code >= 0x6300 && $code <= 0x6400 ) {
			return; // Suspicious Unicode range
		}
		
		// Accept common ranges
		if ( ( $code >= 0x0020 && $code <= 0x007F ) || // Basic Latin
		     ( $code >= 0x00A0 && $code <= 0x00FF ) || // Latin-1 Supplement
		     ( $code >= 0x0100 && $code <= 0x017F ) || // Latin Extended-A
		     ( $code >= 0x0180 && $code <= 0x024F ) || // Latin Extended-B
		     ( $code >= 0x1E00 && $code <= 0x1EFF ) || // Latin Extended Additional
		     ( $code >= 0x2000 && $code <= 0x206F ) || // General Punctuation
		     ( $code >= 0x3000 && $code <= 0x303F ) || // CJK Symbols and Punctuation (common)
		     ( $code >= 0x3040 && $code <= 0x309F ) || // Hiragana
		     ( $code >= 0x30A0 && $code <= 0x30FF ) || // Katakana
		     ( $code >= 0x4E00 && $code <= 0x9FFF ) ) { // CJK Unified Ideographs
			$has_valid_chars = true;
		} else {
			// For other ranges, be more strict - only allow if it's a known letter/number
			if ( preg_match( '/[\p{L}\p{N}]/u', $char ) ) {
				$has_valid_chars = true;
			} else {
				// Unknown character range, reject
				return;
			}
		}
	}
	
	if ( !$has_valid_chars ) {
		return;
	}

	// Check if title is too short (less than 2 characters) or too long (more than 200 characters)
	if ( mb_strlen( $title, 'UTF-8' ) < 2 || mb_strlen( $title, 'UTF-8' ) > 200 ) {
		return;
	}

	// Check if title contains only whitespace or special characters
	$title_clean = preg_replace( '/[\s\-_.,!?()]+/u', '', $title );
	if ( empty( $title_clean ) ) {
		return; // Title contains only whitespace/punctuation
	}

	$get_data = [];
	$recent_data_file = store_dir() . '/searches.json';

	if ( file_exists( $recent_data_file ) ) {
		$json = file_get_contents( $recent_data_file );
		$get_data = json_decode( $json, true );
		
		// Clean existing data: remove invalid entries
		if ( is_array( $get_data ) ) {
			$get_data = array_filter( $get_data, function( $item ) use ( $key ) {
				if ( !isset( $item[$key] ) || empty( $item[$key] ) ) {
					return false;
				}
				$item_title = trim( $item[$key] );
				if ( empty( $item_title ) ) {
					return false;
				}
				// Check for valid characters
				if ( !preg_match( '/^[\p{L}\p{N}\s\-_.,!?()]+$/u', $item_title ) ) {
					return false;
				}
				// Ensure it contains at least one letter or number
				if ( !preg_match( '/[\p{L}\p{N}]/u', $item_title ) ) {
					return false;
				}
				// Check for suspicious Unicode sequences
				for ( $i = 0; $i < mb_strlen( $item_title, 'UTF-8' ); $i++ ) {
					$char = mb_substr( $item_title, $i, 1, 'UTF-8' );
					$code = mb_ord( $char, 'UTF-8' );
					// Reject suspicious Unicode ranges
					if ( $code >= 0x6300 && $code <= 0x6400 ) {
						return false;
					}
				}
				// Check length
				if ( mb_strlen( $item_title, 'UTF-8' ) < 2 || mb_strlen( $item_title, 'UTF-8' ) > 200 ) {
					return false;
				}
				// Check if not only whitespace/punctuation
				$item_title_clean = preg_replace( '/[\s\-_.,!?()]+/u', '', $item_title );
				return !empty( $item_title_clean );
			} );
			$get_data = array_values( $get_data ); // Re-index array
		} else {
			$get_data = [];
		}
	}

	$key_index = search_array( $get_data, $key, $title );
	if ( $key_index >= 0 ) {
		unset( $get_data[$key_index] );
  } 
  
  if ( count( $get_data ) >= $limit ) {
		array_pop( $get_data );
  }

  // Use validated title
  $validated_data = [ $key => $title ];

  if ( count( $get_data ) > 0 ) {
		$update = array_merge( [ $validated_data ], $get_data );
	} else {
		$update = [ $validated_data ];
  }

	if ( file_exists( $recent_data_file ) ) {
		unlink( $recent_data_file );
  }

	$output = json_encode( $update, JSON_UNESCAPED_UNICODE );
	$recent_data_file_update = fopen( $recent_data_file, 'w' );
	fwrite( $recent_data_file_update, $output );
	fclose( $recent_data_file_update );
}

function get_recent_user_access( $limit = 20 ) {
	$recent_data_file = store_dir() . '/searches.json';
	$output = [];
	
	if ( file_exists( $recent_data_file ) ) {
		$json = file_get_contents( $recent_data_file );
		$get_data = json_decode( $json, true );

		if ( $get_data && is_array( $get_data ) ) {
      foreach( $get_data as $key => $item ) {
  			if ( count( $output ) >= $limit ) {
  				break;
        }

  			// Validate item before adding to output
  			if ( isset( $item['title'] ) && !empty( $item['title'] ) ) {
  				$title = trim( $item['title'] );
  				
			// Check if title is valid
				if ( !empty( $title ) && 
				     preg_match( '/^[\p{L}\p{N}\s\-_.,!?()]+$/u', $title ) &&
				     mb_strlen( $title, 'UTF-8' ) >= 2 && 
				     mb_strlen( $title, 'UTF-8' ) <= 200 ) {
  					$title_clean = preg_replace( '/[\s\-_.,!?()]+/u', '', $title );
  					if ( !empty( $title_clean ) ) {
  						$output[] = $item;
  					}
  				}
  			}
  		}
    }
	}

  return ( isset( $output ) ) ? $output : [];
}

function dmca_redirect() {
  $dmca = store_dir() . '/dmca.txt';
  if ( file_exists( $dmca ) ) {
    $urls = array_map( 'trim', file( $dmca ) );
  } else {
    $urls = [];
  }

  if ( in_array( canonical_url(), $urls ) ) {
    redirect( site_url() );
  }
}

function search_array( $array, $key, $value ) {
  if ( $array ) {
    foreach ( $array as $array_key => $subarray ) {
      if ( isset( $subarray[$key] ) && $subarray[$key] == $value ) {
  	    return $array_key;
       }
    }
  }

	return -1;
}

function permalink( $str, $delimiter = '-', $options = [] ) {
	$str = mb_convert_encoding( ( string ) $str, 'UTF-8', mb_list_encodings() );

	$defaults = [
		'delimiter' => $delimiter,
		'limit' => null,
		'lowercase' => true,
		'replacements' => [],
		'transliterate' => false,
	];

	$options = array_merge( $defaults, $options );

	$char_map = [
		// Latin
		'ÃƒÂ€' => 'A', 'ÃƒÂ' => 'A', 'ÃƒÂ‚' => 'A', 'ÃƒÂƒ' => 'A', 'ÃƒÂ„' => 'A', 'ÃƒÂ…' => 'A', 'ÃƒÂ†' => 'AE', 'ÃƒÂ‡' => 'C',
		'ÃƒÂˆ' => 'E', 'ÃƒÂ‰' => 'E', 'ÃƒÂŠ' => 'E', 'ÃƒÂ‹' => 'E', 'ÃƒÂŒ' => 'I', 'ÃƒÂ' => 'I', 'ÃƒÂŽ' => 'I', 'ÃƒÂ' => 'I',
		'ÃƒÂ' => 'D', 'ÃƒÂ‘' => 'N', 'ÃƒÂ’' => 'O', 'ÃƒÂ“' => 'O', 'ÃƒÂ”' => 'O', 'ÃƒÂ•' => 'O', 'ÃƒÂ–' => 'O', 'Ã…Â' => 'O',
		'ÃƒÂ˜' => 'O', 'ÃƒÂ™' => 'U', 'ÃƒÂš' => 'U', 'ÃƒÂ›' => 'U', 'ÃƒÂœ' => 'U', 'Ã…Â°' => 'U', 'ÃƒÂ' => 'Y', 'ÃƒÂž' => 'TH',
		'ÃƒÂŸ' => 'ss',
		'Ãƒ ' => 'a', 'ÃƒÂ¡' => 'a', 'ÃƒÂ¢' => 'a', 'ÃƒÂ£' => 'a', 'ÃƒÂ¤' => 'a', 'ÃƒÂ¥' => 'a', 'ÃƒÂ¦' => 'ae', 'ÃƒÂ§' => 'c',
		'ÃƒÂ¨' => 'e', 'ÃƒÂ©' => 'e', 'ÃƒÂª' => 'e', 'ÃƒÂ«' => 'e', 'ÃƒÂ¬' => 'i', 'ÃƒÂ­' => 'i', 'ÃƒÂ®' => 'i', 'ÃƒÂ¯' => 'i',
		'ÃƒÂ°' => 'd', 'ÃƒÂ±' => 'n', 'ÃƒÂ²' => 'o', 'ÃƒÂ³' => 'o', 'ÃƒÂ´' => 'o', 'ÃƒÂµ' => 'o', 'ÃƒÂ¶' => 'o', 'Ã…Â‘' => 'o',
		'ÃƒÂ¸' => 'o', 'ÃƒÂ¹' => 'u', 'ÃƒÂº' => 'u', 'ÃƒÂ»' => 'u', 'ÃƒÂ¼' => 'u', 'Ã…Â±' => 'u', 'ÃƒÂ½' => 'y', 'ÃƒÂ¾' => 'th',
		'ÃƒÂ¿' => 'y',

		// Latin symbols
		'Ã‚Â©' => '(c)',

		// Greek
		'ÃŽÂ‘' => 'A', 'ÃŽÂ’' => 'B', 'ÃŽÂ“' => 'G', 'ÃŽÂ”' => 'D', 'ÃŽÂ•' => 'E', 'ÃŽÂ–' => 'Z', 'ÃŽÂ—' => 'H', 'ÃŽÂ˜' => '8',
		'ÃŽÂ™' => 'I', 'ÃŽÂš' => 'K', 'ÃŽÂ›' => 'L', 'ÃŽÂœ' => 'M', 'ÃŽÂ' => 'N', 'ÃŽÂž' => '3', 'ÃŽÂŸ' => 'O', 'ÃŽ ' => 'P',
		'ÃŽÂ¡' => 'R', 'ÃŽÂ£' => 'S', 'ÃŽÂ¤' => 'T', 'ÃŽÂ¥' => 'Y', 'ÃŽÂ¦' => 'F', 'ÃŽÂ§' => 'X', 'ÃŽÂ¨' => 'PS', 'ÃŽÂ©' => 'W',
		'ÃŽÂ†' => 'A', 'ÃŽÂˆ' => 'E', 'ÃŽÂŠ' => 'I', 'ÃŽÂŒ' => 'O', 'ÃŽÂŽ' => 'Y', 'ÃŽÂ‰' => 'H', 'ÃŽÂ' => 'W', 'ÃŽÂª' => 'I',
		'ÃŽÂ«' => 'Y',
		'ÃŽÂ±' => 'a', 'ÃŽÂ²' => 'b', 'ÃŽÂ³' => 'g', 'ÃŽÂ´' => 'd', 'ÃŽÂµ' => 'e', 'ÃŽÂ¶' => 'z', 'ÃŽÂ·' => 'h', 'ÃŽÂ¸' => '8',
		'ÃŽÂ¹' => 'i', 'ÃŽÂº' => 'k', 'ÃŽÂ»' => 'l', 'ÃŽÂ¼' => 'm', 'ÃŽÂ½' => 'n', 'ÃŽÂ¾' => '3', 'ÃŽÂ¿' => 'o', 'ÃÂ€' => 'p',
		'ÃÂ' => 'r', 'ÃÂƒ' => 's', 'ÃÂ„' => 't', 'ÃÂ…' => 'y', 'ÃÂ†' => 'f', 'ÃÂ‡' => 'x', 'ÃÂˆ' => 'ps', 'ÃÂ‰' => 'w',
		'ÃŽÂ¬' => 'a', 'ÃŽÂ­' => 'e', 'ÃŽÂ¯' => 'i', 'ÃÂŒ' => 'o', 'ÃÂ' => 'y', 'ÃŽÂ®' => 'h', 'ÃÂŽ' => 'w', 'ÃÂ‚' => 's',
		'ÃÂŠ' => 'i', 'ÃŽÂ°' => 'y', 'ÃÂ‹' => 'y', 'ÃŽÂ' => 'i',

		// Turkish
		'Ã…Âž' => 'S', 'Ã„Â°' => 'I', 'ÃƒÂ‡' => 'C', 'ÃƒÂœ' => 'U', 'ÃƒÂ–' => 'O', 'Ã„Âž' => 'G',
		'Ã…ÂŸ' => 's', 'Ã„Â±' => 'i', 'ÃƒÂ§' => 'c', 'ÃƒÂ¼' => 'u', 'ÃƒÂ¶' => 'o', 'Ã„ÂŸ' => 'g',

		// Russian
		'ÃÂ' => 'A', 'ÃÂ‘' => 'B', 'ÃÂ’' => 'V', 'ÃÂ“' => 'G', 'ÃÂ”' => 'D', 'ÃÂ•' => 'E', 'ÃÂ' => 'Yo', 'ÃÂ–' => 'Zh',
		'ÃÂ—' => 'Z', 'ÃÂ˜' => 'I', 'ÃÂ™' => 'J', 'ÃÂš' => 'K', 'ÃÂ›' => 'L', 'ÃÂœ' => 'M', 'ÃÂ' => 'N', 'ÃÂž' => 'O',
		'ÃÂŸ' => 'P', 'Ã ' => 'R', 'ÃÂ¡' => 'S', 'ÃÂ¢' => 'T', 'ÃÂ£' => 'U', 'ÃÂ¤' => 'F', 'ÃÂ¥' => 'H', 'ÃÂ¦' => 'C',
		'ÃÂ§' => 'Ch', 'ÃÂ¨' => 'Sh', 'ÃÂ©' => 'Sh', 'ÃÂª' => '', 'ÃÂ«' => 'Y', 'ÃÂ¬' => '', 'ÃÂ­' => 'E', 'ÃÂ®' => 'Yu',
		'ÃÂ¯' => 'Ya',
		'ÃÂ°' => 'a', 'ÃÂ±' => 'b', 'ÃÂ²' => 'v', 'ÃÂ³' => 'g', 'ÃÂ´' => 'd', 'ÃÂµ' => 'e', 'Ã‘Â‘' => 'yo', 'ÃÂ¶' => 'zh',
		'ÃÂ·' => 'z', 'ÃÂ¸' => 'i', 'ÃÂ¹' => 'j', 'ÃÂº' => 'k', 'ÃÂ»' => 'l', 'ÃÂ¼' => 'm', 'ÃÂ½' => 'n', 'ÃÂ¾' => 'o',
		'ÃÂ¿' => 'p', 'Ã‘Â€' => 'r', 'Ã‘Â' => 's', 'Ã‘Â‚' => 't', 'Ã‘Âƒ' => 'u', 'Ã‘Â„' => 'f', 'Ã‘Â…' => 'h', 'Ã‘Â†' => 'c',
		'Ã‘Â‡' => 'ch', 'Ã‘Âˆ' => 'sh', 'Ã‘Â‰' => 'sh', 'Ã‘ÂŠ' => '', 'Ã‘Â‹' => 'y', 'Ã‘ÂŒ' => '', 'Ã‘Â' => 'e', 'Ã‘ÂŽ' => 'yu',
		'Ã‘Â' => 'ya',

		// Ukrainian
		'ÃÂ„' => 'Ye', 'ÃÂ†' => 'I', 'ÃÂ‡' => 'Yi', 'Ã’Â' => 'G',
		'Ã‘Â”' => 'ye', 'Ã‘Â–' => 'i', 'Ã‘Â—' => 'yi', 'Ã’Â‘' => 'g',

		// Czech
		'Ã„ÂŒ' => 'C', 'Ã„ÂŽ' => 'D', 'Ã„Âš' => 'E', 'Ã…Â‡' => 'N', 'Ã…Â˜' => 'R', 'Ã… ' => 'S', 'Ã…Â¤' => 'T', 'Ã…Â®' => 'U',
		'Ã…Â½' => 'Z',
		'Ã„Â' => 'c', 'Ã„Â' => 'd', 'Ã„Â›' => 'e', 'Ã…Âˆ' => 'n', 'Ã…Â™' => 'r', 'Ã…Â¡' => 's', 'Ã…Â¥' => 't', 'Ã…Â¯' => 'u',
		'Ã…Â¾' => 'z',

		// Polish
		'Ã„Â„' => 'A', 'Ã„Â†' => 'C', 'Ã„Â˜' => 'e', 'Ã…Â' => 'L', 'Ã…Âƒ' => 'N', 'ÃƒÂ“' => 'o', 'Ã…Âš' => 'S', 'Ã…Â¹' => 'Z',
		'Ã…Â»' => 'Z',
		'Ã„Â…' => 'a', 'Ã„Â‡' => 'c', 'Ã„Â™' => 'e', 'Ã…Â‚' => 'l', 'Ã…Â„' => 'n', 'ÃƒÂ³' => 'o', 'Ã…Â›' => 's', 'Ã…Âº' => 'z',
		'Ã…Â¼' => 'z',

		// Latvian
		'Ã„Â€' => 'A', 'Ã„ÂŒ' => 'C', 'Ã„Â’' => 'E', 'Ã„Â¢' => 'G', 'Ã„Âª' => 'i', 'Ã„Â¶' => 'k', 'Ã„Â»' => 'L', 'Ã…Â…' => 'N',
		'Ã… ' => 'S', 'Ã…Âª' => 'u', 'Ã…Â½' => 'Z',
		'Ã„Â' => 'a', 'Ã„Â' => 'c', 'Ã„Â“' => 'e', 'Ã„Â£' => 'g', 'Ã„Â«' => 'i', 'Ã„Â·' => 'k', 'Ã„Â¼' => 'l', 'Ã…Â†' => 'n',
		'Ã…Â¡' => 's', 'Ã…Â«' => 'u', 'Ã…Â¾' => 'z'
	];

	$str = preg_replace( array_keys( $options['replacements'] ), $options['replacements'], $str );

	if ( $options['transliterate'] ) {
		$str = str_replace( array_keys( $char_map ), $char_map, $str );
	}

	$str = preg_replace( '/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	$str = preg_replace( '/(' . preg_quote( $options['delimiter'], '/') . '){2,}/', '$1', $str);
	$str = substr( $str, 0, ( $options['limit'] ? $options['limit'] : strlen( $str ) ) );
	$str = trim( $str, $options['delimiter'] );

	return $options['lowercase'] ? strtolower( $str ) : $str;
}

function base64_url_encode( $query ) {
	return rtrim( strtr( base64_encode( $query ), '+/', '-_' ), '=' );
}

function base64_url_decode( $query ) {
	return base64_decode( str_pad( strtr( $query, '-_', '+/' ), strlen( $query ) % 4, '=', STR_PAD_RIGHT ) );
}

/**
 * Optimize image URL to use WebP if possible, with fallback.
 * Usage: optimize_image_url($url) returns ['webp' => ..., 'fallback' => ...]
 */
function optimize_image_url($url) {
    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    if ($ext === 'webp') {
        return ['webp' => $url, 'fallback' => $url];
    }
    $webp = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $url);
    return [
        'webp' => $webp,
        'fallback' => $url
    ];
}

/**
 * Generate SVG base64 placeholder for lazy loading.
 */
function get_svg_placeholder($width = 32, $height = 32, $color = '#eee') {
    $svg = '<svg width="'.$width.'" height="'.$height.'" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="'.$color.'"/></svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Truncate description to max N chars, no word cut, add ellipsis.
 */
function truncate_description($desc, $max = 160) {
    $desc = strip_tags($desc);
    if (mb_strlen($desc) <= $max) return $desc;
    $trunc = mb_substr($desc, 0, $max);
    $lastSpace = mb_strrpos($trunc, ' ');
    if ($lastSpace !== false) {
        $trunc = mb_substr($trunc, 0, $lastSpace);
    }
    return rtrim($trunc) . '...';
}

/**
 * Generate meta keywords from tags array, limit to 10, comma separated.
 */
function get_meta_keywords($tags) {
    if (!is_array($tags)) return '';
    $tags = array_map('strtolower', array_unique($tags));
    $tags = array_filter($tags, function($t) { return strlen($t) > 2; });
    return implode(', ', array_slice($tags, 0, 10));
}

/**
 * Generate descriptive alt text for images.
 */
function get_image_alt($title, $site = null) {
    $site = $site ?: get_option('site_name');
    return trim($title . ' - ' . $site);
}

/**
 * Output JS for Intersection Observer lazy loading (call once in footer).
 */
function output_lazyload_js() {
    echo '<script defer>(function(){if(!window.IntersectionObserver){var imgs=document.querySelectorAll("img[data-src]");for(var i=0;i<imgs.length;i++){imgs[i].src=imgs[i].getAttribute("data-src");}}else{var io=new IntersectionObserver(function(entries,observer){entries.forEach(function(e){if(e.isIntersecting){var img=e.target;img.src=img.getAttribute("data-src");img.onload=function(){img.classList.add("loaded")};observer.unobserve(img);}});});var imgs=document.querySelectorAll("img[data-src]");for(var i=0;i<imgs.length;i++){io.observe(imgs[i]);}}})();</script>';
}

/**
 * CDN image optimization function
 * Optimizes image URLs for better performance
 */
function photon_cdn($img_url){
    $arr_1o = array(0,1,2,3);
    $wp = 'https://i'.$arr_1o[array_rand($arr_1o)].'.wp.com/';
    
    if (get_option('photon_cdn')) {
      // Handle both HTTP and HTTPS
      if (strpos($img_url, 'http://') === 0) {
        return str_replace('http://', $wp, $img_url);
      } elseif (strpos($img_url, 'https://') === 0) {
        return str_replace('https://', $wp, $img_url);
      }
    }
    return $img_url;
  }

/**
 * Truncate text to specified number of words
 * @param string $text The text to truncate
 * @param int $limit Maximum number of words
 * @param string $ending String to append if truncated
 * @return string Truncated text
 */
function substrwords($text, $limit, $ending = '...') {
    if (empty($text)) {
        return '';
    }
    
    // Convert to string if not already
    $text = (string) $text;
    
    // Split into words
    $words = preg_split('/\s+/', trim($text));
    
    // If text has fewer words than limit, return as is
    if (count($words) <= $limit) {
        return $text;
    }
    
    // Truncate to limit words
    $truncated = array_slice($words, 0, $limit);
    
    return implode(' ', $truncated) . $ending;
}
