<?php
/*
	导入名单
 */
if($_FILES["file1"]["error"] > 0){
	echo "<script>alert('上传出错！错误代码：'+".$_FILES['file1']['error'].");
		window.location.href='/nav/upload/uploadattendinfo.html';
		</script>";
}
else{
	//读取excel中的数据
	include_once ("../../phpexcel/PHPExcel.php");
	include_once ("../../phpexcel/PHPExcel/Writer/Excel2007.php");
	include_once ("../../phpexcel/PHPExcel/Writer/Excel5.php");
	include_once ("../../phpexcel/PHPExcel/IOFactory.php");
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	//上传到指定目录
	if(is_uploaded_file($_FILES["file1"]["tmp_name"])){
		$filepath = "../uploadfile/attend/".$_FILES["file1"]["name"];
		if(move_uploaded_file($_FILES["file1"]["tmp_name"],$filepath)){
			$objPHPExcel = $objReader->load($filepath);
			$sheet = $objPHPExcel->getSheet(0);
			$highestRow = $sheet->getHighestRow(); // 取得总行数

			//连接数据库
			require_once '../../common/conn.php';
			$conClass = new Conn();
			$con = $conClass->connectMysql();
			mysqli_query($con,'set names utf8');
			//清空旧人员信息表
			$clrsql = "truncate table tmp_empattendinfo;";
			mysqli_query($con,$clrsql);
			//关闭自动提交事务
			mysqli_autocommit($con,FALSE);
			$j = 2;
			for(;$j<=$highestRow;$j++){
				$A = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();
				$B = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();
				//检测excel内容格式
				if(!(is_numeric($A) && strtotime($B)))
					break;
				$sql = "insert into tmp_empattendinfo(EmpNumber,SwipeCardTime) values('$A','$B');";
				mysqli_query($con,$sql);
			}
			if($j>$highestRow){
				mysqli_commit($con);//执行sql
				echo "<script>alert('上传成功,共".$highestRow."行数据');
					window.location.href='/nav/upload/uploadattendinfo.html';
					</script>";
			}
			else{
				echo "<script>alert('上传失败，注意格式');
					window.location.href='/nav/upload/uploadattendinfo.html';
					</script>";
			}
			mysqli_close($con);
		}
		else{
			echo "<script>alert('上传到目录出错');
				window.location.href='/nav/upload/uploadattendinfo.html';
				</script>";
		}
	}
}
?>