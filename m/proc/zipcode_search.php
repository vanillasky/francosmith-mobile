<?php

@include dirname(__FILE__) . "/../lib/library.php";
@include $shopRootDir . "/lib/page.class.php";
@include $shopRootDir . "/lib/json.class.php";

$json = new Services_JSON();

### 변수할당
$loop = array();
$where = array();
$result = array();
$dong = iconv("utf-8","euc-kr",$_GET['dong']);

if ($dong){

	$_param = array(
		'keyword' => $dong,
		'where' => 'dong',
		'page' => 1,
		'page_size' => 1000,
	);

	$_result = Core::loader('Zipcode')->get($_param);

	if($_result->rowCount()) $result['list'] = &$_result->toArray();

}

$result['success'] = true;

echo $json->encode($result);
?>
