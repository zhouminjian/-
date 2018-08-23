<?php 
/*
格式化核对好的考勤
 */
if($_GET["data"]!=1){
	echo "<script>alert('非法访问！');
		window.location.href='/nav/upload/uploadformat.html';
		</script>";
	exit;
}
include_once "conn.php";
$sql1 = "select * from empformat;";
$sql2 = "select * from empnumber";
$conClass = new Conn();
$con = $conClass->connectMysql();
$result = mysqli_query($con,$sql1);
$numberResult = mysqli_query($con,$sql2);
$conClass->closeMysql($con);
$formatArray = array();
while($group = $result->fetch_assoc()){
	$tmpWorkDate = date_format(date_create($group['WorkDate']),"Y/m/d");
	$tmpNumResult = $numberResult;
	//把考勤号码替换成access数据库中的userid
	while($g = $numberResult->fetch_assoc()){
		if($g['Badgenumber']==$group['EmpNumber'])
		{
			$group['EmpNumber'] = $g['USERID'];
			break;
		}
	}
	//签到
	$tmpArray1 = array('EmpNumber'=>$group['EmpNumber'],'Time'=>$tmpWorkDate.' '.$group['FirstSwipeCard']);
	if(strcmp($group['SecondSwipeCard'],"12:00:00")<0){
		//夜班签退为第二日
		$tmpWorkDate = date('Y/m/d',strtotime($group['WorkDate'])+86400);
	}
	//签退
	$tmpArray2 = array('EmpNumber'=>$group['EmpNumber'],'Time'=>$tmpWorkDate.' '.$group['SecondSwipeCard']);
	array_push($formatArray, $tmpArray1);
	array_push($formatArray, $tmpArray2);
}

//导出excel
include_once ("../phpexcel/PHPExcel.php");
include_once ("../phpexcel/PHPExcel/Writer/Excel2007.php");
include_once ("../phpexcel/PHPExcel/IOFactory.php");
ob_end_clean();
$objPHPExcel = new \PHPExcel();
$row = 1;
$objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setName('宋体');
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue("A".$row, 'USERID')
            ->setCellValue("B".$row, 'CHECKTIME')
            ->setCellValue("C".$row, 'CHECKTYPE')
            ->setCellValue("D".$row, 'VERIFYCODE')
            ->setCellValue("E".$row, 'SENSORID')
            ->setCellValue("F".$row, 'Memoinfo')
            ->setCellValue("G".$row, 'WorkCode')
            ->setCellValue("H".$row, 'sn')
            ->setCellValue("I".$row, 'UserExtFm');
foreach ($formatArray as $key => $value) {
	$row++;
	$objPHPExcel->setActiveSheetIndex(0)
            	->setCellValue("A".$row,$value['EmpNumber'])
            	->setCellValue("B".$row,$value['Time'])
            	->setCellValue("C".$row,'I')
            	->setCellValue("D".$row,rand(1,2))
            	->setCellValue("E".$row,rand(1,2))
            	->setCellValue("G".$row,'0')
            	->setCellValue("I".$row,0);
    $objPHPExcel->getActiveSheet()->getStyle('B'.$row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_TIME4);
}
$fileName = iconv("utf-8", "gb2312", date("Ymd").'Access直接导入');
$objPHPExcel->setActiveSheetIndex(0);
header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
header("Content-Disposition:attachment;filename='".$fileName.".xlsx'");  
header('Cache-Control:max-age=0'); 
$objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
$objWriter->save("php://output");
?>