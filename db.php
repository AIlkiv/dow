<?php

function getLocalDb() {
	static $db;

	if ($db) {
		try {
	        $db->query("SHOW STATUS");
	    } catch(\PDOException $e) {
	        if($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
	            throw $e;
	        }

	        $db = null;
	    }
	}

	if (empty($db)) {
		$dbConfig = parse_ini_file(ROOT_DIR.'/replica.my.cnf');
		
		$db = new PDO('mysql:host=tools.labsdb;dbname='.$dbConfig['user'].'__dow', $dbConfig['user'], $dbConfig['password'], [PDO::MYSQL_ATTR_LOCAL_INFILE => true]);
		$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec('set names utf8');
	}

	return $db;
}

$db = getLocalDb();