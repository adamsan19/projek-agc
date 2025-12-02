<?php
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}
function kw_folder(){
	if (file_exists( $_SERVER['DOCUMENT_ROOT'] . '/store/' . $_SERVER['HTTP_HOST'] . '/keywords' )) {
	    return $_SERVER['DOCUMENT_ROOT'] . '/store/' . $_SERVER['HTTP_HOST'] . '/keywords';
	}else{
		return $_SERVER['DOCUMENT_ROOT'] . '/store/default/keywords';
    }
}

if (!function_exists('normalize_keyword_phrase')) {
	function normalize_keyword_phrase($string) {
		// Trim, collapse whitespace, and drop duplicate words while preserving order
		$string = preg_replace('/\s+/u', ' ', trim($string));
		if ($string === '') {
			return '';
		}

		$words = preg_split('/\s+/u', $string);
		$unique_map = [];
		$normalized_words = [];

		foreach ($words as $word) {
			$key = mb_strtolower($word, 'UTF-8');
			if (!isset($unique_map[$key])) {
				$unique_map[$key] = true;
				$normalized_words[] = $word;
			}
		}

		return implode(' ', $normalized_words);
	}
}

function save_to_txt($string, $file='1.txt', $delim="\n" ){
	$string = normalize_keyword_phrase($string);
	if ($string === '') {
		return false;
	}
	$file = kw_folder().'/'.$file; 
	$myfile = fopen($file, "a") or die("Unable to open file!");
	if (! cek_duplicate($string, $file, $delim)) {
		$save = file_put_contents($file, $string.$delim, FILE_APPEND | LOCK_EX);
		return true;
	}
	fclose($myfile); 
}
function cek_duplicate($string, $file, $delimiter = "\n"){
	$string = normalize_keyword_phrase($string);
	$file 	= file_get_contents($file);
	$exp 	= explode($delimiter, $file);
	foreach ($exp as $value) {
		if ($string === normalize_keyword_phrase($value)) {
			return true;
			exit;
		}
	}
	return false;
}
   
if (isset($_POST['injek_kw'])) {
		$term = $_POST['injek_kw'];		
		$terms= explode("\r\n", $term);
		$items = array_filter($terms);
		foreach ($items as $term) {
			$title        = trim($term);
			save_to_txt($title);
		}
		echo '<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
<b>Keywords</b> successfully added
</div>
</div>';
}
else{
}
?>