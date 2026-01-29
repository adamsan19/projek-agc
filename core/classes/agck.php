<?php
require 'vendor/autoload.php';

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

class AGC {
    private $cache;

    public function __construct() {
        // Inisialisasi cache
        $this->cache = new FilesystemAdapter();
    }

    // Fungsi untuk mendapatkan base URL atau path saat ini
    private function get_current_url_context() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return md5($host . $uri);
    }

    // Fungsi bantuan untuk proses pembersihan judul
    private function process_clean_title($raw_title) {
        if (empty($raw_title)) {
            $title = '';
        } else {
            $title = preg_replace('/([a-z])([A-Z])/', '$1 $2', $raw_title);
            $title = preg_replace('/[0-9]+/', '', $title);
            $title = preg_replace('/[^a-zA-Z\s]+/', ' ', $title);
            $title = trim(preg_replace('/\s+/', ' ', $title));
        }
        
        if (empty($title)) {
            $words = [];
        } else {
            $title = ucwords(strtolower($title));
            $words = explode(' ', $title);
            $words = array_filter($words);
        }

        $randomWords = [
            'Simontok', 'Bokep31', 'Bebasindo', 'Bokepin', 'Doodflix',
            'Bokepsatset', 'Doodstream', 'Dood Tele', 'Cantik Tobrut', 'Memeksiana',
            'Doods', 'Vidio Viral', 'Telegram', 'Full Album', 'Viral', 'Videos', 'Poophd',
            'Twitter', 'Bochiel', 'Asupan', 'Link', 'Streaming', 'Web Bekeh', 'Folder',
            'Cilbo', 'Live', 'Tele', 'Terupdate', 'Terbaru', 'Links', 'Lokal',
            'Dodstream', 'Bokep', 'Pemersatu', 'Video', 'Update', 'Dood', 'Doostream',
            'Website', 'Download', 'Indo', 'Lulustream', 'Sotwe', 'Doodsflix', 'Yakwad',
            'Doodflix', 'Tobrut', 'Lagi Viral', 'Stw Tobrut', 'Jilbab', 'Colmek'
        ];

        while(count($words) < 10) {
            $randomWord = $randomWords[array_rand($randomWords)];
            $words[] = $randomWord;
        }

        $title = implode(' ', $words);
        return ucwords(strtolower($title));
    }

    private function get_clean_title($file_code, $raw_title) {
        if (empty($file_code)) {
            return $this->process_clean_title($raw_title);
        }

        $url_context = $this->get_current_url_context();
        $cache_key = "clean_title_by_code_" . $file_code . "_" . $url_context;

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($raw_title) {
            $item->expiresAfter(60 * 60 * 24 * 30); // Cache 30 hari
            return $this->process_clean_title($raw_title);
        });
    }

    private function generate_description_key($title) {
        $randomPhrases = [
            'jilbab indo terbaru viral 2024', 'hijab terbaru viral kesakitan',
            'trend hijab viral', 'twstalker stw binor lokal stw',
            'stw stalker pemuas binor', 'doodstream Telegram terbaru 2025',
            'video viral Telegram doodstream', 'stw binor lokal DoodStream'
        ];

        $processed_title = $this->process_clean_title($title);
        $description_parts = [$processed_title];
        
        while (str_word_count(implode(' ', $description_parts)) < 40) {
            $random_phrase = $randomPhrases[array_rand($randomPhrases)];
            $description_parts[] = $random_phrase;
        }

        $full_description = implode('. ', $description_parts);
        $words = explode(' ', $full_description);
        $limited_words = array_slice($words, 0, 40);
        return trim(implode(' ', $limited_words));
    }

    private function normalize_length($length) {
        if (empty($length)) return '00:00:00';
        if (is_string($length) && preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $length)) return $length;
        if (is_string($length) && preg_match('/^\d{1,2}:\d{2}$/', $length)) return '00:' . $length;
        if (is_numeric($length)) {
            $hours = floor($length / 3600);
            $minutes = floor(($length % 3600) / 60);
            $seconds = $length % 60;
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }
        return '00:00:00';
    }

    private function remove_duplicates($items) {
        $unique_items = [];
        $seen_keys = [];
        if (is_array($items)) {
            foreach ($items as $item) {
                $key = ($item['file_code'] ?? '') . '_' . md5($item['title'] ?? '') . '_' . ($item['length'] ?? '');
                if (!isset($seen_keys[$key])) {
                    $seen_keys[$key] = true;
                    $unique_items[] = $item;
                }
            }
        }
        return $unique_items;
    }

    public function get_search($q = '', $page = 1) {
        if (empty($q)) $q = (string)($_GET['s'] ?? '');
        
        $url_context = $this->get_current_url_context();
        $cache_key = "search_dood_v3_" . md5($q . $page . $url_context);

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($q, $page) {
            // Default expiry jika berhasil
            $item->expiresAfter(86400); 

            $keywords = array_filter(array_map('strtolower', explode(' ', (string)$q)));
            if (empty($keywords)) {
                $item->expiresAfter(300); // Cache hasil kosong 5 menit
                return [];
            }

            $items = [];
            $queries = array_unique(array_merge([$q], $keywords));
            $client = new Client();
            $promises = [];

            foreach (array_slice($queries, 0, 5) as $query) {
                $encoded = urlencode($query);
                $url = "https://doodapi.co/api/search/videos?key=112623ifbcbltzajwjrpjx&search_term={$encoded}&page={$page}";
                $promises[$query] = $client->getAsync($url, ['headers' => ['Referer' => 'https://doodapi.co']]);
            }

            $responses = Utils::settle($promises)->wait();
            $hasValidData = false;

            foreach ($responses as $response) {
                if ($response['state'] === 'fulfilled' && $response['value']->getStatusCode() == 200) {
                    $res = json_decode($response['value']->getBody(), true);
                    if (!empty($res['result'])) {
                        $hasValidData = true;
                        foreach ($res['result'] as $res_item) {
                            $fcode = $res_item['file_code'] ?? '';
                            $items[] = [
                                'title' => $this->get_clean_title($fcode, $res_item['title'] ?? ''),
                                'single_img' => $res_item['single_img'] ?? '',
                                'file_code' => $fcode,
                                'id' => $fcode,
                                'length' => $this->normalize_length($res_item['length'] ?? ''),
                                'views' => $res_item['views'] ?? 0,
                                'uploaded' => $res_item['uploaded'] ?? '',
                                'relevance' => $this->calculate_relevance($res_item['title'] ?? '', $q, $keywords)
                            ];
                        }
                    }
                }
            }

            if (!$hasValidData || empty($items)) {
                $item->expiresAfter(300); // PERBAIKAN: Tetap cache hasil kosong selama 5 menit
                return [];
            }

            $unique_items = $this->remove_duplicates($items);
            usort($unique_items, function($a, $b) {
                return ($b['relevance'] == $a['relevance']) ? 
                    strtotime($b['uploaded']) - strtotime($a['uploaded']) : 
                    $b['relevance'] - $a['relevance'];
            });

            return $unique_items;
        });
    }

    private function calculate_relevance($title, $query, $keywords) {
        $score = 0;
        $title = strtolower($title);
        if (strpos($title, strtolower($query)) !== false) $score += 100;
        foreach ($keywords as $kw) if (strpos($title, $kw) !== false) $score += 10;
        return $score;
    }

    public function get_list_files($halaman = 1) {
        $url_context = $this->get_current_url_context();
        $cache_key = "list_files_v3_" . md5($halaman . $url_context);

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($halaman) {
            $item->expiresAfter(86400);
            try {
                $client = new Client(['headers' => ['Referer' => 'https://doodapi.co']]);
                $url = "https://doodapi.co/api/file/list?key=112623ifbcbltzajwjrpjx&page={$halaman}&per_page=50";
                $response = $client->get($url);
                $res = json_decode($response->getBody(), true);

                if (!empty($res['result']['files'])) {
                    $items = [];
                    foreach ($res['result']['files'] as $f) {
                        $fcode = $f['file_code'] ?? '';
                        $items[] = [
                            'title' => $this->get_clean_title($fcode, $f['title'] ?? ''),
                            'single_img' => $f['single_img'] ?? '',
                            'file_code' => $fcode,
                            'id' => $fcode,
                            'length' => $this->normalize_length($f['length'] ?? ''),
                            'uploaded' => $f['uploaded'] ?? ''
                        ];
                    }
                    return $this->remove_duplicates($items);
                }
            } catch (\Exception $e) {}

            $item->expiresAfter(300); // Cache hasil gagal/kosong 5 menit
            return [];
        });
    }

    public function get_video() {
        global $route;
        $id = $route['vars'][0] ?? '';
        $url_context = $this->get_current_url_context();
        $cache_key = "video_detail_v3_" . md5($id . $url_context);

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(86400);
            if (empty($id)) {
                $item->expiresAfter(300);
                return [];
            }

            try {
                $client = new Client(['headers' => ['Referer' => 'https://doodapi.co']]);
                $url = "https://doodapi.co/api/info/videos?key=112623ifbcbltzajwjrpjx&file_code={$id}";
                $response = $client->get($url);
                $res = json_decode($response->getBody(), true);

                if (!empty($res['result'][0])) {
                    $s = $res['result'][0];
                    return [
                        'title' => $this->get_clean_title($id, $s['title'] ?? ''),
                        'filecode' => $id,
                        'protected_embed' => "https://dodl.pages.dev/{$id}",
                        'size' => $this->get_format_bytes($s['size'] ?? 0),
                        'length' => $this->normalize_length($s['length'] ?? ''),
                        'deskripsi' => $this->generate_description_key($s['title'] ?? ''),
                        'single_img' => $s['single_img'] ?? '',
                        'views' => $s['views'] ?? 0
                    ];
                }
            } catch (\Exception $e) {}

            $item->expiresAfter(300); // Cache kegagalan detail video 5 menit
            return [];
        });
    }

    private function get_format_bytes($bytes, $precision = 2) {
        if (is_string($bytes) && preg_match('/[a-zA-Z]/', $bytes)) return $bytes;
        $bytes = (float)$bytes;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'kB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}

function agc() {
    return new AGC;
}
