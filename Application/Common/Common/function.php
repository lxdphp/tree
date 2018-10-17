<?php

/**
 * 获取HTTP全部头信息
 */
if (!function_exists('apache_request_headers')) {
	function apache_request_headers(){
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach ($_SERVER as $key => $val) {
			if (preg_match($rx_http, $key)) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = explode('_', $arh_key);
				if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
					foreach ($rx_matches as $ak_key => $ak_val)
						$rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}

		return $arh;
	}
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @param  string $auth_key 要加密的字符串
 * @return string
 * @author jry <598821125@qq.com>
 */
function user_md5($str, $auth_key = ''){
	if(!$auth_key){
		$auth_key = C('AUTH_KEY');
	}
	return '' === $str ? '' : md5(sha1($str) . $auth_key);
}

/**
 * @param     $url
 * @param int $timeOut
 * @return bool|mixed
 */
if (!function_exists('curlGet')) {
	function curlGet($url, $timeOut = 10){
		$oCurl = curl_init();
		if (stripos($url, "https://") !== false) {
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeOut);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if (intval($aStatus["http_code"]) == 200) {
			return $sContent;
		} else {
			return false;
		}
	}
}

/**
 * @description
 * @param string $type
 * @param array $data
 * @param array $file  需要单独上传可以填写 $file 单个的对象
 * @param bool $watermark  是否添加水印 默认添加
 * @return mixed
 * @author
 */
function uploadfiles($type='common',$data=array(),$file=array(),$watermark=true){

	switch ($type) {
		case 'admin':
			$pathurl = './Public/Upload/admin/';
			break;
		case 'home':
			$pathurl = './Public/Upload/home/';
			break;
		case 'common':
			$pathurl = './Public/Upload/common/';
			break;
		default:
			$type   = 'common';
			$pathurl = './Public/Upload/common/';
			break;
	}
	$config = array(
		'rootPath' =>  $pathurl, // 设置附件上传根目录
		'maxSize' =>  1024*1024*2, // 设置附件上传大小 2M
		'exts' =>  array('jpg', 'gif', 'png', 'jpeg')// 设置附件上传类型
	);
	$upload = new Upload($config);
	$image = new Image();

	$watermark_url = $_SERVER['DOCUMENT_ROOT'].PUBLIC_PATH.'images/watermark/shuiyin_big_icon_liu.png'; //水印地址
	if($file){
		$info = $upload->uploadOne($file);// 如果不是二维数组，使用单文件依次上传的方法
		unset($file);
		$arr = $info;
		$arr['filepath'] = 'Upload/'.$type.'/'.$info['savepath'].$info['savename'];
		$arr['error_m'] = '';
		$arr['code'] = 0;
		if(!$info){
			$error_m = $upload->getError();
			$arr['code'] = 1;
			$arr['error_m'] = $error_m;
		}else{
			if($watermark) {
				//原图地址
				$img_url = $_SERVER['DOCUMENT_ROOT'] . PUBLIC_PATH . $arr['filepath'];
				/*
				 *  IMAGE_WATER_NORTHWEST =   1 ; //左上角水印
					IMAGE_WATER_NORTH     =   2 ; //上居中水印
					IMAGE_WATER_NORTHEAST =   3 ; //右上角水印
					IMAGE_WATER_WEST      =   4 ; //左居中水印
					IMAGE_WATER_CENTER    =   5 ; //居中水印
					IMAGE_WATER_EAST      =   6 ; //右居中水印
					IMAGE_WATER_SOUTHWEST =   7 ; //左下角水印
					IMAGE_WATER_SOUTH     =   8 ; //下居中水印
					IMAGE_WATER_SOUTHEAST =   9 ; //右下角水印
				 * */
				//添加水印
				$image->open($img_url)->water($watermark_url, 3, 80)->save($img_url);
			}
		}
	}else{
		foreach($_FILES as $key => $value){
			if((count($_FILES[$key]) == count($_FILES[$key],1) && !empty($_FILES[$key]['tmp_name']) )){//判断$_FILES变量是否是二维数组
				$info = $upload->uploadOne($_FILES[$key]);// 如果不是二维数组，使用单文件依次上传的方法
				unset($_FILES[$key]);
				$arr[$key] = $info;
				$arr[$key]['filepath'] = 'Upload/'.$type.'/'.$info['savepath'].$info['savename'];
				$arr[$key]['error_m'] = '';
				$arr[$key]['code'] = 0;

				if(!$info){
					$error_m = $upload->getError();
					$arr[$key]['code'] = 1;
					$arr[$key]['error_m'] = $error_m;
				}else{
					if($watermark) {
						//原图地址
						$img_url = $_SERVER['DOCUMENT_ROOT'] . PUBLIC_PATH . $arr[$key]['filepath'];
					
						//添加水印
						$image->open($img_url)->water($watermark_url,3,80)->save($img_url);
					}
				}
			}
		}
		
	}

	/*if(count($_FILES)){
		$info = $upload->upload();// 如果是二维数组，使用批量上传文件的方法(上传文件时，每个文件域的name属性是未知的或者以数组形式定义的)
		if(!$info){
			$upload->errorMsg($upload->getError());
			exit;
		}
		$arr['array'] = $info;//数组上传的返回信息全部在键名为array的
		$arr['array'] = 'Upload/'.$type.'/'.$info['savepath'].$info['savename'];
	}*/

	unset($upload);
	return $arr;
}