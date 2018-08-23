<?php
/*
单独处理某个人的考勤
 */

include_once "conn.php";	
include_once "trac/fillattend.php";

function checksbWorkTime($array){
	$startTime = date('Y-m-d',strtotime($array[0]));
	$endTime = date('Y-m-d',strtotime($array[1]));
	$empNum = $array[2];
	$classType = $array[3];
	if($classType == 1){
		//白班
		$typeA = 0;
	}
	else{
		//夜班
		$typeA = 1;
	}
	$aStr = "update empinfo set EmpType=".$typeA." where EmpNumber=".$empNum.";";
	$clrsql2 = "truncate table empattendanalysis;";
	$conClass = new Conn();
	$con = $conClass->connectMysql();
	mysqli_query($con,$aStr);
	mysqli_query($con,$clrsql2);
	$conClass->closeMysql($con);
	$allData = array();
	$clrsql1 = "truncate table empattendinfo;";
	//格式化考勤时间
	$formatsql = "SELECT * from (select a.EmpNumber,i.EmpName,DATE_FORMAT(a.SwipeCardTime, '%H:%i:%s') as `时间`,DATE_FORMAT(a.SwipeCardTime, '%Y-%m-%d') as `日期` ,i.EmpType from EmpAttendInfo a,EmpInfo i where a.EmpNumber=i.EmpNumber and i.EmpType=".$typeA." and a.EmpNumber=".$empNum.") c order by c.EmpNumber,c.`日期`,c.`时间`;";
	$tmp_startTime = $startTime;
	while(strcmp($tmp_startTime,$endTime)<=0){
		//处理后的结果放collective中
		$collective = array();
		$tmp_nextTime = date('Y-m-d',strtotime($tmp_startTime)+86400);
		//提取员工编号及时间对应的考勤
		if($typeA==1){
			$attendsql = "insert into EmpAttendInfo(EmpNumber,SwipeCardTime) select t.EmpNumber,t.SwipeCardTime from tmp_empattendinfo t where t.EmpNumber=".$empNum." and t.SwipeCardTime BETWEEN '".$tmp_startTime." 12:00:00' and '".$tmp_nextTime." 12:00:00' order by t.SwipeCardTime;";
		}
		else{
			$attendsql = "insert into EmpAttendInfo(EmpNumber,SwipeCardTime) select t.EmpNumber,t.SwipeCardTime from tmp_empattendinfo t where t.EmpNumber=".$empNum." and t.SwipeCardTime BETWEEN '".$tmp_startTime."' and '".$tmp_nextTime."' order by t.SwipeCardTime;";	
		}
		$con = $conClass->connectMysql();
		//处理完一天清空考勤表
		mysqli_query($con,$clrsql1);
		//临时数据复制到考勤信息表EmpAttendInfo
		mysqli_query($con,$attendsql);
		$result = mysqli_query($con,$formatsql);
		$conClass->closeMysql($con);
		$count = $result->num_rows;
		$group = $result->fetch_assoc();
		$flag = false;
		while($group&&!$flag){
			//读取记录
			$EmpNumber = $group['EmpNumber'];
			$EmpName = $group['EmpName'];
			$EmpTime = $group['时间'];
			$EmpDate = $group['日期'];
			$EmpType = $group['EmpType'];
			if($typeA==0){
				//读取下一条记录
				$group = $result->fetch_assoc(); 
				//12点之前签到
				if(strcmp($EmpTime,"12:00:00")<0){
					if(!$group&&!$flag){
						$time = lack($EmpTime,$EmpType);
						//没有下一条记录，缺签退，随机时间大于12点，签退
						if(strcmp($time,"12:00:00")>0){
							$workTime = countWorkTime($EmpDate.$time,$EmpDate.$EmpTime);
							$f = 3;
							// echo $EmpNumber.$EmpTime.'只有一条签到记录<br>';
							$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
							$flag = true;
						}
					}
					else if(strcmp($group['时间'],"12:00:00")>0&&!$flag){
						//12点之后签退
						$workTime = countWorkTime($EmpDate.$group['时间'],$EmpDate.$EmpTime);
						//1表示正常考勤
						$f = 1;
						$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$group['时间'],'WorkTime'=>$workTime,'Flag'=>$f);
						$flag = true;
					}
				}
				else if(strcmp($EmpTime,"12:00:00")>0&&!$flag){
					//第一条为12点之后签退，缺少签到时间
					$time = lack($EmpTime,$EmpType);
					$workTime = countWorkTime($EmpDate.$EmpTime,$EmpDate.$time); 
					$f = 2;
					$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
					$flag = true;
				}
				//白班--end
			}
			else if($typeA==1){ //夜班
				//读取下一条记录
				$group = $result->fetch_assoc(); 
				if(!$group&&!$flag){
					if(strcmp($EmpTime,"12:00:00")>0){
						//缺少签退时间
						$tomorrow = date('Y-m-d',strtotime($EmpDate)+86400);
						$time = lack($EmpTime,$EmpType);
						if(strcmp($time,"12:00:00")<0){
							$workTime = countWorkTime($tomorrow.$time,$EmpDate.$EmpTime); 
							$f = 3;
							$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
							$flag = true;
						}
					}
					else{
						//缺少签到时间
						$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
						$time = lack($EmpTime,$EmpType);
						if(strcmp($time,"12:00:00")>0){
							$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
							$f = 2;
							$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
						}
					}
				}
				else if(!$flag){
					$day = 0;
					$dateStamp1 = strtotime($EmpDate);
					$dateStamp2 = strtotime($group['日期']);
					$day = ($dateStamp2-$dateStamp1)/3600/24;
					if($day == 1){
						//签到时间大于12点
						if(strcmp($EmpTime,"12:00:00")>0){
							//签退时间于第二日小于12点
							if(strcmp($group['时间'],"12:00:00")<0){
								$workTime = countWorkTime($group['日期'].$group['时间'],$EmpDate.$EmpTime);
								//1表示正常考勤
								$f = 1;
								$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$group['时间'],'WorkTime'=>$workTime,'Flag'=>$f);
								$flag = true;
							}
						}
						else{
							//缺少签到 处理(前一天)
							$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
							$time = lack($EmpTime,$EmpType);
							if(strcmp($time,"12:00:00")>0){
								$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
								$f = 2;
								$collective = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
							}
						}
					}
				}

			}
		}
		//缺勤
		if(!$group&&!$flag){
			$beginTime1 = strtotime("7:45:00");
			$endTime1 = strtotime("8:05:00");
			$beginTime2 = strtotime("20:00:00");
			$endTime2 = strtotime("20:20:00");
			$beginTimeA = strtotime("19:45:00");
			$endTimeA = strtotime("20:05:00");
			$beginTimeB = strtotime("8:00:00");
			$endTimeB = strtotime("8:20:00");
			if($typeA==0){
				$tmp1 = rand($beginTime1,$endTime1);
				$tmp2 = rand($beginTime2,$endTime2);
				$tmpT = countWorkTime($tmp_startTime.date("H:i:s", $tmp2) ,$tmp_startTime.date("H:i:s", $tmp1));
			}
			else{
				$tmp1 = rand($beginTimeA,$endTimeA);
				$tmp2 = rand($beginTimeB,$endTimeB);
				$tmpT = countWorkTime(date('Y-m-d',strtotime($tmp_startTime)+86400).date("H:i:s", $tmp2) ,$tmp_startTime.date("H:i:s", $tmp1));
			}
			//根据编号找到姓名
			$nameSQL = "select EmpName from empinfo where EmpNumber=".$empNum;
			$con = $conClass->connectMysql();
			mysqli_query($con,"set names 'utf8'");
			$result = mysqli_query($con,$nameSQL);
			$conClass->closeMysql($con);
			$Number = array('EmpNumber' => $empNum);
			$empName = $result->fetch_assoc();
			$EmpName = array('EmpName' => $empName['EmpName']);
			$FirstSwipeCard = array('FirstSwipeCard' => date("H:i:s", $tmp1));
			$SecondSwipeCard = array('SecondSwipeCard' => date("H:i:s", $tmp2));
			$WorkDate = array('WorkDate' => $tmp_startTime);
			//计算worktime
			$WorkTime = array('WorkTime' =>$tmpT);
			$Flag = array('Flag' => 4);
			//组合数组
			$collective=array_merge($Number,$EmpName,$WorkDate,$FirstSwipeCard,$SecondSwipeCard,$WorkTime,$Flag);
		}
		//放到数组中一次性写入数据库
		array_push($allData,$collective);

		//跳转下一天
		$tmp_startTime = date('Y-m-d',strtotime($tmp_startTime)+86400);
	}
	//数据存储
	$con = $conClass->connectMysql();
	mysqli_query($con,"set names 'utf8'");
	//关闭自动提交
	mysqli_autocommit($con,FALSE); 
	foreach ($allData as $key => $value){
		$sqlstr = "insert into empattendanalysis(EmpNumber,EmpName,WorkDate,FirstSwipeCard,SecondSwipeCard,WorkTime,Flag) VALUES('".$value['EmpNumber']."','".$value['EmpName']."','".$value['WorkDate']."','".$value['FirstSwipeCard']."','".$value['SecondSwipeCard']."','".$value['WorkTime']."','".$value['Flag']."');";
		mysqli_query($con,$sqlstr);
	}
	//执行提交事务
	mysqli_commit($con);
	$conClass->closeMysql($con);
	return $allData;
}


?>