<?
/*********************************************************
* 파일명     :  lib.core.php
* 프로그램명 :  중요함수
* 작성자     :  dn
* 생성일     :  2012.04.02
**********************************************************/

### 배열 stripslashes
function stripslashesArr(&$arr) {
	foreach($arr as $key => $val) {
		if (is_array($val)) stripslashesArr($arr[$key]);
		else $arr[$key] = stripslashes($val);
	}
}

### 클래스 load
function &loadClass() {
	$args = func_get_args();
	$nums = func_num_args();
	for ($i = 0; $i < $nums; $i++) {
		if ($args[$i] != '') include_once dirname(__FILE__).'/'.$args[$i].'.class.php';
	}
}
/*
function &load_class($obj_name,$class_name=null,$arg1=null,$arg2=null,$arg3=null)
{
	static $instances=array();
	if($class_name===null)
	{
		return $instances[$obj_name];
	}
	else if($instances[$obj_name])
	{
		return $instances[$obj_name];
	}
	else
	{
		if(!class_exists($class_name)) {
			$customClassMap = array(
				'Acecounter'=>SHOP_ROOT.'/lib/acecounter.class.php',
				'NaverCheckout'=>SHOP_ROOT.'/lib/naverCheckout.class.php',
				'Crypt_XXTEA'=>SHOP_ROOT.'/lib/xxtea.class.php',
				'Sms'=>SHOP_ROOT.'/lib/sms.class.php',
				'Goods'=>SHOP_ROOT.'/lib/goods.class.php',
				'aMail'=>SHOP_ROOT.'/lib/amail.class.php',
				'Bank'=>SHOP_ROOT.'/lib/bank.class.php',
				'Captcha'=>SHOP_ROOT.'/lib/captcha.class.php',
				'Cart'=>SHOP_ROOT.'/lib/cart.class.php'
			);
			if($customClassMap[$class_name]) {
				include($customClassMap[$class_name]);
			}
			else {
				include(SHOP_ROOT.'/lib/'.$class_name.'.class.php');
			}
		}

		if($obj_name=='READY') {
			return;
		}

		if($arg1===null) $instances[$obj_name]=new $class_name();
		else if($arg2===null) $instances[$obj_name]=new $class_name(&$arg1);
		else if($arg3===null) $instances[$obj_name]=new $class_name(&$arg1,&$arg2);
		else $instances[$obj_name]=new $class_name(&$arg1,&$arg2,&$arg3);
		return $instances[$obj_name];
	}
}
*/
### 브라우져 정보가져오기
function getBrowser() {
	$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	$ptrn_webkit = '/(webkit)[ \/]([\w.]+)/';
	$ptrn_opera = '/(opera)(?:.*version)?[ \/]([\w.]+)/';
	$ptrn_msie = '/(msie) ([\w.]+)/';
	$ptrn_mozilla = '/(mozilla)(?:.*? rv:([\w.]+))?/';
	if (!preg_match($ptrn_webkit, $ua, &$match))
		if (!preg_match($ptrn_opera, $ua, &$match))
			if (!preg_match($ptrn_msie, $ua, &$match))
				if (preg_match('/compatible/', $ua) || !preg_match($ptrn_mozilla, $ua, &$match)) $match = array();

	$match[1] = ($match[1])? $match[1] : '';
	$match[2] = ($match[2])? $match[2] : '0';
	return array('browser'=> $match[1], 'version'=> $match[2]);
}
/*
### Debug
function debug($data)
{
	print "<xmp style=\"font:8pt 'Courier New';background:#000000;color:#00ff00;padding:10\">";
	print_r($data);
	print "</xmp>";
}

function stripslashes_all(&$var) {
	foreach($var as $k=>$v)
	{
		if(is_array(&$var[$k])) stripslashes_all(&$var[$k]);
		else $var[$k]=stripslashes(&$var[$k]);
	}
}


function array_value_cheking($ar_fields,$ar_data) {
	$ar_result = array();
	foreach($ar_data as $field_name=>$value)
	{
		$ar_attr = $ar_fields[$field_name];

		if(strlen($value)==0 && $ar_attr['require']!=true) {
			continue;
		}

		if(strlen($value)==0 && $ar_attr['require']==true) {
			$ar_result[$field_name][] = 'require';
			continue;
		}

		switch($ar_attr['type'])
		{
			case 'int':
				if(!ctype_digit((string)$value)) $ar_result[$field_name][] = 'type';
				break;
			case 'float':
				if(!preg_match('/^-?[0-9]+(\.[0-9]+)?$/',$value)) $ar_result[$field_name][] = 'type';
				break;
			case 'digit':
				if(!ctype_digit((string)$value)) $ar_result[$field_name][] = 'type';
				break;
			case 'alnum':
				if(!ctype_alnum($value)) $ar_result[$field_name][] = 'type';
				break;
		}

		if($ar_attr['max_byte'] && $ar_attr['max_byte']<strlen($value))
		{
			$ar_result[$field_name][] = 'max_byte';
		}
		if($ar_attr['min_byte'] && $ar_attr['min_byte']<strlen($value))
		{
			$ar_result[$field_name][] = 'min_byte';
		}

		if($ar_attr['max_length'] && $ar_attr['max_length']<mb_strlen($value,'EUC-KR'))
		{
			$ar_result[$field_name][] = 'max_length';
		}
		if($ar_attr['min_length'] && $ar_attr['min_length']>mb_strlen($value,'EUC-KR'))
		{
			$ar_result[$field_name][] = 'min_length';
		}
		if($ar_attr['pattern'] && !preg_match($ar_attr['pattern'],$value))
		{
			$ar_result[$field_name][] = 'pattern';
		}

		if($ar_attr['array'] && !in_array($value,$ar_attr['array']))
		{
			$ar_result[$field_name][] = 'array';
		}

		if($ar_attr['callback']) {
			if(!call_user_func($ar_attr['callback'],$value)) {
				$ar_result[$field_name][] = 'callback';
			}
		}
	}
	return $ar_result;
}
*/
?>