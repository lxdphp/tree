<?php
/**
 * 家族
 * @since   2017/04/24 创建
 * @author  lingxiao
 */

namespace Home\Api;


use Home\ORG\Str;

class Family extends Base {
	public function index() {
	   
	}

	/**
	 * 添加家族
	 * @access public
	 * @param 
	 * @param array $options  家族数组
	 * @return json
	 */
	public function addfamily(){
		$postData = I('.post');
		$uid = I('uid');
		//权限todu

		//数据整理todu

		$arr = array();
		$arr['honorary_chieftain_uid'] = '';
		$arr['chieftain_uid'] = '';
		$arr['creator_uid'] = $uid;
		$arr['category'] = '';
		$arr['name'] = $postData['name'];
		$arr['create_time'] = time();
		$arr['update_time'] = 0;
		$arr['status'] = 0;
		$arr['desc'] = '';
		//存入数据
		$res = M('t_family') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 添加家族成员
	 * @access public
	 * @param 
	 * @param array $options  家族成员数组
	 * @return json
	 */
	public function addmember(){
		$postData = I('.post');

		//权限todu

		//数据整理todu
		//是否已是家族成员
		$res = M('t_family_member')->field('phone')->where(array('phone'=>$postData['phone']))->find();
		if ($res['phone']) {
			$this -> ajaxError('用户已是家族成员！');
		}
		//如果该用户不是系统用户,发送一条邀请消息并且返给前端手机号,如果是系统用户,俩中情况,一是家族成员,不可添加;二是家族创建者,发送家族合并消息
		$res = $this ->bmob(array('where={"phonenum":'.$postData['phone'].'}'));
		if (empty($res['results'])) {
			
		}else{
			
		}

		$arr = array();
		$arr['uid'] = '';
		$arr['paternity_fid'] = '';
		$arr['matrilineal_fid'] = '';
		$arr['spouse_paternity_fid'] = '';
		$arr['spouse_matrilineal_fid'] = '';
		$arr['surname'] = $postData['surname'];
		$arr['name'] = $postData['name'];
		$arr['phone'] = $postData['phone'];
		//$arr['relationship_code'] = '';
		$arr['generational_code'] = $postData['generational_code'];
		$arr['father_creator_uid'] = '';
		$arr['mother_creator_uid'] = '';
		$arr['creator_uid'] = $uid;
		$arr['create_time'] = time();
		$arr['update_time'] = 0;
		$arr['status'] = 0;
		
		//存入数据
		$res = M('t_family_member') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 发送邀请信息
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function Invitation(){
		$postData = I('.post');

		//权限todu

		//数据整理todu

		$arr = array();
		$arr['msg_type'] = '';
		$arr['from_uid'] = '';
		$arr['to_uid'] = '';
		$arr['fid'] = '';
		$arr['is_read'] = 0;
		$arr['datetime'] = time();
		//存入数据
		$res = M('t_family_message') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 获取合并信息
	 * @access public
	 * @param 
	 * @param array $options 
	 * @return json
	 */
	public function combinenews(){
		$uid = I('uid');

		//权限todu

		//where语句整理
		$where = array();
		$where['to_uid'] = $uid;
		$where['is_read'] = 0;
		$where['msg_type'] = 3;
		//数据查询
		$res = M('t_family_message')->field('id,datetime')->where($where)->oreder('datetime desc')->select();
		//数据整理todu
		if (!$res) {
			$this->ajaxError('无合并信息');
		}
		$this->ajaxSuccess('成功',0,$res);
	}

	
}