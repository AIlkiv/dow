<?php

class InitProcessArchives
{
    private $db;
    private $settings;

    private $curDate = null;
    private $hFileViews = null;

    public function __construct($db, $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
        $this->curDate = date('Y-m-01');
        //$this->curDate = '2016-11-01';
    }

    public function execute()
    {
        $_month = date('m', strtotime('-1 month', strtotime($this->curDate)));
        $_d = date('Y-m-01 00:00', strtotime('-1 month', strtotime($this->curDate)));
        while (date("m", strtotime($_d)) == $_month) {
            $this->db->query("INSERT IGNORE INTO archives_log (`date`, status) VALUE ('$_d','new')");
            
            $_d = date('Y-m-d H:i', strtotime($_d) + 60 * 60);
        }

        foreach ($this->settings['known_langs'] as $lang) {
            $filePathViews = ROOT_DIR.'/app/view_stats/tmp/voc/'.$lang.'.txt';
            $this->hFileViews = fopen($filePathViews, 'w');

            $ym = date('Ym', strtotime($this->curDate));
            echo "Process copy main pages\n";
            $filePath = ROOT_DIR.'/public_html/files/page_list/'.$lang.'_'.$ym.'_all_pages.log';
            $this->addPages($filePath, 2);
            
            echo "Process copy redirect pages\n";
            $filePath = ROOT_DIR.'/public_html/files/page_list/'.$lang.'_'.$ym.'_all_redirects.log';
            $this->addPages($filePath, 1);

            fclose($this->hFileViews);

            echo "Success\n";
        }

        $this->db->query('UPDATE settings SET value = "process_archives" WHERE name = "view_stats_refresh_status"');
        return;
    }

    private function addPages($filePathRead, $columnPos)
    {
        $hFile = fopen($filePathRead, 'r');

        if ($hFile === false) {
            throw new \Exception("Not open file: $filePathRead");
        }
// Add exception when unknown file
        while(!feof($hFile)) {
            $row = fgets($hFile, 4096);

            $data = explode('|', rtrim($row), $columnPos + 1);

            if (!empty($data[$columnPos])) {
                fwrite($this->hFileViews, "0 {$data[$columnPos]}\n");
            }
        }

        fclose($hFile);
    }
}
