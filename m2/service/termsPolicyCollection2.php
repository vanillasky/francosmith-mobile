<?php
include dirname(__FILE__).'/../_header.php';

$termsPolicyCollection2 = getTermsGuideContents('terms', 'termsPolicyCollection2');

$tpl->assign('termsPolicyCollection2', $termsPolicyCollection2);
$tpl->print_('tpl');
?>