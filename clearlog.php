<?php
define('SECRET', '');
$filename = '/home/site.ru/core/cache/logs/error.log';
$die = true;

if (isset($_GET['code']) && $_GET['code'] == SECRET) {
	$die = false;
} elseif ($argc == 2 && $argv[1] == SECRET) {
    $die = false;
}
if ($die) {
	die();
}

if (file_exists($filename)) {
	echo 'ok';
	unlink($filename);
} else {
	echo 'non exists';
}
