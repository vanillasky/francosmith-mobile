<?php
if(!preg_match('/^[a-zA-Z0-9_]*$/',$_GET['id'])) exit;
include "../_header.php";
include dirname(__FILE__)."/../..".$cfg['rootDir']."/conf/bd_".$_GET['id'].".php";

if($bdUseMobile != 'Y'){
	msg('해당게시판은 모바일샵에서 미사용중입니다.',-1);
}

$mode = $_GET[mode];

$no = ($mode=="comment") ? $_GET[sno] : $_GET[no];

if (!$mode) $mode = "delete";
$query = ($mode=="comment") ? "select m_no from ".GD_BOARD_MEMO." where sno='".$_GET[sno]."'" : "select m_no from `".GD_BD_.$_GET['id']."` where no='".$_GET[no]."'";
list ($m_no) = $db->fetch($query);

$returnUrl = ($mode=="delete") ? "list.php?".getVars('no') : $_SERVER[HTTP_REFERER];
 
$tpl->define('tpl',"board/$bdSkin/delete.htm"); 
$tpl->print_('tpl');

?>