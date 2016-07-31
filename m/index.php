<?php
	include dirname(__FILE__) . "/_header.php";
	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/conf/config.mobileShop.main.php";

	$_view_search_form = true;
	$mainpage = true;

	if(!$cfgMobileShop['useMobileShop']){
		header("location:".$cfg['rootDir']."/main/intro.php");
	}

	## 모바일샵 일반인트로 사용일 경우 도메인으로 들어 온 것인지 메인으로 들어온 것인지 체크
	if ( $cfg['introUseYNMobile'] == 'Y' && (int)$cfg['custom_landingpageMobile'] < 2 ) {
		if(preg_match('/^http(s)?:\/\/'.$_SERVER[SERVER_NAME].'\/m$/',$_SERVER[HTTP_REFERER]) || strpos($_SERVER[HTTP_REFERER],"http://".$_SERVER[SERVER_NAME]) !==0 ){ // 인트로 사용
			header("location:intro/intro.php" . ($_SERVER[QUERY_STRING] ? "?{$_SERVER[QUERY_STRING]}" : ""));
		}
	}
	
	$tpl->print_('tpl');
?>