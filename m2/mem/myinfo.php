<?
/*********************************************************
* ���ϸ�     :  /mem/myinfo.php
* ���α׷��� :	����ϼ� ȸ������ ����
* �ۼ���     :  ar
* ������     :  2015.12.02
**********************************************************/	
include "../_header.php";
include $shopRootDir ."/conf/fieldset.php";
include $shopRootDir ."/conf/mobile_fieldset.php";

$hpauth = Core::loader('Hpauth');

$hpauthRequestData = $hpauth->getAuthRequestData();

chkMemberMobile();

if(SocialMemberService::getPersistentData('social_code')) {
	msg('SNS �������� �α��� �� ȸ������ ������ PC������ �����մϴ�.', -1);
}

if(!$_SESSION['sess']['confirm_password'] && is_file($tpl->template_dir.'/mem/confirm_password.htm')) {
	$tpl->define(array(
		'frmMember' => '/mem/confirm_password.htm'
	));
} else {
	$tpl->define(array(
		'frmMember'	=> '/mem/_form.htm',
	));
}

// �������� ����
if($_SESSION['sess']['endConfirm'] == "y") {
	unset($_SESSION['sess']['confirm_password']);
	unset($_SESSION['sess']['endConfirm']);
}

$mode = 'modMember';
$data = $db->fetch("select MB.*, SC.category from ".GD_MEMBER." AS MB LEFT JOIN ".GD_TODAYSHOP_SUBSCRIBE." AS SC ON MB.m_id = SC.m_id where MB.m_id='$sess[m_id]'");

if (class_exists('validation') && method_exists('validation', 'xssCleanArray')) {
	$data = validation::xssCleanArray($data, array(
		validation::DEFAULT_KEY	=> 'text'
	));
}

$checked['sex'][$data['sex']] = 'checked';
$checked['marriyn'][$data['marriyn']] = 'checked';
$checked['calendar'][$data['calendar']] = 'checked';
$checked['private2YN'] = $data['private2'] == 'y' ? 'checked' : '';
$checked['private3YN'] = $data['private3'] == 'y' ? 'checked' : '';
if ($data['mailling']=='y') $checked['mailling'] = 'checked';
if ($data['sms']=='y') $checked['sms'] = 'checked';

$selected['job'][$data['job']] = 'selected';

$data['phone']	= explode("-",$data['phone']);
$data['mobile']	= explode("-",$data['mobile']);
$data['fax']		= explode("-",$data['fax']);
$data['zipcode']	= explode("-",$data['zipcode']);
$data['birth']	= array(
	substr($data['birth'],0,2),
	substr($data['birth'],2),
);
$data['marridate']= array(
	substr($data['marridate'],0,4),
	substr($data['marridate'],4,2),
	substr($data['marridate'],6,2),
);
$selected['birth_year'][$data['birth_year']] = 'selected';
$selected['birth0'][$data['birth'][0]] = 'selected';
$selected['birth1'][$data['birth'][1]] = 'selected';
$selected['marridate0'][$data['marridate'][0]] = 'selected';
$selected['marridate1'][$data['marridate'][1]] = 'selected';
$selected['marridate2'][$data['marridate'][2]] = 'selected';

foreach ($checked['reqField'] as $k => $v) $required[$k] = 'required';

$tpl->assign($data);

//�߰������׸�
$consentData = $consentRequired = array();
$result = $db->query("SELECT *,GC.sno as sno FROM ".GD_CONSENT." AS GC LEFT JOIN ".GD_MEMBER_CONSENT." AS GMC ON GC.sno = GMC.consent_sno AND GMC.m_no = '".$sess['m_no']."' WHERE GC.useyn = 'y' AND (GMC.m_no IS NULL OR GC.requiredyn='n') ORDER BY GC.sno");
while ($datas = $db->fetch($result)){
	$datas['requiredyn_text'] = $datas['requiredyn'] == 'y' ? '�ʼ�' : '����';
	$datas['consentyn'] = $datas['consentyn'] == 'y' ? 'checked' : '';
	$datas['termsContent'] = htmlspecialchars_decode(@parseCode(htmlspecialchars($datas['termsContent'])));
	$consentData[] = $datas;
}
$tpl->assign('consentData', $consentData);

// ȸ�����Խ� �޴��� ����Ȯ�λ���ϰ� ȸ�� ���Խ� �޴��� ��ȣ ������ �Ұ����ϸ� �޴�����ȣ ���� �Ұ�
$mobileReadonly='';
if($hpauthRequestData['useyn'] == 'y' && $hpauthRequestData['moduseyn'] == 'y') $mobileReadonly = 'readonly';
$tpl->assign('hpauthyn', $hpauthRequestData['useyn']);
$tpl->assign('moduseyn', $hpauthRequestData['moduseyn']);
$tpl->assign('hpauthCPID', $hpauthRequestData['cpid']);
$tpl->assign('mobileReadonly', $mobileReadonly);

### ���Ẹ�ȼ��� ȸ��ó��url
$tpl->assign('memActionUrl',$sitelink->link('mem/indb.php','ssl'));

$tpl->assign('m_id', $sess['m_id']);
$tpl->print_('tpl');
?>