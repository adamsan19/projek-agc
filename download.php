<?php
if (!defined('BASE')) { define('BASE', __DIR__); }

// All required files from the original script
require_once 'libraries/ua.class.php';
require_once 'core/functions/options.php';
require_once 'core/functions/cache.php';
require_once 'core/functions/permalinks.php';
require_once 'core/functions/common.php';
require_once 'core/functions/site.php';
require_once 'core/functions/breadcrumb.php';
//require_once 'core/classes/agc.php';

// Logic for cache deletion
//delete_cache_json(get_cache_path() . '/download', get_cache_duration('download'));

require_once 'core/classes/VideoService.php';
$agc = new VideoService();
// Get video data with caching
$data = $agc->get_video();
 
if (empty($data) || isset($data['error']) || (isset($data['status']) && $data['status'] === 404)) {
    redirect(site_url());
    exit;
}
$total_videos = 0;

// Main logic block to process video data
// VideoService returns data with 'filecode' or 'file_code' key
$filecode = $data['filecode'] ?? $data['file_code'] ?? '';
if (!empty($data) && !empty($filecode)) {
    // Ensure filecode is set in data for consistency
    $data['filecode'] = $filecode;
    $data['file_code'] = $filecode;
    
    $embed_url = htmlspecialchars($data['protected_embed'] ?? $data['protected_embed_url'] ?? '', ENT_QUOTES, 'UTF-8');
    
    // Title processing to remove forbidden words
    $title_words = array_unique(explode(' ', ucwords(preg_replace('/[0-9]/', '', strtolower($data['title'] ?? '')))));
    $kata_terlarang = ['bocil', 'anak'];
    $title = '';
    foreach ($title_words as $word) {
        if (in_array(strtolower($word), $kata_terlarang)) continue;
        $title .= "$word ";
    }
    $title = trim($title);

    // Image selection logic
    $image = '';
    if (!empty($data['splash_img'])) {
        $image = $data['splash_img'];
    } elseif (!empty($data['single_img'])) {
        $image = $data['single_img'];
    } else {
        $image = '/assets/img/placeholder.png';
    }
    $image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
    // Store CDN URL in variable to ensure exact match between preload and img tag
    $image_cdn_url = photon_cdn($image);
    $searches = get_recent_user_access(get_option('recent_searches_count', 20));
    // Variable sanitization
    $uploaded = htmlspecialchars($data['uploaded'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8');
    $length = htmlspecialchars($data['length'] ?? '0', ENT_QUOTES, 'UTF-8');
    $size = htmlspecialchars($data['size'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
    $view = htmlspecialchars($data['views'] ?? $data['view'] ?? '0', ENT_QUOTES, 'UTF-8');
    
    $site_name = get_option('site_name') ?? '';
    
    // SEO Title
    $optimized_title = "{$title} - {$site_name}";
    
    // Meta Description - more unique and relevant with video metadata
    $length_formatted = is_numeric($length) ? gmdate("H:i:s", (int)$length) : $length;
    $deskripsi = !empty($data['deskripsi']) ? 
        strip_tags($data['deskripsi']) : 
        "Nonton video {$title} secara gratis. Durasi: {$length_formatted}, {$view} views. Download sekarang!";
    $meta_description = substr($deskripsi, 0, 160);
    if (strlen($deskripsi) > 160) {
        $meta_description .= '...';
    }
    
    // Last modified date for content freshness
    $last_modified = $data['uploaded'] ?? date('Y-m-d');
    
    // Tag generation logic from title
    $titleTags = [];
    $title_words_for_tags = explode(' ', strtolower($title));
    $kata_terlarang_tags =['bocil', 'anak', 'itu', 'ini', 'sini', 'situ', 'sana', 'dan', 'atau', 'tetapi', 'namun', 'melainkan', 'sedangkan', 'serta', 'lagipula', 'di', 'ke', 'dari', 'pada', 'untuk', 'dengan', 'tanpa', 'atas', 'bawah', 'depan', 'belakang', 'dalam', 'luar', 'antara', 'sejak', 'selama', 'hingga', 'sampai', 'menuju', 'via', 'yang', 'lah', 'kah', 'pun', 'tah', 'nya', 'tak', 'ber', 'ter', 'me', 'mem', 'men', 'meng', 'meny', 'pe', 'pem', 'pen', 'peng', 'peny', 'per', 'se', 'di', 'ke', 'ku', 'mu', 'kau', 'sebuah', 'suatu', 'para', 'sang', 'si', 'seseorang', 'suatu', 'adalah', 'ialah', 'sudah', 'telah', 'akan', 'belum', 'pernah', 'sedang'];
    
    foreach ($title_words_for_tags as $word) {
        $word = trim($word);
        if (!empty($word) && !in_array($word, $kata_terlarang_tags) && strlen($word) > 2) {
            $titleTags[] = ucwords($word);
        }
    }
    
    // Context-aware keywords for more natural tags
    $contextual_tags = ['Video Viral', 'Streaming Online', 'Download Gratis'];
    $additional_tags = ['Streaming', 'Download'];
    $titleTags = array_merge($titleTags, $additional_tags, $contextual_tags);
    $titleTags = array_unique($titleTags);
    $titleTags = array_slice($titleTags, 0, 10);
    
    // Category generation for breadcrumbs using breadcrumb functions
    $category_name = breadcrumb_category_name($title);
    $category_url = breadcrumb_url($title);

    // Get related videos using VideoService
    $searchResult = $agc->get_related($data['title'] ?? '', 1);
    $related_videos = [];
    
    // VideoService.get_search() returns array with 'videos' key
    if (!empty($searchResult) && isset($searchResult['videos']) && is_array($searchResult['videos'])) {
        $related_videos = $searchResult['videos'];
    } elseif (is_array($searchResult) && !isset($searchResult['videos'])) {
        // Fallback: if it's already an array of videos
        $related_videos = $searchResult;
    }
    
    $current_filecode = $filecode;
    
    // Filter out current video
    $filtered_videos = array_filter($related_videos, function($item) use ($current_filecode) {
        $item_filecode = $item['file_code'] ?? $item['filecode'] ?? '';
        return $item_filecode !== $current_filecode;
    });
    
    // Remove duplicates based on length value
    $unique_videos = [];
    $seen_lengths = [];
    
    foreach ($filtered_videos as $item) {
        $length = $item['length'] ?? $item['duration'] ?? '';
        
        // Normalize length format for comparison
        $length_normalized = '';
        if (!empty($length)) {
            if (is_numeric($length)) {
                // If numeric, convert to seconds
                $length_normalized = (string)(int)$length;
            } else {
                // Handle time format like "00:05:30", "5:30", or "05:30"
                // Convert to total seconds for comparison
                $parts = explode(':', $length);
                $parts = array_map('intval', $parts);
                
                if (count($parts) === 3) {
                    // HH:MM:SS format
                    $length_normalized = (string)($parts[0] * 3600 + $parts[1] * 60 + $parts[2]);
                } elseif (count($parts) === 2) {
                    // MM:SS format
                    $length_normalized = (string)($parts[0] * 60 + $parts[1]);
                } else {
                    // Fallback: use as is
                    $length_normalized = preg_replace('/[^0-9]/', '', $length);
                }
            }
        }
        
        // Use normalized length as identifier to remove duplicates
        // Keep only the first occurrence of each length
        if (!isset($seen_lengths[$length_normalized])) {
            $seen_lengths[$length_normalized] = true;
            $unique_videos[] = $item;
        }
    }
    
    $limited_videos = array_slice(array_values($unique_videos), 0, 40);
    $total_videos = count($limited_videos);

} else {
    // Redirect if no data
    redirect(site_url());
}

// Page-level variables for meta tags
$download_title_option = (string)(get_option('download_title') ?? 'Download %title% - %site_name%');
$download_desc_option = (string)(get_option('download_description') ?? '');
$title_safe = (string)($title ?? '');
$site_name = get_option('site_name') ?? '';
$site_title = str_replace(['%title%', '%site_name%'], [$title_safe, $site_name], $download_title_option);
// Only override meta_description if download_description option is set, otherwise keep the one generated earlier
if (!empty($download_desc_option)) {
    $size_safe = $size ?? '';
    $meta_description = str_replace(['%title%', '%size%', '%site_name%'], [$title_safe, $size_safe, $site_name], $download_desc_option);
}
    $meta_robots = "index,follow";
    $ogimage = $image;
    
    // Preload critical video poster image
    $additional_head = '<link rel="preload" href="' . htmlspecialchars($image_cdn_url, ENT_QUOTES, 'UTF-8') . '" as="image" fetchpriority="high">';
    
    // Preload play.svg for this page
    $preload_play_svg = true;
    include 'header.php';
?> 
    

    
    <main class="main-content">
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item">
                    <a href="<?php echo site_url(); ?>">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Home
                    </a>
                </li>
                <li class="breadcrumb-separator">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo htmlspecialchars($category_url, ENT_QUOTES, 'UTF-8'); ?>">
                        Bokep <?php echo htmlspecialchars(ucwords($category_name), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
                <li class="breadcrumb-separator">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </li>
                <li class="breadcrumb-item active">
                    <span><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
                </li>
            </ol>
        </nav>

        <div id="video-player" class="video-player">
            <img id="video-poster" 
                 src="<?php echo htmlspecialchars($image_cdn_url, ENT_QUOTES, 'UTF-8'); ?>" 
                 alt="Poster for <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>" 
                 class="video-player__poster" 
                 fetchpriority="high"
                 loading="eager"
                 decoding="async"
                 width="1280"
                 height="720"
                 onerror="this.onerror=null; this.src='<?php echo site_url(); ?>/assets/img/placeholder.svg';">
            <iframe id="video-iframe" class="video-player__iframe" allowfullscreen referrerpolicy="no-referrer" title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - Video Player Utama"></iframe>
            <button id="play-button" class="video-player__play-btn" aria-label="Putar Video">
                <img src="<?php echo site_url(); ?>/play.svg" alt="Tombol Putar" width="72" height="72" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='▶';">
            </button>
        </div>

        <section class="video-details">
            <h1 class="video-details__title" id="video-title" itemprop="name"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            
            <div class="video-details__meta">
                <div class="video-details__stats">
                    <span id="video-views">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg> 
                        <?php echo number_format((int)$view); ?> views
                    </span>
                    <span id="video-duration">Durasi: <?php echo is_numeric($length) ? gmdate("H:i:s", (int)$length) : htmlspecialchars($length, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="video-details__actions">
                    <button class="video-details__action-btn" id="like-btn">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a2 2 0 0 1 1.79 1.11L15 5.88Z"/></svg>
                        <span>Suka</span>
                    </button>
                    <a href="/dl/<?php echo htmlspecialchars($filecode, ENT_QUOTES, 'UTF-8'); ?>" id="download-link" class="video-details__action-btn">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                        <span>Unduh</span>
                    </a>
                </div>
            </div>

            <div class="video-details__summary is-collapsed" id="video-summary-container">
                <div class="video-details__summary-content" id="video-description-content">
                    <?php echo htmlspecialchars($deskripsi, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <button class="video-details__toggle-btn" id="toggle-summary-btn">Baca Selengkapnya</button>
            </div>
            <!-- Player Ad - Lazy Loaded -->
            <div id="ad-player-placeholder" 
                 class="ad-placeholder"
                 data-adcode="<?php echo htmlspecialchars(get_option('ad_code_player'), ENT_QUOTES, 'UTF-8'); ?>"
                 style="min-height: 90px;">
            </div>
            <?php if (!empty($titleTags)) { ?>
            <div class="video-details__tags" id="video-tags-container">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>
                <?php foreach ($titleTags as $tag) { ?>
                    <a href="/f/bokep-<?php echo permalink($tag); ?>" class="video-details__tag-link">
                        Bokep <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php } ?>
            </div>
            <?php } ?>
            
        </section>
        
        <!-- ALL SEO SCHEMA MARKUP FROM ORIGINAL download.php IS PRESERVED HERE -->
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": [
            {"@type": "ListItem", "position": 1, "name": "<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>", "item": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>"},
            {"@type": "ListItem", "position": 2, "name": "<?php echo htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8'); ?>", "item": "<?php echo htmlspecialchars($category_url, ENT_QUOTES, 'UTF-8'); ?>"},
            {"@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>", "item": "<?php echo htmlspecialchars(canonical_url(), ENT_QUOTES, 'UTF-8'); ?>"}
          ]
        }
        </script>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "WebPage",
          "name": "<?php echo htmlspecialchars($optimized_title, ENT_QUOTES, 'UTF-8'); ?>",
          "description": "<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>",
          "url": "<?php echo htmlspecialchars(canonical_url()); ?>",
          "dateModified": "<?php echo htmlspecialchars($last_modified, ENT_QUOTES, 'UTF-8'); ?>",
          "inLanguage": "id-ID",
          "isPartOf": {
            "@type": "WebSite",
            "name": "<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>",
            "url": "<?php echo htmlspecialchars(site_url()); ?>"
          },
          "primaryImageOfPage": {
            "@type": "ImageObject",
            "url": "<?php echo htmlspecialchars($image_cdn_url, ENT_QUOTES, 'UTF-8'); ?>"
          }
        }
        </script>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "VideoObject",
          "name": "<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>",
          "description": "<?php echo htmlspecialchars($meta_description, ENT_QUOTES, 'UTF-8'); ?>",
          "thumbnailUrl": "<?php echo htmlspecialchars($image_cdn_url, ENT_QUOTES, 'UTF-8'); ?>",
          "uploadDate": "<?php echo htmlspecialchars($uploaded, ENT_QUOTES, 'UTF-8'); ?>",
          "duration": "PT<?php echo is_numeric($length) ? str_replace(':', 'M', gmdate('i:s', (int)$length)) : '5M0S'; ?>S",
          "contentUrl": "<?php echo htmlspecialchars(canonical_url()); ?>",
          "embedUrl": "<?php echo htmlspecialchars($embed_url, ENT_QUOTES, 'UTF-8'); ?>",
          "interactionStatistic": {
            "@type": "InteractionCounter",
            "interactionType": { "@type":"WatchAction"},
            "userInteractionCount": <?php echo (int)$view;?>
          },
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.5",
            "ratingCount": "<?php echo (int)$view > 0 ? (int)$view : 1;?>",
            "bestRating": "5",
            "worstRating": "1"
          },
          "publisher": {
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>",
            "logo": {
              "@type": "ImageObject",
              "url": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>/assets/img/logo.png"
            }
          },
          "genre": "Entertainment",
          "keywords": "<?php echo htmlspecialchars(implode(', ', $titleTags), ENT_QUOTES, 'UTF-8'); ?>"
        }
        </script>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?php echo htmlspecialchars($site_name); ?>",
            "url": "<?php echo htmlspecialchars(site_url()); ?>",
            "logo": "<?php echo htmlspecialchars(site_url()); ?>/assets/img/logo.png",
            "founder": {
                "@type": "Person",
                "name": "<?php echo htmlspecialchars($site_name); ?>"
            },
            "foundingDate": "<?php echo date('Y-m-d'); ?>"
        }
        </script>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "<?php echo htmlspecialchars($site_name); ?>",
            "url": "<?php echo htmlspecialchars(site_url()); ?>",
            "description": "<?php echo htmlspecialchars($meta_description); ?>",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?php echo htmlspecialchars(site_url()); ?>/?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
        </script>
        <?php if (!empty($limited_videos)): ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ItemList",
            "numberOfItems": <?php echo count($limited_videos); ?>,
            "itemListElement": [
            <?php foreach ($limited_videos as $index => $item): ?>
            {
              "@type": "ListItem",
              "position": <?php echo $index + 1; ?>,
              "url": "<?php echo download_permalink($item['file_code'] ?? $item['filecode'] ?? ''); ?>"
            }<?php echo ($index < count($limited_videos) - 1) ? ',' : ''; ?>
            <?php endforeach; ?>
            ]
        }
        </script>
        <?php endif; ?>

        <?php if (!empty($limited_videos)) { ?>
        <!-- VideoGallery Schema for Related Videos -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "VideoGallery",
            "name": "Video Terkait <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>",
            "description": "Koleksi video terkait <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>",
            "numberOfItems": <?php echo $total_videos; ?>,
            "url": "<?php echo htmlspecialchars(canonical_url()); ?>#related-videos"
        }
        </script>
        <section class="related-videos">
            <h2 class="related-videos__heading" id="related-videos-heading"><?php echo htmlspecialchars($total_videos, ENT_QUOTES, 'UTF-8'); ?> Video Terkait</h2>
            <div class="related-videos__list" id="related-videos-list">
                <?php foreach ($limited_videos as $item) { ?>
                <article class="video-card-list-item">
                    <a href="<?php echo download_permalink($item['file_code'] ?? $item['filecode'] ?? ''); ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="video-card-list-item__link">
                        <div class="video-card-list-item__thumbnail-wrapper">
                            <?php 
                            $thumbnail_img = !empty($item['single_img']) ? 
                                htmlspecialchars(photon_cdn($item['single_img']), ENT_QUOTES, 'UTF-8') : 
                                site_url() . '/assets/img/placeholder.png';
                            ?>
                            <img class="video-card-list-item__thumbnail lazyload" 
                                 src="<?php echo site_url(); ?>/assets/img/placeholder.png"
                                 data-src="<?php echo $thumbnail_img; ?>" 
                                 alt="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 width="320"
                                 height="180"
                                 loading="lazy"
                                 decoding="async"
                                 onerror="this.onerror=null; this.src='<?php echo site_url(); ?>/assets/img/placeholder.svg';">
                            <span class="video-card-list-item__duration"><?php echo is_numeric($item['length'] ?? null) ? gmdate("H:i:s", (int)$item['length']) : htmlspecialchars($item['length'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="video-card-list-item__info">
                            <h3 class="video-card-list-item__title"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="video-card-list-item__meta">
                                <span><?php echo number_format((int)($item['views'] ?? 0)); ?> views</span>
                            </div>
                        </div>
                    </a>
                </article>
                <?php } ?>
            </div>
           
        </section>
    <!-- Header Ad - Lazy Loaded -->
    <div id="ad-header-placeholder" 
         class="ad-placeholder"
         data-adcode="<?php echo htmlspecialchars(get_option('ad_code_header'), ENT_QUOTES, 'UTF-8'); ?>"
         style="min-height: 90px; text-align: center;">
    </div>

        <?php } ?>
        
        <?php 
        // Get recent searches
      
        if (!empty($searches)): 
        ?>
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
                    <a href="<?php echo search_permalink($item['title']); ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="recent-searches-widget__link">
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
    </main>

<?php
// Set embed_url for footer.php (will be used if additional_footer_script is not set)
// Custom video player initialization script for download.php
$embed_url_with_autoplay = $embed_url . '?autoplay=1';
$additional_footer_script = '
<!-- Video Player Initialization with PHP embed URL -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const container = document.getElementById("video-player");
    const iframe = document.getElementById("video-iframe");
    const poster = document.getElementById("video-poster");
    const playBtn = document.getElementById("play-button");
    const embedUrl = ' . json_encode($embed_url_with_autoplay) . ';

    if (!container || !iframe) return;
    
    const play = () => {
        if (container.classList.contains("is-playing")) return;
        iframe.src = embedUrl;
        container.classList.add("is-playing");
        if (poster) poster.style.display = "none";
        if (playBtn) playBtn.style.display = "none";
    };
        
    container.addEventListener("click", (e) => { if (e.target !== iframe) play(); });
    if (playBtn) playBtn.addEventListener("click", (e) => { e.stopPropagation(); play(); });
});
</script>
';

include 'footer.php';
?>
