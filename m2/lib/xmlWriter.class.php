<?
/*********************************************************
* 파일명     :  xmlWriter.class.php
* 프로그램명 :  xml Writer 클래스 ( 오픈소스 사용 )
* 작성자     :  이윤섭
* 생성일     :  2012.04.02
**********************************************************/

// Simon Willison, 16th April 2003
// Based on Lars Marius Garshol's Python XMLWriter class
// See http://www.xml.com/pub/a/2003/04/09/py-xml.html

class xmlWriter_ {
	var $xml;
	var $indent;
	var $stack = array();
	function xmlWriter_($indent = '') {
		$this->indent = $indent;
		$this->xml = '<?xml version="1.0" encoding="euc-kr"?>'."\n";
	}
	function _indent() {
		for ($i = 0, $j = count($this->stack); $i < $j; $i++) {
			$this->xml .= $this->indent;
		}
	}
	function push($element, $attributes = array()) {
		$this->_indent();
		$this->xml .= '<'.$element;
		foreach ($attributes as $key => $value) {
			$this->xml .= ' '.$key.'="'.htmlentities($value).'"';
		}
		$this->xml .= ">\n";
		$this->stack[] = $element;
	}
	function element($element, $content, $attributes = array()) {
		$this->_indent();
		$this->xml .= '<'.$element;
		foreach ($attributes as $key => $value) {
			$this->xml .= ' '.$key.'="'.htmlentities($value).'"';
		}
		if (ctype_alnum($content) === false) $content = "<![CDATA[{$content}]]>";
		$this->xml .= '>'.$content.'</'.$element.'>'."\n";
	}
	function emptyelement($element, $attributes = array()) {
		$this->_indent();
		$this->xml .= '<'.$element;
		foreach ($attributes as $key => $value) {
			$this->xml .= ' '.$key.'="'.htmlentities($value).'"';
		}
		$this->xml .= " />\n";
	}
	function pop() {
		$element = array_pop($this->stack);
		$this->_indent();
		$this->xml .= "</$element>\n";
	}
	function getXml() {
		return $this->xml;
	}
	function act($element, $arr, $attri=array()) {
		$cube = create_function('$n', 'return is_int($n) ? 1 : 0;');
		$b = array_map($cube, array_keys($arr));
		$onlyIntKey = (array_sum($b) == count($b) ? true : false);

		if ($onlyIntKey === false) $this->push($element, $attri);
		foreach ($arr as $k => $v) {
			if (is_array($v) === false && is_string($k)){
				$this->element($k, $v);
			}
			else if (is_array($v) === true){
				if (is_int($k)) $this->act($element, $v, array('id' => ($k+1)));
				else $this->act($k, $v);
			}
		}
		if ($onlyIntKey === false) $this->pop();
	}
}


?>