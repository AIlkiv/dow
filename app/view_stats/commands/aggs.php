<?php
require __DIR__.'/../../../config.php';

$r = new Aggs($db, $settings);
$r->start();

class Aggs
{
    protected $db;
    protected $settings;
    protected $limit = 100;

    public function __construct($db, $settings)
    {
    	$this->db = $db;
        $this->settings = $settings;
    }

    public function start()
    {
        $dirName = ROOT_DIR."/app/view_stats/tmp/";
    	$count = $this->db->query('SELECT COUNT(*) total, SUM(IF(status="split", 1, 0)) split FROM archives_log')->fetch();
var_dump($count);
		if ($count['split'] < $this->limit && $count['split'] != $count['total']) {
			return;
		}
        echo date('Y-m-d H:i:s').' AGG: START'."\n";
		$dateForDownload = $this->db->query('SELECT date FROM archives_log WHERE status IN ("split") ORDER BY date')->fetchAll();
        foreach ($this->settings['known_langs'] as $lang) {
            try {
                echo date('Y-m-d H:i:s').' AGG: start '.$lang."\n";
                $listAggFiles = [];
                foreach ($dateForDownload as $row) {
                    $time = strtotime($row['date']);
                    $name = date('YmdH', $time);

                    $fileName = $dirName.'splits/'.$name.'.'.$lang;
                    if (file_exists($fileName)) {
                        $listAggFiles[] = $fileName;
                    }
                }

                if (!empty($listAggFiles)) {
                    file_put_contents($dirName.$lang.'.list', implode("\n", $listAggFiles));

                    $result = exec(ROOT_DIR.'/app/view_stats/agg_stats '.$dirName.'voc/'.$lang.'.txt '.$dirName.$lang.'.list '.$dirName.'voc/_'.$lang.'.txt');

                    if ($result !== 'SUCCESS') {
                        throw new \Exception('cpp error');
                    }
                    
                    if (rename($dirName.'voc/_'.$lang.'.txt', $dirName.'voc/'.$lang.'.txt') === false) {
                        throw new \Exception('rename error');
                    }
                    foreach ($dateForDownload as $row) {
                        $time = strtotime($row['date']);
                        $name = date('YmdH', $time);

                        $fileName = $dirName.'splits/'.$name.'.'.$lang;
                        if (file_exists($fileName)) {
                            unlink($fileName);
                        }
                    }
                }
    			echo date('Y-m-d H:i:s').' AGG: success '.$lang."\n";
    		}
            catch (\Exception $e) {
    			echo date('Y-m-d H:i:s').' AGG: don`t download '.$lang." {$e->getMessage()} "."\n";
                return false;
            }
		}

        foreach ($dateForDownload as $row) {
            $time = strtotime($row['date']);
            $name = date('YmdH', $time);
            $removeFiles = 'rm -rf '.$dirName.'splits/'.$name.'.*';
            `$removeFiles`;
        }

        $db = getLocalDb();
        $dateForDownload = array_map([$db, 'quote'], array_column($dateForDownload, 'date'));
        //var_dump($dateForDownload);
        $db->query('DELETE FROM archives_log WHERE `date` IN ('.implode(', ', $dateForDownload).')');
        echo date('Y-m-d H:i:s').' AGG: SUCCESS'."\n";
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