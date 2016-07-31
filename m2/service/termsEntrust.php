<?php
include dirname(__FILE__).'/../_header.php';

$termsEntrust = getTermsGuideContents('terms', 'termsEntrust');

$tpl->assign('termsEntrust', $termsEntrust);
$tpl->print_('tpl');
?>