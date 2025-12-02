<?php
if (!defined('BASE')) { define('BASE', __DIR__); }

// Include required files
require_once 'libraries/ua.class.php';
require_once 'core/functions/options.php';
require_once 'core/functions/cache.php';
require_once 'core/functions/permalinks.php';
require_once 'core/functions/common.php';
require_once 'core/functions/site.php';
require_once 'core/classes/VideoService.php';

// Get raw query from route to check if it needs lowercase redirect
global $route;
$raw_query = '';
$current_page = get_current_page();

if (isset($route['name']) && ($route['name'] === 'search' || $route['name'] === 'search-paged')) {
    $raw_query = isset($route['vars'][0]) ? $route['vars'][0] : '';
}

// Redirect to lowercase URL if query from URL is not lowercase
if (!empty($raw_query)) {
    $raw_query_decoded = urldecode($raw_query);
    $raw_query_lower = mb_strtolower($raw_query_decoded, 'UTF-8');
    if ($raw_query_decoded !== $raw_query_lower) {
        $normalized_url = search_pagination_permalink($raw_query_lower, $current_page);
        redirect($normalized_url);
        exit;
    }

    // Ensure slug matches normalized phrase (handles duplicate words)
    $normalized_phrase = normalize_keyword_phrase(str_replace(['-', '_'], ' ', $raw_query_lower));
    $normalized_phrase = trim($normalized_phrase);

    if ($normalized_phrase === '') {
        redirect(site_url());
        exit;
    }

    $expected_slug = strtolower(permalink(mb_strtolower($normalized_phrase, 'UTF-8')));
    if ($raw_query_lower !== $expected_slug) {
        $normalized_url = search_pagination_permalink($normalized_phrase, $current_page);
        redirect($normalized_url);
        exit;
    }
}

$search_query = get_search_query();

$agc = new VideoService();
$searchResult = $agc->get_search($search_query, $current_page);

// Handle empty or error results
if (empty($searchResult) || isset($searchResult['error'])) {
    redirect(site_url());
    exit;
}

// Extract videos from VideoService result
if (isset($searchResult['videos']) && is_array($searchResult['videos'])) {
    $results = $searchResult['videos'];
    $total_results = (int)($searchResult['total_results'] ?? 0);
    $total_pages = (int)($searchResult['total_pages'] ?? 0);
} elseif (is_array($searchResult) && !isset($searchResult['videos'])) {
    $results = $searchResult;
    $total_results = count($results);
    $total_pages = 1;
} else {
    $results = [];
    $total_results = 0;
    $total_pages = 0;
}

// Save to recent searches if there are results
if ($total_results > 0 && !empty($search_query)) {
    set_recent_user_access(['title' => $search_query], 'title', get_option('recent_searches_limit', 25000));
}

$searches = get_recent_user_access(get_option('recent_searches_count'));

// Remove duplicates based on length value
$unique_results = [];
$seen_lengths = [];
foreach ($results as $item) {
    $length_raw = $item['length'] ?? '';
    $length_normalized = is_numeric($length_raw) ? (int)$length_raw : $length_raw;
    if (!isset($seen_lengths[$length_normalized])) {
        $seen_lengths[$length_normalized] = true;
        $unique_results[] = $item;
    }
}
$results = $unique_results;

// Redirect if no results found
if (empty($results) || $total_results == 0) {
    redirect(site_url());
    exit;
}

// Setup page variables for header
$site_name = get_option('site_name', '');
$page_number = $current_page;

// Build page title
$search_title_template = get_option('search_title', 'Hasil Pencarian: %query%');
$site_title = str_replace(
    ['%query%', '%total%', '%total_results%', '%site_name%', '%domain%'],
    [htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'), $total_results, $total_results, $site_name, $_SERVER['HTTP_HOST'] ?? ''],
    $search_title_template
);
$site_title .= ($page_number != '1' ? ' - Halaman ' . $page_number : '');

// Build meta description
$search_desc_template = get_option('search_description', '');
$meta_description = str_replace(
    ['%query%', '%total%', '%total_results%', '%size%', '%site_name%', '%domain%'],
    [htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'), $total_results, $total_results, $total_results, $site_name, $_SERVER['HTTP_HOST'] ?? ''],
    $search_desc_template
);

$meta_robots = 'index, follow';
$ogimage = !empty($results[0]['single_img']) ? photon_cdn($results[0]['single_img']) : site_url() . '/assets/img/placeholder.png';
$last_modified = date('Y-m-d');

// Additional head content - Search-specific schemas
$itemListElements = [];
if (!empty($results)) {
    foreach ($results as $index => $item) {
        $itemListElements[] = '{
            "@type": "ListItem",
            "position": ' . ($index + 1) . ',
            "name": "' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') . '",
            "url": "' . download_permalink($item['file_code'] ?? $item['filecode'] ?? '') . '"
        }';
    }
}

$additional_head = '
<meta name="last-modified" content="' . htmlspecialchars($last_modified, ENT_QUOTES, 'UTF-8') . '">
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '"},
        {"@type": "ListItem", "position": 2, "name": "Pencarian: ' . htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars(canonical_url(), ENT_QUOTES, 'UTF-8') . '"}
    ]
}
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "' . htmlspecialchars($site_title, ENT_QUOTES, 'UTF-8') . '",
    "description": "' . htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8') . '",
    "url": "' . htmlspecialchars(canonical_url(), ENT_QUOTES, 'UTF-8') . '",
    "dateModified": "' . $last_modified . '"
}
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Hasil Pencarian: ' . htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8') . '",
    "numberOfItems": ' . $total_results . ',
    "itemListElement": [' . implode(',', $itemListElements) . ']
}
</script>';

