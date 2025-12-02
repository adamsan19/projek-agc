import asyncio
import importlib
import json
import os

try:
    aiohttp = importlib.import_module("aiohttp")
except ImportError as exc:
    raise ImportError(
        "aiohttp is required. Activate the project virtualenv "
        "and run `pip install -r core/classes/requirements.txt`."
    ) from exc
import time
import glob
from datetime import datetime
from typing import List, Dict, Any, Optional
import hashlib
import random
import re
from pathlib import Path

# --- Constants ---
FETCH_LOCK_FILE = 'fetch.lock'
FETCH_STATUS_FILE = 'fetch_status.json'
DATA_PER_PAGE = 200

# --- Configuration ---
CONFIGS = {
    'doodstream': {
        'api_url': 'https://doodapi.com/api/file/list',
        'api_key': '112623ifbcbltzajwjrpjx',
        'per_page': 200,
        'request_delay': 1,
        'max_retries': 3
    },
    'lulustream': {
        'api_url': 'https://api.lulustream.com/api/file/list',
        'api_key': '37943j35tc5i1bg3gje5y',
        'per_page': 500,
        'request_delay': 1,
        'max_retries': 3
    }
}

class DataNormalizer:
    def clean_and_format_title(self, title: str) -> str:
        if not title:
            return ''
        
        # Add space before capital letters following lowercase
        clean_title = re.sub(r'([a-z])([A-Z])', r'\1 \2', title)
        
        # Replace special characters within words with spaces
        clean_title = re.sub(r'([a-zA-Z])[^a-zA-Z\s]+([a-zA-Z])', r'\1 \2', clean_title)
        
        # Remove remaining special characters
        clean_title = re.sub(r'[^a-zA-Z\s]', '', clean_title)
        
        # Replace multiple spaces and trim
        clean_title = re.sub(r'\s+', ' ', clean_title).strip()
        
        # Convert to proper case
        clean_title = clean_title.title()
        
        words = clean_title.split()
        if len(words) < 12:
            additional_words = self.get_random_words(5)
            clean_title += ' ' + ' '.join(additional_words)
        
        words = clean_title.split()
        unique_words = list(dict.fromkeys(words))  # Preserve order
        limited_words = unique_words[:12]
        
        return ' '.join(limited_words)
    
    def get_random_words(self, count: int) -> List[str]:
        words = [
            'Sotwe', 'Bokep Dood', 'Twitter', 'Bokepsatset', 'Simontok', 'Video Viral',
            'Video Viral', 'Bokep Video', 'Simintok', 'Xpanas', 'Full Album', 'Doodstream',
            'Bokepsin', 'Simontok', 'Bokep', 'Asupan', 'Bokepsin', 'Bebasindo',
            'Pekoblive', 'Terabox', 'Streaming', 'Viral', 'Indo', 'Tiktok', 'Telegram',
            'Doods Pro', 'Telegram', 'Ful Album', 'Viral', 'Videos',
            'Poophd', 'Bochiel', 'Link Web', 'Folder',
            'Cilbo', 'Live', 'Tele', 'Terupdate', 'Links', 'Lokal', 'Dodstream',
            'Pemersatu', 'Update', 'Dood', 'Doostream', 'Website',
            'Downloader', 'Lulustream', 'Doodsflix', 'Yakwad', 'Doodflix',
            'Tobrut', 'Lagi Viral', 'Doodstreem', 'Jilbab',
            'Asupan Viral',
            'Pejuang Lendir', 'Popstream', 'Staklam', 'Bokepind', 'Video Bokep',
            'Bokep31', 'Video Indo', 'Video Colmek', 'Toketbagus',  
            'Video Sma', 'Doods Pro', 'Ngentot',  
            'Indonesia', 'Bokepin', 'Dood Tele', 'Cantik Tobrut', 'Memeksiana'
        ]
        random.shuffle(words)
        return words[:count]
    
    def parse_duration(self, duration) -> int:
        if isinstance(duration, (int, float)):
            return int(duration)
        
        if isinstance(duration, str):
            parts = list(reversed(duration.split(':')))
            seconds = 0
            for idx, val in enumerate(parts):
                seconds += int(val) * (60 ** idx)
            return seconds
        
        return 0
    
    def format_bytes(self, bytes_size: int, decimals: int = 2) -> str:
        if bytes_size == 0:
            return "0 Bytes"
        
        k = 1024
        sizes = ["Bytes", "KB", "MB", "GB", "TB"]
        i = int((len(str(bytes_size)) - 1) / 3)
        return f"{bytes_size / (k ** i):.{decimals}f} {sizes[i]}"
    
    def generate_long_seo_description(self, raw_title: str, title_tags: List[str]) -> str:
        clean_title = self.clean_and_format_title(raw_title)
        desc_words = list(dict.fromkeys(title_tags + self.get_random_words(5)))
        random.shuffle(desc_words)
        desc_words = desc_words[:12]
        return f"{' '.join(desc_words).capitalize()}. {clean_title} - Streaming dan download video terbaru."
    
    def normalize_data(self, api_data: Dict, api_source: str = 'doodstream', existing_videos: List[Dict] = None) -> List[Dict]:
        if existing_videos is None:
            existing_videos = []
        
        normalized_items = []
        
        if not api_data.get('result') or not isinstance(api_data['result'].get('files'), list):
            return normalized_items
        
        existing_videos_map = {}
        for video in existing_videos:
            filecode = video.get('filecode') or video.get('file_code')
            if filecode:
                existing_videos_map[filecode] = video
        
        for item in api_data['result']['files']:
            filecode = item.get('file_code') or item.get('filecode', '')
            raw_title = item.get('title') or item.get('file_title', '')
            clean_title = self.clean_and_format_title(raw_title)
            title_words = re.findall(r'[a-zA-Z0-9]+', clean_title)
            title_tags = list(dict.fromkeys(title_words))
            description = self.generate_long_seo_description(raw_title, title_tags)
            
            duration = self.parse_duration(item.get('length') or item.get('file_length', 0))
            
            if api_source == 'doodstream':
                normalized_item = {
                    'protected_embed': item.get('protected_embed', f'https://dodl.pages.dev/{filecode}'),
                    'size': self.format_bytes(item.get('size', self.convert_duration_to_size(duration))),
                    'length': time.strftime("%H:%M:%S", time.gmtime(duration)),
                    'protected_dl': item.get('download_url', f'https://doodstream.com/d/{filecode}'),
                    'views': item.get('views', 0),
                    'single_img': item.get('single_img') or item.get('splash_img', ''),
                    'title': clean_title,
                    'status': item.get('status', '200'),
                    'uploaded': item.get('uploaded', ''),
                    'last_view': item.get('last_view', ''),
                    'splash_img': item.get('splash_img') or item.get('single_img', ''),
                    'filecode': filecode,
                    'file_code': filecode,
                    'canplay': item.get('canplay', True),
                    'api_source': item.get('api_source', 'doodstream'),
                    'tag': title_tags,
                    'deskripsi': description
                }
            else:
                normalized_item = {
                    'protected_embed': item.get('protected_embed', f'https://luvluv.pages.dev/{filecode}'),
                    'size': self.format_bytes(item.get('file_size', self.convert_duration_to_size(duration))),
                    'length': time.strftime("%H:%M:%S", time.gmtime(duration)),
                    'protected_dl': item.get('download_url', f'https://lulustream.com/d/{filecode}'),
                    'views': item.get('views', item.get('file_views', 0)),
                    'single_img': item.get('player_img', f'https://img.lulucdn.com/{filecode}_t.jpg'),
                    'splash_img': item.get('player_img', f'https://img.lulucdn.com/{filecode}_xt.jpg'),
                    'title': clean_title,
                    'status': item.get('status', '200'),
                    'uploaded': item.get('uploaded', ''),
                    'last_view': item.get('last_view', ''),
                    'filecode': filecode,
                    'file_code': filecode,
                    'canplay': item.get('canplay', True),
                    'api_source': item.get('api_source', 'lulustream'),
                    'tag': title_tags,
                    'deskripsi': description
                }
            
            if filecode in existing_videos_map:
                normalized_items.append(self.update_existing_data(existing_videos_map[filecode], normalized_item))
            else:
                normalized_items.append(normalized_item)
        
        return normalized_items
    
    def convert_duration_to_size(self, duration: int, bitrate: int = 1) -> float:
        return duration * bitrate * 1024 * 1024
    
    def update_existing_data(self, existing_data: Dict, new_data: Dict) -> Dict:
        preserved_title = existing_data.get('title', '')
        updated_data = new_data.copy()
        updated_data['title'] = preserved_title
        
        if existing_data.get('deskripsi'):
            updated_data['deskripsi'] = existing_data['deskripsi']
        if existing_data.get('tag'):
            updated_data['tag'] = existing_data['tag']
        
        return updated_data

