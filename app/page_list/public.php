<?php
$pageListWidget = new Widget('page_list', $db);

$curDate = $pageListWidget->getSetting('cur_date');

switch ($pageListWidget->getSetting('status')) {
	case 'ok':
	$_ym = date("Ym", strtotime($curDate));
	$_date = date('Y-m-01', strtotime($curDate));
	$contents = '';
	foreach ($pageListWidget->getSetting('known_langs') as $lang) {
		$contents .= "<h3>{$lang}</h3>";
		$contents .= "<ul>";
		if (file_exists(ROOT_DIR."/public_html/files/page_list/{$lang}_{$_ym}_all_pages.log")) {
			$contents .= "<li><a href='".MAIN_URL."/files/page_list/{$lang}_{$_ym}_all_pages.log'>all pages</a></li>";
		}
		if (file_exists(ROOT_DIR."/public_html/files/page_list/{$lang}_{$_ym}_all_redirects.log")) {
			$contents .= "<li><a href='".MAIN_URL."/files/page_list/{$lang}_{$_ym}_all_redirects.log'>all redirects</a></li>";
		}
		if (file_exists(ROOT_DIR."/public_html/files/page_list/{$lang}_{$_ym}_all_redirects_rel.log")) {
			$contents .= "<li><a href='".MAIN_URL."/files/page_list/{$lang}_{$_ym}_all_redirects_rel.log'>all relations from redirectID to pageID</a></li>";
		}
		$contents .= "</ul>";
	}
	echo <<<"templ"
Date: $_date
$contents




templ;
		break;
	case 'process':
		echo 'Refresh data';
		break;
}