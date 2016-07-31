<?
include '../../shop/lib/library.php';

$id = $_GET[id];
$div = $_GET[div] + 0;

$data = $db->fetch("select * from `".GD_BD_.$id."` where no='".$_GET[no]."'");

$old_file	= explode("|",$data[old_file]);
$new_file	= explode("|",$data[new_file]);

$file_name = $old_file[$div];
$file_size = filesize("../../shop/data/board/$id/$new_file[$div]");

$dir = (!$_GET[thumbnail]) ? "../../shop/data/board/$id/" : "../../shop/data/board/$id/t/";
$file_name = str_replace(';','%3B',$file_name);
if (!$_GET[mode]){

	if (strstr($HTTP_USER_AGENT,"MSIE 5.5")){
		Header("Content-Type: doesn/matter"); 
		Header("Content-Length: ".$file_size); 
		Header("Content-Disposition: filename=$file_name"); 
		Header("Content-Transfer-Encoding: binary"); 
		Header("Pragma: no-cache"); 
		Header("Expires: 0"); 
	} else { 
		Header("Content-type: file/unknown"); 
		Header("Content-Length: ".$file_size); 
		Header("Content-Disposition: attachment; filename=$file_name"); 
		Header("Content-Description: PHP3 Generated Data"); 
		Header("Pragma: no-cache"); 
		Header("Expires: 0"); 
	} 

} else {
	Header("Content-type: ".filetype("$dir/$new_file[$div]"));
}

if ($fp = fopen("$dir/$new_file[$div]", "rb")){
	while (!feof($fp)) {
		$buf = fread($fp, 8196);
		$read = strlen($buf);
		print($buf);
	}
} 
fclose($fp); 
exit(); 

?>