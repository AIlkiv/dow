<?php

require_once __DIR__.'/../../config.php';
$pageListWidget = new Widget('page_list', $db);

$curDate = $pageListWidget->getSetting('cur_date');

switch ($pageListWidget->getSetting('status')) {
	case 'ok':
		if (date('Y-m') != date('Y-m', strtotime($curDate))) {
			$db->query("INSERT IGNORE INTO page_list_queue (lang) VALUES (".implode("), (", array_map([$db, 'quote'], $pageListWidget->getSetting('known_langs'))).")");
			$db->query("UPDATE page_list_settings SET value = NOW() WHERE name = 'cur_date'");
			$cmd = 'rm -rf '.ROOT_DIR.'/public_html/files/page_list/*';
			`$cmd`;
			$db->query("UPDATE page_list_settings SET value = 'process' WHERE name = 'status'");
		}
		break;
	case 'process':
		$langs = $db->query('SELECT lang FROM page_list_queue ORDER BY lang LIMIT 5')->fetchAll();

		if (!empty($langs)) {
			if (checkLimitTasks('page_list_', 5)) {
				$langs = array_column($langs, 'lang');

				foreach ($langs as $lang) {
					$prefix = getDbPrefix($lang);
					$cmd = "jsub -N page_list_{$prefix} -once -mem 4000m -l release=trusty php app/page_list/page_list.php {$lang}";
					echo `$cmd`;
				}
			}
		}
		else {
			$db->query("UPDATE page_list_settings SET value = 'ok' WHERE name = 'status'");
		}
		break;
}
