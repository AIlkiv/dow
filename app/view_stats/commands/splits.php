<?php
require __DIR__.'/../../../config.php';
echo "start\n";

$r = new Splits($db, $settings);
$r->start();

class Splits
{
    protected $db;
    protected $settings;
    protected $limit = 20;

    protected $buffer = '';
    protected $bufferLimit = 10485;

    private function _fwrite($h, $s)
    {
        $this->buffer .= $s;
        if (strlen($this->buffer) > $this->bufferLimit) {
            fwrite($h, $this->buffer);
            $this->buffer = '';
        }
    }

    private function _fclose($h)
    {
        fwrite($h, $this->buffer);
        $this->buffer = '';
        fclose($h);
    }

    public function __construct($db, $settings)
    {
    	$this->db = $db;
        $this->settings = $settings;
    }

    public function start()
    {
        $dirName = ROOT_DIR."/app/view_stats/tmp/";

        $dateForDownload = $this->db->query('SELECT date FROM archives_log WHERE status IN ("unpack") ORDER BY date LIMIT '.($this->limit))->fetchAll();

        foreach ($dateForDownload as $row) {
            echo date('Y-m-d H:i:s').' SPLITS: start '.$row['date']."\n";
            try {
                $time = strtotime($row['date']);
                $name = date('YmdH', $time);
                
                if (file_exists($dirName.'unpacks/'.$name.'.log')) {
                    $removeFiles = 'rm -rf '.$dirName.'splits/'.$name.'.*';
                    `$removeFiles`;

                    $result = exec(ROOT_DIR.'/app/view_stats/sp '.$dirName.'unpacks/'.$name.'.log '.$dirName.'splits/'.$name.'.');

                    if ($result !== 'SUCCESS') {
                        throw new \Exception('cpp error');
                    }

/*
                $currentLang = '';
                $currentOutput = null;
                while (($s = fgets($inputH)) !== false) {
                    $line = explode(' ', $s);
                    if (count($line) < 3) {
                        continue;
                    }
                    
                    $langs = explode('.', $line[0], 2);
                    if ($langs[0] != $currentLang) {
                        $currentLang = $langs[0];

                        if (!is_null($currentOutput)) {
                            $this->_fclose($currentOutput);
                        }
                        if (in_array($currentLang, $this->settings['known_langs'])) {
                            $currentOutput = fopen($dirName.'splits/'.$name.'.'.$currentLang, 'a');
                        }
                        else {
                            $currentOutput = null;
                            continue;
                        }
                    }

                    if (!is_null($currentOutput)) {
                       $this->_fwrite($currentOutput, $line[1].' '.$line[2]."\n");
                    }
                }
                if (!is_null($currentOutput)) {
                    $this->_fclose($currentOutput);
                }
                fclose($inputH);
*/
                    unlink($dirName.'unpacks/'.$name.'.log');
                }

				$this->db->query("UPDATE archives_log SET status = 'split' WHERE `date` = '{$row['date']}'");

				echo date('Y-m-d H:i:s').' SPLITS: success '.$row['date']."\n";
			} catch (\Exception $e) {
				echo date('Y-m-d H:i:s').' SPLITS: don`t splits '.$row['date']." {$e->getMessage()} "."\n";
			}

		}
    }

    private function getLang($row, $currentLang)
    {
        $langs = explode('.', $row[0], 2);
        if ($langs[0] == $currentLang) {
            return $currentLang;
        }
        elseif (in_array($langs[0], $this->settings['known_langs'])) {
            return $langs[0];
        }
        return $langs[0];
    }

    private function readf($h)
    {
        $row = '';
        while (true) {
            $row = fgets($h);
            if ($row === false) {
                return $row;
            }

            if (count(explode(' ', $row)) < 3) {
                continue;
            }

            return $row;
        }
    }


}