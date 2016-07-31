<?php
include dirname(__FILE__).'/../_header.php';

$termsThirdPerson = getTermsGuideContents('terms', 'termsThirdPerson');

$tpl->assign('termsThirdPerson', $termsThirdPerson);
$tpl->print_('tpl');
?>