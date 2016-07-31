<?
/*********************************************************
* 파일명     :  /mem/nomember_order.php
* 프로그램명 :	모바일샵 비회원 주문확인
* 작성자     :  dn
* 생성일     :  2012.07.10
**********************************************************/	
include "../_header.php";

### 회원인증여부
if( $sess ){
	msg("고객님은 로그인 중입니다.",$code=-1 );
}

if (!$_GET['returnUrl']) $returnUrl = $_SERVER['HTTP_REFERER'];
else $returnUrl = $_GET['returnUrl'];

$tpl->assign('url',$url);
$tpl->print_('tpl');

?>