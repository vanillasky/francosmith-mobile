<?php
include dirname(__FILE__).'/../_header.php';

$sno = $_GET['sno'];

$consent = $db->fetch("SELECT title,termsContent FROM ".GD_CONSENT." WHERE sno = '".$sno."'");
$consent['termsContent'] = htmlspecialchars_decode(@parseCode(htmlspecialchars($consent['termsContent'])));

$tpl->assign('consent', $consent);
$tpl->print_('tpl');
?>