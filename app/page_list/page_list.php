<?php

require __DIR__.'/../../config.php';
$pageListWidget = new Widget('page_list', $db);

if (!in_array($argv[1], $pageListWidget->getSetting('known_langs'))) {
	echo "Error: Language not exists\n";
	return;

}
$lang = $argv[1];
$prefix = getDbPrefix($lang);
$t = time();
ini_set('memory_limit', '1800M');
function dumping($langDb, $sql, $path, $key, $keysOutput, $isSingleLoop = false)
{
	global $t;
	$i = 0;
	$rowI = 0;
	$offset = 0;
	$output = '';
	$fp = fopen($path, 'w');
	while (true) {
		$rowI = 0;
		$data = $langDb->query($isSingleLoop ? $sql : sprintf($sql, $langDb->quote($offset)));
		while ($row = $data->fetch()) {
			$rowI++;
			$offset = $row[$key];
			
			$output .= implode('|', array_intersect_key($row, array_flip($keysOutput)))."\n";
			$i++;
			if ($i % 100000 == 0) {
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
//$sql = 'SELECT page_id, page_len, page_title FROM page WHERE page_namespace = 0 AND page_is_redirect = 0';
//dumping($langDb, $sql, $pathPages, 'page_id', ['page_id', 'page_len', 'page_title'], true);
echo "step1 ".(time() - $t)."\n";

$pathRedirects = __DIR__."/tmp/{$lang}_all_redirect.log";
$sql = 'SELECT page_id, page_title FROM page WHERE page_namespace = 0 AND page_is_redirect = 1 AND page_id > %s ORDER BY page_id LIMIT 10000';
dumping($langDb, $sql, $pathRedirects, 'page_id', ['page_id', 'page_title']);
//$sql = 'SELECT page_id, page_title FROM page WHERE page_namespace = 0 AND page_is_redirect = 1';
//dumping($langDb, $sql, $pathRedirects, 'page_id', ['page_id', 'page_title'], true);
echo "step1_5 ".(time() - $t)."\n";
$pathRedirectsRel = __DIR__."/tmp/{$lang}_all_redirect_rel_id.log";
/*$sql = 'SELECT redirect.rd_from, page.page_title, redirect.rd_title FROM page JOIN redirect ON page.page_id = redirect.rd_from WHERE page.page_namespace = 0 AND page.page_is_redirect != 0 AND page.page_id > %s ORDER BY page.page_id LIMIT 500';
dumping($langDb, $sql, $pathRedirectsRel, 'rd_from', ['rd_from', 'page_title', 'rd_title']);*/
$sql = 'SELECT redirect.rd_from, page.page_id FROM page JOIN redirect ON page.page_namespace = 0 and redirect.rd_namespace = 0 and page.page_title = redirect.rd_title';
dumping($langDb, $sql, $pathRedirectsRel, 'page_id', ['page_id', 'rd_from'], true);
echo "step2 ".(time() - $t)."\n";
//exit;
/*$db = getLocalDb();
$pages = [];
$fh = fopen($pathPages, 'r');
while (($line = fgets($fh)) !== false) {
	$line = explode('|', $line, 3);
	$pages[$line[2]] = $line[0];
}
fclose($fh);
echo "step3 ".(time() - $t)."\n";
$pathRedirectsFinal = __DIR__."/tmp/{$lang}_all_redirectpage2.log";

$fh = fopen($pathRedirects, 'r');
$fRedirectH = fopen($pathRedirectsFinal, 'w');
while (($line = fgets($fh)) !== false) {
	$line = explode('|', $line, 3);

	if (isset($pages[$line[2]])) {
		fwrite($fRedirectH, $pages[$line[2]].'|'.$line[1]."\n");
	}
}
fclose($fh);
fclose($fRedirectH);*/

//echo "step4 ".(time() - $t)."\n";
// jsub -N page_list_en -once -mem 2000m -l release=trusty php app/page_list/page_list.php en
/*
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
dumping($db, $sql, $pathRedirects, 'redirect_id', ['pageid', 'redirect_title']);*/

rename($pathPages, ROOT_DIR."/public_html/files/page_list/{$lang}_".date("Ym")."_all_pages.log");
rename($pathRedirects, ROOT_DIR."/public_html/files/page_list/{$lang}_".date("Ym")."_all_redirects.log");
rename($pathRedirectsRel, ROOT_DIR."/public_html/files/page_list/{$lang}_".date("Ym")."_all_redirects_rel.log");

$db = getLocalDb();
$db->query("DELETE FROM page_list_queue WHERE lang = {$db->quote($lang)}");
echo "step5 ".(time() - $t)."\n";

echo date('Y-m-d H:i').' FINISH '.$lang."\n";