// Include header
require_once 'header.php';
?>

<div class="space-y-4 mb-6">
    <h1 class="page-title">
        Hasil Pencarian: "<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>"
        <?php if ($page_number != '1') echo ' - Halaman ' . $page_number; ?>
    </h1>
    <?php if ($total_results > 0): ?>
    <p class="text-gray-500">
        Ditemukan <?php echo number_format($total_results); ?> video yang relevan.
    </p>
    <?php endif; ?>
    
    <!-- Header Ad - Lazy Loaded -->
    <div id="ad-header-placeholder" 
         class="ad-placeholder"
         data-adcode="<?php echo htmlspecialchars(get_option('ad_code_header', ''), ENT_QUOTES, 'UTF-8'); ?>"
         style="min-height: 90px; text-align: center;">
    </div>
</div>

<section class="related-videos">
    <div class="related-videos__list" id="related-videos-list">
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $item): ?>
                <article class="video-card-list-item">
                    <a href="<?php echo download_permalink($item['file_code'] ?? $item['filecode'] ?? ''); ?>" 
                       title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" 
                       class="video-card-list-item__link">
                        <div class="video-card-list-item__thumbnail-wrapper">
                            <?php $thumbnail_img = !empty($item['single_img']) ? htmlspecialchars(photon_cdn($item['single_img']), ENT_QUOTES, 'UTF-8') : site_url() . '/assets/img/placeholder.png'; ?>
                            <img class="video-card-list-item__thumbnail lazyload" 
                                 src="<?php echo site_url(); ?>/assets/img/placeholder.png"
                                 data-src="<?php echo $thumbnail_img; ?>" 
                                 alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 width="320" height="180"
                                 loading="lazy" decoding="async"
                                 onerror="this.onerror=null; this.src='<?php echo site_url(); ?>/assets/img/placeholder.svg';">
                            <span class="video-card-list-item__duration">
                                <?php echo is_numeric($item['length'] ?? null) ? gmdate("H:i:s", (int)$item['length']) : htmlspecialchars($item['length'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                        <div class="video-card-list-item__info">
                            <h3 class="video-card-list-item__title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="video-card-list-item__meta">
                                <span><?php echo number_format((int)($item['views'] ?? 0)); ?> views</span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="col-span-full text-center text-gray-500">
                Tidak ada hasil untuk "<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>".
            </p>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination p-4 sm:p-6">
        <?php
        global $route;
        if (!isset($route)) {
            $route = ['name' => 'search', 'vars' => [$search_query]];
        } elseif (!isset($route['vars'])) {
            $route['vars'] = [$search_query];
        }
        paging2_search($total_pages, $search_query);
        ?>
    </div>
    <?php endif; ?>
</section>

<!-- Footer Ad - Lazy Loaded -->
<div id="ad-footer-placeholder" 
     class="ad-placeholder"
     data-adcode="<?php echo htmlspecialchars(get_option('ad_code_footer', ''), ENT_QUOTES, 'UTF-8'); ?>"
     style="min-height: 90px; text-align: center;">
</div>

<?php if (!empty($searches)): ?>
<aside class="recent-searches-widget">
    <h3 class="recent-searches-widget__title">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
        </svg>
        Pencarian Terkini
    </h3>
    <ul class="recent-searches-widget__list">
        <?php foreach($searches as $item): ?>
        <li class="recent-searches-widget__item">
            <a href="<?php echo search_permalink($item['title']); ?>" 
               title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" 
               class="recent-searches-widget__link">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14"/>
                    <path d="m12 5 7 7-7 7"/>
                </svg>
                <span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</aside>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
