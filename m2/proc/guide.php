<?php

include dirname(__FILE__).'/../_header.php';
$guideOperate = getTermsGuideContents('guide', 'guideOperate');

$tpl->assign('guideOperate', $guideOperate);
$tpl->print_('tpl');




?>