<?
class StrucXMLParser {

	var $parser;
	var $encoding = "ISO-8859-1";
    var $error_code;
    var $error_string;
    var $current_line;
    var $current_column;
    var $data = array();
    var $datas = array();
    var $detailStruc = true;

	function StrucXMLParser(){
		$this->_create();
	}

	function _create(){
		$this->parser = @xml_parser_create($this->encoding);

		if (is_resource($this->parser)) {
//			xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING,0);
			xml_parser_set_option($this->parser,XML_OPTION_SKIP_WHITE,1);
			xml_set_object($this->parser,&$this);
			xml_set_element_handler($this->parser, "startHandler", "endHandler");
			xml_set_character_data_handler($this->parser, "cdataHandler");
			return true;
		}
		return false;

	}

	function free(){
		if (is_resource($this->parser)) {
			xml_parser_free($this->parser);
			unset( $this->parser );
		}
		return null;
	}

	function startHandler($parser, $element, $attr) {
		if ($this->detailStruc){
	        $this->data['child'][$element][] = array('data' => '', 'attr' => $attr, 'child' => array());
	        $this->datas[] =& $this->data;
	        $this->data =& $this->data['child'][$element][count($this->data['child'][$element])-1];
	    }
	    else {
	    	if ($attr[ID]){
		        $this->data[$element][] = '';
		        $this->datas[] =& $this->data;
		        $this->data =& $this->data[$element][count($this->data[$element])-1];
	    	}
		    else {
		        $this->data[$element] = '';
		        $this->datas[] =& $this->data;
		        $this->data =& $this->data[$element];
		    }
	    }
	}

	function endHandler($parser, $element){
        $this->data =& $this->datas[count($this->datas)-1];
        array_pop($this->datas);
	}

	function cdataHandler($parser, $cdata) {
		if ($this->detailStruc){
        	$this->data['data'] .= trim($cdata);
        }
        else if (trim($cdata)){
        	$this->data .= trim($cdata);
        }
	}

	function parse($data) {
        if (!xml_parse($this->parser, $data)) {
            $this->data = array();
            $this->error_code = xml_get_error_code($this->parser);
            $this->error_string = xml_error_string($this->error_code);
            $this->current_line = xml_get_current_line_number($this->parser);          
            $this->current_column = xml_get_current_column_number($this->parser);
        }
        else if ($this->detailStruc){
        	$this->data = $this->data['child'];
        }

		return true;
	}

	function parseOut() {
		return $this->data;
	}
}
/*
사용의 예
$file = "http://".$cfg[shopUrl]."/cband-status-me?xml";

$buffer = file_get_contents($file); xml파일 읽기
$xml = new StrucXMLParser(); xml파싱 클래스 정의
$xml->parse($buffer); xml파싱
$data = $xml->parseOut(); xml파싱 결과를 array로 받는다
*/
?>