<?php
header('Access-Control-Allow-Origin: http://tools.wmflabs.org', true);  
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');  
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');

require_once __DIR__.'/../config.php';
//$pageListWidget = new Widget('page_list', $db);

$allowedWidget = ['main', 'page_list', 'cop'];
$widget = !empty($_GET['view']) && in_array($_GET['view'], $allowedWidget) ? $_GET['view'] : 'main';

include_once ROOT_DIR."/app/{$widget}/api.php";
?>
