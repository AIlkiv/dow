<?php
require __DIR__.'/../../config.php';
require __DIR__.'/InitProcessArchives.php';

switch ($settings['view_stats_refresh_status']) {
	case 'process_archives':
		exec('jsub -N download -once -mem 500m -l release=trusty php '.__DIR__.'/commands/downloads.php');
		exec('jsub -N unpack -once -mem 500m -l release=trusty php '.__DIR__.'/commands/unpack.php');
		exec('jsub -N split -once -mem 500m -l release=trusty php '.__DIR__.'/commands/splits.php');
		exec('jsub -N agg -once -mem 2000m -l release=trusty php '.__DIR__.'/commands/aggs.php');

		$count = $db->query('SELECT COUNT(*) FROM archives_log')->fetchColumn();
		if ($count === 0) {
			$db->query('UPDATE settings SET value = "finish_processing" WHERE name = "view_stats_refresh_status"');
		}
		break;
	case '':
		break;
}