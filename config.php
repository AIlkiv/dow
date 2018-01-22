<?php

define('ROOT_DIR', __DIR__);
define('MAIN_URL', 'https://tools.wmflabs.org/dow');

require_once __DIR__.'/db.php';
require_once __DIR__.'/app/helper.php';

$_settings = $db->query('SELECT name, value FROM settings')->fetchAll();
foreach ($_settings as $row) {
	$settings[$row['name']] = $row['value'];
}
unset($_settings);

$knownLangs = $settings['known_langs'] = array_unique(array_filter(explode('|', $settings['known_langs'])));

function getRuningTasks()
{
	$s = `qstat -xml`;
	$xml = new SimpleXMLElement($s);
	$result = $xml->xpath('.//JB_name');

	$tasks = [];
	foreach ($result as $node) {
		$tasks[] = $node;
	}
	
	return $tasks;
}

function checkLimitTasks($prefix, $limit)
{
	$tasks = getRuningTasks();

	$tasks = array_filter($tasks, function($task) use ($prefix) {
		return strpos($task, $prefix) === 0;
	});

	return count($tasks) < $limit;
}

class Widget
{

	public $ident = '';

	private $db = null;
	private $settings = [];


	function __construct($ident, $db)
	{
		$this->db = $db;
		$this->ident = $ident;

		$_settings = $db->query("SELECT name, value FROM {$this->ident}_settings")->fetchAll();
		foreach ($_settings as $row) {
			$this->settings[$row['name']] = $row['value'];
		}

		$this->settings['known_langs'] = $this->settings['known_langs'] = array_unique(array_filter(explode('|', $this->settings['known_langs'])));
	}

	public function getSetting($key)
	{
		return $this->settings[$key];
	}

	public function setSetting($key, $value)
	{
		if ($value != 'NOW()') {
			$value = $this->db->quote($value);
		}
		$this->db->query("UPDATE {$this->ident}_settings SET value = {$value} WHERE name = {$this->db->quote($key)}");
	}
}