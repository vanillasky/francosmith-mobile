<?
/*********************************************************
* ���ϸ�     :  /mem/nomember_order.php
* ���α׷��� :	����ϼ� ��ȸ�� �ֹ�Ȯ��
* �ۼ���     :  dn
* ������     :  2012.07.10
**********************************************************/	
include "../_header.php";

### ȸ����������
if( $sess ){
	msg("������ �α��� ���Դϴ�.",$code=-1 );
}

if (!$_GET['returnUrl']) $returnUrl = $_SERVER['HTTP_REFERER'];
else $returnUrl = $_GET['returnUrl'];

$tpl->assign('url',$url);
$tpl->print_('tpl');

?>