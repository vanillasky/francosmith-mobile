<?php
include "../_header.php";

$mode = $_GET[mode];
$sno = $_GET[sno];
$m_no = $_GET[m_no];

$tpl->assign('mode',$mode);
$tpl->assign('sno',$sno);
$tpl->assign('m_no',$m_no);
$tpl->define('tpl',"goods/review_delete.htm"); 
$tpl->print_('tpl');
	
?>
