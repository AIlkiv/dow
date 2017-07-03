<?php
require __DIR__.'/../../../config.php';
echo "start\n";

$r = new Unpack($db);
$r->start();

class Unpack
{
    protected $db;
    protected $limit = 100;

    public function __construct($db)
    {
    	$this->db = $db;
    }

    public function start()
    {
        $dirName = ROOT_DIR."/app/view_stats/tmp/";

		$dateForDownload = $this->db->query('SELECT date FROM archives_log WHERE status IN ("download") ORDER BY date LIMIT '.($this->limit))->fetchAll();
		foreach ($dateForDownload as $row) {
			echo date('Y-m-d H:i:s').' UNPACKS: start '.$row['date']."\n";
			try {
				$time = strtotime($row['date']);
				$name = date('YmdH', $time);
                exec('gzip -d '.$dirName.'archives/'.$name.'.gz');
                /*if ($this->uncompress($dirName.'archives/'.$name.'.gz', $dirName.'unpacks/'.$name.'.log') === false) {
                    throw new \Exception('error');
                }*/

                #unlink($dirName.'archives/'.$name.'.gz');
                if (file_exists($dirName.'archives/'.$name.'.gz')) {
                    throw new \Exception('error');
                }

                if (!rename($dirName.'archives/'.$name, $dirName.'unpacks/'.$name.'.log')) {
                    throw new \Exception('error');
                }

				$this->db->query("UPDATE archives_log SET status = 'unpack' WHERE `date` = '{$row['date']}'");

				echo date('Y-m-d H:i:s').' UNPACKS: unpack success '.$row['date']."\n";
			} catch (\Exception $e) {
				echo date('Y-m-d H:i:s').' UNPACKS: don`t unpack '.$row['date']." {$e->getMessage()} "."\n";
			}
		}
    }

    private function uncompress($srcName, $dstName)
    {
        $sfp = gzopen($srcName, "rb");
        $fp = fopen($dstName, "w");
        if ($sfp === false || $fp === false) {
            return false;
        }

        while ($string = gzread($sfp, 1024 * 1024)) {
            if (fwrite($fp, $string, strlen($string)) === false) {
                return false;
            }
        }

        gzclose($sfp);
        fclose($fp);

        return true;
    }

}