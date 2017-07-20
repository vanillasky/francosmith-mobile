<?php
	@include dirname(__FILE__) . "/lib/library.php";
	@include $shopRootDir . "/Template_/Template_.class.php";
	@include_once $shopRootDir . "/lib/tplSkinMobileView.php";
	@include_once $shopRootDir . "/lib/facebook.class.php";
	$cfgMobileShop = array_map("slashes",$cfgMobileShop);

	if(!$cfgMobileShop['tplSkinMobile']) $cfgMobileShop['tplSkinMobile'] = 'default';

	### 카운터정보획득
	include $shopRootDir."/lib/_log.php";

	### 메타태그 변수 할당
	$meta_title = $cfg[title];
	$meta_keywords = $cfg[keywords];

	$tpl = new Template_;
	$tpl->template_dir	= $shopRootDir."/data/skin_mobile/".$cfgMobileShop['tplSkinMobile'];
	$tpl->compile_dir	= $shopRootDir."/Template_/_compiles/skin_mobile/".$cfgMobileShop['tplSkinMobile'];
	$tpl->prefilter		= "adjustPath|include_file|capture_print";
	
	// PHP 태그 비활성화
	if ($cfg['skinSecurityMode'] == 'y') {
		$tpl->disable_php_tag = true;
	}
	
	{ // File Key

	$key_file = preg_replace( "'^.*$mobileRootDir/'si", "", $_SERVER['SCRIPT_NAME'] );
	$key_file = preg_replace( "'\.php$'si", ".htm", $key_file );

	if ( $key_file == 'html.htm' && $_GET['htmid'] != '' ) $key_file = $_GET['htmid'];

	$data_file		= $design_skin[ $key_file ];		# File Data
	}

	$referer_url = parse_url($_SERVER['HTTP_REFERER']);
	$host_url = explode(":",$_SERVER['HTTP_HOST']);

	$referer_domain = str_replace('www.','',$referer_url['host']);
	$shop_domain = str_replace('www.','',$host_url[0]);

	$cookie_domain = str_replace('www','',$host_url[0]);
	if(substr($cookie_domain,0,1) != '.') $cookie_domain = ".".$cookie_domain;

	### 성인인증 인트로
    if($cfg[custom_landingpageMobile] > 1 && !preg_match( "/intro\/intro*/", $key_file) && !preg_match( "/mem\/join*/", $key_file) && !preg_match( "/mem\/login*/", $key_file) && !preg_match( "/proc\/zipcode*/", $key_file)){
		$auth_date = getAdultAuthDate($session->m_id);
		$auth_date = $auth_date['auth_date'];
		$current_date = date("Y-m-d");
		$auth_period = strtotime("+1 years", strtotime($auth_date)); 
		$auth_period = date("Y-m-d", $auth_period);

        if ($cfg[custom_landingpageMobile] == 2 && !Clib_Application::session()->isAdult() && !$sess) {    // 성인
            header('location:'.$mobileRootDir.'/intro/intro_adult.php' . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
        }
		else if ($cfg[custom_landingpageMobile] == 2 && $sess && ($auth_date == '0000-00-00' || $current_date > $auth_period) && ((int)($session->level) < 80)) {	// 회원 성인인증기간(adult_date) 경과 검증
			header('location:'.$mobileRootDir.'/intro/intro_adult_login.php' . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
		}
        elseif ($cfg[custom_landingpageMobile] == 3 && !$sess) {    // 회원
            header('location:'.$mobileRootDir.'/intro/intro_member.php' . ($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : ''));
        }
    }

	### ssl 리다이렉트
	$sitelink->ready_refresh_mobile();
	### 보안서버용 로긴url
	$tpl->assign('loginActionUrl',$sitelink->link_mobile('mem/login_ok.php','ssl'));

	function _destroyNcachCookie() {

		global $cookie_domain;

		$expire = time() - 3600;

		foreach (array('Ncisy','N_t','N_e','N_ba','N_aa') as $v) {
		setCookie($v, "", $expire ,"/",$cookie_domain);
		$_COOKIE[$v] = "";
		}
		setCookie("cookie_check",0,0,'/',$cookie_domain); # 유효기간 24시간

	}
	if ($_COOKIE['N_e'] < time()) {
		_destroyNcachCookie();
	}
	else if ($_COOKIE['cookie_check'] != 1) {
		_destroyNcachCookie();
	}
	else if ($referer_domain && ($shop_domain != $referer_domain) && (!preg_match('/(naver\.com|godo\.co\.kr)/',$referer_url['host']))){
		_destroyNcachCookie();
	}
	
	// 네이버 공통유입 스크립트
	$naverCommonInflowScript = &load_class('NaverCommonInflowScript', 'naverCommonInflowScript');
	$tpl->assign('naverCommonInflowScript', $naverCommonInflowScript->getCommonInflowScript());

	$tpl->define( array(
		'tpl'			=> $key_file,
		'header'		=> 'outline/_header.htm',
		'footer'		=> 'outline/_footer.htm',
		'sub_header'	=> 'outline/_sub_header.htm',
		) );

	$tpl->assign( array(
		pfile	=> basename($_SERVER[PHP_SELF]),
		pdir	=> basename(dirname($_SERVER[PHP_SELF])),
		) );

//페이스북 연동 치환코드
$fb = new Facebook();
$tpl->assign('mfbbnr', $fb->mbfbButton());

//하단주소
$cfg['old_address'] = $cfg['address'];
if($cfg['road_address']) {
	$cfg['address'] = $cfg['road_address'];
} else {
	$cfg['address'] = $cfg['address'];
}

?>