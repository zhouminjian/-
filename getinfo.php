<?php 

include_once "common/handel.php";
include_once "common/handelsb.php";
if(!isset($_POST["data"])){
	echo "<script>alert('非法访问！');
		window.location.href='/nav';
		</script>";
	exit;
}

//处理前台提交的查询条件
$data = json_decode(stripslashes($_POST["data"]),true);
$array = array();
$k = 0;
for($i=0;$i<count($data)/4;$i++){
	if($data[$i*4]["value"]&&$data[$i*4+1]["value"]&&$data[$i*4+3]["value"]){
		for($j=0;$j<4;$j++){
			$array[$k][$j] = $data[$i*4+$j]["value"];
		}
		$k++;
	}
}
//查询条件处理结束
$obj = array(); 
for($i=0;$i<$k;$i++){
	if(!$array[$i][2]){
		//没有指定查询某个人,查询名单上的所有人
		array_push($obj,checkWorkTime($array[$i]));
	}else{
		array_push($obj,checksbWorkTime($array[$i]));
	}
}
session_start();
$_SESSION["result"] = $obj;
print_r($obj);
?>
