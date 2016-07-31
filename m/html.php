<?
$noDemoMsg = 1;
include "./_header.php";

function _realpath($path)
{
	$path = str_replace('\\',  '/', $path);
	$path = preg_replace('/\/+/', '/', $path);

	$segments = explode('/', $path);
	$parts = array();

	foreach ($segments as $segment) {
		if ($segment == '..') {
			array_pop($parts);
		}
		else if ($segment == '.') {
			continue;
		}
		else {
			$parts[] = $segment;
		}
	}
	return implode(DIRECTORY_SEPARATOR, $parts);
}

$file = _realpath($tpl->template_dir . DIRECTORY_SEPARATOR . $_GET['htmid']);

if (!in_array(pathinfo($file, PATHINFO_EXTENSION ), array('htm'))) {
	$error = true;
}
else if (!preg_match('/^(\/data)?\/skin/', str_replace('\\','/',str_replace(SHOPROOT, '', $file)), $matches)) {
	$error = true;
}
else {
	$error = false;
}

if ($error === true) {
	go('../');
	exit;
}

$tpl->print_('tpl');
?>