class AsyncAPIClient:
    def __init__(self):
        self.normalizer = DataNormalizer()
        self.user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
        ]
    
    def get_random_user_agent(self) -> str:
        return random.choice(self.user_agents)
    
    async def fetch_page(self, session: aiohttp.ClientSession, url: str, max_retries: int = 3) -> Optional[Dict]:
        for attempt in range(max_retries):
            try:
                headers = {'User-Agent': self.get_random_user_agent()}
                async with session.get(url, headers=headers, timeout=aiohttp.ClientTimeout(total=15)) as response:
                    if response.status == 200:
                        data = await response.json()
                        return data
                    else:
                        print(f"HTTP {response.status} for {url}")
            except Exception as e:
                print(f"Attempt {attempt + 1} failed for {url}: {str(e)}")
                if attempt < max_retries - 1:
                    await asyncio.sleep(2 ** attempt)  # Exponential backoff
        return None
    
    async def fetch_all_pages(self, config: Dict, api_source: str, existing_videos: List[Dict] = None) -> List[Dict]:
        if existing_videos is None:
            existing_videos = []
        
        all_data = []
        page = 1
        max_requests_per_second = 10
        request_interval_ms = 1000
        
        async with aiohttp.ClientSession() as session:
            while True:
                tasks = []
                for i in range(max_requests_per_second):
                    current_page = page + i
                    url = f"{config['api_url']}?key={config['api_key']}&per_page={config['per_page']}&page={current_page}"
                    tasks.append(self.fetch_page(session, url, config['max_retries']))
                
                results = await asyncio.gather(*tasks, return_exceptions=True)
                has_data_in_batch = False
                
                for result in results:
                    if isinstance(result, dict) and result.get('status') == 200:
                        normalized_data = self.normalizer.normalize_data(result, api_source, existing_videos)
                        if normalized_data:
                            all_data.extend(normalized_data)
                            has_data_in_batch = True
                
                if not has_data_in_batch:
                    break
                
                page += max_requests_per_second
                await asyncio.sleep(request_interval_ms / 1000)
        
        # Apply title preservation
        return self.preserve_existing_titles(all_data, existing_videos)
    
    def preserve_existing_titles(self, new_videos: List[Dict], existing_videos: List[Dict]) -> List[Dict]:
        existing_titles_map = {}
        
        for video in existing_videos:
            filecode = video.get('filecode') or video.get('file_code', '')
            title = video.get('title', '')
            if filecode and title:
                existing_titles_map[filecode] = title
        
        for video in new_videos:
            filecode = video.get('filecode') or video.get('file_code', '')
            if filecode in existing_titles_map:
                video['title'] = existing_titles_map[filecode]
                video['title_preserved'] = True
            else:
                video['title_preserved'] = False
        
        return new_videos

