<?
if(!preg_match('/^[a-zA-Z0-9_]*$/',$_GET['id'])) exit;

## �����Ҵ�
include "../_header.php";

if(class_exists('validation') && method_exists('validation','xssCleanArray')){
	$_GET = validation::xssCleanArray($_GET, array(
		validation::DEFAULT_KEY => 'text',
	));
}

$id		= $_GET['id'];
$no		= $_GET['no'];
$mode	= $_GET['mode'];

include dirname(__FILE__)."/../..".$cfg['rootDir']."/conf/bd_".$_GET['id'].".php";

if($bdUseMobile != 'Y'){
	msg('�ش�Խ����� ����ϼ����� �̻�����Դϴ�.',-1);
}

$b_referer = (!$sess) ? '../mem/login.php?returnUrl=../board/list.php?'.$_SERVER['QUERY_STRING'] : '../board/list.php?'.$_SERVER['QUERY_STRING'];
if($bdLvlW && !$sess && ($mode == "write" || !$mode || $mode == "reply")){
	msg("������ �����ϴ�.",$b_referer);
}
if ($bdLvlW && $bdLvlW > $sess['level'] && ($mode == "write" || !$mode)){
	$level_name = $db->fetch("select grpnm from gd_member_grp where level='".$bdLvlW."'");
	msg($level_name['grpnm']." ����̻� �ۼ��� �����մϴ�.",$b_referer);
}
if ($bdLvlP && $bdLvlP > $sess['level'] && $mode == "reply"){
	$level_name = $db->fetch("select grpnm from gd_member_grp where level='".$bdLvlP."'");
	msg($level_name['grpnm']." ����̻� ��� �ۼ��� �����մϴ�.",$b_referer);
}

if(($mode == 'write' || !$mode) && !$sess){
	$termsPolicyCollectionYn = 'Y';
}

# Anti-Spam ����
$rst = antiSpam(($bdSpamBoard&1 ? '1' : '0'));
if ($rst['code'] <> '0000') msg("���� ��ũ�� �����մϴ�.",-1);

# �����뷮üũ
list( $disk_errno, $disk_msg ) = disk();
if ( !empty( $disk_errno ) ) $bdUseFile="";

if (!$mode) $mode = "write";
$checked['br'] = "checked";

switch ($mode){

	case "modify":

		$data = $db->fetch("select * from ".GD_BD_.$id." where no=".$no);

		if ($data['notice']) $checked['notice']	= "o";
		if ($data['secret']) $checked['secret']	= "o";

		if ($data['old_file']){
			$div = explode("|",$data['old_file']);
			$div2 = explode("|",$data['new_file']);
			for ($tmp='',$i=0; $i < count($div); $i++){
				$tmp .= "<input type=\"hidden\" name=\"del_file[$i]\" />";
				$data['prvFilePath'][] = $cfg['rootDir'].'/data/board/'.$id.'/t/'.$div2[$i];
				$data['prvFileName'][] = $div[$i]; 
			}
			$data['prvFile'] = $tmp;
		}
		break;

	case "reply":
		list ($data['no'],$data['subject']) = $db->fetch("select no,subject from `".GD_BD_.$id."` where no='".$no."'");
	case "write":
		$data['category'] = $_GET['subSpeech'];
}

# ��б� ���� - �ۼ��� �⺻ ��б�
$_secretFlag = 'off';
if($checked['secret'] == 'o') {
	$_secretFlag = 'on';
}
if ($bdSecretChk == 1){
	if($mode != "modify"){
		$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" vlaue='o' /><div class='secret_button on'></div>";
	}
	else{
		$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" vlaue='".$checked['secret']."' /><div class='secret_button ".$_secretFlag."'></div>";
	}
# ��б� ���� - �ۼ��� ������ �Ϲݱ�
}else if ($bdSecretChk == 2){
	$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" value=\"\" /><div class='secret_button off' style='display:none'></div>";

# ��б� ���� - �ۼ��� ������ ��б�
}else if ($bdSecretChk == 3){
	$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" value=\"o\" /><div class='secret_button on'></div>";
}
else{
	# ��б� ���� - �ۼ��� �⺻��
	if($mode != "modify"){
		$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" value='".$checked['secret']."' /><div class='secret_button off'></div>";
	}
	else{
		$inputSecretStr	= "<input type=\"hidden\" name=\"secret\" value='".$checked['secret']."' /><div class='secret_button ".$_secretFlag."'></div>";
	}
}


$chk['secret'] = $inputSecretStr;
$chk['notice'] = '';
if ($ici_admin && $mode!="reply" && !$data['sub']){
	$_flag = 'off';
	if($checked['notice'] == 'o') {
		$_flag = 'on';
	}
	$chk['notice'] = "<input type=\"hidden\" name=\"notice\" value='".$checked['notice']."' ><div class='notice_button ".$_flag."'></div>";
}
# ���Ӹ�
if ($bdUseSubSpeech){
	$subSpeech	= explode("|",$bdSubSpeech);
	foreach ($subSpeech AS $sKey => $sVal){
		$chk['subSpeech'] = ($data['category']==$sVal) ? "selected" : "";
		$speechBox .= "<option value=\"".$sVal."\" ".$chk['subSpeech'].">".$sVal."</option>";
	}
	$data['subSpeech'] = "<select name=\"subSpeech\" class=\"speechBox\">".$speechBox."</select>";
}

$maxFileNumber = 12;

$termsPolicyCollection3 = getTermsGuideContents('terms', 'termsPolicyCollection3', 'Y');
$tpl->assign('termsPolicyCollection3', $termsPolicyCollection3);

### ���Ẹ�ȼ��� ȸ��ó��url
$tpl->assign('boardwriteActionUrl',$sitelink->link_mobile('board/write_ok.php','ssl'));

if ($data) $tpl->assign($data);
if ($div) $tpl->assign(array(file => $div));
$tpl->assign(array(
			id		=> $id,
			mode	=> $mode,
			page	=> $page
			));

$tpl->define('write',"board/".$bdSkin."/write.htm");
$tpl->print_('write');
?>