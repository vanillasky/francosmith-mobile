<?

/**
 * MySQL class
 */

class DB
{
	var $db_host, $db_user, $db_pass, $db_conn, $err_report;
	var $count;

	var $page_number=10;

	function DB($iniFile)
	{
		include $iniFile;
		$this->db_host = $db_host;
		$this->db_user = $db_user;
		$this->db_pass = $db_pass;
		$this->connect($db_name);
	}

	function connect($db_name="")
	{
		$this->db_conn = @mysql_connect($this->db_host, $this->db_user, $this->db_pass,true);
		if (!$this->db_conn){
			$err['msg'] = 'DB connection error..';
			$this->error($err);
		}
		if ($db_name) $this->select($db_name);
	}

	function select($db_name)
	{
		$ret = mysql_select_db($db_name);
		if (!$ret){
			$err['msg'] = 'DB selection error..';
			$this->error($err);
		}
	}

	function query($query)
	{
		$time[] = microtime();

		$res = mysql_query($query, $this->db_conn);
		if (preg_match("/^select/",trim(strtolower($query)))) $this->count = $this->count_($res);

		if (!$res){
			$debug = @debug_backtrace();
			if($debug){
				krsort($debug);
				foreach ($debug as $v) $debuginf[] = $v['file']." (line:$v[line])";
				$debuginf = implode("<br>",$debuginf);
			}

			$err['query']	= $query;
			$err['file']	= $debuginf;
			$this->error($err);
		}

		$time[] = microtime();
		$this->time[] = get_microtime($time[0],$time[1]);
		$this->log[] = $query;

		if ($res) return $res;
	}

	function fetch($res,$mode=0)
	{
		if (!is_resource($res)) $res = $this->query($res);
		return (!$mode) ? @mysql_fetch_array($res) : @mysql_fetch_assoc($res);
	}

	function count_($result)
	{
		if(is_resource($result))$rows = mysql_num_rows($result);
		if ($rows !== null) return $rows;
	}

	function tableCheck($tablename)
	{
		$tableQuery	= "show tables like '".$tablename."'";
		if( $this->count_($this->query($tableQuery)) >= 1 ){
			return true;
		}else{
			return false;
		}
	}


	function _escape($var) {
		return mysql_real_escape_string($var,$this->db_conn);
	}

	function _query_print($query) {
		$argList = func_get_args();
		array_shift($argList);
		$this->replaceNum=0;
		$this->replaceArgs=$argList;
		$query = preg_replace_callback('/\[(i|d|s|c|cv|vs|v)\]/',array(&$this,'_queryReplace'), $query);
		return $query;
	}

	function _query($query) {
		$result = mysql_query($query,$this->db_conn);
		if($result) {
			return $result;
		}
		else {
			return false;
		}
	}

	function _last_insert_id() {
		$result = mysql_query("SELECT LAST_INSERT_ID()",$this->db_conn);
		$row = mysql_fetch_row($result);
		return $row[0];
	}

	function _select($query) {
		$result = mysql_query($query,$this->db_conn);
		if(!$result) {
			return false;
		}
		$arResult=array();
		while ($row = mysql_fetch_assoc($result)) {
			$arResult[]=$row;
		}
		return $arResult;
	}

	function _select_page($number,$page,$query) {
		$start = ($page-1)*$number;
		$query= trim($query)." limit $start , $number";

		if(!preg_match('/SQL_CALC_FOUND_ROWS/',$query)) {
			$query = preg_replace("/^select/i","select SQL_CALC_FOUND_ROWS",$query);
		}

		if(!($result = mysql_query($query,$this->db_conn))) {
			return false;
		}

		if(!($c_result = mysql_query("SELECT FOUND_ROWS()",$this->db_conn)))
		{
			return false;
		}
		list($totalcount) = mysql_fetch_row($c_result);

		return $this->__paging($result,$totalcount,$number,$page);
	}

	function _select_manual_page($number,$page,$totalcount,$query) {
		$start = ($page-1)*$number;
		$query= trim($query)." limit $start , $number";
		if(!preg_match("/^select/i",$query)) {
			return false;
		}

		if(!($result = mysql_query($query,$this->db_conn))) {
			return false;
		}

		return $this->__paging($result,$totalcount,$number,$page);
	}

	function __paging($result,$totalcount,$number,$page) {
		$start = ($page-1)*$number;
		$ar_return['record'] = array();
		$count=1;
		while($row = mysql_fetch_assoc($result))
		{
			$row['_no'] =$start+$count;
			$row['_rno'] =$totalcount-($start+$count)+1;
			$ar_return['record'][] = $row;
			$count++;
		}

		if($totalcount%$number)
			$totalpage = (int)($totalcount/$number)+1;
		else
			$totalpage = $totalcount/$number;

		$step = ceil($page/$this->page_number);

		$ar_return['page']=array(
			'totalpage'=>$totalpage,
			'totalcount'=>$totalcount,
			'nowpage'=>$page,
			'page'=>array(),
			'next'=>false,
			'prev'=>false,
			'last'=>false,
			'first'=>false
		);

		if($step*$this->page_number<$totalpage) $ar_return['page']['next']=$step*$this->page_number+1;
		if($step!=1) $ar_return['page']['prev']=($step-1)*$this->page_number;

		if($ar_return['page']['prev']) $ar_return['page']['first']=1;
		if($ar_return['page']['next']) $ar_return['page']['last']=$totalpage;

		if($ar_return['page']['next']) $count=$this->page_number;
		else {
			if($totalpage) $count=$totalpage%$this->page_number ? $totalpage%$this->page_number : $this->page_number;
			else $count=0;
		}

		$loop_start = ($step-1)*$this->page_number+1;
		for($i=0;$i<$count;$i++)
		{
			$ar_return['page']['page'][$i]=$loop_start+$i;
		}

		return $ar_return;
	}



