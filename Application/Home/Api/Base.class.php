<?php
/**
 *
 * @since   2017/03/02 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace Home\Api;

use Home\ORG\Response;
use Home\ORG\ReturnCode;

class Base {

	protected $city;
	protected $userInfo;

	public function __construct() {
		$this->city = C('CITY');
		$this->userInfo = C('USER_INFO');
	}

	/**
	 * Ajax正确返回，自动添加debug数据
	 * @param $msg
	 * @param array $data
	 * @param int $code
	 */
	public function ajaxSuccess( $msg, $code = 0, $data = array(),$extra = array() ){
		Response::setSuccessMsg($msg);
		Response::success($data,$code,$extra);
	}
	/**
	 * Ajax正确返回，自动添加debug数据
	 * @param $msg
	 * @param array $data
	 * @param int $code
	 */
	public function ajaxError( $msg, $code = 1, $data = array()){
		Response::setSuccessMsg($msg);
		Response::success($data,$code);
	}

	function get_millisecond()  
	{  
			$time = explode (" ", microtime () );   
			$time = $time [1] . ($time [0] * 1000);   
			$time2 = explode ( ".", $time );   
			$time = $time2 [0]; 
			//153915535639 
			if (strlen($time) == 12) {
				$time = $time.'0';
			}
			return $time;  
	} 
	/**
	 * 云后台链接 获取用户
	 * @param $msg
	 * @param array $data
	 * @param int $code
	 */
	public function bmob($where = array(),$table = "User"){
		include_once 'Public/bmob_php_sdk/lib/BmobObject.class.php';
		$bmobObj = new \BmobObject($table);
		if ($where) {
			$res = $bmobObj -> get('',$where);
		}else{
			$res = $bmobObj -> get();
		}
		
		$res = json_decode(json_encode($res),true);
		return $res;
	}

	/**
	 * 云后台链接 发送短信
	 * @param $msg
	 * @param array $data
	 * @param int $code
	 */
	public function sms($phone,$yzm = ""){
		include_once 'Public/bmob_php_sdk/lib/BmobSms.class.php';
		$sms = new \BmobSms();
		if ($yzm) {
			try{
				$res = $sms->verifySmsCode($phone, $yzm);  //验证短信验证码
			} catch (Exception $e) {
			    
			}
			
		}else{
			$res = $sms->sendSmsVerifyCode($phone, "treeknow");  //发送短信验证码
		}
		
		$res = json_decode(json_encode($res),true);
		//print_r($res);exit;
		return $res;
	}
	/*
	 *  @param $saveWhere ：想要更新主键ID数组
	 *  @param $saveData    ：想要更新的ID数组所对应的数据
	 *  @param $tableName  : 想要更新的表明
	 *  @param $saveWhere  : 返回更新成功后的主键ID数组
	 * */
	public function saveAll($saveWhere,&$saveData,$tableName){
		if($saveWhere==null||$tableName==null)
			return false;
		//获取更新的主键id名称
		$key = array_keys($saveWhere)[0];
		//获取更新列表的长度
		$len = count($saveWhere[$key]);
		$flag=true;
		$model = isset($model)?$model:M($tableName);
		//开启事务处理机制
		$model->startTrans();
		//记录更新失败ID
		$error=[];
		for($i=0;$i<$len;$i++){
			//预处理sql语句
			$isRight=$model->where($key.'='.$saveWhere[$key][$i])->save($saveData[$i]);
			if($isRight==0){
				//将更新失败的记录下来
				$error[]=$i;
				$flag=false;
			}
			//$flag=$flag&&$isRight;
		}
		if($flag ){
			//如果都成立就提交
			$model->commit();
			return $saveWhere;
		}elseif(count($error)>0&count($error)<$len){
			//先将原先的预处理进行回滚
			$model->rollback();
			for($i=0;$i<count($error);$i++){
				//删除更新失败的ID和Data
				unset($saveWhere[$key][$error[$i]]);
				unset($saveData[$error[$i]]);
			}
			//重新将数组下标进行排序
			$saveWhere[$key]=array_merge($saveWhere[$key]);
			$saveData=array_merge($saveData);
			//进行第二次递归更新
			$this->saveAll($saveWhere,$saveData,$tableName);
			return $saveWhere;
		}
		else{
			//如果都更新就回滚
			$model->rollback();
			return false;
		}
	}}
