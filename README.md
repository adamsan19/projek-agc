# Dokumentasi Teknis AGC Video Platform

## Daftar Isi

1. [Gambaran Umum](#gambaran-umum)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Struktur Direktori](#struktur-direktori)
4. [Backend Components](#backend-components)
5. [Frontend Components](#frontend-components)
6. [Sistem Routing](#sistem-routing)
7. [Sistem Konfigurasi](#sistem-konfigurasi)
8. [Sistem Cache](#sistem-cache)
9. [Video Service & API](#video-service--api)
10. [Keamanan](#keamanan)
11. [Dependencies](#dependencies)
12. [Instalasi dan Setup](#instalasi-dan-setup)
13. [Performance Optimizations](#performance-optimizations)
14. [SEO Optimizations](#seo-optimizations)
15. [UI/UX Best Practices](#uiux-best-practices)
16. [Troubleshooting](#troubleshooting)

---

## Gambaran Umum

AGC Video Platform adalah aplikasi web berbasis PHP untuk platform streaming dan download video. Aplikasi ini menggunakan arsitektur MVC-like dengan custom routing, mendukung multi-domain, dan dilengkapi dengan sistem cache multi-layer.

### Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Video Streaming** | Streaming video dari berbagai sumber (Doodstream, Lulustream) |
| **Search & Pagination** | Pencarian video dengan pagination dan SEO-friendly URLs |
| **Download Manager** | Download video dengan informasi detail |
| **Playlist** | Manajemen playlist video |
| **Sitemap Generator** | Auto-generate XML sitemap untuk SEO |
| **Multi-domain** | Support hosting multiple domains |
| **Cache System** | File-based (Symfony) + Redis cache |
| **Admin Panel** | Panel admin untuk konfigurasi (lite/) |
| **Responsive UI** | Mobile-first responsive design |

---

## Arsitektur Sistem

### Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              REQUEST FLOW                                │
└─────────────────────────────────────────────────────────────────────────┘

Browser Request
      │
      ▼
┌─────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  index.php  │────▶│  config/init.php │────▶│  core/uri.php   │
│ (Entry Point)     │ (Load Config)    │     │ (Parse URI)     │
└─────────────┘     └──────────────────┘     └────────┬────────┘
                                                       │
                                                       ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Response      │◀────│   Handler File   │◀────│ core/router.php │
│   (HTML/JSON)   │     │ (home/search/dl) │     │ (Match Route)   │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │   VideoService   │
                    │   AGC Class      │
                    │   Cache Layer    │
                    └──────────────────┘
```

### Layer Architecture

| Layer | Komponen | Fungsi |
|-------|----------|--------|
| **Entry** | `index.php` | Bootstrap aplikasi, load config & routing |
| **Config** | `config/` | Konfigurasi site, routes, options |
| **Core** | `core/` | Router, URI parser, helper functions |
| **Service** | `core/classes/` | Business logic (VideoService, AGC, API) |
| **Handler** | `*.php` (root) | Route handlers (home, search, download) |
| **View** | `header.php`, `footer.php` | Template components |
| **Storage** | `store/`, `cache/` | Data persistence |
| **Assets** | `assets/` | Static files (CSS, JS, images) |

---

## Struktur Direktori

```
/agc
├── index.php                 # Entry point utama
├── config/                   # Konfigurasi
│   ├── init.php             # Bootstrap konfigurasi
│   └── default/
│       ├── common.php       # PHP config array
│       ├── config.json      # JSON configuration
│       └── routes.php       # Route definitions
│
├── core/                     # Core components
│   ├── router.php           # Route matcher
│   ├── uri.php              # URI parser
│   ├── functions/           # Helper functions
│   │   ├── general.php      # site_url(), redirect(), etc.
│   │   ├── common.php       # Shared utilities + mbstring polyfills
│   │   ├── cache.php        # Cache functions
│   │   ├── options.php      # get_option(), set_option()
│   │   ├── permalinks.php   # URL generators
│   │   ├── keyword.php      # Keyword management
│   │   ├── paging.php       # Pagination
│   │   └── site.php         # Site utilities
│   └── classes/             # Core classes
│       ├── VideoService.php # Video data service
│       ├── agc.php          # Title processing & cache
│       └── api.php          # API handler class
│
├── [Route Handlers]          # Page handlers
│   ├── home.php             # Homepage (/) and pagination (/page/2)
│   ├── search.php           # Search results (/f/query) and pagination
│   ├── download.php         # Video detail (/e/id)
│   ├── dl.php               # Alternative download (/dl/id)
│   ├── player.php           # Video player (/p/id)
│   ├── playlist.php         # Playlist page
│   ├── sitemap.php          # XML sitemap generator
│   └── 404.php              # Error page
│
├── [Templates]
│   ├── header.php           # HTML head + header section
│   └── footer.php           # Footer + modals + scripts
│
├── assets/                   # Static assets
│   ├── css/
│   │   └── style.css        # Main stylesheet (BEM)
│   ├── js/
│   │   ├── main.js          # Main JavaScript
│   │   └── jquery.min.js    # jQuery library
│   ├── img/                 # Images & icons
│   └── fonts/               # Web fonts
│
├── store/                    # Data storage
│   ├── datalist/            # Video data pages
│   │   ├── data_page_*.json # Paginated video data
│   │   └── pages_metadata.json
│   └── default/             # Default store
│       ├── keywords/        # Keyword files
│       ├── pages/           # Static page data
│       └── searches.json    # Recent searches
│
├── cache/                    # Cache storage
│   └── symfony/             # Symfony cache adapter
│
├── lite/                     # Admin panel
│   ├── index.php            # Dashboard
│   ├── settings.php         # Site settings
│   ├── keywords.php         # Keyword management
│   └── includes/            # Admin templates
│
├── vendor/                   # Composer dependencies
├── libraries/                # Third-party libraries
│   ├── simple_html_dom.php  # HTML parser
│   └── ua.class.php         # User agent handler
│
└── [Config Files]
    ├── key.txt              # Keywords list
    ├── robots.txt           # Robots configuration
    ├── favicon.ico          # Site favicon
    └── play.svg             # Play button icon
```

---

## Backend Components

### 1. Entry Point (`index.php`)

```php
// Fungsi utama:
// 1. Set timezone & error reporting
// 2. Define constants (IDC, BASE, PATH)
// 3. Load config, functions, router
// 4. Execute route handler
```

**Constants:**
| Constant | Deskripsi |
|----------|-----------|
| `IDC` | Absolute directory path |
| `BASE` | Normalized base path |
| `PATH` | URL path relative to document root |

### 2. Configuration (`config/init.php`)

Memuat konfigurasi dari berbagai sumber:

```php
// Load order:
1. config/default/common.php    // Default config
2. config/{domain}/common.php   // Domain-specific (optional)
3. config/default/routes.php    // Routes definition
4. config/{domain}/routes.php   // Domain routes (optional)
```

### 3. URI Parser (`core/uri.php`)

```php
// Output variables:
$uri['path']    // Cleaned URI path (e.g., "f/video-viral")
$uri['query']   // Query string
$has_slashes    // Trailing slash flag
```

### 4. Router (`core/router.php`)

Pattern-based routing dengan regex:

```php
// Route pattern → handler mapping
'f/([^/]+)/page/([0-9]+)' => ['name' => 'search-paged', 'file' => 'search.php']

// Output:
$found = true/false
$route = [
    'name' => 'route-name',
    'file' => 'handler.php',
    'vars' => ['param1', 'param2']
]
```

### 5. VideoService (`core/classes/VideoService.php`)

Service utama untuk data video:

```php
class VideoService {
    // Methods:
    get_video($file_code)           // Get single video
    get_videos($page)               // Get paginated videos
    search_videos($query, $page)    // Search videos
    get_related_videos($file_code)  // Get related videos
    
    // Data source: store/datalist/data_page_*.json
}
```

**Data Structure:**
```json
{
    "title": "Video Title",
    "filecode": "abc123xyz",
    "splash_img": "https://...",
    "length": "00:05:30",
    "single_img": "https://...",
    "size": "150 MB",
    "protected_embed": "https://..."
}
```

### 6. AGC Class (`core/classes/agc.php`)

Title processing dan caching:

```php
class AGC {
    get_clean_title($file_code, $raw_title)  // Cached clean title
    process_clean_title($raw_title)          // Process & clean title
}
```

### 7. API Handler (`core/classes/api.php`)

REST-like API endpoints:

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/list/{type}` | GET | List data |
| `/api/search/{query}` | GET | Search API |
| `/api/info/{id}` | GET | Video info |
| `/api/fetch` | POST | Fetch new data |

---

## Frontend Components

### 1. Header Template (`header.php`)

**Optimasi Performa:**
- ✅ Critical CSS inline untuk First Paint cepat
- ✅ Font loading async dengan preload
- ✅ CSS external dengan preload (non-blocking)
- ✅ Preload critical resources (logo, play.svg, placeholder.png)
- ✅ Meta tags SEO lengkap (author, language, revisit-after)

**SEO Features:**
- ✅ Open Graph tags untuk social sharing
- ✅ Twitter Card tags
- ✅ Canonical URL untuk duplicate content
- ✅ Schema.org JSON-LD (WebSite, Organization)
- ✅ Itemscope/Itemtype untuk microdata

```html
<!-- Structure: -->
<!DOCTYPE html>
<html lang="id-ID" itemscope itemtype="https://schema.org/WebPage">
<head>
    <!-- Critical CSS Inline -->
    <!-- Preload Resources -->
    <!-- Meta tags (SEO, Open Graph, Twitter) -->
    <!-- Fonts (Inter - async load) -->
    <!-- CSS (style.css - async load) -->
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header">
            <!-- Logo & Brand -->
            <!-- Navigation (Desktop) -->
            <!-- Search & Menu buttons -->
        </header>
```

**Required Variables:**
```php
$site_title        // Page title
$meta_description  // Meta description
$meta_robots       // Robots directive (default: "index, follow")
$site_name         // Site name (auto-loaded from config)
$ogimage           // Open Graph image
$additional_head   // Additional schemas/scripts
```

### 2. Footer Template (`footer.php`)

```html
    <footer class="site-footer">
        <!-- Copyright -->
    </footer>
</div>

<!-- Mobile Navigation Drawer -->
<!-- Search Modal -->
<!-- Category Modal -->

<!-- JavaScript -->
<script src="assets/js/main.js"></script>
</body>
</html>
```

### 3. CSS Architecture (`assets/css/style.css`)

Menggunakan **BEM naming convention**:

```css
/* Block */
.site-header { }

/* Element */
.site-header__brand { }
.site-header__nav { }

/* Modifier */
.site-header__nav-link--active { }
```

**CSS Variables:**
```css
:root {
    --color-brand: #007bff;
    --color-text-primary: #212529;
    --color-bg-primary: #fff;
    --border-radius-sm: 8px;
    --tap-target-size: 44px;
}
```

**Dark Mode:**
```css
@media (prefers-color-scheme: dark) {
    :root {
        --color-text-primary: #e0e0e0;
        --color-bg-primary: #121212;
    }
}
```

### 4. JavaScript (`assets/js/main.js`)

**Optimasi:**
- ✅ Defer loading untuk non-blocking
- ✅ Error handling untuk lazy-loaded images
- ✅ Smooth animations dengan requestAnimationFrame
- ✅ Body scroll lock untuk mobile drawer
- ✅ Event delegation untuk performance

```javascript
// Features:
- Mobile navigation drawer (smooth animations)
- Search modal (keyboard accessible)
- Category modal
- Lazy loading images (lazysizes integration)
- Video player initialization
- Error handling (status 451, 404, etc.)
- Expandable summary/description
```

**File Structure:**
```
assets/js/
├── main.js      # Main functionality (defer)
├── lazy.js      # Lazysizes library (sync)
└── ads.js       # Ad loading system (defer)
```

### 5. Page Handlers

| File | Route | Fungsi | SEO Status |
|------|-------|--------|------------|
| `home.php` | `/` | Homepage dengan video terbaru | ✅ Index, ItemList Schema |
| `home.php` | `/page/{num}` | Home pagination (halaman 2+) | ✅ Index, ItemList Schema |
| `search.php` | `/f/{query}` | Hasil pencarian + pagination | ✅ Index, ItemList Schema |
| `download.php` | `/e/{id}` | Detail video + player + related | ✅ Index, VideoObject Schema |
| `dl.php` | `/dl/{id}` | Alternative download page | ✅ Index, VideoObject Schema |
| `player.php` | `/p/{id}` | Embedded player | ⚠️ Noindex (embedded) |
| `sitemap.php` | `/sitemap.xml` | XML sitemap | ✅ Auto-generated |

**SEO Optimizations:**
- ✅ All pages use `index, follow` (except player.php)
- ✅ Schema.org JSON-LD optimized (moved out of loops)
- ✅ ItemList schema for video collections
- ✅ BreadcrumbList schema for navigation
- ✅ VideoObject schema for individual videos
- ✅ Canonical URLs on all pages
- ✅ Meta descriptions optimized

---

## Sistem Routing

### Route Definitions (`config/default/routes.php`)

```php
return [
    // Homepage
    '/' => ['name' => 'home', 'file' => 'home.php'],
    
    // Search with pagination
    'f/([^/]+)/page/([0-9]+)' => ['name' => 'search-paged', 'file' => 'search.php'],
    'f/([^/]+)' => ['name' => 'search', 'file' => 'search.php'],
    
    // Video pages
    'e/([^/]+)' => ['name' => 'download', 'file' => 'download.php'],
    'dl/([^/]+)' => ['name' => 'dl', 'file' => 'dl.php'],
    'p/([^/]+)' => ['name' => 'player', 'file' => 'player.php'],
    
    // Other pages
    'playlist/([^/]+)' => ['name' => 'playlist', 'file' => 'playlist.php'],
    'page/([0-9]+)' => ['name' => 'home-paged', 'file' => 'home.php'],
    
    // Sitemap
    'sitemap.xml' => ['name' => 'sitemap-index', 'file' => 'sitemap.php'],
    'sitemap/([0-9]+).xml' => ['name' => 'sitemap-keywords', 'file' => 'sitemap.php'],
    
    // API
    'api/([^/]+)' => ['name' => 'api', 'file' => 'api.php'],
];
```

### URL Patterns

| Pattern | Contoh URL | Variables |
|---------|------------|-----------|
| `f/%query%` | `/f/video-viral` | `$route['vars'][0]` = "video-viral" |
| `f/%query%/page/%num%` | `/f/video/page/2` | `vars[0]` = "video", `vars[1]` = "2" |
| `e/%id%` | `/e/abc123xyz` | `vars[0]` = "abc123xyz" |

---

## Sistem Konfigurasi

### Configuration File (`config/default/config.json`)

```json
{
    // Site Settings
    "site_name": "Video Viral",
    "site_tagline": "Platform Video Streaming",
    
    // SEO Templates
    "home_title": "...",
    "search_title": "Kumpulan %query% (%total%) - %site_name%",
    "download_title": "Download %title% - %site_name%",
    
    // Permalinks
    "search_permalink": "f/%query%",
    "download_permalink": "e/%id%",
    
    // Cache Settings
    "enable_cache": "1",
    "cache_time": "43200",
    "cache_duration_home": "3600",
    "cache_duration_search": "1800",
    
    // Redis (Optional)
    "enable_redis_cache": "1",
    "redis_host": "127.0.0.1",
    "redis_port": "6379",
    "redis_auth": "password"
}
```

### Getting Options

```php
// Di handler files:
$site_name = get_option('site_name', 'Default Name');
$cache_enabled = get_option('enable_cache', '0');
```

---

## Sistem Cache

### Cache Layers

```
┌─────────────────────────────────────────────┐
│              APPLICATION CACHE               │
├─────────────────┬───────────────────────────┤
│   Symfony       │        Redis              │
│   (File-based)  │     (Optional)            │
├─────────────────┼───────────────────────────┤
│ cache/symfony/  │   In-memory              │
│ Fast read/write │   Ultra-fast             │
│ Default adapter │   For high traffic       │
└─────────────────┴───────────────────────────┘
```

### Cache Usage

```php
// Symfony Cache (default)
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new FilesystemAdapter('', 0, BASE . '/cache/symfony');
$data = $cache->get('cache-key', function ($item) {
    $item->expiresAfter(3600);
    return $expensiveComputation();
});

// Redis Cache (optional)
if (get_option('enable_redis_cache')) {
    $redis = new Redis();
    $redis->connect($host, $port);
    // ...
}
```

### Cache Durations

| Content Type | Duration | Config Key |
|--------------|----------|------------|
| Homepage | 1 hour | `cache_duration_home` |
| Search Results | 30 min | `cache_duration_search` |
| Video Details | 12 hours | `cache_time` |
| Mobile | 12 hours | `cache_duration_mobile` |

---

## Video Service & API

### Data Structure

**Video Object:**
```json
{
    "title": "Video Title Here",
    "filecode": "abc123xyz",
    "splash_img": "https://cdn.example.com/thumb.jpg",
    "single_img": "https://cdn.example.com/poster.jpg", 
    "length": "00:05:30",
    "size": "150 MB",
    "protected_embed": "https://player.example.com/embed/abc123xyz",
    "views": 12345,
    "uploaded": "2024-01-15"
}
```

**Metadata (`pages_metadata.json`):**
```json
{
    "total_pages": 140,
    "total_videos": 2800,
    "videos_per_page": 20,
    "last_updated": "2024-01-20T10:30:00Z"
}
```

### API Endpoints

```
GET /api/info/{filecode}
Response: { "success": true, "data": { video object } }

GET /api/search/{query}?page=1
Response: { "success": true, "data": [...], "pagination": {...} }

GET /api/list/recent?limit=20
Response: { "success": true, "data": [...] }
```

---

## Keamanan

### Security Measures

1. **Direct Access Protection:**
```php
// Di setiap file PHP:
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}
```

2. **Input Sanitization:**
```php
// Output escaping
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// URL sanitization
$query = sanitize_text($route['vars'][0]);
```

3. **Route Parameter Validation:**
```php
// Regex patterns mencegah injection
'f/([^/!#$&*()+={}\[\]|;,]+)' // Hanya karakter aman
```

4. **mbstring Polyfills:**
```php
// Polyfill untuk server tanpa mbstring extension
if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = null) {
        return preg_match_all('/./us', $str);
    }
}
```

---

## Dependencies

### PHP Requirements

| Requirement | Version | Status |
|-------------|---------|--------|
| PHP | 7.4+ (8.0+ recommended) | Required |
| json extension | - | Required |
| curl extension | - | Required |
| mbstring extension | - | Optional (polyfill available) |
| redis extension | - | Optional |

### Composer Packages

```json
{
    "require": {
        "symfony/cache": "^6.0",
        "guzzlehttp/guzzle": "^7.0",
        "psr/cache": "^3.0"
    }
}
```

### Third-party Libraries

| Library | File | Fungsi |
|---------|------|--------|
| Simple HTML DOM | `libraries/simple_html_dom.php` | HTML parsing |
| UA Class | `libraries/ua.class.php` | User agent detection |

---

## Instalasi dan Setup

### 1. Prerequisites

```bash
# Install PHP dan extensions
sudo apt install php8.3 php8.3-curl php8.3-json php8.3-mbstring

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Installation

```bash
# Clone/upload project
cd /var/www/html
git clone [repository] agc

# Install dependencies
cd agc
composer install

# Set permissions
chmod -R 755 cache/ store/ output/
chown -R www-data:www-data cache/ store/ output/
```

### 3. Configuration

```bash
# Edit konfigurasi
nano config/default/config.json

# Set site name, API keys, cache settings
```

### 4. Web Server

**Apache (.htaccess sudah tersedia):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5. Redis Setup (Optional)

```bash
# Install Redis
sudo apt install redis-server

# Configure in config.json
{
    "enable_redis_cache": "1",
    "redis_host": "127.0.0.1",
    "redis_port": "6379",
    "redis_auth": "your-password"
}
```

---

## Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| **Route not found** | Check `routes.php`, verify `.htaccess` |
| **Cache not working** | Check `cache/` permissions, clear cache |
| **mbstring error** | Polyfill sudah tersedia di `common.php` |
| **Redis connection failed** | Verify Redis running, check credentials |
| **Blank page** | Enable `display_errors` in `index.php` |

### Debug Mode

```php
// Di index.php (development only):
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

### Cache Management

```bash
# Clear Symfony cache
rm -rf cache/symfony/*

# Clear all cache
rm -rf cache/*
php -r "opcache_reset();"
```

### Logs

```bash
# Check PHP error log
tail -f /var/log/php/error.log

# Check fetch status
cat fetch_status.json
cat fetch_output.log
```

---

## Admin Panel (lite/)

Panel admin untuk mengelola konten dan konfigurasi:

| Page | Fungsi |
|------|--------|
| `index.php` | Dashboard overview |
| `settings.php` | Site configuration |
| `keywords.php` | Keyword management |
| `playlist.php` | Playlist management |
| `robots.php` | Robots.txt editor |
| `pingsitemap.php` | Sitemap ping tool |

**Access:** `https://yourdomain.com/lite/`

---

## Performance Optimizations

### Loading Strategy

| Resource | Strategy | Reason |
|----------|----------|--------|
| **Critical CSS** | Inline in `<head>` | Fast First Paint |
| **Main CSS** | Preload async | Non-blocking |
| **Fonts** | Preload async | Non-blocking |
| **JavaScript** | Defer | Non-blocking |
| **Images** | Lazy load | Reduce initial load |
| **Ads** | Lazy load on interaction | Improve Core Web Vitals |

### Core Web Vitals Targets

| Metric | Target | Implementation |
|--------|--------|----------------|
| **LCP** | < 2.5s | Critical CSS inline, preload images |
| **FID** | < 100ms | Defer JS, optimize event handlers |
| **CLS** | < 0.1 | Fixed dimensions, no layout shift |
| **FCP** | < 1.8s | Critical CSS, preload resources |

### Image Optimization

- ✅ Lazy loading dengan lazysizes
- ✅ Placeholder.png untuk initial load
- ✅ Placeholder.svg untuk error fallback
- ✅ Width/height attributes untuk CLS
- ✅ Decoding="async" untuk non-blocking
- ✅ CDN dengan photon_cdn() untuk optimization

## SEO Optimizations

### On-Page SEO

| Element | Implementation | Status |
|---------|----------------|--------|
| **Title Tags** | Dynamic dari config.json | ✅ |
| **Meta Descriptions** | Template-based dengan variables | ✅ |
| **Canonical URLs** | Setiap halaman | ✅ |
| **Robots Meta** | index, follow (kecuali player) | ✅ |
| **Schema.org** | JSON-LD (optimized, tidak di loop) | ✅ |
| **Open Graph** | Facebook sharing | ✅ |
| **Twitter Cards** | Twitter sharing | ✅ |
| **Breadcrumbs** | BreadcrumbList schema | ✅ |
| **ItemList** | Untuk koleksi video | ✅ |
| **VideoObject** | Untuk detail video | ✅ |

### Schema.org Implementation

**Optimized Approach:**
- ❌ **Before:** Schema di dalam loop (20+ script tags)
- ✅ **After:** Single ItemList schema dengan array

**Benefits:**
- Reduced HTML size
- Faster parsing
- Better SEO structure
- Easier maintenance

### URL Structure

```
✅ SEO-Friendly URLs:
- /f/video-viral          (search)
- /f/video-viral/page/2  (pagination)
- /e/abc123xyz           (video detail)
- /dl/abc123xyz          (download)

✅ Lowercase normalization
✅ Trailing slash handling
✅ Canonical URLs
```

## UI/UX Best Practices

### Accessibility

- ✅ Semantic HTML5 elements
- ✅ ARIA labels untuk buttons
- ✅ Keyboard navigation support
- ✅ Focus indicators
- ✅ Alt text untuk images
- ✅ Proper heading hierarchy (h1, h2, h3)

### Mobile Optimization

- ✅ Mobile-first responsive design
- ✅ Touch-friendly tap targets (44px minimum)
- ✅ Smooth drawer animations
- ✅ Body scroll lock saat drawer open
- ✅ Viewport meta tag optimized

### Performance UX

- ✅ Skeleton loading states
- ✅ Progressive image loading
- ✅ Smooth transitions
- ✅ No layout shift (CLS = 0)
- ✅ Fast page transitions

## Changelog

### Version 1.2 (Current)
- ✅ **Performance:** Critical CSS inline, async font/CSS loading
- ✅ **SEO:** Optimized schema (moved out of loops), fixed noindex issues
- ✅ **Error Handling:** Lazysizes error handling untuk status 451
- ✅ **Mobile:** Smooth drawer animations dengan stagger effect
- ✅ **Code:** Simplified dl.php dengan template system
- ✅ **Accessibility:** ARIA labels, keyboard navigation
- ✅ **Meta Tags:** Added author, language, revisit-after

### Version 1.1
- Added mbstring polyfills for PHP compatibility
- Cleaned up unused files and directories
- Improved documentation
- Fixed CSS file structure

### Version 1.0
- Initial release
- Video streaming and download
- Search with pagination
- Admin panel

---

**Dokumentasi Terakhir Diupdate:** Desember 2024  
**PHP Version:** 7.4+ / 8.0+ / 8.3+  
**Maintainer:** AGC Development Team
