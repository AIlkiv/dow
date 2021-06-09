<?php

require_once __DIR__.'/../config.php';

$portals = [
  'Хорватія' => [
    'depth' => 2,
  ],
  'Україна' => [],
  'Білорусь' => [],
  'Освіта' => [
    'depth' => 4,
  ],
  'Католицтво' => [
    'categories' => ['Католицизм'],
    'depth' => 6,
  ],
  'Культура' => [],
  'Право' => [],
  'Філософія' => [],
  'Медицина' => [],
  'Географія' => [
    'depth' => 4,
  ],
  'Музика' => [
    'depth' => 5,
  ],
  'Швеція' => [],
  'Молдова' => [],
  'Польща' => [],
  'Росія' => [],
  'Румунія' => [],
  'Словаччина' => [],
  'Угорщина' => [],
  'Туреччина' => [],
  'Історія' => [
    'depth' => 2,
  ],
  'Спорт' => [],
  'Залізниця' => [
    'categories' => ['Залізничний транспорт'],
    'depth' => 6,
  ],
  'Африка' => [],
  'Європа' => [],
  'Азія' => [],
  'Хімія' => [],
  'Фізика' => [],
  'Математика' => [],
  'Біологія' => [],
  'Психологія' => [],
  'Українська діаспора' => [
    'depth' => 2,
  ],
  'Українська мова' => [
    'depth' => 1,
  ], 
  'Норвегія' => [],
];


define('PAGE_EDITED', 1);
define('PAGE_NEW', 2);

$dateFrom = !empty($_GET['date_from']) ? date('Y-m-d', strtotime($_GET['date_from'])) : date('Y-m-d', strtotime('-1 DAY'));
$dateTo = !empty($_GET['date_to']) ? date('Y-m-d', strtotime($_GET['date_to'])) : date('Y-m-d');

$curPortal = key($portal);
if (!empty($_GET['portal'])) {
  $curPortalData = getPage('https://dow.toolforge.org/api.php?'.http_build_query(['view' => 'cop', 'action' => 'get_settings', 'portal' => $_GET['portal']]));
  if (empty($curPortalData)) {
    echo 'Unkonown portal';
    exit;
  }
  $curPortalData = json_decode($curPortalData, true);
  if (!$curPortalData['status']) {
    echo 'Unkonown portal';
    exit;
  }
  $curPortalData = $curPortalData['portal'];

  $curPortal = $_GET['portal'];
  $curTypes = empty($_GET['type']) ? [PAGE_EDITED, PAGE_NEW] : [$db->quote($_GET['type'])];
  $categories = [];
  $depth = !empty($curPortalData['depth']) ? $curPortalData['depth'] : 3;
  $portalCategories = !empty($curPortalData['categories']) ? $curPortalData['categories'] : [$curPortal];
  $igonoreCategories = !empty($curPortalData['ignore']) ? $curPortalData['ignore'] : [];
 
  $categories = $portalCategories;
  $categories = array_merge($categories, getSubCategories($portalCategories, $igonoreCategories, $depth));

  $categories = array_map([$db, 'quote'], $categories);
  $time = array(
    'from' => date('Ymd000000', strtotime($dateFrom)),
    'to' => date('Ymd235959', strtotime($dateTo)),
  );

  $sql = "SELECT ch.*
    FROM uk_categories_changes ch
    WHERE ch.type IN (".implode(", ", $curTypes).") AND ch.date BETWEEN {$db->quote($time['from'])} AND {$time['to']} AND ch.category IN (".implode(", ", $categories).") GROUP BY type, date, page_id ORDER BY ch.date DESC LIMIT 1000";
  $listRevisions = $db->query($sql)->fetchAll();

  $langDb = getLangWikiDb('uk');
  $userIds = array_column($listRevisions, 'user_id');
  if (!empty($userIds)) {
    $_users = $langDb->query('SELECT user_id, user_name FROM user WHERE user_id IN ('.implode(', ', $userIds).')')->fetchAll();
    $users = [];
    foreach ($_users as $row) {
      $users[$row['user_id']] = $row['user_name'];
    }
  }

  $pageIds = array_column($listRevisions, 'page_id');
  if (!empty($pageIds)) {
    $_pages = $langDb->query('SELECT page_id, page_namespace, page_title FROM page WHERE page_id IN ('.implode(', ', $pageIds).')')->fetchAll();
    $pages = [];
    foreach ($_pages as $row) {
      switch ($row['page_namespace']) {
        case '0':
          break;
        case '14':
          $row['page_title'] = 'Категорія:'.$row['page_title'];
          break;
        case '10':
          $row['page_title'] = 'Шаблон:'.$row['page_title'];
          break;
        case '100':
          $row['page_title'] = 'Портал:'.$row['page_title'];
          break;
      }
      $pages[$row['page_id']] = $row['page_title'];
    }
  
    $lastPatrolDate = [];
    $_lastPatrolDate = $langDb->query('select fr_page_id, fr_timestamp from flaggedrevs where fr_page_id IN ('.implode(', ', $pageIds).')')->fetchAll();
    foreach ($_lastPatrolDate as $row) {
      $lastPatrolDate[$row['fr_page_id']] = $row['fr_timestamp'];
    }
  }

  if (!empty($_GET['response_type'])) {
    switch ($_GET['response_type']) {
      case 'wiki':
        $i = 0;
        foreach ($listRevisions as $row) {
          if ($row['namespace'] == 14) {
            continue;
          }
          if (!empty($lastPatrolDate[$row['page_id']]) && $lastPatrolDate[$row['page_id']] > $row['date']) {
            continue;
          }
          if ($i++ > 150) {
            break;
          }
          $page = !empty($pages[$row['page_id']]) ? $pages[$row['page_id']] : '';
          if (empty($page)) {
            continue;
          }
          $datetime = new DateTime($row['date']);
          /*$la_time = new DateTimeZone('Europe/Kiev');
          $datetime->setTimezone($la_time);*/
          echo '{{Редагують зараз|'.(str_replace('_', ' ', $page)).'|'.$datetime->format('H:i, Y-m-d').'|'.(!empty($users[$row['user_id']]) ? $users[$row['user_id']] : 'IP').'}}';
        }
        exit;
      case 'json':
        $data = [];
        foreach ($listRevisions as $row) {
          if ($row['namespace'] == 14) {
            continue;
          }
          if (!empty($lastPatrolDate[$row['page_id']]) && $lastPatrolDate[$row['page_id']] > $row['date']) {
            continue;
          }
          if ($i++ > 150) {
            break;
          }
          $page = !empty($pages[$row['page_id']]) ? $pages[$row['page_id']] : '';
          if (empty($page)) {
            continue;
          }
          $datetime = new DateTime($row['date']);
          /*$la_time = new DateTimeZone('Europe/Kiev');
          $datetime->setTimezone($la_time);*/
          $data[] = ['title' => str_replace('_', ' ', $page), 'date' => $datetime->format('Y-m-d H:i'), 'user_id' => (!empty($users[$row['user_id']]) ? $users[$row['user_id']] : 'IP')];
        }
        echo json_encode(['status' => true, 'data' => $data]);
        exit;

    }
  }
}

