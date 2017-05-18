<?php

require __DIR__.'/../config.php';
define('RC_EDIT', 0);
define('RC_NEW', 1);

define('PAGE_EDITED', 1);

/*
CREATE TABLE IF NOT EXISTS uk_categories_changes (
	category varbinary(255),
	page_id int,
	type int,
	date datetime,
	user_id varbinary(255) default NULL,
	namespace int,
	PRIMARY KEY (type, date, category, page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/
$lang = 'uk';
$prefix = getDbPrefix($lang).'_';
$curDate = strtotime(date('YmdH0000'));
$time = array(
	'from' => date('YmdHis', strtotime('-24 HOUR', $curDate)),
	'to' => date('Ymd235959', $curDate),
);

$langDb = getLangWikiDb($lang);

$sql = "SELECT rc_cur_id page_id, rc_timestamp, rc_user, rc_namespace
	FROM recentchanges
	WHERE rc_namespace IN (0, 14, 10)
	AND rc_timestamp BETWEEN {$db->quote($time['from'])} AND {$db->quote($time['to'])}
	AND rc_type IN (".implode(", ", [RC_EDIT, RC_NEW]).")";
$res = $langDb->query($sql)->fetchAll();

$pageIds = array_column($res, 'page_id');
$sql = 'SELECT cl.cl_from, cl.cl_to
	FROM categorylinks cl
	WHERE cl.cl_from IN ('.implode(', ', $pageIds).')';
$_categories = $langDb->query($sql)->fetchAll();

$categories = [];
foreach ($_categories as $row) {
	$categories[$row['cl_from']][] = $row['cl_to'];
}

foreach ($res as $row) {
	if (empty($categories[$row['page_id']])) {
		continue;
	}

	foreach ($categories[$row['page_id']] as $category) {
		$sqlData[] = "(".implode(", ", [$row['rc_timestamp'], $langDb->quote($category), $row['page_id'], $row['rc_user'], PAGE_EDITED, $row['rc_namespace']]).")";
	}
	
	if (count($sqlData) > 10000) {
		$db->query("INSERT IGNORE INTO {$prefix}categories_changes (date, category, page_id, user_id, type, namespace) VALUE ".implode(", ", $sqlData));
		$sqlData = [];
	}
}

if (!empty($sqlData)) {
	$db->query("INSERT IGNORE INTO {$prefix}categories_changes (date, category, page_id, user_id, type, namespace) VALUE ".implode(", ", $sqlData));
	$sqlData = [];
}
	