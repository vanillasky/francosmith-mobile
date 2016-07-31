<?php

include dirname(__FILE__).'/../_header.php';

$termsAgreement = getTermsGuideContents('terms', 'termsAgreement');

$tpl->assign('termsAgreement', $termsAgreement);
$tpl->print_('tpl');

?>