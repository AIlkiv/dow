<?php
require_once __DIR__.'/../../config.php';
$pageListWidget = new Widget('page_list', $db);

$curDate = $pageListWidget->getSetting('cur_date');

if (!empty($argv[1])) {
	if (!in_array($argv[1], $knownLangs)) {
		echo "Error: Unknown language\n";
		return;
	}
	$langs = [$argv[1]];
}
else {
	$langs = $knownLangs;
}

if (date('d') < 15) {
	$time = [
		'from' => date('Ym15000000', strtotime('-1 month')),
		'to' => date('Ymt235959', strtotime('-1 month')),
	];
}
else {
	$time = [
		'from' => date('Ym01000000'),
		'to' => date('Ym14235959'),
	];
}

$timeRuns = time();
foreach ($langs as $lang) {
	$prefix = getDbPrefix($lang).'_';
	$langDb = getLangWikiDb($lang);

	$sql = "SELECT rc_cur_id page_id, count(*) count, MAX(rc_this_oldid) last_rev
		FROM recentchanges
		WHERE rc_namespace = 0
		AND rc_timestamp BETWEEN {$db->quote($time['from'])} AND {$db->quote($time['to'])}
		AND rc_type IN (".implode(", ", [RC_EDIT, RC_NEW]).")
		GROUP BY rc_cur_id";
	$res = $langDb->query($sql);

	$sqlData = [];
	while ($row = $res->fetch()) {
		$sqlData[] = "(".implode(", ", [$row['page_id'], $row['count'], $row['last_rev']]).")";

		if (count($sqlData) > 10000) {
			$db->query("INSERT INTO {$prefix}change_pages (pageid, count, last_rev) VALUE ".implode(", ", $sqlData));
			$sqlData = [];
		}
	}
	if (!empty($sqlData)) {
		$db->query("INSERT INTO {$prefix}change_pages (pageid, count, last_rev) VALUE ".implode(", ", $sqlData));
	}

	$db->exec("UPDATE settings SET value = CURDATE() WHERE name = '{$prefix}last_check'");
}

var_dump(time() - $timeRuns);