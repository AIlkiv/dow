<?php

require_once __DIR__.'/../../config.php';
$pageListWidget = new Widget('page_list', $db);

if (empty($argv[1])) {
	echo "Error: Unknown language\n";
	return;
}

if (in_array($argv[1], $pageListWidget->getSetting('known_langs'))) {
	echo "Error: Language already exists\n";
	return;

}

$db->query("UPDATE page_list_settings SET value = CONCAT(value, '|', '{$argv[1]}') WHERE name = 'known_langs'");
