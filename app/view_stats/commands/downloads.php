<?php
require __DIR__.'/../../../config.php';
echo "start\n";
#echo `wget https://dumps.wikimedia.org/other/pageviews/2016/2016-09/pageviews-20160901-000000.gz`;
$r = new DownloaderArchives($db);
$r->start();

class DownloaderArchives
{
    protected $db;
    protected $limit = 110;// 100

    protected $urlWikiArchives = 'https://dumps.wikimedia.org/other/pageviews/';

    public function __construct($db)
    {
    	$this->db = $db;
    }

    public function start()
    {
        $dirName = ROOT_DIR."/app/view_stats/tmp/archives/";
    	$count = $this->db->query('SELECT COUNT(*) FROM archives_log WHERE status NOT IN ("new")')->fetchColumn();
		if ($count >= $this->limit) {
			return;
		}

        $countDownloads = $this->limit - $count;
		$dateForDownload = $this->db->query('SELECT date FROM archives_log WHERE status IN ("new") ORDER BY date LIMIT '.$countDownloads)->fetchAll();
		foreach ($dateForDownload as $row) {
			echo date('Y-m-d H:i:s').' DOWNLOADER: start '.$row['date']."\n";
			try {
				$time = strtotime($row['date']);
				$name = date('YmdH', $time);
				$url = $this->urlWikiArchives.date('Y', $time).'/'.date('Y-m', $time).'/pageviews-'.date('Ymd', $time).'-'.date('H', $time).'0000.gz';

				if ($this->saveUrlDataToFile($url, $dirName.$name.'.gz') === false) {
					throw new \Exception('diff size');
				}
				$this->db->query("UPDATE archives_log SET status = 'download' WHERE `date` = '{$row['date']}'");

				echo date('Y-m-d H:i:s').' DOWNLOADER: download success '.$row['date']."\n";
			} catch (\Exception $e) {
				echo date('Y-m-d H:i:s').' DOWNLOADER: don`t download '.$row['date']." {$e->getMessage()} "."\n";
			}

		}
    }

    private function fileSizeByUrl($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);

        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        curl_close($ch);
        return $size;
    }

    private function saveUrlDataToFile($url, $filePath)
    {
    	$t = file_get_contents($url);
    	if ($t === false) {
    		return false;
    	}

    	file_put_contents($filePath, $t);
    	return true;
/*
        $fp = fopen($filePath, 'w+');
        
        set_time_limit(0); // unlimited max execution time
        $options = array(
          CURLOPT_FILE    => $fp,
          CURLOPT_TIMEOUT =>  28800, // set this to 8 hours so we dont timeout on big files
          CURLOPT_URL     => $url,
          CURLOPT_DNS_USE_GLOBAL_CACHE => false,
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_exec($ch);

        if(curl_errno($ch)){
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        fclose($fp);*/
    }
}