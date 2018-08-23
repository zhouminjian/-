<?php 
//读取excel中的数据
include_once ("../phpexcel/PHPExcel.php");
include_once ("../phpexcel/PHPExcel/Writer/Excel2007.php");
include_once ("../phpexcel/PHPExcel/Writer/Excel5.php");
include_once ("../phpexcel/PHPExcel/IOFactory.php");
if(!isset($_GET["data"])){
	echo "<script>alert('非法访问！');
		window.location.href='/nav';
		</script>";
	exit;
}
ob_end_clean();
session_start();
$variable = $_SESSION["result"];
$objPHPExcel = new \PHPExcel();
$row = 1;
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue("A".$row, '员工编号')
            ->setCellValue("B".$row, '姓名')
            ->setCellValue("C".$row, '工作日期')
            ->setCellValue("D".$row, '签到时间')
            ->setCellValue("E".$row, '签退时间')
            ->setCellValue("F".$row, '工时');

for($i=0;$i<count($variable);$i++) {
	for ($j=0;$j<count($variable[$i]);$j++) {
		$row++;
		$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue("A".$row, $variable[$i][$j]['EmpNumber'])
            ->setCellValue("B".$row, $variable[$i][$j]['EmpName'])
            ->setCellValue("C".$row, $variable[$i][$j]['WorkDate'])
            ->setCellValue("D".$row, $variable[$i][$j]['FirstSwipeCard'])
            ->setCellValue("E".$row, $variable[$i][$j]['SecondSwipeCard'])
            ->setCellValue("F".$row, '=round(IF(TIME(HOUR(D'.$row.'),MINUTE(D'.$row.'),SECOND(D'.$row.'))<TIME(12,0,0),(E'.$row.'-D'.$row.')*24-1,(E'.$row.'-D'.$row.'+TIME(24,0,0))*24+23),2)');
	}
}

$fileName = iconv("utf-8", "gb2312", date("Ymd"));
$objPHPExcel->setActiveSheetIndex(0);
header('Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
header("Content-Disposition:attachment;filename='".$fileName.".xlsx'");  
header('Cache-Control:max-age=0'); 
$objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
$objWriter->save("php://output");
?>