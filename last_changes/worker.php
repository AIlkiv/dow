<?php

require __DIR__.'/../config.php';
define('RC_EDIT', 0);
define('RC_NEW', 1);

define('PAGE_EDITED', 1);
define('PAGE_NEW', 2);
define('PAGE_DELETE', 3);

/*
CREATE TABLE IF NOT EXISTS uk_categories_changes (
  category varbinary(255),
  page_id int,
  type int,
  date datetime,
  user_id varbinary(255) default NULL,
  namespace int,
  PRIMARY KEY (type, date, category, page_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

*/
$lang = 'uk';
$prefix = getDbPrefix($lang).'_';
$curDate = strtotime(date('YmdH0000'));
$time = array(
  'from' => date('YmdHis', strtotime('-24 HOUR', $curDate)),
  'to' => date('Ymd235959', $curDate),
);

$langDb = getLangWikiDb($lang);

$sql = "SELECT rc_cur_id page_id, rc_timestamp, rc_user, rc_namespace, rc_type
  FROM recentchanges
  WHERE rc_namespace IN (0, 14, 10)
  AND rc_timestamp BETWEEN {$db->quote($time['from'])} AND {$db->quote($time['to'])}
  AND rc_type IN (".implode(", ", [RC_EDIT, RC_NEW]).")";
$res = $langDb->query($sql)->fetchAll();

$pageIds = array_column($res, 'page_id');
$sql = 'SELECT cl.cl_from, cl.cl_to
  FROM categorylinks cl
  WHERE cl.cl_from IN ('.implode(', ', $pageIds).')';
$_categories = $langDb->query($sql)->fetchAll();

$categories = [];
foreach ($_categories as $row) {
  $categories[$row['cl_from']][] = $row['cl_to'];
}

foreach ($res as $row) {
  if (empty($categories[$row['page_id']])) {
    continue;
  }

  foreach ($categories[$row['page_id']] as $category) {
    $sqlData[] = "(".implode(", ", [$row['rc_timestamp'], $langDb->quote($category), $row['page_id'], $row['rc_user'], ($row['rc_type'] == RC_EDIT ? PAGE_EDITED : PAGE_NEW), $row['rc_namespace']]).")";
  }
  
  if (count($sqlData) > 10000) {
    $db->query("INSERT IGNORE INTO {$prefix}categories_changes (date, category, page_id, user_id, type, namespace) VALUE ".implode(", ", $sqlData));
    $sqlData = [];
  }
}

if (!empty($sqlData)) {
  $db->query("INSERT IGNORE INTO {$prefix}categories_changes (date, category, page_id, user_id, type, namespace) VALUE ".implode(", ", $sqlData));
  $sqlData = [];
}
  

if (date('G') == 3 && date('i') < 30) {
  $params = [
    'language' => 'uk',
    'project' => 'wikipedia',
    'ns' => [
      0 => 1, // статті
      6 => 1, // файли
      10 => 1, // шаблони
      14 => 1, // категорії
    ],
    'format' => 'json',
    'doit' => '1',
  ];

  $data = getWebPage('https://tools.wmflabs.org/dow/last.php?'.http_build_query($params));

  if (empty($res['*']['0']['a']['*'])) {
    return [];
  }

  return array_column($res['*']['0']['a']['*'], 'title');
}


function getWebPage( $url )
{
  $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

  $options = array(
    CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
    CURLOPT_POST           =>false,        //set to GET
    CURLOPT_USERAGENT      => $user_agent, //set user agent
    CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
    CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => false,    // don't return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle all encodings
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
  );

  $ch      = curl_init( $url );
  curl_setopt_array( $ch, $options );
  $content = curl_exec( $ch );
  $err     = curl_errno( $ch );
  $errmsg  = curl_error( $ch );
  $header  = curl_getinfo( $ch );
  curl_close( $ch );

  $header['errno']   = $err;
  $header['errmsg']  = $errmsg;
  $header['content'] = $content;
  return $header;
}
