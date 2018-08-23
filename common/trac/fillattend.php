<?php 
function lack($EmpTime,$EmpType){
	if(strcmp($EmpTime,'12:00:00')>0){
		if($EmpType == 0){
			//echo "缺少签到时间"; // 添加签到时间
			$timeStamp = rand(strtotime("7:40:00"),strtotime("8:10:00"));
			//$collective[] = array('EmpNumber'=>$EmpNumber,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$group['时间'],'WorkTime'=>$workTime);
			return date("H:i:s", $timeStamp);  
		}
		else {
			//echo "缺少签退时间";  //添加签退时间
			$timeStamp = rand(strtotime("7:55:00"),strtotime("8:20:00"));
			return date("H:i:s", $timeStamp); 
		}
	}
	else if(strcmp($EmpTime,'12:00:00')<0){
		if($EmpType == 0){
			//echo "缺少签退时间";
			$timeStamp = rand(strtotime("19:55:00"),strtotime("20:20:00"));
			return date("H:i:s", $timeStamp); 
		}
		else{
			//echo "缺少签到时间";
			$timeStamp = rand(strtotime("19:40:00"),strtotime("20:05:00"));
			return date("H:i:s", $timeStamp); 
		}
	}
}
function countWorkTime($end,$start){
	$tempDate1 = strtotime($end)-strtotime($start);
	$tempDateHour = (int)($tempDate1%(3600*24)/3600);
	$tempDateMin = (int)($tempDate1%3600/60);
	$workTime = round(($tempDateHour+$tempDateMin/60),2)-1;
	return $workTime;
}


 ?>