<?php
if(!preg_match('/^[a-zA-Z0-9_]*$/',$_GET['id'])) exit;

include "../_header.php";

include "../..".$cfg['rootDir']."/lib/page.class.php";
include "../..".$cfg['rootDir']."/lib/board.class.php";
include "../..".$cfg['rootDir']."/conf/bd_".$_GET['id'].".php";

if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY => 'text',
		'search'=>'disable',
		'subSpeech'=>'html',
	));

	$_GET['search'] = validation::xssCleanArray($_GET['search'], array(
		validation::DEFAULT_KEY => 'text',
	));
}

if($bdUseMobile != 'Y'){
	msg('해당게시판은 모바일샵에서 미사용중입니다.',-1);
}

if (!is_file("../..".$cfg['rootDir']."/conf/bd_".$_GET['id'].".php")) msg("게시판이 존재하지 않습니다",-1);
if ($bdLvlL && $bdLvlL>$sess['level']) msg("글 목록 권한이 없습니다",-1);
 
### bd class
$bd = new mobile_board($_GET['page'],10);

$bd->db		= &$db;
$bd->tpl	= &$tpl;
$bd->cfg	= &$cfg;
if ( file_exists( dirname(__FILE__) . '/../..'.$cfg['rootDir'].'/data/skin/' . $cfg['tplSkin'] . '/admin.gif') ) $bd->adminicon = 'admin.gif';

$bd->id			= $_GET['id'];
$bd->subSpeech	= $_GET['subSpeech'];
$bd->search		= $_GET['search'];
$bd->sess		= $sess;
$bd->ici_admin	= $ici_admin;
$bd->date		= $_GET['date'];

$bd->assign(array(
			bdSearchMode		=> $bdSearchMode,
			bdUseSubSpeech		=> $bdUseSubSpeech,
			bdSubSpeech			=> $bdSubSpeech,
			bdSubSpeechTitle	=> $bdSubSpeechTitle,
			bdLvlR				=> $bdLvlR,
			bdLvlW				=> $bdLvlW,
			bdStrlen			=> $bdStrlen,
			bdNew				=> $bdNew,
			bdHot				=> $bdHot,
			));

$loop = $bd->getList();
$board_cnt = $bd->recode['total'];

### tpl class
$tpl->assign('list',$loop);
$tpl->assign('ici_admin',$bd->ici_admin);
$tpl->define('list',"board/".$bdSkin."/list.htm");
if ($templateCache->isEnabled() && $templateCache->checkCacingPage() && $templateCache->checkCondition()) {
	$templateCache->setCache($tpl, 'list');
}
if (!$pageView){
	$tpl->print_('list');
}

?>