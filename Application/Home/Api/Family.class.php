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
		$uid = I('uid');

		//获取用户基本信息
		$userinfo = M('t_family')->where(array('creator_uid' => $uid))->find();
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
			$is_visitor = 1;
		}else{
			$rest = M('t_family')->field('creator_uid')->where(array('creator_uid'=>$res['results']['id']))->find();
			if ($rest) {
				$item = array();
				$item['msg_type'] = 3;
				$item['from_uid'] = $uid;
				$item['to_uid']   = $res['results']['id'];
				$item['datetime'] = time();
				$item['fid']      = "";
				$item['is_read']  = 0;

				M('t_family_message')->add($item);
			}
		}

		$arr = array();
		$arr['uid'] = $uid;
		if ($userinfo['category'] == 1) {
			$arr['paternity_fid'] = '';
			$arr['matrilineal_fid'] = $userinfo['id'];
		}else{
			$arr['paternity_fid'] = $userinfo['id'];
			$arr['matrilineal_fid'] = '';
		}
		
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
	public function invitation(){
		$postData = I('.post');
		$uid = I('uid');
		//获取用户基本信息
		$userinfo = M('t_family')->where(array('creator_uid' => $uid))->find();
		//权限todu

		//数据整理todu

		$arr = array();
		$arr['msg_type'] = 0;
		$arr['from_uid'] = $uid;
		$arr['to_uid'] = $postData['to_uid'];
		$arr['fid'] = $userinfo['id'];
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
		$this->ajaxSuccess('成功',0,array('list' => $res));
	}

	/**
	 * 用户同意/拒绝 加入家族
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function join(){
		$mid = I('mid'); 
		$uid = I('uid');
		$fid = I('fid');
		$status = I('status'); //  1 同意 2拒绝
		//获取用户基本信息
		$userinfo = M('t_family')->where(array('creator_uid' => $uid))->find();
		$userinfos = M('t_family_member')->where(array('uid' => $uid))->find();
		//获取邀请人的信息
		$from_userinfo =  M('t_family_message')->where(array('id' => $uid))->find();
		//权限todu

		//数据整理todu

		if ($status != 1) {
			//更新
			$update = array();
			if ($userinfo) {
				$update['msg_type'] = 5;
			}else{
				$update['msg_type'] = 2;
			}
			M('t_family_message')->where(array('id' => $mid))->save($update);
		}else{
			if ($userinfo) {
				//合并家族并且向其成员发送邀请信息
				
			}else{
				$update['msg_type'] = 1;
				M('t_family_message')->where(array('id' => $mid))->save($update);
				//更新家族成员的 代数 家族id 还是插入一条
				$f_update = array();
				$f_update['fid'] = $fid;	
			}
		}

		$arr = array();
		$arr['msg_type'] = 0;
		$arr['from_uid'] = $uid;
		$arr['to_uid'] = $postData['to_uid'];
		$arr['fid'] = $userinfo['id'];
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
	 * 获取家族成员列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function list(){
		$fid = I('fid');

		//权限校验todu

		//获取数据
		$list = M('t_family_member')->field('id,surname,name,generational_code')->where(array('fid' => $fid))->select();

		//数据整理，按照代数分组
		$n_list = array();
		foreach ($list as $key => $val) {
			$n_list[$val['generational_code']][] = $val;
		}

		$this ->ajaxSuccess('成功',0,array('list' => $n_list));
	}

	/**
	 * 申请成为/卸任名誉族长 申请成为/卸任族长
	 * @access public
	 * @param  category  1 名誉族长 2 族长
	 * @param status  1 申请成为 2 卸任 
	 * @return json
	 */
	public function leader(){
		$uid = I('uid');
		$status = I('status');
		$category = I('category');
		$fid = I('fid');

		//update
		$update = array();
		
		if ($status == 1) {
			if ($category == 1) {
				$update['honorary_chieftain_uid'] = $uid;
			}else{
				$update['chieftain_uid'] = $uid;
			}
		}else{
			//你是不是族长或名誉族长
			if ($category == 1) {
				$info = M('t_family')->where(array('honorary_chieftain_uid' => $uid,'id' => $fid)) -> find();
				if ($info) {
					$update['honorary_chieftain_uid'] = '';
				}
			}else{
				$info = M('t_family')->where(array('chieftain_uid' => $uid,'id' => $fid)) -> find();
				if ($info) {
					$update['chieftain_uid'] = '';
				}
			}	
		}
		
		$res = M('t_family')-> where(array('id' => $fid)) -> save($update);
		if (!$res) {
			$this -> ajaxError('操作失败！');
		}else{
			$this -> ajaxSuccess('成功');
		}
	}


}