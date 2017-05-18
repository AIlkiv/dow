<?php

require __DIR__.'/../config.php';

error_reporting(E_ALL);
set_time_limit(0);
ini_set('memory_limit', 3000000000);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($argv[1])) {
	echo "Error: Unknown language\n";
	return;
}
if (in_array($argv[1], $knownLangs)) {
	echo "Error: Language already exists\n";
	return;

}

$prefix = getDbPrefix($argv[1]);

try {
	$db->query("UPDATE settings SET value = CONCAT(value, '|', '{$argv[1]}') WHERE name = 'known_langs'");

	$db->query("INSERT INTO settings (name, value) VALUE ('{$prefix}_last_update_page_list', NOW())");

	$cmd = 'mkdir '.ROOT_DIR.'/public_html/files/'.$lang;
	exec($cmd);
} catch (Exception $e) {
	echo "Error: DB\n";
	return;
}