# --- File Operations ---
def save_paginated_data_files(videos: List[Dict]) -> Dict:
    output_dir = Path('output')
    output_dir.mkdir(exist_ok=True)
    
    # Remove old files
    for old_file in output_dir.glob('data_page_*.json'):
        old_file.unlink()
    
    chunks = [videos[i:i + DATA_PER_PAGE] for i in range(0, len(videos), DATA_PER_PAGE)]
    page_files = {}
    filecode_title_map = {}
    filecode_page_map = {}
    
    for page, chunk in enumerate(chunks):
        page_number = page + 1
        filename = f"data_page_{page_number}.json"
        filepath = output_dir / filename
        
        with open(filepath, 'w', encoding='utf-8') as f:
            json.dump(chunk, f, indent=2, ensure_ascii=False)
        
        page_files[page_number] = filename
        
        for video in chunk:
            filecode = video.get('filecode') or video.get('file_code', '')
            title = video.get('title', '')
            if filecode and title:
                filecode_title_map[filecode] = title
                filecode_page_map[filecode] = page_number
        
        print(f"Created page file: {filename} with {len(chunk)} videos")
    
    metadata = {
        'total_pages': len(chunks),
        'total_videos': len(videos),
        'videos_per_page': DATA_PER_PAGE,
        'pages': page_files,
        'filecode_title_map': filecode_title_map,
        'filecode_page_map': filecode_page_map,
        'last_updated': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    }
    
    with open(output_dir / 'pages_metadata.json', 'w', encoding='utf-8') as f:
        json.dump(metadata, f, indent=2, ensure_ascii=False)
    
    print(f"✅ Paginated data created: {len(chunks)} pages, {len(videos)} total videos")
    print(f"✅ Filecode-title map: {len(filecode_title_map)} mappings")
    print(f"✅ Filecode-page map: {len(filecode_page_map)} mappings")
    
    return page_files

def load_all_videos_from_paginated_files() -> List[Dict]:
    output_dir = Path('output')
    metadata_file = output_dir / 'pages_metadata.json'
    
    if not metadata_file.exists():
        return []
    
    with open(metadata_file, 'r', encoding='utf-8') as f:
        metadata = json.load(f)
    
    total_pages = metadata.get('total_pages', 0)
    if total_pages == 0:
        return []
    
    all_videos = []
    for page in range(1, total_pages + 1):
        page_file = output_dir / f"data_page_{page}.json"
        if page_file.exists():
            with open(page_file, 'r', encoding='utf-8') as f:
                videos = json.load(f)
                all_videos.extend(videos)
    
    return all_videos

