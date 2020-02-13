<?php
/**
 * CAPYBARA INDEXING SCRIPT
 * Index all of your folder with ease.
 */

 /**
  * Date time settings (according to your timezone)
  */
define('DATE_TIME_FORMAT', "D, j F Y - H:i:s");
//date_default_timezone_set('Asia/Jakarta');

 /**
  * Show the folder link, instead of using the viewer link
  * if the directory have index file in it. (is recursively checked)
  */
define('AUTODETECT_INDEX', true);


/**
 * The Index file direction, if you need to change the file
 * name.
 * Default: '/', (or change to index.php if you really worried).
 */
define('INDEX_NAME', '/');

/**
 * Index file list, will be checked for each folder.
 * If you have activated the AUTODETECT_INDEX, this setting
 * will be crucial to detect your index file.
 */
define('AUTODETECT_INDEX_FILELIST', [
	'index.html',
	'index.htm',
	'index.php',
	'index.py'
]);

/**
 * NOT ALLOWED FILE PATH (GLOBAL)
 */
define('NOT_ALLOWED_FOLDER', [
	'cgi-bin',
	'error_log'
]);

/**
 * END OF SETTINGS, YOU CAN ENJOY YOUR DAY NOW.
 */


$_GET['q'] = isset($_GET['q'])?$_GET['q']:null;
$not_allowed_url = [
	"/" => "",
	"./"=> "",
	"../"=>"",
];
// Query Validation
if (strpos($_GET['q'], './') || strpos($_GET['q'], '../' || $_GET['q'] == '.')){
	$location = INDEX_NAME;
	if($_GET['q'] == '.')
		$_GET['q']='';
	$location .= ltrim(str_replace(
		key($not_allowed_url,
		array_values($not_allowed_url),
		$_SERVER['QUERY_STRING']), '/')
	);

	header('Location: ' . $location);
}

function endsWith($haystack, $needle) {
	return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

$checked_path = [];
function precheck($path){
	global $checked_path;
	if (key_exists($path, $checked_path))
		return $checked_path[$path];

	if ($path == '/' || $path == DIRECTORY_SEPARATOR) {
		$checked_path[$path] = true;
		return true;
	}

	if (basename($path)[0] == "." || 
		endsWith(basename($path), '.php') ||
		in_array(basename($path), NOT_ALLOWED_FOLDER) || 
		is_file($path . DIRECTORY_SEPARATOR . '.noindex')) {
		
		$checked_path[$path] = false;
		return false;
	}

	$checked_path[$path] = precheck(dirname($path));
	return $checked_path[$path];
}

function get_link($path) {
	if (!is_dir("./" . $path))
		return $path;
	
	if (AUTODETECT_INDEX)
		foreach(AUTODETECT_INDEX_FILELIST as $idx){
			if(is_file($path . DIRECTORY_SEPARATOR . $idx))
				return $path;   
		}
	
	return ltrim(INDEX_NAME . '?q=' . $path);
}

function usortFolderTime($a, $b) {
	return filemtime($b) - filemtime($a);
}

function humanFilesize($bytes, $decimals = 2) {
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

// note: $path are always the full path.
$path = __DIR__;
if ($_GET['q'])
	$path .= DIRECTORY_SEPARATOR . $_GET['q'];

$folders = [];
$folder = array_slice(scandir($path), 2);
foreach ($folder as $paths) {
	if (!precheck($path . DIRECTORY_SEPARATOR . $paths))
		continue;
	$folders[] = (!empty($_GET['q'])?$_GET['q'] . DIRECTORY_SEPARATOR . $foldr:"") . $paths;
}

usort($folders, "usortFolderTime");

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<title>Index of /<?= htmlentities($_GET['q']?:'') ?></title>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.8.0/css/bulma.min.css">
		<script defer src="https://use.fontawesome.com/releases/v5.0.0/js/all.js"></script>
		<style>
			.footer { padding-top: 2rem; padding-bottom: 2rem; }
			.center-text { text-align: center; }
			.right-text { text-align: right; }
			.hero-body { padding-top: 1rem; padding-bottom: 1rem; }
			.section { padding-left: 0.5rem; padding-right: 0.5rem; }
			body { display: flex; flex-direction: column; min-height: 100vh; }
			main { flex: 1; }
			.thumb { width: 64px; height: 64px; display: inline-block; }
			.columns-striped > .columns:nth-child(odd) { background-color: #EAEAEA; }
		</style>
	</head>
	<body>
		<section class="hero is-dark">
			<div class="hero-head">
				<nav class="navbar">
					<div class="container">
						<div class="navbar-brand">
						<a class="navbar-item" href="<?= htmlentities(INDEX_NAME) ?>">
							<?= $_SERVER['HTTP_HOST'] ?>
						</a>
					</div>
				</nav>
			</div>
			<div class="hero-body">
				<div class="container">
					<h2 class="subtitle">
						/<?= htmlentities($_GET['q']?:'') ?>
					</h2>
				</div>
			</div>
		</section>

		<main>
			<section class="section">
				<div class="container">
					<div class="content columns-striped">
						<?php if($_GET['q'] && $_GET['q'] != '/' && $_GET['q'] != '.'): ?>
							<div class="columns">
								<div class="column is-1 center-text">
									<i class="fa fa-angle-up"></i>
								</div>
								<div class="column is-11">
									<a href="<?= htmlentities(INDEX_NAME . '?q=' . 
										(dirname($_GET['q'])=="."?"":dirname($_GET['q']))) ?>"><i>Go Up</i></a>
								</div>
							</div>
						<?php endif; ?>
						<?php 
							foreach($folders as $foldr):
						?>
							<?php if(is_dir($foldr)): ?>
								<div class="columns">
									<div class="column is-1 center-text">
										<i class="fa fa-folder"></i>
									</div>
									<div class="column is-4">
										<a href="<?= htmlentities(get_link($foldr)) ?>"><?= htmlentities(basename($foldr)) ?></a>
									</div>
									<div class="column is-4 right-text">
										<?= date(DATE_TIME_FORMAT, filemtime($foldr)) ?>
									</div>
									<div class="column is-3 right-text"></div>
								</div>
							<?php else: ?>
								<div class="columns">
									<?php if(endsWith(basename($foldr), '.png') || endsWith(basename($foldr), '.jpg')): ?>
										<div class="column is-1 center-text">
											<img class="thumb" src="<?= htmlentities(get_link($foldr)) ?>" />
										</div>
									<?php else: ?>
										<div class="column is-1 center-text">
											<i class="fa fa-file"></i>
										</div>
									<?php endif; ?>
									<div class="column is-4">
										<a href="<?= htmlentities(get_link($foldr)) ?>"><?= htmlentities(basename($foldr)) ?></a>
									</div>
									<div class="column is-4 right-text">
										<?= date(DATE_TIME_FORMAT, filemtime($foldr)) ?>
									</div>
									<div class="column is-3 right-text">
										<?= humanFilesize(filesize($foldr)?:0) ?>b
									</div>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		</main>
		<footer class="footer">
			<div class="container">
				<div class="content has-text-centered">
					Indexed by <a href="https://github.com/MattNer0/capdex.php"><i class="fab fa-github"></i> Capdex.php</a>. <b>Special note:</b> All timezone are localized to <b><?= htmlentities(date_default_timezone_get()) ?></b>.
				</div>
			</div>
		</footer>
	</body>
</html>