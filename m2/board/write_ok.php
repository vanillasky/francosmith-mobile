<?
@include dirname(__FILE__) . "/../lib/library.php";
include dirname(__FILE__)."/../..".$cfg['rootDir']."/lib/upload.lib.php";

if (!isset($_POST['id'])) {
	msg("�������� �뷮�� ������ �� (".ini_get('post_max_size').")�� �ʰ��Ͽ����ϴ�",-1);
}

if(!preg_match('/^[a-zA-Z0-9_]*$/',$_POST['id'])) exit;
include dirname(__FILE__)."/../..".$cfg['rootDir']."/conf/bd_".$_POST['id'].".php";

foreach ($_FILES["file"]["error"] as $key => $error)
{
	if ($error === UPLOAD_ERR_INI_SIZE){
		$fileMaxSize =  (isset($bdMaxSize) && $bdMaxSize != null) ? byte2str($bdMaxSize) : ini_get("upload_max_filesize");
		msg("���� ���ε� �ִ� ������� ".$fileMaxSize."�Դϴ�",-1);
	}
}

if(class_exists('validation') && method_exists('validation', 'xssCleanArray')){
	$_POST = validation::xssCleanArray($_POST , array(
		validation::DEFAULT_KEY=>'text',
		'contents'=>'disable',
		'subject'=>'disable',
		'captcha_key'=>'disable',
		'mode'=>'disable',
		'page'=>'disable',
		'chkSpamKey'=>'disable',
		'subSpeech'=>'disable',
		));
}

if($bdUseMobile != 'Y'){
	msg('�ش�Խ����� ����ϼ����� �̻�����Դϴ�.',-1);
}

if($sess) {
	$_POST['name'] = $_SESSION['member']['name'];
	$_POST['email'] = $_SESSION['member']['email'];
}

$_POST['subject'] = html_entity_decode($_POST['subject']);
$_POST['contents'] = html_entity_decode($_POST['contents']);

$_POST['contents'] = nl2br($_POST['contents']);

if(class_exists('validation') && method_exists('validation', 'xssCleanArray')){
	$_POST['contents'] = validation::xssClean($_POST['contents'], $bdUseXss, 'ent_compat', $bdAllowPluginTag, $bdAllowPluginDomain);
	$_POST['subject'] = validation::xssClean($_POST['subject'],$bdUseXss , 'ent_compat' , $bdAllowPluginTag , $bdAllowPluginDomain);
	$_POST['subSpeech'] = validation::xssClean($_POST['subSpeech'],$bdUseXss , 'ent_compat' , $bdAllowPluginTag , $bdAllowPluginDomain);
}

if ($bdLvlW && $bdLvlW>$sess[level] && $_POST['mode']=="write") msg("�� �ۼ� ������ �����ϴ�",-1);
if ($bdLvlP && $bdLvlP>$sess[level] && $_POST['mode']=="reply") msg("��� �ۼ� ������ �����ϴ�",-1);

# Anti-Spam ����
$switch = ($bdSpamBoard&1 ? '123' : '000') . ($bdSpamBoard&2 ? '4' : '0');
$rst = antiSpam($switch, "board/write.php", "post");
if (substr($rst[code],0,1) == '4') msg("�ڵ���Ϲ������ڰ� ��ġ���� �ʽ��ϴ�. �ٽ� �Է��Ͽ� �ֽʽÿ�.",-1);
if ($rst[code] <> '0000') msg("���� ��ũ�� �����մϴ�.",-1);

//* bd class

if($_POST['mode']=="reply")
{
	$query = "select no from `".GD_BD_.$_POST[id]."` where no='".$_POST['no']."'";
	list($tmp) = $db->fetch($query);
	if(!$tmp) msg("������ �����Ǿ� �亯���� ���� �� �����ϴ�",-1);
}

$bd = Core::loader('miniSave');

$bd->db		= &$db;
$bd->id		= $_POST[id];
$bd->no		= $_POST[no];
$bd->mode	= $_POST[mode];
$bd->isMobileBoard	= true;
$bd->sess	= $sess;
$bd->ici_admin	= $ici_admin;
$bd->bdMaxSize	= isset($bdMaxSize) ? $bdMaxSize : ini_get("upload_max_filesize");
$bd->exec_();

// ������ĳ�� �ʱ�ȭ
$templateCache = Core::loader('TemplateCache');
$templateCache->clearCacheByClass('board');

$loc_url =  $sitelink->link_mobile("board/list.php?id=$_POST[id]&".getReUrlQuery('no,id,mode', $_SERVER[HTTP_REFERER]),"regular");
go($loc_url);
?>