def parse_uploaded_timestamp(uploaded: str) -> int:
    if not uploaded:
        return 0
    
    if isinstance(uploaded, (int, float)):
        return int(uploaded)
    
    try:
        # Try to parse various date formats
        dt = datetime.fromisoformat(uploaded.replace('Z', '+00:00'))
        return int(dt.timestamp())
    except:
        try:
            dt = datetime.strptime(uploaded, '%Y-%m-%d %H:%M:%S')
            return int(dt.timestamp())
        except:
            return 0

def merge_video_data(new_videos: List[Dict], existing_videos: List[Dict]) -> List[Dict]:
    merged_videos = []
    existing_map = {}
    
    for video in existing_videos:
        filecode = video.get('filecode') or video.get('file_code', '')
        if filecode:
            existing_map[filecode] = video
    
    for new_video in new_videos:
        filecode = new_video.get('filecode') or new_video.get('file_code', '')
        
        if filecode and filecode in existing_map:
            existing_video = existing_map[filecode]
            merged_video = new_video.copy()
            
            # Preserve important fields from existing data
            merged_video['title'] = existing_video.get('title', new_video['title'])
            merged_video['deskripsi'] = existing_video.get('deskripsi', new_video.get('deskripsi'))
            merged_video['tag'] = existing_video.get('tag', new_video.get('tag'))
            merged_video['custom_fields'] = existing_video.get('custom_fields', new_video.get('custom_fields', {}))
            merged_video['title_preserved'] = True
            
            merged_videos.append(merged_video)
            del existing_map[filecode]
        else:
            new_video['title_preserved'] = False
            merged_videos.append(new_video)
    
    # Add remaining existing videos
    for existing_video in existing_map.values():
        existing_video['title_preserved'] = True
        existing_video['data_preserved'] = True
        merged_videos.append(existing_video)
    
    return merged_videos

# --- Main Async Function ---
async def main_async_fetch():
    start_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    print(f"=== ASYNC FETCH STARTED at {start_time} ===")
    
    try:
        # Load existing data
        existing_videos = load_all_videos_from_paginated_files()
        print(f"Loaded {len(existing_videos)} existing videos for title preservation")
        
        all_videos = []
        client = AsyncAPIClient()
        
        # Fetch from all sources
        for source_name, config in CONFIGS.items():
            print(f"Fetching data from {source_name}...")
            source_videos = await client.fetch_all_pages(config, source_name, existing_videos)
            all_videos.extend(source_videos)
            print(f"Fetched {len(source_videos)} videos from {source_name}")
        
        # Merge data
        merged_videos = merge_video_data(all_videos, existing_videos)
        
        # Calculate preservation stats
        preserved_count = sum(1 for v in merged_videos if v.get('title_preserved', False))
        new_count = len(merged_videos) - preserved_count
        print(f"Merge result: {preserved_count} preserved titles, {new_count} new titles")
        
        # Remove duplicates
        unique_videos = []
        seen_filecodes = set()
        for video in merged_videos:
            filecode = video.get('filecode') or video.get('file_code', '')
            if filecode and filecode not in seen_filecodes:
                unique_videos.append(video)
                seen_filecodes.add(filecode)
        
        print(f"Total unique videos: {len(unique_videos)}")
        
        # Sort by uploaded date (newest first)
        unique_videos.sort(key=lambda x: parse_uploaded_timestamp(x.get('uploaded', '')), reverse=True)
        print("✅ Videos sorted by uploaded date (newest first)")
        
        # Save paginated data
        page_files = save_paginated_data_files(unique_videos)
        
        end_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        print(f"=== ASYNC FETCH COMPLETED at {end_time} ===")
        print(f"Total videos: {len(unique_videos)}")
        print(f"Title preservation: {preserved_count} preserved, {new_count} new")
        
        return {
            'status': 'completed',
            'started_at': start_time,
            'completed_at': end_time,
            'total_videos': len(unique_videos),
            'preserved_titles': preserved_count,
            'new_videos': new_count,
            'pages_created': len(page_files)
        }
        
    except Exception as e:
        error_time = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        print(f"=== ASYNC FETCH ERROR at {error_time} ===")
        print(f"Error: {str(e)}")
        
        return {
            'status': 'error',
            'started_at': start_time,
            'completed_at': error_time,
            'error': str(e)
        }

if __name__ == "__main__":
    result = asyncio.run(main_async_fetch())
    print("Fetch result:", json.dumps(result, indent=2))