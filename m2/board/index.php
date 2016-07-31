<?php
include "../_header.php";

$boardList = $db->_select('select id from '.GD_BOARD.' order by sno asc');
for($i=0; $i<count($boardList); $i++) {
	$bdUseMobile = '';
	$bdSkin = '';
	$bdName = '';

	$_confPath = dirname(__FILE__).'/../..'.$cfg['rootDir'].'/conf/bd_'.$boardList[$i]['id'].'.php';
	if(file_exists($_confPath)){
		include $_confPath;

		if(($bdUseMobile == 'Y' && ($bdSkin == 'gallery' || $bdSkin == 'default')) ){
			$boardData[$boardList[$i]['id']] = $bdName;
		}
	}
}

$tpl->assign('boardData',$boardData);
$tpl->print_('tpl');
?>