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
	public function ajaxSuccess( $msg, $code = 0, $data = array()){
		Response::setSuccessMsg($msg);
		Response::success($data,$code);
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

	/**
	 * 云后台链接 获取用户
	 * @param $msg
	 * @param array $data
	 * @param int $code
	 */
	public function bmob($where = array()){
		include_once 'Public/bmob_php_sdk/lib/BmobObject.class.php';
    	$bmobObj = new BmobObject("User");

    	$res = $bmobObj -> get();
    	$res = json_decode(json_encode($res),true);
    	return $res;
	}
}