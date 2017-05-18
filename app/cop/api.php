<?php

$allowedAction = ['get', 'get_settings'];
$action = isset($_GET['action']) && in_array($_GET['action'], $allowedAction) ? $_GET['action'] : null;
switch ($action) {
	case 'get':
		$categories = explode("\n", $_POST['category']);
		$ignore = array_filter(explode("\n", $_POST['ignore']));

		$categories = array_merge($categories, getSubCategories($categories, $ignore, $_POST['depth']));
		echo json_encode($categories);
		exit;
	case 'get_settings':

		require __DIR__.'/portal_data.php';
		echo json_encode(isset($_GET['portal']) && array_key_exists($_GET['portal'], $portals) ? ['status' => true, 'portal' => $portals[$_GET['portal']]] : ['status' => false]);

		exit;
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

  return array_column($res['*']['0']['a']['*'], 'title');
}