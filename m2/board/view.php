<?php
if(!preg_match('/^[a-zA-Z0-9_]*$/',$_GET['id'])) exit;

include "../_header.php";

## 변수할당
$id = $_GET['id'];
$no = $_GET['no'];

include_once dirname(__FILE__)."/../..".$cfg['rootDir']."/conf/bd_".$_GET['id'].".php";

include "../..".$cfg['rootDir']."/lib/page.class.php";
include "../..".$cfg['rootDir']."/lib/board.class.php";

if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY => 'text',
	));
}

if($bdUseMobile != 'Y'){
	msg('해당게시판은 모바일샵에서 미사용중입니다.',-1);
}

### bd class
$bd = new mobile_board();

$bd->db  = &$db;
$bd->tpl = &$tpl;
$bd->cfg = &$cfg;
if ( file_exists( dirname(__FILE__) . '/../..'.$cfg['rootDir'].'/data/skin/' . $cfg['tplSkin'] . '/admin.gif') ) $bd->adminicon = 'admin.gif';

$bd->id			= $id;
$bd->sess		= $sess;
$bd->ici_admin	= $ici_admin;

$bd->assign(array(
	bdLvlW			=> $bdLvlW,
	));
 
$bd->bdSkin	= $bdSkin;

if ($bdLvlR && $bdLvlR>$sess[level]) msg("글 보기 권한이 없습니다","list.php?id=".$id);
if ($bdLvlC && $bdLvlC>$sess[level]) $bdDenyComment = true;

$bd->no = $no;
$bd->_view();
$loop[] = $bd->data;

if ($bd->mini_idno) setCookie("mini_idno","{$bd->mini_idno}$_COOKIE[mini_idno]",0);

### tpl class
$tpl->assign(array(
			'id'	=> $id,
			'loop'	=> $loop,
			));
$tpl->define('view',"board/$bdSkin/view.htm");
$tpl->print_('view');

?>