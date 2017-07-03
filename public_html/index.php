<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config.php';
//$pageListWidget = new Widget('page_list', $db);

$allowedWidget = ['main', 'page_list', 'cop'];
$widget = !empty($_GET['view']) && in_array($_GET['view'], $allowedWidget) ? $_GET['view'] : 'main';

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

    <title>DOW</title>
    <base href="https://tools.wmflabs.org/dow/">

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//tools-static.wmflabs.org/static/bootstrap/3.2.0/css/bootstrap.min.css">

    <!-- Custom styles for this template -->
    <link href="css/jumbotron-narrow.css" rel="stylesheet">
    <script src="//tools-static.wmflabs.org/static/jquery/2.1.0/jquery.min.js"></script>
    <script src="//tools-static.wmflabs.org/static/bootstrap/3.2.0/js/bootstrap.min.js"></script>

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
            <li role="presentation"><a href="#">About</a></li>
            <li role="presentation"><a href="#">Contact</a></li>
          </ul>
        </nav>
        <h3 class="text-muted">DOW</h3>
      </div>

<?php if ($widget == 'main'):?>
      <div class="jumbotron">
        <h1>DOW</h1>
        <select>
          <option value='uk'>uk.wikipedia.org</option><option value='en'>en.wikipedia.org</option>        </select>
        <a href='/files/uk/2016-09-01.log'>month_log</a>        <p class="lead">Cras justo odio, dapibus ac facilisis in, egestas eget quam. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus.</p>
        <p><a class="btn btn-lg btn-success" href="#" role="button">Sign up today</a></p>
      </div>
      <div class="row marketing">
        <div class="col-lg-6">
          <h4><a href="<?=MAIN_URL?>/?view=page_list">All Pages</a></h4>
          <p>Dump all pages/redirects wint main namespace.</p>

          <h4>Subheading</h4>
          <p>Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras mattis consectetur purus sit amet fermentum.</p>

          <h4>Subheading</h4>
          <p>Maecenas sed diam eget risus varius blandit sit amet non magna.</p>
        </div>

        <div class="col-lg-6">
          <h4>Subheading</h4>
          <p>Donec id elit non mi porta gravida at eget metus. Maecenas faucibus mollis interdum.</p>

          <h4>Subheading</h4>
          <p>Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cras mattis consectetur purus sit amet fermentum.</p>

          <h4>Subheading</h4>
          <p>Maecenas sed diam eget risus varius blandit sit amet non magna.</p>
        </div>
      </div>
<?php else: ?>
	<?php include_once ROOT_DIR."/app/{$widget}/public.php";?>
<?php endif ?>
      <footer class="footer">
        <p>&copy; 2016 Company, Inc.</p>
      </footer>

    </div> <!-- /container -->
  </body>
</html>

