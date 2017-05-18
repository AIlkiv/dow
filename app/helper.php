<?php

function getDbPrefix($lang)
{
	return str_replace('-', '_', $lang);
}

function getLangWikiDb($lang)
{
	$dbConfig = parse_ini_file(ROOT_DIR.'/replica.my.cnf');

	$db = new PDO('mysql:host='.getDbPrefix($lang).'wiki.labsdb;dbname='.getDbPrefix($lang).'wiki_p', $dbConfig['user'], $dbConfig['password'], [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
	$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec('set names utf8');

	return $db;
}