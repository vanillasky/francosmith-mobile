<?
/*********************************************************
* 파일명     :  lib/eAPI.class.php
* 프로그램명 :  eAPI 전용 클래스 파일
* 작성자     :  dn
* 생성일     :  2012.04.02
**********************************************************/
include_once "eAPIlibrary.php";

if(!defined('E_PROTOCOL')) define('E_PROTOCOL', 'http://');		//프로토콜 디파인
if(!defined('E_DOMAIN')) define('E_DOMAIN', $_SERVER['HTTP_HOST']);		//이나무 도메인 디파인
if(!defined('E_PATH')) define('E_PATH', '/shop/partner/eAPI/');		//eAPI 경로 디파인

if(!defined('CHECK_API')) define('CHECK_API', E_PATH.'eCheckApi.php');		//api 체크
if(!defined('GET_GOODS_LIST')) define('GET_GOODS_LIST', E_PATH.'eGoodsList.php');		//상품 리스트 가져오기
if(!defined('GET_MOBILE_DESIGN_DATA')) define('GET_MOBILE_DESIGN_DATA', E_PATH.'eMobileDesignData.php');		//모바일 디자인 데이터 가져오기
if(!defined('GET_MOBILE_DISPLAY_DATA')) define('GET_MOBILE_DISPLAY_DATA', E_PATH.'eMobileDisplayData.php');		//모바일 진열 데이터 가져오기
if(!defined('GET_MOBILE_DESIGN_DATA_EVENT')) define('GET_MOBILE_DESIGN_DATA_EVENT', E_PATH.'eMobileDesignDataEvent.php');		//모바일 디자인 데이터 가져오기(이벤트)
if(!defined('GET_MOBILE_DISPLAY_DATA_EVENT')) define('GET_MOBILE_DISPLAY_DATA_EVENT', E_PATH.'eMobileDisplayDataEvent.php');		//모바일 진열 데이터 가져오기(이벤트)
if(!defined('GET_MOBILE_GOODS_DATA')) define('GET_MOBILE_GOODS_DATA', E_PATH.'eMobileGoodsData.php');		//모바일 상품 데이터 
if(!defined('GET_MOBILE_MYMENU_DATA')) define('GET_MOBILE_MYMENU_DATA', E_PATH.'eMobileMyMenuData.php');		//모바일 마이메뉴 데이터 
if(!defined('GET_MOBILE_CART')) define('GET_MOBILE_CART', E_PATH.'eMobileCart.php');		//모바일 마이메뉴 데이터 

class EAPI{
	
	var $domain;
	var $url;
	var $sno;
	var $param;
	var $data_type;
	var $method_type = 1;
	var $db = null;

	function EAPI()	{
		include '../lib/parsexmlstruc.class.php';	
		include '../lib/xmlWriter.class.php';	
		include '../lib/json.class.php';

		$this->domain = E_DOMAIN;
		GLOBAL $db;
		$this->db = &$db;
	}

	function connectShop($mode, $data=Array(), $type) {
		$this->data_type = $type;
		$mode = strtoLower($mode);
		switch($mode) {
			case 'checkapi' :
				$this->url = E_PROTOCOL.$this->domain.CHECK_API;
				break;
			case 'getgoodslist' :
				$this->url = E_PROTOCOL.$this->domain.GET_GOODS_LIST;
				break;
			case 'getmobiledesigndata' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_DESIGN_DATA;
				break;
			case 'getmobiledisplaydata' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_DISPLAY_DATA;
				break;
			case 'getmobiledesigndataevent' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_DESIGN_DATA_EVENT;
				break;
			case 'getmobiledisplaydataevent' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_DISPLAY_DATA_EVENT;
				break;
			case 'getmobilegoodsdata' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_GOODS_DATA;
				break;
			case 'getmobilemymenudata' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_MYMENU_DATA;
				break;
			case 'getmobilecart' :
				$this->url = E_PROTOCOL.$this->domain.GET_MOBILE_CART;
				break;
			
		}

		$this->param = $this->createData($data) ;
		if($type == 'get') {
			$this->url .= $this->param;
			$this->method_type = 0;
		}
		$ret = $this->curlFunc();
		return $ret;
	}

