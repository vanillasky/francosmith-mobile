<?php

/*
 * Naver mileage bridge version 2
 */

include dirname(__FILE__).'/../_header.php';

if (empty($_GET) === true) exit;

setCookie('reqTxId', $_GET['reqTxId'], time()+600, '/');
$naverNcash = Core::loader('naverNcash');
$naverCommonInflowScript = Core::loader('naverCommonInflowScript');

if (get_magic_quotes_gpc()) {
	stripslashes_all($_POST);
	stripslashes_all($_GET);
}

function hash_hmac_php4($algo,$data,$passwd)
{
	/* php4 용 md5 and sha1 only */
	$algo=strtolower($algo);
	$p=array('md5'=>'H32','sha1'=>'H40');
	if(strlen($passwd)>64) $passwd=pack($p[$algo],$algo($passwd));
	if(strlen($passwd)<64) $passwd=str_pad($passwd,64,chr(0));
	$ipad=substr($passwd,0,64) ^ str_repeat(chr(0x36),64);
	$opad=substr($passwd,0,64) ^ str_repeat(chr(0x5C),64);
	return($algo($opad.pack($p[$algo],$algo($ipad.$data))));
}

$totalAccumRate = $_GET['baseAccumRate'] + $_GET['addAccumRate'];

// signature check
if ($_GET['resultCode'] == 'OK') {
	$signature = hash_hmac_php4('sha1',$_GET['resultCode'].$_GET['reqTxId'].($_GET['baseAccumRate']*10).($_GET['addAccumRate']*10).$_GET['mileageUseAmount'].$_GET['cashUseAmount'].$_GET['totalUseAmount'].$_GET['balanceAmount'],$naverNcash->api_key);

	if ($_GET['sig'] == $signature) $isValid = true;
	else $isValid = false;
}
else if ($_GET['resultCode'] == 'CLOSE') {
	$isClose = true;
}
else if($_GET['resultCode'] == 'CANCEL') {
	$isCancel = true;
}
?>
<!doctype html>
<html lang="ko">
	<head>
		<meta charset="euc-kr" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=yes"/>
		<title>마일리지 적립/사용 팝업 : 네이버 마일리지</title>
		<?php echo $naverCommonInflowScript->getCommonInflowScript(); ?>
		<?php echo $naverNcash->getMobileBaseScript(); ?>
	</head>
	<body class="skin">
		<div id="_mile_ct"></div>
		<script type="text/javascript">
		<?php if (isset($isValid) && $isValid === true) { // 유효한 요청 ?>
			nbp.mileage.bridge.pass();
		<?php } else if (isset($isClose) && $isClose === true) { // 닫기버튼 클릭 ?>
			nbp.mileage.bridge.pass();
		<?php } else if (isset($isCancel) && $isCancel === true) { // 적립취소 ?>
			nbp.mileage.bridge.pass();
		<?php } else { // 인증오류 ?>
			nbp.mileage.bridge.failure();
		<?php } ?>
		</script>
	</body>
</html>