function getPage($url)
{
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Opera 10.00');
    $data = curl_exec($ch);
  
    if(curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
        echo curl_errno($ch).' '.curl_getinfo($ch, CURLINFO_HTTP_CODE).' '.curl_error($ch);
    }
    curl_close($ch);

    return $data;
}

function getSubCategories($categories, $negcats, $depth) {
  $params = [
    'language' => 'uk',
    'project' => 'wikipedia',
    'depth' => $depth,
    'categories' => implode("\n", $categories),
    'ns' => [14 => 1],
    'format' => 'json',
    'doit' => '1',
    'combination' => 'union'
  ];
  if (!empty($negcats)) {
    $params['negcats'] = implode("\n", $negcats);
  }

  $res = json_decode(file_get_contents('https://petscan.wmflabs.org/?'.http_build_query($params)), true);
  if (empty($res['*']['0']['a']['*'])) {
    return [];
  }

  $cat = array_column($res['*']['0']['a']['*'], 'title');
  $cat = array_map(function ($c) { return str_replace('_', ' ', $c); }, $cat);
  $cat = array_diff($cat, $negcats);

  return $cat;
}


?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">

    <title>Зміни за порталом</title>
    <base href="https://tools.wmflabs.org/revisions-blacklist/">

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.min.css" crossorigin="anonymous">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
  

    <!-- Custom styles for this template -->
    <link href="css/jumbotron-narrow.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
 	      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">
      <div class="header clearfix">
        <nav>
          <ul class="nav nav-pills pull-right">
            <li role="presentation" class="active"><a href="#">Home</a></li>
          </ul>
        </nav>
        <h3 class="text-muted">Зміни за порталом</h3>
      </div>

      <div class="jumbotron">
        <h1>Зміни за порталом</h1>
        <form action="http://tools.wmflabs.org/dow/last.php">
        <select name="portal">
          <?php
          foreach ($portals as $portal => $categies) {
            echo "<option value='{$portal}' ".($curPortal == $portal ? 'selected' : '').">Портал:{$portal}</option>";
          }
          ?>
        </select>
        <div id="datepicker">
          <label>Дата від:</label><input type="text" name="date_from" value="<?=$dateFrom?>"/><br/>
          <label>Дата до:</label><input type="text" name="date_to" value="<?=$dateTo?>"/>
        </div>
        <button type="submit">Дивитися</button>
        </form>
        <p>
<?php
if (!empty($listRevisions)) {
  echo '<table>';
  foreach ($listRevisions as $row) {
    $page = !empty($pages[$row['page_id']]) ? $pages[$row['page_id']] : '';
    echo '<tr><td>'.date('Y-m-d H:i:s', strtotime($row['date'])).'</td><td><a href="https://uk.wikipedia.org/wiki/'.$page.'">'.(str_replace('_', ' ', $page)).'</a></td><td>'.(!empty($users[$row['user_id']]) ? $users[$row['user_id']] : 'IP').'</td></tr>';
  }
  echo '<table>';
}
?>
        </p>
      </div>
      <div class="row marketing">
        
      </div>
      <footer class="footer">
        <p>&copy; 2016 Company, Inc.</p>
      </footer>

    </div> <!-- /container -->
<script>
$('#datepicker input').datepicker({format: 'yyyy-mm-dd'});
</script>
  </body>
</html>