	function createData($arr) { 
		$data['header'] = $this->getKey();
		$data['req_data'] = $arr['req_data'];
		if($arr['list_data']) $data['list_data'] = $arr['list_data'];
		switch($this->data_type) {
			case 'xml' :
				$ret = $this->createXml($data);			
				break;
			case 'json' :
				$ret = $this->createJson($data);
				break;
			case 'serialize' :
				$ret = $this->createSerialize($data);
				break;
			case 'post' : 
				$tmp_data = array_merge($data['header'], $data['req_data'], $data['list_data']);
				$ret = $this->createPost($tmp_data);
				break;
			case 'get' :
				$tmp_data = array_merge($data['header'], $data['req_data'], $data['list_data']);
				$ret = $this->createGet($tmp_data);
				break;
		}
		return $ret;
	}

	function curlFunc() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, $this->method_type);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		if($this->method_type) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->param);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

		$contents = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if($error) {
			switch($this->data_type) {
				case 'xml' :
					$tmp_res = Array();
					$tmp_res['header']['code'] = '101';
					$tmp_res['header']['msg'] = $error;
					$contents = $this->createXml($tmp_res);
					break;
				case 'json' :
					$tmp_res = Array();
					$tmp_res['header']['code'] = '101';
					$tmp_res['header']['msg'] = $error;
					$contents = $this->createJson($tmp_res);
					break;
				case 'serialize' :
				case 'post' :
				case 'get' :
					$tmp_res = Array();
					$tmp_res['header']['code'] = '101';
					$tmp_res['header']['msg'] = $error;
					$contents = $this->createSerialize($tmp_res);
					break;
			}
		}
		$contents = str_replace('\"', '"', $contents);
		return $contents;
	}

	function getKey() {
		$key_query = $this->db->_query_print('SELECT value FROM gd_env WHERE category=[s] AND name=[s]', 'eAPI', 'mobileshop_key');
		$res_key = $this->db->_select($key_query);
		$key['authentic_key'] = $res_key[0]['value'];
		$key['connection_key'] = 'mobileshop';
		return $key;
	}

	function createXml($data) {
		$rtn_value = Array();
		$xml = new xmlWriter_();
		$this->arrayToXml($xml, 'data', $data);
		$rtn_value['xml_data'] = $xml->getXml();
		unset($xml);
		return $rtn_value;
	}

	function createJson($data) {
		$rtn_value = Array();
		$json = new Services_JSON(16);
		$rtn_value['json_data'] = $json->encode($data);
		unset($json);
		return $rtn_value;
	}

	function createSerialize($data) {
		$rtn_value = Array();
		$rtn_value['serialize_data'] = serialize($data);
		return $rtn_value;
	}

	function createPost($data) {
		$rtn_value = Array();
		if(is_array($data) && !empty($data)) {
			foreach($data as $key => $val) {
				if(is_array($val)) {
					$rtn_value['arr_'.$key] = implode('|',  $val);
				}
				else {
					$rtn_value[$key] = $val;
				}
			}
		}
		return $rtn_value;
	}

	function createGet($data) {
		$tmp_arr = Array();
		if(!empty($data) && is_array($data)) {
			foreach($data as $key=>$val) {

				if(is_array($val)) {
					$tmp_arr[] = 'arr_'.$key.'='.implode('|', $val);
				}
				else {
					$tmp_arr[] = $key.'='.$val;
				}			
			}
		}
		$rtn_value = '?'.implode('&', $tmp_arr);
		return $rtn_value;
	}

	function arrayToXml(&$xml, $key, $val) {
		if (is_array($val)) {
			$xml->push($key);
			foreach($val as $k => $v) {
				$this->arrayToXml($xml, $k, $v);
			}
			$xml->pop();
		}
		else $xml->element($key, $val);		
	}

	function typeToArray($res_data) {	
		switch($this->data_type) {
			case 'xml' :
				$ret_data = $this->xmlToArray($res_data);
				break;
			case 'json' :
				$ret_data = $this->jsonToArray($res_data);
				break;
			case 'serialize' :
				$ret_data = $this->serializeToArray($res_data);
				break;
			default :
				$ret_data = $this->serializeToArray($res_data);	//POST 혹은 GET 방식으로 통신을 할 경우에는 serialize 형태로 return됨.
				break;
		}
		return $ret_data;
	}

	function createArrData($arr) {
		if(is_array($arr[0]['child']) && !empty($arr[0]['child'])) {
			$arr = $arr[0]['child'];

			foreach($arr as $key => $val) {
				if(is_array($val) && !empty($val)) {
					unset($arr[$key]);
					$arr[strToLower($key)] = $this->createArrData($val);
				}
			}
		}
		else {
			$arr = $arr['0']['data'];
		}
		return $arr;
	}

	function xmlToArray($res_data) {
		$parser = new StrucXMLParser();	
		$parser->parse($res_data);
		$rtn_value = $parser->parseOut();
		$ret_data = $this->createArrData($rtn_value['DATA']);
		return $ret_data;
	}

	function itemToArray($res_data) {
		$ret_data = array();
		if(is_array($res_data) && !empty($res_data)) {
			foreach($res_data as $key =>$val) {

				if(strstr($key, 'item')) {
					$ret_data[] = $val;
				}
				else {
					$ret_data[$key] = $val;
				}
			}

		}
		
		return $ret_data;
	}
	/*
	function xmlToArray($xml_data) {
	
		$xml_parser = new xmlParser(trim($xml_data));
		$xml_parser->Parse();
		$ret_arr = $this->objToArray($xml_parser->document);	
		return $ret_arr;
	}

	function objToArray($obj) {
		
		$ret_arr = Array();
		if(!empty($obj)) {
			if(count($obj->tagChildren) > 0) {
				foreach($obj->tagChildren as $row_obj) {
					if($row_obj->tagName == 'item') {
						$ret_arr[] = $this->objToArray($row_obj);
					}
					else {
						$ret_arr[$row_obj->tagName] = $this->objToArray($row_obj);
					}
					
				}
			}
			else {
				$ret_arr = $obj->tagData;
			}
		}
		
		return $ret_arr;
	}
	*/
	function jsonToArray($json_data) {
		$json = new Services_JSON(16);
		$ret_arr = $json->decode($json_data);
		return $ret_arr;
	}

	function serializeToArray($serialize_data) {
		$ret_arr = unserialize($serialize_data);
		return $ret_arr;
	}
	
	function createReturnData($arr) {
		$header = $arr['header'];
		
		$ret_arr = array();
		if($header['code'] == '000') {
			$ret_data = $arr['return_data'];
		}
		else {
			$ret_arr['code'] = $header['code'];
			$ret_arr['msg'] = $header['msg'];

		}
		return $ret_data;
	}
	
	function checkApi($arr, $type='xml') {
		$res = $this->connectShop('checkapi', $arr, $type);
		$res = $this->typeToArray($res);
		return $res;
	}

	function getGoodsList($arr, $type='xml') {
		$res = $this->connectShop('getgoodslist', $arr, $type);
		$res = $this->typeToArray($res);
		return $res;
	}

	function getMobileDesignData($arr, $type='xml') {
		$res = $this->connectShop('getmobiledesigndata', $arr, $type);
		$res = $this->typeToArray($res);
		$res = $this->createReturnData($res);
		$res = $this->itemToArray($res);
		return $res;
	}

	function getMobileDisplayData($arr, $type='xml') {		
		$res = $this->connectShop('getmobiledisplaydata', $arr, $type);
				//debug($res);
		$res = $this->typeToArray($res);
				//debug($res);
		$res = $this->createReturnData($res);
				
		$res = $this->itemToArray($res);

		if($arr['req_data']['display_type'] == '5') {
		
			foreach($res as $row) {		
				$tmp_res[] = $this->itemToArray($row);
			}
			$res = $tmp_res;
		}
		return $res;
	}

	function getMobileDesignDataEvent($arr, $type='xml') {
		$res = $this->connectShop('getmobiledesigndataevent', $arr, $type);
		$res = $this->typeToArray($res);
				//debug($res);
		$res = $this->createReturnData($res);
				//debug($res);
		$res = $this->itemToArray($res);
		return $res;
	}

	function getMobileDisplayDataEvent($arr, $type='xml') {
		$res = $this->connectShop('getmobiledisplaydataevent', $arr, $type);
		$res = $this->typeToArray($res);
		$res = $this->createReturnData($res);
		$res = $this->itemToArray($res);
		return $res;
	}

	function getMobileGoodsData($arr, $type='xml') {
		$res = $this->connectShop('getmobilegoodsdata', $arr, $type);
		$res = $this->typeToArray($res);
		$res = $this->createReturnData($res);
		return $res;
	}

	function getMobileMyMenuData($arr, $type='xml') {
		$res = $this->connectShop('getmobilemymenudata', $arr, $type);
		$res = $this->typeToArray($res);
		$res = $this->createReturnData($res);
		return $res;
	}

	function getMobileCart($arr, $type='xml') {
		$res = $this->connectShop('getmobilecart', $arr, $type);
		$res = $this->typeToArray($res);
		$res = $this->createReturnData($res);
		return $res;
	}

}

?>