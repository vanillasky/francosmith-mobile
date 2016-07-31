<?
include "library.php";

loadClass('eAPI');

$eAPI = new EAPI();

$tmp_data['header']['authentic_key'] = 'aaaa-alal-alal';
$tmp_data['header']['connection_key'] = 'mobileshop';

$tmp = Array();

$tmp['aaa']['obj_0'] = '111111';
$tmp['aaa']['obj_1'] = '222222';
$tmp['aaa']['obj_2'] = '333333';

$tmp['bbb'] = 'ccccc';
$tmp['ccc'] = 'ddddd';

$tmp_data['req_data'] = $tmp;

$ret = $eAPI->checkApi($tmp_data);

debug($ret);
exit;

debug('aa');
debug($eAPI->getKey());



?>