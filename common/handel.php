<?php
/*
处理考勤
 */
include_once "conn.php";	
include_once "trac/fillattend.php";

function checkWorkTime($array){
	$startTime = date('Y-m-d',strtotime($array[0]));
	$endTime = date('Y-m-d',strtotime($array[1]));
	$classType = $array[3];

	if($classType == 1){//甲班白班   定义白班0 夜班1
		$typeA = 0;
		$typeB = 1;
	}
	else{
		//乙班白班
		$typeA = 1;
		$typeB = 0;
	}
	$aStr = "update empinfo set EmpType=".$typeA." where EmpGroup=0;";
	$bStr = "update empinfo set EmpType=".$typeB." where EmpGroup=1;";
	$clrsql2 = "truncate table empattendanalysis;";

	$conClass = new Conn();
	$con = $conClass->connectMysql();
	mysqli_query($con,"set names 'utf8'");
	//员工信息表添加值班信息
	mysqli_query($con,$aStr);
	mysqli_query($con,$bStr);
	mysqli_query($con,$clrsql2);
	$conClass->closeMysql($con);
	$allData = array();
	//每次循环清理表内容
	$clrsql1 = "truncate table empattendinfo;";

	$tmp_startTime = $startTime;
	while(strcmp($tmp_startTime,$endTime)<=0){
		//处理后的结果放collective中
		$collective = array();
		$tmp_nextTime = date('Y-m-d',strtotime($tmp_startTime)+86400);
		$sqlstr1 = "insert into EmpAttendInfo(EmpNumber,SwipeCardTime) select t.EmpNumber,SwipeCardTime from tmp_empattendInfo t,empinfo e where t.EmpNumber=e.EmpNumber and e.EmpType=0 and SwipeCardTime BETWEEN '".$tmp_startTime."' and '".$tmp_nextTime."' order by SwipeCardTime;";
		$sqlstr2 = "insert into EmpAttendInfo(EmpNumber,SwipeCardTime) select t.EmpNumber,SwipeCardTime from tmp_empattendInfo t,empinfo e where t.EmpNumber=e.EmpNumber and e.EmpType=1 and SwipeCardTime BETWEEN '".$tmp_startTime." 12:00:00' and '".$tmp_nextTime." 12:00:00' order by SwipeCardTime;";
		$con = $conClass->connectMysql();
		mysqli_query($con,"set names 'utf8'");
		mysqli_query($con,$clrsql1);
		//临时数据复制到考勤信息表EmpAttendInfo
		mysqli_query($con,$sqlstr1);
		mysqli_query($con,$sqlstr2);
		//白班--start
		$sqlstr = "SELECT * from (select a.EmpNumber,i.EmpName,DATE_FORMAT(a.SwipeCardTime, '%H:%i:%s') as `时间`,DATE_FORMAT(a.SwipeCardTime, '%Y-%m-%d') as `日期` ,i.EmpType from EmpAttendInfo a,EmpInfo i where a.EmpNumber=i.EmpNumber and i.EmpType=0) c order by c.EmpNumber,c.`日期`,c.`时间`;";
		// echo $sqlstr;
		$result = mysqli_query($con,$sqlstr);
		$conClass->closeMysql($con);
		$count = $result->num_rows;
		$group = $result->fetch_assoc();
		
		while($group){
			//读取记录
			$EmpNumber = $group['EmpNumber'];
			$EmpName = $group['EmpName'];
			$EmpTime = $group['时间'];
			$EmpDate = $group['日期'];
			$EmpType = $group['EmpType'];
			//读取下一条记录
			$group = $result->fetch_assoc(); 
			// echo $EmpNumber.$EmpTime.'while0<br>';
			$flag = false;
			//同一个编号,同一天
			if($EmpNumber == $group['EmpNumber']&&strcmp($EmpDate, $group['日期'])==0){
				//未计算
				if(!$flag){
					//12点之前签到
					if(strcmp($EmpTime,"12:00:00")<0){
						//12点之后签退
						if(strcmp($group['时间'],"12:00:00")>0){
							$workTime = countWorkTime($EmpDate.$group['时间'],$EmpDate.$EmpTime);
							//1表示正常考勤
							$f = 1;
							$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$group['时间'],'WorkTime'=>$workTime,'Flag'=>$f);
							$flag = true;
							// echo $EmpNumber.$EmpTime.'<br>';
							//下面如果还有重复的签退，就过滤
							do{
								$EmpNumber = $group['EmpNumber'];
								$EmpName = $group['EmpName'];
								$EmpTime = $group['时间'];
								$EmpDate = $group['日期'];
								$EmpType = $group['EmpType'];
								$group = $result->fetch_assoc();
								// echo $EmpNumber.$EmpTime.'while1<br>';
							}while(strcmp($EmpDate, $group['日期'])==0 && $EmpNumber == $group['EmpNumber']);
						}
						//下一条时间还是<12点——重复签到,继续往下读
						else if(strcmp($group['时间'],"12:00:00")<0){
							// $EmpNumber = $group['EmpNumber'];
							// $EmpName = $group['EmpName'];
							// $EmpTime = $group['时间'];
							// $EmpDate = $group['日期'];
							// $EmpType = $group['EmpType'];
							// //读取下一条记录
							// $group = $result->fetch_assoc();
							// echo $EmpNumber.$EmpTime.'过滤重复签到<br>';
						}
					}
					//只有一条12点之后签退记录
					else if(strcmp($EmpTime,"12:00:00")>0){
						$time = lack($EmpTime,$EmpType);
						$workTime = countWorkTime($EmpDate.$EmpTime,$EmpDate.$time); 
						$f = 2;
						$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
						$flag = true;
						//下面如果还有重复的签退，就过滤
						do{
							$EmpNumber = $group['EmpNumber'];
							$EmpName = $group['EmpName'];
							$EmpTime = $group['时间'];
							$EmpDate = $group['日期'];
							$EmpType = $group['EmpType'];
							$group = $result->fetch_assoc();
							// echo $EmpNumber.$EmpTime.'while2<br>';
						}while(strcmp($EmpDate, $group['日期'])==0 && $EmpNumber == $group['EmpNumber']);
					}
				}
			}
			else if($EmpNumber == $group['EmpNumber']&&strcmp($EmpDate, $group['日期'])!=0){
				//只有上班时间
				if(strcmp($EmpTime,"12:00:00")<0){
					$time = lack($EmpTime,$EmpType);
					//随机时间大于12点，签退
					if(strcmp($time,"12:00:00")>0){
						$workTime = countWorkTime($EmpDate.$time,$EmpDate.$EmpTime);
						$f = 3;
						// echo $EmpNumber.$EmpTime.'只有一条签到记录<br>';
						$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
						$flag = true;
					}
				}
				else if(strcmp($EmpTime,"12:00:00")>0&&!$flag){
					//只有下班时间
					$time = lack($EmpTime,$EmpType);
					$workTime = countWorkTime($EmpDate.$EmpTime,$EmpDate.$time); 
					$f = 2;
					$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
					$flag = true;
				}
			}
			//一个人只有一条签到记录时需另做处理
			if(strcmp($EmpNumber,$group['EmpNumber'])!=0 && !$flag){
				$time = lack($EmpTime,$EmpType);
				//随机时间大于12点，签退
				if(strcmp($time,"12:00:00")>0){
					$workTime = countWorkTime($EmpDate.$time,$EmpDate.$EmpTime);
					$f = 3;
					// echo $EmpNumber.$EmpTime.'只有一条签到记录<br>';
					$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
				}
				//小于12点，签到
				else{
					$workTime = countWorkTime($EmpDate.$EmpTime,$EmpDate.$time); 
					$f = 2;
					// echo $EmpNumber.$EmpTime.'只有一条签退记录<br>';
					$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
				}
			}
		}
		//白班--end
		mysqli_free_result($result);
		//夜班--start
		$sqlstr = "SELECT * from (select a.EmpNumber,i.EmpName,DATE_FORMAT(a.SwipeCardTime, '%H:%i:%s') as `时间`,DATE_FORMAT(a.SwipeCardTime, '%Y-%m-%d') as `日期` ,i.EmpType from EmpAttendInfo a,EmpInfo i where a.EmpNumber=i.EmpNumber and (i.EmpType=1 or i.EmpType=2)) c order by c.EmpNumber,c.`日期`,c.`时间`;";
		$con = $conClass->connectMysql();
		mysqli_query($con,"set names 'utf8'");
		$result = mysqli_query($con,$sqlstr);
		$conClass->closeMysql($con);
		$count = $result->num_rows;
		$group = $result->fetch_assoc();
		while($group){
			//读取记录
			$EmpNumber = $group['EmpNumber'];
			$EmpName = $group['EmpName'];
			$EmpTime = $group['时间'];
			$EmpDate = $group['日期'];
			$EmpType = $group['EmpType'];
			//读取下一条记录
			$group = $result->fetch_assoc(); 
			$flag = false;
			$day = 0;
			//i 测试用的
			$i=1;
			//同一个编号
			while($group && $EmpNumber==$group['EmpNumber']){
				//防死循环 测试--start
				$i++;
				if($i>10) {echo $tmp_startTime." ".$EmpNumber."不正常<br>"; break;}
				//测试--end
				$dateStamp1 = strtotime($EmpDate);
				$dateStamp2 = strtotime($group['日期']);
				$day = ($dateStamp2-$dateStamp1)/3600/24;
				//判断日期是否相隔一天
				if($day == 1){
					//签到时间大于12点
					if(strcmp($EmpTime,"12:00:00")>0){
						//签退时间于第二日小于12点
						if(strcmp($group['时间'],"12:00:00")<0){
							$workTime = countWorkTime($group['日期'].$group['时间'],$EmpDate.$EmpTime);
							//1表示正常考勤
							$f = 1;
							$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$group['时间'],'WorkTime'=>$workTime,'Flag'=>$f);
							$flag = true;
							//过滤重复签退信息
							do{
								$EmpNumber = $group['EmpNumber'];
								$EmpName = $group['EmpName'];
								$EmpTime = $group['时间'];
								$EmpDate = $group['日期'];
								$EmpType = $group['EmpType'];
								$group = $result->fetch_assoc();
							}while(strcmp($EmpDate, $group['日期'])==0 && strcmp($group['时间'],"12:00:00")<0 && $EmpNumber==$group['EmpNumber']);
							continue;
						}
						//第二天>12点，说明没有签退信息
						else if(strcmp($group['时间'],"12:00:00")>0 && !$flag){
							//补充签退时间
							$tomorrow = date('Y-m-d',strtotime($EmpDate)+86400);
							$time = lack($EmpTime,$EmpType);
							if(strcmp($time,"12:00:00")<0){
								$workTime = countWorkTime($tomorrow.$time,$EmpDate.$EmpTime); 
								$f = 3;
								$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
								$flag = true;
							}
						}
						//读取下一条记录
						$EmpNumber = $group['EmpNumber'];
						$EmpName = $group['EmpName'];
						$EmpTime = $group['时间'];
						$EmpDate = $group['日期'];
						$EmpType = $group['EmpType'];
						$group = $result->fetch_assoc();
					}
					else{
						//缺少签到 处理(前一天)
						$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
						$time = lack($EmpTime,$EmpType);
						if(strcmp($time,"12:00:00")>0){
							$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
							$f = 2;
							$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
						}
						//读取下一条记录
						$EmpNumber = $group['EmpNumber'];
						$EmpName = $group['EmpName'];
						$EmpTime = $group['时间'];
						$EmpDate = $group['日期'];
						$EmpType = $group['EmpType'];
						$group = $result->fetch_assoc();
					}
				}
				else{
					if(!$flag && $day==0){
						if(strcmp($EmpTime,"12:00:00")<0){
							//同一天同一时间段，重复签退
							if(strcmp($group['时间'],"12:00:00")<0){
								//读取下一条记录
								$EmpNumber = $group['EmpNumber'];
								$EmpName = $group['EmpName'];
								$EmpTime = $group['时间'];
								$EmpDate = $group['日期'];
								$EmpType = $group['EmpType'];
								$group = $result->fetch_assoc();
							}
							else{
								//缺少签到处理(前一天)
								$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
								$time = lack($EmpTime,$EmpType);
								if(strcmp($time,"12:00:00")>0){
									$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
									$f = 2;
									$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
								}
								//读取下一条记录
								$EmpNumber = $group['EmpNumber'];
								$EmpName = $group['EmpName'];
								$EmpTime = $group['时间'];
								$EmpDate = $group['日期'];
								$EmpType = $group['EmpType'];
								$group = $result->fetch_assoc();
							}
						}
						else if(strcmp($EmpTime,"12:00:00")>0){
							//同一天同一时间段，重复签到
							if(strcmp($group['时间'],"12:00:00")>0){
								//读取下一条记录
								$EmpNumber = $group['EmpNumber'];
								$EmpName = $group['EmpName'];
								$EmpTime = $group['时间'];
								$EmpDate = $group['日期'];
								$EmpType = $group['EmpType'];
								$group = $result->fetch_assoc();
							}
						}
					}
					else if(!$flag && $day>1){
						if(strcmp($EmpTime,"12:00:00")<0){
						//缺少签到时间
							$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
							$time = lack($EmpTime,$EmpType);
							if(strcmp($time,"12:00:00")>0){
								$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
								$f = 2;
								$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
							}
						}
						else if(strcmp($EmpTime,"12:00:00")>0){
						//缺少签退时间
							$tomorrow = date('Y-m-d',strtotime($EmpDate)+86400);
							$time = lack($EmpTime,$EmpType);
							if(strcmp($time,"12:00:00")<0){
								$workTime = countWorkTime($tomorrow.$time,$EmpDate.$EmpTime); 
								$f = 3;
								$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
								$flag = true;
							}
						}
						//读取下一条记录
						$EmpNumber = $group['EmpNumber'];
						$EmpName = $group['EmpName'];
						$EmpTime = $group['时间'];
						$EmpDate = $group['日期'];
						$EmpType = $group['EmpType'];
						$group = $result->fetch_assoc();
					}
				}
			}
			//一个人只有一条签到记录时需另做处理
			if(strcmp($EmpNumber,$group['EmpNumber'])!=0 && !$flag){
				$time = lack($EmpTime,$EmpType);
				//随机时间大于12点，签到
				if(strcmp($time,"12:00:00")>0){
					$yesterday = date('Y-m-d',strtotime($EmpDate)-86400);
					$workTime = countWorkTime($EmpDate.$EmpTime,$yesterday.$time); 
					$f = 2;
					$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$yesterday,'FirstSwipeCard'=>$time,'SecondSwipeCard'=>$EmpTime,'WorkTime'=>$workTime,'Flag'=>$f);
					$flag = true;
				}
				//小于12点，签退
				else{
					//夜班漏签退处理
					$tomorrow = date('Y-m-d',strtotime($EmpDate)+86400);
					$workTime = countWorkTime($tomorrow.$time,$EmpDate.$EmpTime); 
					$f = 3;
					$collective[] = array('EmpNumber'=>$EmpNumber,'EmpName'=>$EmpName,'WorkDate'=>$EmpDate,'FirstSwipeCard'=>$EmpTime,'SecondSwipeCard'=>$time,'WorkTime'=>$workTime,'Flag'=>$f);
					$flag = true;
				}
			}
		}
		//夜班--end
		//释放结果集
		mysqli_free_result($result);
		//数据存储
		$con = $conClass->connectMysql();
		mysqli_query($con,"set names 'utf8'");
		//关闭自动提交
		mysqli_autocommit($con,FALSE); 
		foreach ($collective as $key => $value){
			array_push($allData,$value);
			$sqlstr = "insert into empattendanalysis(EmpNumber,WorkDate,FirstSwipeCard,SecondSwipeCard,WorkTime,Flag) VALUES('".$value['EmpNumber']."','".$value['WorkDate']."','".$value['FirstSwipeCard']."','".$value['SecondSwipeCard']."','".$value['WorkTime']."','".$value['Flag']."');";
			mysqli_query($con,$sqlstr);
		}
		//执行提交事务
		mysqli_commit($con);
		$conClass->closeMysql($con);
		//缺勤一天处理
		//时间根据用户选择调整
		$sqlstr = "SELECT e.EmpNumber,e.EmpName,e.EmpType from empinfo e where e.EmpNumber not in (select EmpNumber from empattendanalysis where WorkDate = '".$tmp_startTime."' group by EmpNumber)";
		$con = $conClass->connectMysql();
		mysqli_query($con,"set names 'utf8'");
		$result = mysqli_query($con,$sqlstr);
		$conClass->closeMysql($con);
		$beginTime1 = strtotime("7:45:00");
		$endTime1 = strtotime("8:05:00");
		$beginTime2 = strtotime("20:00:00");
		$endTime2 = strtotime("20:20:00");
		$beginTimeA = strtotime("19:45:00");
		$endTimeA = strtotime("20:05:00");
		$beginTimeB = strtotime("8:00:00");
		$endTimeB = strtotime("8:20:00");
		//缺勤添加时间
		$con = $conClass->connectMysql();
		mysqli_query($con,"set names 'utf8'");
		//关闭自动提交
		mysqli_autocommit($con,FALSE); 
		while($group = $result->fetch_assoc()){
			$empName = $group['EmpName'];
			if($group['EmpType'] == 0){
				//随机生成7:45-8:00和20:00-20:20之间的时间
				$tmp1 = rand($beginTime1,$endTime1);
				$tmp2 = rand($beginTime2,$endTime2);
				$tmpT = countWorkTime($tmp_startTime.date("H:i:s", $tmp2) ,$tmp_startTime.date("H:i:s", $tmp1));
			}
			else{
				//随机生成19:45-20:00和8:00-8:20之间的时间
				$tmp1 = rand($beginTimeA,$endTimeA);
				$tmp2 = rand($beginTimeB,$endTimeB);
				$tmpT = countWorkTime(date('Y-m-d',strtotime($tmp_startTime)+86400).date("H:i:s", $tmp2) ,$tmp_startTime.date("H:i:s", $tmp1));
			}
			$FirstSwipeCard = array('FirstSwipeCard' => date("H:i:s", $tmp1));
			$SecondSwipeCard = array('SecondSwipeCard' => date("H:i:s", $tmp2));
			$WorkDate = array('WorkDate' => $tmp_startTime);
			//计算worktime
			$WorkTime = array('WorkTime' =>$tmpT);
			$Flag = array('Flag' => 4);
			//组合数组
			$group=array_merge($group,$WorkDate,$FirstSwipeCard,$SecondSwipeCard,$WorkTime,$Flag);
			array_push($allData,$group);
			$sqlstr = "insert into empattendanalysis(EmpNumber,EmpName,WorkDate,FirstSwipeCard,SecondSwipeCard,WorkTime,Flag) VALUES('".$group['EmpNumber']."','".$group['EmpName']."','".$group['WorkDate']."','".$group['FirstSwipeCard']."','".$group['SecondSwipeCard']."','".$group['WorkTime']."','".$group['Flag']."');";
			//写入数据库
			mysqli_query($con,$sqlstr);
			// var_dump($group);echo "<br>";
		}
		//执行提交事务
		mysqli_commit($con); 
		$conClass->closeMysql($con);
		//释放结果集
		mysqli_free_result($result);
		//跳转下一天
		$tmp_startTime = date('Y-m-d',strtotime($tmp_startTime)+86400);
	}
	return $allData;
}

?>