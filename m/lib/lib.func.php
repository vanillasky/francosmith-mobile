<?php

### ��ǰ�̹���
/*
$hidden		0	�Ϲ� ����� ������
			1	������ ������
			2	������ ������ (onerror�� hidden)
			3	���������
*/

function goodsimgMobile($arrSrc,$size='',$tmp='',$hidden='')
{
	global $cfg;
	
	if(is_array($arrSrc)){
		foreach($arrSrc as $r){	if($r){ $src = $r; break;}}
	}
	else{
		$src = $arrSrc;
	}

	if(!preg_match('/http:\/\//',$src)){
		if ($hidden) $path = "../";
		$path .= $cfg['rootDir']."/data/goods/";
		if ($hidden==3) $path = "http://".$GLOBALS[cfg][shopUrl].$GLOBALS[cfg][rootDir]."/data/goods/";
	}
	if ($size){
		$size = explode(",",$size);
		$vsize = " width=$size[0]";
		if ($size[1]) $vsize .= " height=$size[1]";
	}
	if ($tmp) $tmp = " ".$tmp;

	if ($size[0]>300) $nosize = 500;
	else if ($size[0]>130) $nosize = 300;
	else if ($size[0]>100) $nosize = 130;
	else $nosize = 100;

	$onerror = ($hidden<2) ? "onerror=\"this.src='".$GLOBALS[cfg][rootDir]."/data/skin/".$GLOBALS[cfg][tplSkin]."/img/common/noimg_$nosize.gif'\"" : "onerror=\"this.style.display='none'\"";

	return "<img src='$path{$src}'{$vsize}{$tmp} $onerror>";
}

/* ����or�迭�� euckr�� ���� */
function utf8ToEuckr($obj){
	if(is_array($obj)){
		foreach($obj as $k=>$v) $obj[$k] = utf8ToEuckr($v);
	}
	else{
		$obj = iconv('utf-8','euc-kr',$obj);
	}
	return $obj;
}

### ȸ����������
function chkMemberMobile()
{
	if (!$GLOBALS[sess]) msg("�α����ϼž� �� ���񽺸� �̿��Ͻ� �� �ֽ��ϴ�","../mem/login.php?returnUrl=".urlencode($_SERVER['REQUEST_URI']));
}


### ȸ���������� - shoptouch
function chkMemberShopTouch($guest=0)
{
	if($guest) {
		if (!$GLOBALS[sess]) msg("�α����ϼž� �� ���񽺸� �̿��Ͻ� �� �ֽ��ϴ�","../shopTouch_mem/login.php?guest=1&returnUrl=".urlencode($_SERVER['REQUEST_URI']));
	}
	else {
		if (!$GLOBALS[sess]) msg("�α����ϼž� �� ���񽺸� �̿��Ͻ� �� �ֽ��ϴ�","../shopTouch_mem/login.php?returnUrl=".urlencode($_SERVER['REQUEST_URI']));
	}
	
}

?>