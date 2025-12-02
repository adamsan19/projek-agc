<?php 
if (!defined('BASE')) { define('BASE', __DIR__); }

// Include required files
require_once 'libraries/ua.class.php';
require_once 'core/functions/options.php';
require_once 'core/functions/permalinks.php';
require_once 'core/functions/common.php';
require_once 'core/functions/site.php';
require_once 'core/classes/VideoService.php';

// Handle search redirect
if (isset($_GET['q'])) {
    if ($_GET['q']) {
        redirect(search_permalink($_GET['q']));
    } else {
        redirect(site_url());
    }
}

// Get page number from URL (using get_current_page() like search.php)
$current_page = get_current_page();

// Get videos data
$agc = new VideoService();
$videos_data = $agc->get_list_files($current_page);

if (is_array($videos_data) && isset($videos_data['result']['files'])) {
    $videos = $videos_data['result']['files'];
    $total_pages = $videos_data['result']['total_pages'] ?? 1;
} else {
    $videos = [];
    $total_pages = 1;
}

$total_results = count($videos);
$page_number = $current_page;

// Setup page variables for header
$site_name = get_option('site_name', '');
$home_title_option = get_option('home_title', '');
$site_title = $home_title_option . ($page_number != '1' ? ' - Halaman ' . $page_number : '');
$meta_description = get_option('home_description', '');
$meta_robots = 'index, follow';
$ogimage = site_url() . '/assets/img/placeholder.png';

// Get recent searches
$searches = get_recent_user_access(get_option('recent_searches_count', 20));

// Additional head content (BreadcrumbList + ItemList schema)
$itemListElements = [];
if (!empty($videos)) {
    foreach ($videos as $index => $item) {
        $itemListElements[] = '{
            "@type": "ListItem",
            "position": ' . ($index + 1) . ',
            "name": "' . htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') . '",
            "url": "' . download_permalink($item['file_code'] ?? $item['filecode'] ?? '') . '"
        }';
    }
}

$additional_head = '
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '"}
    ]
}
</script>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "' . htmlspecialchars($home_title_option, ENT_QUOTES, 'UTF-8') . '",
    "numberOfItems": ' . count($videos) . ',
    "itemListElement": [' . implode(',', $itemListElements) . ']
}
</script>';

// Include header
include 'header.php';
?>

<div class="space-y-4 mb-6">
    <h1 class="page-title">
        <?php echo htmlspecialchars($home_title_option, ENT_QUOTES, 'UTF-8'); ?>
        <?php if ($page_number != '1') echo ' - Halaman ' . $page_number; ?>
    </h1>
    <?php if ($total_results > 0): ?>
    <p class="text-gray-500">
        Menampilkan <?php echo $total_results; ?> video.
    </p>
    <?php endif; ?>
</div>

<section class="related-videos">
    <div class="related-videos__list" id="related-videos-list">
        <?php if (!empty($videos)): ?>
            <?php foreach ($videos as $item): ?>
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
                                 width="320"
                                 height="180"
                                 loading="lazy"
                                 decoding="async"
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
            <p class="col-span-full text-center text-gray-500">Tidak ada video yang ditemukan.</p>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination p-4 sm:p-6">
        <?php
        global $route;
        if (!isset($route)) {
            $route = ['name' => 'home'];
        }
        paging2_home($total_pages);
        ?>
    </div>
    <?php endif; ?>
</section>

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

<?php include 'footer.php'; ?>
