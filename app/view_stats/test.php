<?php

require __DIR__.'/../../config.php';
require __DIR__.'/InitProcessArchives.php';

$t = new InitProcessArchives($db, $settings);

$t->execute();
/*
jsub -N download -once -mem 500m -l release=trusty php ./app/view_stats/commands/downloads.php

jsub -N unpack -once -mem 500m -l release=trusty php ./app/view_stats/commands/unpack.php

jsub -N split -once -mem 500m -l release=trusty php ./app/view_stats/commands/splits.php

jsub -N agg -once -mem 2000m -l release=trusty php ./app/view_stats/commands/aggs.php


jsub -N page_list -once -mem 2000m -l release=trusty php app/page_list.php uk
*/