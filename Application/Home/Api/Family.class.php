<?php
/**
 * 家族
 * @since   2018/04/24 创建
 * @author  lingxiao
 */

namespace Home\Api;


use Home\ORG\Str;

class Family extends Base {
	public function index() {
	   
	}

	/**
	 * 创建家族
	 * @access public
	 * @param 
	 * @param array $options  家族数组
	 * @return json
	 */
	public function addfamily(){
		$postData = I('post.');
		//print_r($postData);exit;
		$uid = I('uid');
		//权限todu

		//数据整理todu
		//一个人最多可创建2个家族
		$rows = M('t_family')->where(array('creator_uid' => $uid))->count();

		if ($rows >= 2) {
			$this -> ajaxError('不允许创建家族，您已有2个家族');
		}

		
		$arr = array();
		$arr['honorary_chieftain_uid'] = '';
		$arr['chieftain_uid'] = '';
		$arr['creator_uid'] = $uid;
		$arr['category'] = $postData['category'];
		$arr['name'] = $postData['name'];
		$arr['create_time'] = time();
		$arr['update_time'] = 0;
		$arr['status'] = 0;
		$arr['desc'] = '';
		//print_r($arr);exit;
		//存入数据
		$id = M('t_family') -> add($arr);
		if (!$id) {
			$this->ajaxError('添加失败');
		}
		//同步插入一条，创建者的成员信息
		$user_info = $this ->bmob(array('where={"id":'.$uid.'}'));
		
		$arrs = array();
		$arrs['uid'] = $uid;
		$arrs['paternity_fid'] = '';
		$arrs['matrilineal_fid'] = '';
		$arrs['fid'] = $id;
		$arrs['spouse_paternity_fid'] = '';
		$arrs['spouse_matrilineal_fid'] = '';
		$arrs['surname'] = $user_info['results'][0]['nickname'];
		$arrs['name'] = '';
		$arrs['phone'] = $user_info['results'][0]['phonenum'];
		//$arrs['relationship_code'] = '';
		$arrs['generational_code'] = 0;
		$arrs['father_creator_uid'] = '';
		$arrs['mother_creator_uid'] = '';
		$arrs['creator_uid'] = $uid;
		$arrs['create_time'] = time();
		$arrs['update_time'] = 0;
		$arrs['status'] = 1;
		$res = M('t_family_member') -> add($arrs);

		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 我的家族列表 包括我和配偶的
	 * @access public
	 * @param 
	 * @param array 
	 * @return json
	 */
	public function familylist(){
		$uid = I('uid');
		$category = I('category');
		//
		$family_fids = M('t_family_member')->alias("m")
						->join("left join t_family f on m.fid=f.id")
						->field('m.fid')
						->where(array('m.uid' => $uid,'f.category' => $category))
						->group('m.fid')
						->select();
		//print_r($family_fids);exit;
		$family_db = M('t_family');
		$list = array();
		foreach ($family_fids as $key => $val) {
			$family_info = $family_db->field('id,name,honorary_chieftain_uid,chieftain_uid,creator_uid')->where(array('id' => $val['fid']))->find();
			$list[] = $family_info;
		}

		$this -> ajaxSuccess('成功',0,$list);
	}


	/**
	 * 添加家族成员
	 * @access public
	 * @param 
	 * @param array $options  家族成员数组
	 * @return json
	 */
	public function addmember(){
		$postData = I('post.');
		$uid = I('uid');

		//获取家族基本信息
		$family = M('t_family')->where(array('creator_uid' => $uid))->find();
		//权限todu
		//print_r($family);exit;
		//数据整理todu
		//是否已是家族成员
		$res = M('t_family_member')->field('phone')->where(array('phone' => $postData['phone'],'fid' => $family['id']))->find();
		//print_r($res);exit;
		if ($res['phone']) {
			$this -> ajaxError('用户已是家族成员！');
		}
		//如果该用户不是系统用户,发送一条邀请消息并且返给前端手机号,如果是系统用户,俩中情况,一是家族成员,不可添加;二是家族创建者,发送家族合并消息

		//echo 'where={"phonenum":'.$postData['phone'].'}';exit;
		$res = $this ->bmob(array('where={"phonenum":"'.$postData['phone'].'"}'));
		//print_r($res);exit;
		if (empty($res['results'])) {
			$is_visitor = 1;
			//
			$item = array();
			$item['msg_type'] = 0;
			$item['from_uid'] = $uid;
			$item['to_uid']   = $postData['phone'];
			$item['datetime'] = time();
			$item['fid']      = $family['id'];
			$item['is_read']  = 0;

			M('t_family_message')->add($item);
		}else{
			$rest = M('t_family')->field('creator_uid')->where(array('creator_uid'=>$res['results'][0]['id']))->find();
			if ($rest && $family['category'] == $rest['category']) {
				$item = array();
				$item['msg_type'] = 3;
				$item['from_uid'] = $uid;
				$item['to_uid']   = $res['results'][0]['id'];
				$item['datetime'] = time();
				$item['fid']      = $family['id'];
				$item['is_read']  = 0;

				M('t_family_message')->add($item);
			}
		}

		$arr = array();
		$arr['uid'] = $res['results'][0]['id'] ?: '';
		if ($family['category'] == 1) {
			$arr['paternity_fid'] = '';
			$arr['matrilineal_fid'] = $family['id'];
		}else{
			$arr['paternity_fid'] = $family['id'];
			$arr['matrilineal_fid'] = '';
		}
		
		$arr['fid'] = $family['id'];
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
		if ($is_visitor) {
			$this->ajaxSuccess('成功',0,array('is_visitor' => 1,'phone' => $postData['phone']));
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 发送邀请信息   暂时不需要
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	/*public function invitation(){
		$postData = I('post.');
		$uid = I('uid');
		//获取用户基本信息 是否是家族创建者
		$family = M('t_family')->where(array('creator_uid' => $uid))->find();
		$fid = $family['id'];
		if (!$family) {
			$userinfo = M('t_family_member')->where(array('uid' => $uid))->find();
			$fid = $userinfo['fid'];
		}
		//权限todu

		//数据整理todu

		$arr = array();
		$arr['msg_type'] = 0;
		$arr['from_uid'] = $uid;
		$arr['to_uid'] = $postData['to_uid'];
		$arr['fid'] = $fid;
		$arr['is_read'] = 0;
		$arr['datetime'] = time();
		//存入数据
		$res = M('t_family_message') -> add($arr);

		//插入家族成员信息，头像为问号
		//查询受邀人的姓名
		$to_userinfo = $this ->bmob(array('where={"id":'.$postData['to_uid'].'}'));

		$member = array();
		$member['uid'] = $to_userinfo['results'][0]['id'];
		$member['fid'] = $fid;
		$member['spouse_paternity_fid'] = '';
		$member['spouse_matrilineal_fid'] = '';
		$member['surname'] = $to_userinfo['results'][0]['nickname'];
		$member['name'] = '';
		$member['phone'] = $to_userinfo['results'][0]['phonenum'];
		$member['generational_code'] = $postData['generational_code'];
		$member['father_creator_uid'] = '';
		$member['mother_creator_uid'] = '';
		$member['creator_uid'] = $uid;
		$member['create_time'] = time();
		$member['update_time'] = 0;
		$member['status'] = 0;

		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}*/

	/**
	 * 获取信息  0  被邀请加入家族 1 被邀请人加入了你的家族 2 被邀请人拒绝加入了你的家族 3 请求族长合并家族 4 族长同意合并家族 5 族长拒绝合并家族
	 * @access public
	 * @param 	
	 * @param array $options 
	 * @return json
	 */
	public function news(){
		$uid = I('uid');
		$type = I('type');
		//权限todu

		//where语句整理
		$where = array();
		$where['to_uid'] = $uid;
		$where['is_read'] = 0;
		$where['msg_type'] = $type;
		//数据查询
		$res = M('t_family_message')->field('id,msg_type,datetime,is_read')->where($where)->order('datetime desc')->select();
		//数据整理todu
		if (!$res) {
			$this->ajaxError('无合并信息！');
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
		//$fid = I('fid');
		$status = I('status'); //  1同意 2拒绝
		//获取用户基本信息
		$family = M('t_family')->where(array('creator_uid' => $uid))->find();
		$userinfos = M('t_family_member')->where(array('uid' => $uid))->find();
		$fid = $userinfos['fid'];
		//echo $fid;exit;
		//获取邀请人的信息
		$message =  M('t_family_message')->where(array('id' => $mid))->find();
		//echo $fid;
		//print_r($message);exit;
		$from_userinfo = M('t_family_member')->where(array('uid' => $message['to_uid']))->find();
		$from_family = M('t_family')->where(array('creator_uid' => $message['to_uid']))->find();
		//权限todu

		//数据整理todu

		if ($status != 1) {
			//
			$arr = array();
			if ($family) {
				$arr['msg_type'] = 5;
			}else{
				$arr['msg_type'] = 2;
			}
			//插入一条消息
			$arr['from_uid'] = $uid;
			$arr['to_uid'] = $message['from_uid'];
			$arr['fid'] = $fid;
			$arr['is_read'] = 0;
			$arr['datetime'] = time();
			$arr['relation_mid'] = $mid;
			$res = M('t_family_message') -> add($arr);
		}else{
			if ($family) {
				//合并家族并且向其成员发送邀请信息,父系家族不可与母系家族合并
				if ($family['category'] != $from_family['category']) {
					$this -> ajaxError('性质不符,不可合并！');
				}
				//插入同意message
				//更新家族成员家族及代数
				//查找其与邀请人相同性质的家族id
				//查找其家族所有成员，发送邀请信息
				$arr = array();
				$arr['from_uid'] = $uid;
				$arr['to_uid'] = $message['from_uid'];
				$arr['fid'] = $fid;
				$arr['is_read'] = 0;
				$arr['datetime'] = time();
				$arr['msg_type'] = 1;
				$arr['relation_mid'] = $mid;
				//print_r($arr);exit;
				$res = M('t_family_message') -> add($arr);
				//更新家族成员的 代数 
				$f_update = array();
				//$f_update['fid'] = $fid;
				$f_update['generational_code'] = $userinfos['generational_code'] + $from_userinfo['generational_code'];
				M('t_family_member')->where(array('uid' => $uid,'fid'=>$fid))->save($f_update);
				//
				$family_merge_info = M('t_family')->where(array('creator_uid' => $uid, 'category' => $from_family['category']))->find();
				//print_r($family_merge_info);exit;
				$members_merge_info = M('t_family_member')->where(array('fid' => $family_merge_info['id']))->select();
				//print_r($members_merge_info);exit;
				$message_ints = array();
				foreach ($members_merge_info as $key => $val) {
					if ($val['uid'] == $uid) {
						continue;
					}
					$item = array();
					$item['from_uid'] = $message['from_uid'];
					$item['to_uid'] = $val['uid'];
					$item['fid'] = $fid;
					$item['is_read'] = 0;
					$item['datetime'] = time();
					$item['msg_type'] = 0;
					$arr['relation_mid'] = $mid;

					$message_ints[] = $item;
				}
				//print_r($message_ints);exit;
				M('t_family_message') -> addAll($message_ints);
			}else{
				$arr = array();
				$arr['from_uid'] = $uid;
				$arr['to_uid'] = $message['from_uid'];
				$arr['fid'] = $fid;
				$arr['is_read'] = 0;
				$arr['datetime'] = time();
				$arr['msg_type'] = 1;
				$arr['relation_mid'] = $mid;
				$res = M('t_family_message') -> add($arr);
				//更新家族成员的 代数 
				$f_update = array();
				//$f_update['fid'] = $fid;
				$f_update['generational_code'] = $userinfos['generational_code'] + $from_userinfo['generational_code'];
				M('t_family_member')->where(array('uid' => $uid,'fid'=>$fid))->save($f_update);	
			}
		}

		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 消息标为已读
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function readinfo(){
		$mid = I('mid');

		//权限校验todu

		$update = array();
		$update['is_read'] = 1;
		$update['update_time'] = time();

		$res = M('t_family_message')->where(array('id' => $mid))->save($update);
		if (!$res) {
			$this -> ajaxError('提交失败！');
		}
		$this -> ajaxSuccess('成功', 0 , $update);
	}

	/**
	 * 获取家族成员列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function lists(){
		$fid = I('fid');

		//权限校验todu

		//家族的基本信息
		//$info = M('t_family')->field('id,name')->where(array('id' => $fid))->find();	
		//获取数据
		$list = M('t_family_member')->field('id,uid,surname,name,generational_code')->where(array('fid' => $fid))->order('generational_code desc')->select();

		//数据整理，按照代数分组
		$n_list = array();
		foreach ($list as $key => $val) {
			//头像获取
			$info = $this ->bmob(array('where={"id":'.$val['uid'].'}'));
			$val['headimg'] = $info['results'][0]['headimg'] ?: "";
			unset($info);
			$n_list[$val['generational_code']][] = $val;
		}
		$members = array_values($n_list);

		$arr = array();
		//$arr['id'] = $info['id'];
		//$arr['name'] = $info['name'];
		$arr['members'] = $members;

		$this ->ajaxSuccess('成功',0,array('list' => $arr));
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