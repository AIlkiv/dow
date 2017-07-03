<?php

require __DIR__.'/../config.php';

$cmd = "jlocal -N page_list -once -mem 200m -l release=trusty php app/page_list/worker.php";
echo `$cmd`;
/*
foreach ($knownLangs as $lang) {
	$prefix = getDbPrefix($lang);
	if (date('m', strtotime($settings[$prefix.'_last_update_page_list'])) != date('m')) {
		$cmd = "jsub -N page_list_{$prefix} -once -mem 2000m -l release=trusty php app/page_list.php {$lang}";
		echo `$cmd`;
	}

}*/