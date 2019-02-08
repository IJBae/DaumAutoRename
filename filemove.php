<?php
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
set_time_limit(0);

class FileMove {
	public function __construct($source_dir, $target_dir) {
		$this->source_dir = $source_dir;
		$this->target_dir = $target_dir;
		$this->init();
	}
	private function init() {
		$this->source_lists = $this->read_all_files($this->source_dir);
		$this->target_lists = $this->read_all_files($this->target_dir);
		ob_start(); 
		ob_flush(); 
		flush(); 
		//echo '<pre>'.print_r($this->source_lists,1).'</pre>'; exit;
		foreach($this->source_lists['files'] as $file) {
			$filename = str_replace($this->source_dir.'/', '', $file);
			//echo $file.'<br>'; exit;
			$file_str = explode('.', preg_replace('/m\.net/i', 'Mnet', $filename)); //Mnet 이 .으로 split 되는 문제 수정
			$file_str = trim(preg_replace('/[0-9]+부$|^\[.+\]|[0-9]{1,2}\-[0-9]{1,2}회|합본|[0-9]+회\s특집|[가-힣\s]+특집|미니시리즈|UHD|신년기획|연말결선|2018|2019|신년|특별기획드라마|특별기획|특별판|여름특선|송년기획|추석 기획|추석맞이|스페셜 비하인드|스페셜 넘버|^MBN|수목 드라마/', '', $file_str[0])); //검색되지 않는 문구 제거
			$file_str = trim(preg_replace('/\-/', ' ', $file_str));
			$result = $this->getDaumInfo($file_str);
			$html = $result['result'];
			$html = explode("<div disp-attr='TVP'>", $html)[1];
			preg_match('/(?<=\<span class\="txt_info"\>)([^<]+)(?=\<\/span\>)/', $html, $matches);
			$data['type'] = trim($matches[0]);
			preg_match('/(<a.+class\="link_tit"[^>]+\>)([^<]+)(?=\<\/a\>)/', $html, $matches);
			$data['title'] = trim($matches[2]);
			//echo '<pre>'.print_r($html,1).'</pre>'; exit;
			/*
			$html = explode("<div disp-attr='TWA'>", $html)[0];
			$info = explode('<script type="text/javascript">', $html)[1];
			$info = explode('</script>', $info)[0];
			preg_match('/C\.TVP\.data = (\{.+\});/', $info, $matches);
			$json = trim(preg_replace('/\'/', '"', $matches[1]));
			$info = json_decode($json, true);
			$data['title'] = $info['kakaoMessage']['tvProgram']['title'];
			*/
			//echo '<pre>'.print_r($matches[2],1).'</pre>'; exit;
			
			if($data['title'] && $data['type']) {
				$target = $this->target_dir.'/'.$data['type'].'/'.$data['title'];
				if(!is_dir($this->target_dir.'/'.$data['type'])) mkdir($this->target_dir.'/'.$data['type']);
				if(!is_dir($target)) mkdir($target);
				rename($file, $target.'/'.$filename);
				echo $file. ' >> '. $target.'/'.$filename.' 이동 완료<br>'.PHP_EOL;
			} else {
				$target = $this->target_dir.'/특집프로그램';
				rename($file, $target.'/'.$filename);
				echo $file_str.'_'.$file.' 특집프로그램으로 이동<br>'.PHP_EOL;
			}
			//echo '<pre>'.print_r($json,1).'</pre>'; exit;
			ob_flush(); flush(); ob_clean(); usleep(1);
		}
		ob_end_clean(); 
	}

	private function getDaumInfo($stxt) {
		$url = 'https://m.search.daum.net/search';
		$data = array('nil_profile'=>'simpleurl', 'w'=>'tot', 'q'=>$stxt);
		$result = $this->curl($url, $data);
		
		return $result;
	}

	private function read_all_files($root = '.'){
		$files  = array('files'=>array(), 'dirs'=>array());
		$directories  = array();
		$last_letter  = $root[strlen($root)-1];
		$root  = ($last_letter == '\\' || $last_letter == '/') ? $root : $root.DIRECTORY_SEPARATOR;

		$directories[]  = $root;

		while (sizeof($directories)) {
			$dir  = array_pop($directories);
			if ($handle = opendir($dir)) {
				while (false !== ($file = readdir($handle))) {
					if ($file == '.' || $file == '..' || $file == '@eaDir' || $file == 'Plex Versions' || preg_match('/season|special|s[0-9]{2}|subs/i', $file)) {
						continue;
					}
					$file  = $dir.$file;
					if (is_dir($file)) {
						$directory_path = $file.DIRECTORY_SEPARATOR;
						array_push($directories, $directory_path);
						$files['dirs'][]  = $directory_path;
					} elseif (is_file($file)) {
						$files['files'][]  = $file;
					}
				}
				closedir($handle);
			}
		}
		return $files;
	}

	private function curl($url, $data) {
		$headers = array('Content-type: application/json');
		$ch = curl_init();
		$data = http_build_query($data, '', '&');
		$url .= '?'.$data;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, false);
		$result['result'] = curl_exec($ch);
		$result['info'] = curl_getinfo($ch);
		$result['error'] = curl_error($ch);
		curl_close($ch);

		return $result;
	}
}
$filemove = new FileMove('/volume1/gdrive/media', '/volume1/gdrive/video/TV/국내'); //(원본 경로, 이동할 경로)
//$filemove = new FileMove('/volume1/gdrive/video/TV/국내/특집프로그램', '/volume1/gdrive/video/TV/국내');
?>