<?

include dirname(__FILE__) . "/../_header.php";

$query = "
select * from
	".GD_ORDER." a
	left join ".GD_LIST_BANK." b on a.bankAccount=b.sno
where
	a.ordno='$_GET[ordno]'
";
$data = $db->fetch($query,1);

if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$data = validation::xssCleanArray($data, array(
		validation::DEFAULT_KEY => 'text',
	));
}

### PG �������л���
if(preg_match('/������� : (.*)\n/',$data['settlelog'], $matched)){
	$data['pgfailreason'] = $matched[1];
}

$tpl->assign($data);
$tpl->print_('tpl');

?>