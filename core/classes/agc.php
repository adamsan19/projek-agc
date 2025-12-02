<?php
require 'vendor/autoload.php';
//require_once 'ua.class.php';
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
        // Dapatkan protocol (http atau https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        
        // Dapatkan host
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Dapatkan path/URI
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Gabungkan menjadi URL lengkap
        $full_url = $protocol . '://' . $host . $uri;
        
        // Untuk cache key, kita bisa menggunakan hash dari URL atau bagian tertentu
        // Gunakan host + path untuk menghindari cache yang sama di domain berbeda
        return md5($host . $uri);
    }

    // Fungsi bantuan untuk proses pembersihan judul - DIPERBAIKI
    private function process_clean_title($raw_title) {
        // Jika judul kosong, mulai dengan string kosong
        if (empty($raw_title)) {
            $title = '';
        } else {
            // Tambahkan spasi sebelum huruf kapital yang mengikuti huruf kecil (contoh: VideoTerbaru -> Video Terbaru)
            $title = preg_replace('/([a-z])([A-Z])/', '$1 $2', $raw_title);
            // Hapus semua angka
            $title = preg_replace('/[0-9]+/', '', $title);
            // Ganti semua karakter non-alfabet (selain spasi) dengan spasi tunggal.
            $title = preg_replace('/[^a-zA-Z\s]+/', ' ', $title);
            // Ganti spasi berlebih dengan satu spasi dan trim ujung-ujungnya.
            $title = trim(preg_replace('/\s+/', ' ', $title));
        }
        
        // Jika setelah pembersihan judul masih kosong, buat array kata kosong
        if (empty($title)) {
            $words = [];
        } else {
            // Ubah setiap kata pertama menjadi huruf kapital dan sisanya huruf kecil
            $title = ucwords(strtolower($title));
            // Pecah judul menjadi kata-kata
            $words = explode(' ', $title);
            $words = array_filter($words); // Hapus elemen kosong jika ada
        }

        $randomWords = ['Simontok', 'Bokep31', 'Bebasindo', 'Bokepin', 'Doodflix',
        'Bokepsatset', 'Doodstream', 'Dood Tele', 'Cantik Tobrut', 'Memeksiana',
        'Doods', 'Vidio Viral', 'Doods', 'Telegram', 'Full Album', 'Viral', 'Videos', 'Poophd',
        'Twitter', 'Bochiel', 'Asupan', 'Link', 'Streaming', 'Web Bekeh', 'Folder',
        'Cilbo', 'Live', 'Tele', 'Terupdate', 'Terbaru', 'Links', 'Lokal',
        'Dodstream', 'Bokep', 'Pemersatu', 'Video', 'Update', 'Dood', 'Doostream',
        'Website', 'Download', 'Indo', 'Lulustream', 'Sotwe', 'Doodsflix', 'Yakwad',
        'Doodflix', 'Cilbo', 'sotwe', 'bokep dood', 'twitter', 'bokepsatset', 'simontok', 'terbaru',
        'video', 'bokep video', 'simintok', 'xpanas', 'Full album', 'baru',
        'montok', 'memek', 'bokep', 'asupan', 'doodstream', 'bokepsin', 'bebasindo',
        'Pekoblive', 'Terabox', 'streaming', 'viral', 'indo', 'tiktok', 'telegram',
        'Doodstream', 'Doods', 'Pro', 'Telegram', 'Full', 'Album', 'Viral', 'Videos',
        'Poophd', 'Twitter', 'Bochiel', 'Asupan', 'Link', 'Streaming', 'Web', 'Folder',
        'Cilbo', 'Live', 'Tele', 'Terupdate', 'Terbaru', 'Links', 'Lokal', 'Dodstream',
        'Bokep', 'Pemersatu', 'Video', 'Update', 'Dood', 'Doostream', 'Website',
        'Downloader', 'Sakandal Indo', 'Lulustream', 'Sotwe', 'Doodsflix', 'Yakwad', 'Doodflix',
        'Tobrut', 'Lagi Viral', 'Stw Tobrut', 'Doodstreem', 'Jilbab',
        'Sesuai Gambar', 'Colmek', 'Binor', 'Viral Smp', 'Video Yandex Vk', 'Asupan viral',
        'Download', 'Video Terbaru', 'Hijab', 'Hijabers', 'Rusia', 'tele', 'Bangsa',
        'Pejuang', 'Lendir', 'Popstream', 'Staklam', 'bokepind', 'Video Bokep',
        'simontok', 'bokep31', 'Simontok', 'Video indo', 'terabox', 'video',
        'Video colmek', 'Toket Bagus', 'Video arachu',
        'Video sma', 'Doods pro', 'ngentot', 'feby senda',
        'penjaga warung', 'indonesia', 'open bo', 'bebasindo', 'bokepin', 'doodflix',
        'bokepsatset', 'doodstream', 'dood tele', 'cantik Tobrut', 'memeksiana'];

        // PERBAIKAN: Selalu tambahkan kata acak hingga jumlah kata mencapai 10, bahkan jika judul kosong
        while(count($words) < 10) {
            $randomWord = $randomWords[array_rand($randomWords)];
            $words[] = $randomWord;
        }

        $title = implode(' ', $words);
        
        // Pastikan judul akhir memiliki format yang benar
        return ucwords(strtolower($title));
    }

    // Fungsi utama untuk mendapatkan judul yang telah dibersihkan dan di-cache berdasarkan file_code
    private function get_clean_title($file_code, $raw_title) {
        if (empty($file_code)) {
            // Jika tidak ada file_code, kembalikan hasil proses tanpa cache
            return $this->process_clean_title($raw_title);
        }

        $url_context = $this->get_current_url_context();
        $cache_key = "clean_title_by_code_" . $file_code . "_" . $url_context; // Tambahkan URL context

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($raw_title) {
            $item->expiresAfter(60 * 60 * 24 * 30); // Cache selama 30 hari
            return $this->process_clean_title($raw_title); // Proses pembersihan dilakukan di sini
        });
    }

    // Fungsi baru untuk menghasilkan deskripsi dengan judul dan kata acak - DIPERBAIKI
    private function generate_description_key($title) {
        $randomPhrases = [
            'jilbab indo terbaru viral 2024',
            'hijab terbaru viral kesakitan',
            'jilbab viral gen 5',
            'trend hijab viral',
            'twitter hijab terbaru',
            'hijab hit',
            'jilbab indo terbaru 2025 viral',
            'jilbab twitter baru',
            'twstalker stw binor lokal stw',
            'stw stalker pemuas binor',
            'stw vcs binor jagoanneon',
            'twstalker pemuas stw',
            'binor twisakler',
            'binor twstalker pemuas',
            'stw twslaker',
            'binor stalker',
            'vmaniakj twstalker',
            'twitter hijab cantik',
            'hijab twitter malay',
            'twstalker hijab',
            'hijab malaysia twitter',
            'twitter hijab pemersatu',
            'twstalker hijab addict',
            'twstalker hijab pap',
            'Telegram',
            'doodstream Telegram terbaru 2025',
            'link doodstream Telegram terbaru 2022',
            'doodstream Telegram terbaru',
            'video viral Telegram doodstream',
            'dood Telegram viral terbaru',
            'Telegram video viral dood',
            'asupan video doodstream tele',
            'viral terbaru 2025 dood',
            'stw binor lokal sotwe smp',
            'stw vcs binor jagoanneon',
            'binor sebelah twstalker',
            'twitter binor dan stw',
            'stw binor website',
            'stw binor harga',
            'yandex binor lokal',
            'penikmat stw dan binor',
            'stw binor lokal DoodStream',
            'stw and binor twitter',
            'link DoodStream stw',
            'streaming binor stw',
            'DoodStream stw lokal',
            'stw binor DoodStream twitter',
            'twitter stw binor lokal',
            'stw binor lokal sotwe smp',
            'Twitter doodstream hijab',
            'Twitter jilbab baru',
            'Twitter hijab',
            'Twitter hijab style',
            'tweet hijab',
            'doods hijab',
            'jilbab viral tweet',
            'dood viral hijab',
            'DoodStream hijab fix',
            'DoodStream hijabers viral',
            'DoodStream hijabers',
            'link DoodStream hijab',
            'DoodStream hijab download',
            'twitter jilbab baru',
            'twitter link hijab',
            'jilbab bahan dood streaming',
            'twicopy binor installer',
            'stw binor website',
            'twicopy cd bra stw binal',
            'twstalker pemuas stw',
            'twstalker pencembur binor',
            'twstalker binor indonesia',
            'twstalker binor terbaru',
            'binor lokal stw',
            'binor stw yang baru',
            'twicopy stw and binor',
            'tweet stw binor sebelah',
            'binor stw pemuas',
            'stw installer binor',
            'twicopy pemburu stw binal',
            'stw binor daster installer',
            'video viral tante dood',
            'indo cantik dood',
            'tante dood twitter',
            'dood mania linktree',
            'video doodstream',
            'download video doodstream free',
            'doodstream video downloader',
            'cara mencari video di doodstream',
            'download video doodstream tanpa aplikasi',
            'video dood terbaru',
            'video dood viral',
            'video dood viral terbaru',
            'DoodStream hijab fix',
            'DoodStream hijabers viral',
            'DoodStream hijabers',
            'link DoodStream hijab',
            'DoodStream hijab download',
            'twitter jilbab baru',
            'twitter link hijab',
            'jilbab bahan dood streaming'
        ];

        // PERBAIKAN: Gunakan judul yang diproses, bahkan jika kosong akan tetap menghasilkan kata
        $processed_title = $this->process_clean_title($title);
        
        // Gabungkan judul dengan frasa acak
        $description_parts = [$processed_title];
        $phrases_needed = 40 - str_word_count($processed_title); // Hitung sisa kata yang dibutuhkan

        if ($phrases_needed > 0) {
            // Pilih beberapa frasa acak secara acak untuk mencapai ~40 kata
            while (str_word_count(implode(' ', $description_parts)) < 40) {
                $random_phrase = $randomPhrases[array_rand($randomPhrases)];
                $description_parts[] = $random_phrase;
                if (str_word_count(implode(' ', $description_parts)) >= 40) {
                    break; // Hentikan jika sudah mencapai atau melebihi 40 kata
                }
            }
        }

        $full_description = implode('. ', $description_parts);
        // Batasi ke 40 kata
        $words = explode(' ', $full_description);
        $limited_words = array_slice($words, 0, 40);
        return trim(implode(' ', $limited_words));
    }

    private function normalize_length($length) {
        if (empty($length) || $length === '') {
            return '00:00:00';
        }
    
        // Jika sudah format HH:MM:SS, return as is
        if (is_string($length) && preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $length)) {
            return $length;
        }
    
        // Jika format MM:SS, convert ke HH:MM:SS
        if (is_string($length) && preg_match('/^\d{1,2}:\d{2}$/', $length)) {
            return '00:' . $length;
        }
    
        // Jika numeric (seconds), convert ke HH:MM:SS
        if (is_numeric($length)) {
            $hours = floor($length / 3600);
            $minutes = floor(($length % 3600) / 60);
            $seconds = $length % 60;
            return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }
    
        // Default untuk format tidak dikenali
        return '00:00:00';
    }

    // Perbaikan: Fungsi untuk menghapus duplikat berdasarkan kombinasi file_code dan length
    private function remove_duplicates($items) {
        $unique_items = [];
        $seen_keys = [];
    
        if (is_array($items)) {
            foreach ($items as $item) {
                // Ambil file_code, title, dan length dari item
                $file_code = $item['file_code'] ?? '';
                $title = $item['title'] ?? '';
                $length = $item['length'] ?? '';
    
                // Normalisasi length untuk konsistensi
                $normalized_length = $this->normalize_length($length);
    
                // Buat kunci unik berdasarkan kombinasi file_code + title + length
                $key = $file_code . '_' . md5($title) . '_' . $normalized_length;
    
                // Cek apakah kunci ini sudah pernah dilihat
                if (!isset($seen_keys[$key])) {
                    // Tandai kunci sebagai sudah dilihat
                    $seen_keys[$key] = true;
                    // Tambahkan item ke daftar unik
                    $unique_items[] = $item;
                }
            }
        }
        return $unique_items;
    }

    public function get_search($q = '', $page = 1) {
        if (empty($q)) {
            $q = get_search_query();
        }

        $q = (string) $q;
        $url_context = $this->get_current_url_context();
        $cache_key = "full_scesasrch_dood_" . md5($q . $page . $url_context); // Tambahkan URL context

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($q, $page) {
            $item->expiresAfter(86400); 

            // Pecah query menjadi array kata-kata
            $keywords = explode(' ', $q);
            $keywords = array_filter(array_map('strtolower', $keywords));

            if (empty($keywords)) {
                $item->expiresAfter(60);
                return [];
            }

            $items = [];
            $queries = [];
            $hasValidData = false;

            // Tambahkan setiap kata sebagai query terpisah
            foreach ($keywords as $word) {
                $queries[] = $word;
            }

            // Tambahkan kombinasi kata sebagai query
            if (count($keywords) > 1) {
                $queries[] = implode(' ', array_slice($keywords, 0, 2));
                if (count($keywords) > 2) {
                    $queries[] = implode(' ', array_slice($keywords, 0, 3));
                }
            }

            $queries = array_unique($queries);

            $client = new Client();
            $promises = [];

            foreach ($queries as $query) {
                $encoded_query = urlencode($query);
                $url = "https://doodapi.co/api/search/videos?key=112623ifbcbltzajwjrpjx&search_term={$encoded_query}&page={$page}";
                $promises[$query] = $client->getAsync($url, ['headers' => ['Referer' => 'https://doodapi.co']]);
            }

            $responses = Utils::settle($promises)->wait();

            foreach ($responses as $query => $response) {
                if ($response['state'] === 'fulfilled' && $response['value']->getStatusCode() == 200) {
                    $response_array = json_decode($response['value']->getBody(), true);

                    if (isset($response_array['result']) && is_array($response_array['result']) && !empty($response_array['result'])) {
                        $hasValidData = true;
                        foreach ($response_array['result'] as $result_item) {
                            $original_title = $result_item['title'] ?? '';
                            $file_code = $result_item['file_code'] ?? ''; // Ambil file_code
                            $data['title'] = $this->get_clean_title($file_code, $original_title); // Gunakan get_clean_title
                            $data['single_img'] = $result_item['single_img'] ?? '';
                            $data['splash_img'] = $result_item['splash_img'] ?? '';
                            $data['file_code'] = $file_code; // Pastikan file_code disimpan
                            $data['id'] = $file_code;
                            $data['api_source'] = $result_item['api_source'] ?? 'doodstream';
                            $data['length'] = $this->normalize_length($result_item['length'] ?? ''); // Gunakan normalize_length
                            $data['views'] = $result_item['views'] ?? 0;
                            $data['uploaded'] = $result_item['uploaded'] ?? '';
                            $data['relevance'] = $this->calculate_relevance($original_title, $q, $keywords);
                            $items[] = $data;
                        }
                    }
                }
            }

            // PERBAIKAN: Jika tidak ada data valid, kembalikan array kosong tanpa cache
            if (!$hasValidData || empty($items)) {
                return [];
            }

            // Hapus duplikat dengan length yang sudah dinormalisasi
            $unique_items = $this->remove_duplicates($items);

            // Urutkan berdasarkan relevansi, lalu berdasarkan tanggal upload
            usort($unique_items, function($a, $b) {
                if ($a['relevance'] == $b['relevance']) {
                    return strtotime($b['uploaded']) - strtotime($a['uploaded']);
                }
                return $b['relevance'] - $a['relevance'];
            });

            return $unique_items;
        });
    }

    private function calculate_relevance($title, $original_query, $keywords) {
        $relevance = 0;
        $clean_title_for_match = strtolower(trim(preg_replace(['/[0-9]+/', '/[^a-zA-Z\s]+/', '/\s+/'], ['', ' ', ' '], $title)));
        $query_lower = strtolower(trim($original_query));

        if (!empty($query_lower) && strpos($clean_title_for_match, $query_lower) !== false) {
            $relevance += 100;
        }

        $match_count = 0;
        foreach ($keywords as $keyword) {
            if (strpos($clean_title_for_match, $keyword) !== false) {
                $match_count++;
            }
        }

        if (count($keywords) > 0) {
            $relevance += ($match_count / count($keywords)) * 10;
        }

        return $relevance;
    }

    public function get_list_files($halaman = 1) {
        $halaman = filter_var($halaman, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $url_context = $this->get_current_url_context();
        $cache_key = "listz_scvsddxod_v2_" . md5($halaman . $url_context); // Tambahkan URL context

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($halaman) {
            $item->expiresAfter(86400); 

            $items = [];
            $hasValidData = false;

            try {
                $client = new Client(['headers' => ['Referer' => 'https://doodapi.co']]);
                $url = "https://doodapi.co/api/file/list?key=112623ifbcbltzajwjrpjx&page={$halaman}&per_page=50";
                $response = $client->get($url);

                if ($response->getStatusCode() == 200) {
                    $response_array = json_decode($response->getBody(), true);

                    if (isset($response_array['result']['files']) && is_array($response_array['result']['files']) && !empty($response_array['result']['files'])) {
                        $hasValidData = true;
                        $jumlah_halaman = $response_array['result']['total_pages'] ?? null;

                        foreach ($response_array['result']['files'] as $file_item) {
                            $file_code = $file_item['file_code'] ?? '';
                            $data = [
                                'title' => $this->get_clean_title($file_code, $file_item['title'] ?? ''), // Gunakan get_clean_title
                                'single_img' => $file_item['single_img'] ?? '',
                                'file_code' => $file_code,
                                'download_url' => $file_item['download_url'] ?? '',
                                'id' => $file_code,
                                'api_source' => $file_item['api_source'] ?? 'doodstream',
                                'views' => $file_item['views'] ?? null,
                                'length' => $this->normalize_length($file_item['length'] ?? ''), // Gunakan normalize_length
                                'cat' => $file_item['fld_id'] ?? null,
                                'uploaded' => $file_item['uploaded'] ?? null,
                                'halaman' => $jumlah_halaman
                            ];
                            $items[] = $data;
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching list files: {$e->getMessage()}");
            }

            // PERBAIKAN: Jika tidak ada data valid, kembalikan array kosong tanpa cache
            if (!$hasValidData || empty($items)) {
                return [];
            }

            return $this->remove_duplicates($items);
        });
    }

    public function get_video() {
        global $route;
        $id = $route['vars'][0] ?? ''; // $id adalah file_code
        $url_context = $this->get_current_url_context();
        $cache_key = "vid_vdood_" . md5($id . $url_context); // Tambahkan URL context

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(86400); 

            if (empty($id)) {
                return [];
            }

            try {
                $client = new Client(['headers' => ['Referer' => 'https://doodapi.co']]);
                $url = "https://doodapi.co/api/info/videos?key=112623ifbcbltzajwjrpjx&file_code={$id}";
                $response = $client->get($url);

                if ($response->getStatusCode() != 200) {
                    return [];
                }

                $response_array = json_decode($response->getBody(), true);

                if (!isset($response_array['result']) || !is_array($response_array['result']) || empty($response_array['result'])) {
                    return [];
                }

                $snippet = $response_array['result'][0];
                $file_code = $snippet['filecode'] ?? $id; // Gunakan filecode jika tersedia, jika tidak gunakan $id
                $raw_title = $snippet['title'] ?? ''; // Ambil judul asli untuk deskripsi

                return [
                    'canplay' => $snippet['canplay'] ?? 0,
                    'uploaded' => $snippet['uploaded'] ?? '',
                    'views' => $snippet['views'] ?? '0',
                    'title' => $this->get_clean_title($file_code, $raw_title), // Gunakan get_clean_title
                    'single_img' => $snippet['single_img'] ?? '',
                   // 'protected_embed' => $snippet['protected_embed'] ?? '',
                   'protected_embed' => "https://dodl.pages.dev/{$snippet['filecode']}",
                    'status' => $snippet['status'] ?? 0,
                    'filecode' => $file_code,
                    'source' => $snippet['api_source'] ?? 'doodstream',
                    'id' => $file_code,
                    'protected_dl' => "https://doodstream.com/d/{$snippet['filecode']}",
                    'splash_img' => $snippet['splash_img'] ?? '',
                    'last_view' => $snippet['last_view'] ?? '',
                    'size' => $this->get_format_bytes($snippet['size'] ?? 0),
                    'length' => $this->normalize_length($snippet['length'] ?? ''), // Gunakan normalize_length
                    // Tambahkan kunci deskripsi
                    'deskripsi' => $this->generate_description_key($raw_title)
                ];
            } catch (Exception $e) {
                error_log("Error fetching video: {$e->getMessage()}");
                return [];
            }
        });
    }

    private function get_format_bytes($bytes, $precision = 2) {
        // If input is already a formatted string (contains letters), return as-is
        if (is_string($bytes) && preg_match('/[a-zA-Z]/', $bytes)) {
            return $bytes;
        }
        
        // Convert to numeric value
        $bytes = (float) $bytes;
        
        // If bytes is 0 or negative, return 0 B
        if ($bytes <= 0) {
            return '0 B';
        }
        
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function get_related($title) {
        if (is_null($title)) {
            $title = '';
        }
        $url_context = $this->get_current_url_context();
        $cache_key = "related_video_dood_" . md5($title . $url_context); // Tambahkan URL context

        return $this->cache->get($cache_key, function (ItemInterface $item) use ($title) {
            $item->expiresAfter(86400);
            return $this->get_search($title);
        });
    }
}

function agc() {
    return new AGC;
}