	function _queryReplace($matches) {
		if($matches[1]=='i') {
			$result = (int)$this->replaceArgs[$this->replaceNum];
		}
		elseif($matches[1]=='d') {
			$result = (float)$this->replaceArgs[$this->replaceNum];
		}
		elseif($matches[1]=='s') {
			if(!is_scalar($this->replaceArgs[$this->replaceNum])) {
				die('query_error');
			}
			$result = '"'.mysql_real_escape_string($this->replaceArgs[$this->replaceNum],$this->db_conn).'"';
		}
		elseif($matches[1]=='c') {
			$cols = &$this->replaceArgs[$this->replaceNum];
			if(!(is_array($cols) && count($cols))) {
				die('query_error');
			}
			foreach($cols as $eachCol) {
				if(!preg_match("/[_a-zA-Z0-9-]+/",$eachCol)) {
					die('query_error');
				}
			}
			$result = '('.implode(",",$cols).')';
		}
		elseif($matches[1]=='v') {
			$values = &$this->replaceArgs[$this->replaceNum];
			if(!(is_array($values) && count($values))) {
				die('fff');
			}
			foreach($values as $k=>$eachValue) {
				if(is_null($eachValue)) {
					$values[$k]='null';
				}
				else {
					$values[$k]='"'.mysql_real_escape_string($eachValue,$this->db_conn).'"';
				}

			}
			$result = '('.implode(",",$values).')';
		}
		elseif($matches[1]=='vs') {
			$values = &$this->replaceArgs[$this->replaceNum];
			if(!(is_array($values) && count($values))) {
				die('query_error');
			}
			$arRecord=array();
			foreach($values as $eachValue) {
				foreach($eachValue as $k=>$eachElement) {
					if(is_null($eachElement)) {
						$eachValue[$k]='null';
					}
					else {
						$eachValue[$k]='"'.mysql_real_escape_string($eachElement,$this->db_conn).'"';
					}

				}
				$arRecord[]='('.implode(",",$eachValue).')';
			}
			$result = implode(',',$arRecord);
		}
		elseif($matches[1]=='cv') {
			$colValues = &$this->replaceArgs[$this->replaceNum];
			if(!(is_array($colValues) && count($colValues))) {
				die('query_error');
			}
			$arImplode=array();
			foreach($colValues as $eachCol=>$eachValue) {
				if(is_null($eachValue)) {
					$arImplode[]= $eachCol.'=null';
				}
				else {
					$arImplode[]= $eachCol.'="'.mysql_real_escape_string($eachValue,$this->db_conn).'"';
				}

			}
			$result = implode(",",$arImplode);
		}
		$this->replaceNum++;
		return $result;
	}


	function close()
	{
		$ret = @mysql_close($this->db_conn);
		$this->db_conn = null;
		return $ret;
	}

	function error($err)
	{
		if($this->err_report){
			//msg("정상적인 요청이 아니거나 DB에 문제가 있습니다",-1);
			echo "
			<div style='background-color:#f7f7f7;padding:2'>
			<table width=100% border=1 bordercolor='#cccccc' style='border-collapse:collapse;font:9pt tahoma'>
			<col width=100 style='padding-right:10;text-align:right;font-weight:bold'><col style='padding:3 0 3 10'>
			<tr><td>error</td><td>".mysql_error()."</td></tr>
			";
			foreach ($err as $k=>$v) echo "<tr><td>$k</td><td>$v</td></tr>";
			echo "</table></div>";
			//exit();
		}
	}

	function viewLog()
	{
		echo "
		<table width=800 border=1 bordercolor='#cccccc' style='border-collapse:collapse;font:8pt tahoma'>
		<tr bgcolor='#f7f7f7'>
			<th width=40 nowrap>no</th>
			<th width=100%>query</th>
			<th width=80 nowrap>time</th>
		</tr>
		<col align=center><col style='padding-left:5'><col align=center>
		";
		foreach ($this->log as $k=>$v){
			echo "
			<tr>
				<td>".++$idx."</td>
				<td>$v</td>
				<td>{$this->time[$k]}</td>
			</tr>
			";
		}
		echo "
		<tr bgcolor='#f7f7f7'>
			<td>total</td>
			<td></td>
			<td>".array_sum($this->time)."</td>
		</tr>
		</table>
		";
	}
}

?>