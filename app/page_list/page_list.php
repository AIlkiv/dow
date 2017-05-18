<?php

require __DIR__.'/../../config.php';
$pageListWidget = new Widget('page_list', $db);

if (!in_array($argv[1], $pageListWidget->getSetting('known_langs'))) {
	echo "Error: Language already exists\n";
	return;

}
$lang = $argv[1];
$prefix = getDbPrefix($lang);
$t = time();

function dumping($langDb, $sql, $path, $key, $keysOutput, $isSingleLoop = false)
{
	$i = 0;
	$rowI = 0;
	$offset = 0;
	$output = '';
	$fp = fopen($path, 'w');
	while (true) {
		$rowI = 0;
		$data = $langDb->query($isSingleLoop ? $sql : sprintf($sql, $offset));
		while ($row = $data->fetch()) {
			$rowI++;
			$offset = $row[$key];
			
			$output .= implode('|', array_intersect_key($row, array_flip($keysOutput)))."\n";
			$i++;
			if ($i % 10000 == 0) {
				fwrite($fp, $output);
				$output = '';
			}
			if ($i % 1000000 == 0) {
				echo "-- ".(time() - $GLOBALS['t'])."\n";
			}
		}
		if (empty($rowI) || $isSingleLoop) {
			break;
		}
	}
	if (!empty($output)) {
		fwrite($fp, $output);
	}
	fclose($fp);
}
echo date('Y-m-d H:i').' START '.$lang."\n";

$key = rand().'_'.time();
$langDb = getLangWikiDb($lang);
$pathPages = __DIR__."/tmp/{$lang}_all_pages.log";
$sql = 'SELECT page_id, page_len, page_title FROM page WHERE page_namespace = 0 AND page_is_redirect = 0 AND page_id > %s ORDER BY page_id LIMIT 10000';
dumping($langDb, $sql, $pathPages, 'page_id', ['page_id', 'page_len', 'page_title']);
echo "step1 ".(time() - $t)."\n";

$pathRedirects = __DIR__."/tmp/{$lang}_all_redirectpage.log";
$sql = 'SELECT redirect.rd_from, page.page_title, redirect.rd_title FROM page JOIN redirect ON page.page_id = redirect.rd_from WHERE redirect.rd_namespace = 0 AND redirect.rd_from > %s ORDER BY redirect.rd_from LIMIT 500';
dumping($langDb, $sql, $pathRedirects, 'rd_from', ['rd_from', 'page_title', 'rd_title']);
echo "step2 ".(time() - $t)."\n";

$db = getLocalDb();
$db->exec("CREATE TEMPORARY TABLE IF NOT EXISTS {$key}pages (
	pageid int NOT NULL,
	title varchar(255) NOT NULL,
	INDEX (title)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8");
$db->exec("LOAD DATA LOCAL INFILE '{$pathPages}'
            REPLACE INTO TABLE {$key}pages FIELDS TERMINATED BY '|' ENCLOSED BY '' ESCAPED BY '' LINES TERMINATED BY '\\n'
            (pageid, @dummy, title)");
echo "step3 ".(time() - $t)."\n";

$db->exec("CREATE TEMPORARY TABLE IF NOT EXISTS {$key}redirects (
	redirect_id int NOT NULL,
	redirect_title varchar(255) NOT NULL,
	main_title varchar(255) NOT NULL,
	PRIMARY KEY (redirect_id)	
	) ENGINE=MyISAM DEFAULT CHARSET=utf8");
$db->exec("LOAD DATA LOCAL INFILE '{$pathRedirects}'
            REPLACE INTO TABLE {$key}redirects FIELDS TERMINATED BY '|' ENCLOSED BY '' ESCAPED BY '' LINES TERMINATED BY '\\n'
            (redirect_id, redirect_title, main_title)");
echo "step4 ".(time() - $t)."\n";

$sql = "SELECT redirects.redirect_id, pages.pageid, redirects.redirect_title 
  FROM {$key}redirects redirects
  JOIN {$key}pages pages ON pages.title = redirects.main_title
  WHERE redirects.redirect_id > %s
  ORDER BY redirects.redirect_id
  LIMIT 500";
dumping($db, $sql, $pathRedirects, 'redirect_id', ['pageid', 'redirect_title']);
echo "step5 ".(time() - $t)."\n";

rename($pathPages, ROOT_DIR."/public_html/files/page_list/{$lang}_".date("Ym")."_all_pages.log");
rename($pathRedirects, ROOT_DIR."/public_html/files/page_list/{$lang}_".date("Ym")."_all_redirects.log");

$db->query("DELETE FROM page_list_queue WHERE lang = {$db->quote($lang)}");

echo date('Y-m-d H:i').' FINISH '.$lang."\n";