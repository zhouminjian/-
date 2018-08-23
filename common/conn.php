<?php
/*
*	连接数据库
*/
class Conn{
	function connectMysql(){
		$con = mysqli_connect("127.0.0.1","root","","attendance");
		return $con;
	}
	function closeMysql($con){
		mysqli_close($con);
	}
}
?>