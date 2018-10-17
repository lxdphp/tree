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
		//print_r($postData);exit;
		$uid = I('uid');
		//权限todu

		//数据整理todu
		/*if (isset($postData['family_name']) && !empty($postData['family_name'])) {
			//数据格式判断
		}else{
			$this -> ajaxError('请填写家族名称！');
		}
		if (isset($postData['category'])) {
			//数据格式判断
		}else{
			$this -> ajaxError('请选择家族类别！');
		}*/
		//一个人最多可创建2个家族
		$rows = M('t_family')->where(array('creator_uid' => $uid,'status' => 0))->select();
		$categorys = array_column($rows, 'category');
		if (in_array($postData['category'], $categorys)) {
			//$this -> ajaxError('一个类型仅允许创建一个家族',2101);
		}
		$counts = count($categorys);
		if ($counts >= 2) {
			$this -> ajaxError('不允许创建家族，您已有2个家族',2101);
		}
		if ($count == 1) {
			$category = 1;
		}
		
		$arr = array();
		$arr['honorary_chieftain_uid'] = -1;
		$arr['chieftain_uid'] = -1;
		$arr['creator_uid'] = $uid;
		$arr['category'] = $postData['category'];
		$arr['family_name'] = $postData['family_name'];
		$arr['create_time'] = $this -> get_millisecond();
		$arr['update_time'] = 0;
		//$arr['status'] = 0;
		$arr['desc'] = '';
		$arr['del_flg'] = 0;

		//print_r($arr);exit;
		//存入数据
		$id = M('t_family') -> add($arr);
		if (!$id) {
			$this->ajaxError('添加失败');
		}
		//同步插入一条，创建者的成员信息
		$user_info = M('t_user')->where(array('id'=>$uid))->find();
		//$user_info = $this ->bmob(array('where={"id":'.$uid.'}'));
		
		$arrs = array();
		$arrs['uid'] = $uid;
		$arrs['fid'] = $id;
		
		//$arrs['surname'] = $postData['surname'];
		$arrs['name'] = $postData['name'];
		//$arrs['phone'] = $user_info['results'][0]['phonenum'];
		$arrs['phone'] = $user_info['phone'];
		//$arrs['relationship_code'] = '';
		$arrs['generational_code'] = 0;
		//$arrs['father_creator_uid'] = '';
		//$arrs['mother_creator_uid'] = '';
		$arrs['creator_uid'] = $uid;
		$arrs['create_time'] = $this -> get_millisecond();
		$arrs['update_time'] = 0;
		$arrs['status'] = 1;
		$arrs['category'] = isset($category) ? $category : $postData['category'];
		
		
		$ids = M('t_family_member') -> add($arrs);

		//
		$arr['fid'] = $id; 
		
		$this->ajaxSuccess('成功',0,$arr,array('mid' =>$ids));
	}

	/**
	 * 我的家族列表 包括我和配偶的
	 * @access public
	 * @param 
	 * @param array 
	 * @return json
	 */
	/*public function familylist(){
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
	}*/


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
		$fid = I('fid');

		if (!$postData['phone']) {
			$this -> ajaxError('手机号不可为空！',2102);
		}
		
		//家族详情
		$family = M('t_family')->where(array('id'=>$fid))->find();
		//我的这个家族是我的第几个人家族
		$member_info = M('t_family_member')->where(array('fid' => $fid,'uid' => $uid))->find();
		//用户是否是已注册用户
		$inv_user = M('t_user')->where(array('phone'=>$postData['phone']))->find();
		if (!$inv_user) {
			//未注册用户todo
			$inv_user['id'] = 0;
			$is_visitor = 1;
		}
		if ($inv_user['id'] == $uid) {
			$this->ajaxError('不可邀请自己！');
		}
		//判断，无配偶是否存在俩个家族，有配偶 可以存在4个家族
		$inv_family_user = M('t_family_member')->where(array('uid'=>$inv_user['id'],'status'=>1))->select();
		//print_r($inv_family_user);exit;
		$num = $spouse_num = array();
		foreach ($inv_family_user as $key => $val) {
			if ($val['category'] == 0 || $val['category'] == 1) {
				$num[] = $val['id'];
			}
			if ($postData['spouse'] && ($val['uid'] == $inv_user['id']) && ($val['fid'] == $fid)) {
				$this->ajaxError('您配偶已在家族,请您配偶退出家族,添加家族成员,并勾选配偶选项！');
			}
			if ($postData['spouse'] && $val['category'] == 2 && $member_info['category'] == 0) {
				$this->ajaxError('您配偶已有家族！');
			}
			if ($postData['spouse'] && $val['category'] == 3 && $member_info['category'] == 1) {
				$this->ajaxError('您配偶已有家族！');
			}
		}
		$count = count($num);
		
		if ($count == 2 && !$postData['spouse']) {
			$this->ajaxError('已加入俩个家族！');
		}
		if ($count == 1 && !$postData['spouse']) {
			$category = 1;
		}	

		//通过关系找到配偶
		/*$spouse = M('t_family_member')->alias("m")
				->field('m.uid,f.category')
				->join("left join t_family f on m.fid=f.id")
				->where(array('m.spouse_id'=>$inv_user['id'],'m.fid'=>$fid))
				->find();
		if ($spouse && $postData['spouse']) {
			$this->ajaxError('您已添加配偶！');
		}*/
		$spouse = M('t_family_member')->alias("m")
				->field('m.uid,f.category')
				->join("left join t_family f on m.fid=f.id")
				->where(array('m.spouse_id'=>$uid,'m.fid'=>$fid))
				->find();
		if ($spouse && $postData['spouse']) {
			$this->ajaxError('您已添加配偶！');
		}
		//关系仅存在一条
		$result = M('t_family_member')->where(array('fid' => $fid,'relation' => $postData['relation']))->find();
		if ($result) {
			unset($result);
			$relation = $this -> getName($postData['relation']);
			if ($relation) {
				$this->ajaxError('您已添加'.$relation.'！');
			}	
		}
		
		if ($postData['relation'] == "5") {
			//5在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '4'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
		if ($postData['relation'] == "1") {
			//1在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '0'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
		if ($postData['relation'] == "2") {
			//4在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '3'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
	
		//通过关系找到配偶
		if ($postData['relation'] == "4") {
			//5在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '5'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				//print_r($res);exit;
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
		//echo 123;exit;
		if ($postData['relation'] == "0") {
			//1在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '1'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
		if ($postData['relation'] == "3") {
			//4在不在家族
			$info = M('t_family_member')->where(array('fid' => $fid,'relation' => '2'))->find();
			if (!$info) {
				$d_spouse_id = "";
				$d_fid = "";
			}else{
				
				$d_spouse_id = $info['uid'];
				$d_fid = 0;
			}
		}
		

		//是否是二次邀请
		$res = M('t_family_member')->where(array('phone' => $postData['phone'],'fid' => $fid))->find();
		if ($res) {
			if ($res['status'] == 0) {
				//更新member 时间  再次发出邀请
				M('t_family_member')->where(array('id' => $res['id']))->save(array('update_time' => $this -> get_millisecond()));
				$res['headimg'] = $inv_user['headimg'] ?: '';

				$info = M('t_family_message')->where(array('from_uid' => $uid,'fid' => $fid,'to_uid' => $inv_user['id']))->order('datetime desc')->find();
				if ($info['status'] == 1) {
					$this -> ajaxError('用户已是家族成员！');
				}
				M('t_family_message')->where(array('id' => $info['id']))->save(array('update_time' => $this -> get_millisecond()));

				$this->ajaxSuccess('成功',0,$res);
			}elseif ($res['status'] == 2) {
				$this->ajaxSuccess('成功',0,array('is_visitor' => 1,'phone' => $postData['phone'],'name' => $res['name'],'generational_code' => $res['generational_code'],'status' => 2));
			}
			else{
				$this -> ajaxError('用户是家族成员！');
			}
		}

		$arr = array();
		$arr['uid'] = $inv_user['id'];
		$arr['fid'] = $fid;
		$arr['name'] = $postData['name'];
		$arr['phone'] = $postData['phone'];
		if ($postData['spouse']) {
			$arr['generational_code'] = $member_info['generational_code'];
		}else{
			$arr['generational_code'] = $postData['generational_code'];
		}
		
		$arr['creator_uid'] = $family['creator_uid'];
		$arr['create_time'] = $this -> get_millisecond();
		$arr['update_time'] = 0;
		if ($postData['spouse'] == 1) {
			$arr['spouse_id'] = $uid;
			//更新配偶数据spouse_id
			M('t_family_member')->where(array('uid' => $uid,'fid'=>$fid))->save(array('spouse_id' => $inv_user['id']));	
		}
		if ($is_visitor) {
			$arr['status'] = 2;
		}else{
			$arr['status'] = 0;
		}

		if ($member_info['category'] == 0) {
			if ($postData['spouse'] == 1) {
				$arr['category'] = 2;
			}else{
				
				if ($category) {
					
					$arr['category'] = 1;
				}else{
					$arr['category'] = 0;
				}
				
			}
			
		}
		
		if ($member_info['category'] == 1) {
			if ($postData['spouse'] == 1) {
				$arr['category'] = 3;
			}else{
				if ($category) {
					$arr['category'] = 1;
				}else{
					$arr['category'] = 0;
				}
			}
		}
		
		//关系
		$arr['relation'] = $postData['relation'] ?: -1;
		if($d_spouse_id){	
			$arr['spouse_id'] = $d_spouse_id;
			//更新配偶数据spouse_id
			//M('t_family_member')->where(array('uid' => $d_spouse_id,'fid'=>$fid))->save(array('spouse_id' => $inv_user['id']));
		}
		
		//存入数据
		$res = M('t_family_member') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}

		//unset($arr['fid']);
		//获取用户头像
		$arr['headimg'] = $inv_user['headimg'];

		//获取配偶的家族id
		$spouse_fid = 0;
		if ($postData['spouse']) {
			$opt = array();
			$opt['m.uid'] = $inv_user['id'];
			//$opt['m.creator_uid'] = array('neq',$invite_uid);
			$opt['f.category'] = $family['category'];
			$opt['m.status'] = 1;
			$rest = M('t_family_member')->alias("m")
					->field('f.id')
					->join("left join t_family f on m.fid=f.id")
					->where($opt)
					->find();
			//print_r($rest);exit;
			$spouse_fid = $rest ? $rest['id'] : 0;
		}
		//echo 123;exit;
		//通知消息
		$item = array();
		$item['msg_type'] = 0;
		$item['from_uid'] = $uid;
		if ($is_visitor) {
			$item['to_uid']   = $postData['phone'];
		}else{
			$item['to_uid']   = $inv_user['id'];
		}
		
		$item['datetime'] = $this -> get_millisecond();
		$item['fid']      = $fid;
		$item['is_read']  = 0;
		if($postData['spouse'] == 1){
			$item['spouse_id'] = $uid;
			$item['to_fid'] = $spouse_fid;
		}
		if ($d_spouse_id) {
			$item['relation'] = 1;
			$item['spouse_id'] = $d_spouse_id;
			$item['to_fid'] = $d_fid;
		}
		if ($postData['spouse']) {
			$item['generational_code'] = $member_info['generational_code'];
		}else{
			$item['generational_code'] = $postData['generational_code'];
		}
		
		
		//print_r($item);exit;
		M('t_family_message')->add($item);

		if ($is_visitor) {
			$this->ajaxSuccess('成功',0,array('is_visitor' => 1,'phone' => $postData['phone'],'name' => $postData['name'],'generational_code' => $postData['generational_code'],'status' => 2));
		}
		$this->ajaxSuccess('成功',0,$arr);
		
	}

	public function getName($name){
		switch ($name) {
			case  0:
				return  '爷爷';
				break;
			case  1:
				return  '奶奶';
				break;
			case  2:
				return  '姥爷';
				break;
			case  3:
				return  '姥姥';
				break;
			case  4:
				return  '父亲';
				break;
			case  5:
				return  '母亲';
				break;
			/*case '儿子':
				return  6;
				break;
			case '女儿':
				return  7;
				break;
			case '孙子':
				return  8;
				break;
			case '孙女':
				return  9;
				break;*/
			default:
				# code...
				break;
		}
	}

	/**
	 * 编辑家族成员
	 * @access public
	 * @param 
	 * @param array $options  家族成员数组
	 * @return json
	 */
	public function edit(){
		$uid = I('uid');
		$id  = I('id');
		$name = I('name');

		$info = M('t_family_member')->where(array('id' => $id))->find();
		if (!$info) {
			$this -> ajaxError('成员不存在！');
		}

		$update = array();
		$update['name'] = $name;

		M('t_family_member')->where(array('id' => $id))->save($update);
		$this -> ajaxSuccess('成功');
	}

	/**
	 * 发送合并信息   
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function invitation(){
		$postData = I('post.');
		$uid = I('uid');
	
		//权限todu

		//数据整理todu
		//查询配偶
		$info = M('t_family_member')->where(array('uid' => $postData['to_uid'],'fid' => $postData['fid']))->find();
		//print_r($info);exit;
		$spouse_id = $info['spouse_id'] ?: 0;
		

		$arr = array();
		$arr['msg_type'] = 3;
		$arr['from_uid'] = $uid;
		$arr['to_uid'] = $postData['to_uid'];
		$arr['fid'] = $postData['fid'];
		$arr['is_read'] = 0;
		$arr['datetime'] = $this -> get_millisecond();
		$arr['to_fid'] = $postData['to_fid'];
		$arr['spouse_id'] = $spouse_id;
		$arr['invitation'] = $postData['invitation'];
		$arr['generational_code'] = $postData['generational_code'];
		//存入数据
		$res = M('t_family_message') -> add($arr);

		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 删除通知
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function del(){
		$uid = I('uid');
		$id = I('id');
		
		//权限todu

		//where语句整理
		$where = array();
		$where['id'] = $id;
		$info = M('t_family_message')->where($where)->find();
		if (!$info) {
			$this -> ajaxError('信息不存在！');
		}
		if ($info['to_uid'] != $uid) {
			$this -> ajaxError('权限不足！');
		}

		M('t_family_message')->where($where)->delete();

		$this -> ajaxSuccess('成功');
	}

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
		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;
		//权限todu

		//where语句整理
		$where = array();
		$where['to_uid'] = $uid;
		//$where['is_read'] = 0;
		if ($type) {
			$where['msg_type'] = $type;
		}
		
		if ($page_id) {
			$where['id'] = array('lt',$page_id);
		}
		//数据查询
		$res = M('t_family_message')
			   ->field('id,msg_type,datetime,is_read,from_uid,to_uid,fid,status')
			   ->where($where)
			   ->order('datetime desc')
			   ->limit($page_size)
			   ->select();
		//print_r($res);exit;
		//数据整理todu
		/*if (!$res) {
			$this->ajaxSuccess('无信息！');
		}*/
		$db = M('t_family_member');
		foreach ($res as $key => $val) {
			//
			$info = $db->where(array('fid' => $val['fid'],'uid'=>$val['from_uid']))->find();
			//echo $db->_sql();
			//print_r($info);exit;		
			$info_head = M('t_user')->where(array('id'=>$val['from_uid']))->find();
			//$info_head = $this ->bmob(array('where={"id":'.$val['from_uid'].'}'));
			//print_r($info_head);exit;
			$res[$key]['from_name'] = $info['name'];
			$res[$key]['from_head'] = $info_head['headimg'] ?: "";
			//
			$info2 = $db->where(array('fid' => $val['fid'],'uid'=>$val['to_uid']))->find();
			$info_head2 = M('t_user')->where(array('id'=>$val['to_uid']))->find();
			//$info_head2 = $this ->bmob(array('where={"id":'.$val['to_uid'].'}'));
			$res[$key]['to_name'] = $info2['name'];
			$res[$key]['to_head'] = $info_head2['headimg'] ?: "";
		}
		$this->ajaxSuccess('成功',0,$res);
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
		$status = I('status'); //  1同意 2拒绝 
		//获取邀请人的信息
		$message = M('t_family_message')->where(array('id' => $mid))->find();

		$fid = $message['fid'];
		$to_fid = $message['to_fid'];
		//获取用户基本信息
		if ($to_fid) {
			/*$to_info = M('t_family_member')->alias("m")
					->join("left join t_family f on m.fid=f.id")
					->field('f.category,m.fid,m.generational_code,m.name,m.phone,f.creator_uid')
					->where(array('m.uid' => $message['to_uid'],'m.fid'=>$to_fid))
					->find();*/
		}
		//echo  M('t_family_member')->_sql();exit;
		//print_r($to_info);exit;
		$from_info = M('t_family_member')->alias("m")
					->join("left join t_family f on m.fid=f.id")
					->field('f.category,m.fid,m.generational_code,m.name,m.phone,f.creator_uid')
					->where(array('m.uid' => $message['from_uid'],'m.fid'=>$fid,'m.status'=>1))
					->find();
		
		//权限todu
		//数据整理todu
		//更新本条消息状态
		M('t_family_message')->where(array('id' => $mid))->save(array('update_time' => $this -> get_millisecond(),'status'=> $status));
		//拒绝
		if ($status != 1) {
			$arr = array();
			$arr['msg_type'] = 2;
			$arr['from_uid'] = $uid;
			$arr['to_uid'] = $message['from_uid'];
			$arr['fid'] = $fid;
			$arr['is_read'] = 0;
			$arr['datetime'] = $this -> get_millisecond();
			$arr['relation_mid'] = $mid;
			$res = M('t_family_message') -> add($arr);
		}else{
			//同意加入
			//插入同意message
			//更新家族成员家族及代数
			//查找其与邀请人相同性质的家族id
			//查找其家族所有成员，发送邀请信息
			$arr = array();
			$arr['from_uid'] = $uid;
			$arr['to_uid'] = $message['from_uid'];
			$arr['fid'] = $fid;
			$arr['is_read'] = 0;
			$arr['datetime'] = $this -> get_millisecond();
			$arr['msg_type'] = 1;
			$arr['relation_mid'] = $mid;
			$res = M('t_family_message') -> add($arr);
			//更新家族成员的 代数 
			$f_update = array();
			$f_update['status'] = 1;
			if (isset($message['generational_code'])) {
				$f_update['generational_code'] = $message['generational_code'];
			}
			
			/*if (!empty($to_fid)) {
				if ($message['relation']) {
					//$f_update['generational_code'] = (int)($to_info['generational_code']);
				}else{
					$f_update['generational_code'] = (int)($to_info['generational_code'] + $from_info['generational_code']);
				}
				
			}*/
			
			/*if ($message['spouse_id'] && !$message['relation']) {
				if ($family['category'] == 1) {
					$f_update['category'] = 3;
				}else{
					$f_update['category'] = 2;
				}
				
			}*/
			
			M('t_family_member')->where(array('uid' => $uid,'fid'=>$fid))->save($f_update);
			//echo 123;exit;
			/*if ($message['spouse_id']) {
				//同辈份配偶 需要插入一条配偶的数据 我添加配偶 配偶的对应家族需要有一条我的数据
				if ($message['relation']) {
					$relation_info =  M('t_family_member')->where(array('uid' => $message['spouse_id'],'fid'=>$fid,'status'=>1))->find();
				}
				//print_r($to_info);exit;
				if ($to_info) {
					//配偶是否已在我的家族
					$exit_info = M('t_family_member')->where(array('uid' => $message['spouse_id'],'fid'=>$to_fid,'status'=>1))->find();
					
					if (!$exit_info) {
						$arr = array();
						$arr['uid'] = $message['spouse_id'];
						$arr['fid'] = $to_fid;
						if ($relation_info) {
							$arr['name'] = $relation_info['name'];
							$arr['phone'] = $relation_info['phone'];
							$arr['creator_uid'] = $to_info['creator_uid'];
						}else{
							$arr['name'] = $from_info['name'];
							$arr['phone'] = $from_info['phone'];
							$arr['creator_uid'] = $to_info['creator_uid'];
						}
						
						$arr['generational_code'] = $to_info['generational_code'];
						$arr['create_time'] = $this -> get_millisecond();
						$arr['update_time'] = 0;
						$arr['spouse_id'] = $uid;
						$arr['status'] = 1;
						
						if ($to_info['category'] == 0) {
							$arr['category'] = 2;
						}else{
							$arr['category'] = 3;	
						}
					
						M('t_family_member')->add($arr);
					}
				}
				
				//更新配偶的spouse_id
				M('t_family_member')->where(array('uid' =>$uid,'fid' => $to_fid))->save(array('spouse_id' => $message['spouse_id']));
			}*/
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
		$update['update_time'] = $this -> get_millisecond();

		$res = M('t_family_message')->where(array('id' => $mid))->save($update);
		if (!$res) {
			$this -> ajaxError('提交失败！');
		}
		$this -> ajaxSuccess('成功', 0 , $update);
	}

	/**
	 * 消息标为已读 (看到列表就认为是已读)
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function readinfoLlist(){
		//echo 123;exit;
		$uid = I('uid');
		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;
		//权限校验todu

		//查找要更新的ids
		$where = array();
		$where['id'] = array('egt', $page_id);
		$where['to_uid'] = $uid;

		$list = M('t_family_message')->field('id')->where($where)->select();
		
		$ids = array_column($list,'id');

		$update = array();
		$update['is_read'] = 1;
		$update['update_time'] = $this -> get_millisecond();

		$opt = array();
		if ($ids) {
			$opt['id'] = array('in', $ids);
			$res = M('t_family_message')->where($opt)->save($update);
			//echo M('t_family_message')->_sql();exit;
			if (!$res) {
				$this -> ajaxError('提交失败！');
			}
		}
		
		//print_r($opt);exit;
		
		$this -> ajaxSuccess('成功', 0 , $update);
	}

	/**
	 * 未读家族信息个数
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function notices_family(){
		$uid = I('uid');
		//$type = I('type');
		
		//权限todu

		//where语句整理
		$where = array();
		$where['to_uid'] = $uid;
		$where['is_read'] = 0;
		
		$count = M('t_family_message')->where($where)->count();
		$id = M('t_family_message')->where($where)->order('id desc')->find();

		$this -> ajaxSuccess('成功',0,array('count' => $count,'id' => $id));
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
		$uid = I('uid');
		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;

		$where = array();
		$where['fid'] = $fid;
		$where['del_flg'] = 1;
		if ($page_id) {
			$where['id'] = array('lt',$page_id);
		}
		//权限校验todu

		//家族的基本信息
		//$info = M('t_family')->field('id,name')->where(array('id' => $fid))->find();	
		//获取数据
		$list = M('t_family_member')->field('id,uid,name,generational_code,status,fid,phone')->where($where)->order('generational_code asc')->limit($page_size)->select();
		
		//数据整理，按照代数分组
		$n_list = array();
		foreach ($list as $key => $val) {
			//头像获取
			$info = M('t_user')->where(array('id'=>$val['uid']))->find();
			//$info = $this ->bmob(array('where={"id":'.$val['uid'].'}'));
			$val['nickname'] = $info['nickname'] ?: "";
			$val['headimg'] = $info['headimg'] ?: "";
			unset($info);
			$n_list[$val['generational_code']]['gen_code'] = $val['generational_code'];
			$n_list[$val['generational_code']]['members'][] = $val;
			//$n_list['gen_code'][] = $val['generational_code'];
		}
		$members = array_values($n_list);
		foreach ($members as $key => $row)
		{
			$volume[$key]  = $row['gen_code'];
			//$edition[$key] = $row['edition'];
		}

		array_multisort($volume, SORT_ASC, $members);
		//获取家族成员
		$family_list = M('t_family_member')->field('id,uid,name,status,phone')->where(array('fid' => $fid,'uid'=>$uid))->find();

		$this ->ajaxSuccess('成功',0,$members,$family_list);
	}

	/**
	 * 获取家族成员列表-2
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function lists_t(){
		$fid = I('fid');
		$uid = I('uid');
		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;

		$where = array();
		$where['fid'] = $fid;
		//$where['del_flg'] = 1;
		if ($page_id) {
			$where['id'] = array('lt',$page_id);
		}
		//权限校验todu

		//家族的基本信息
		//$info = M('t_family')->field('id,name')->where(array('id' => $fid))->find();	
		//获取数据
		$list = M('t_family_member')->field('id,uid,name,generational_code,status,fid,phone,creator_uid')->where($where)->limit($page_size)->select();
		//echo M('t_family_member')->_sql();exit;
		//数据整理，按照代数分组
		$n_list = $creator = array();
		//print_r($list);exit;
		foreach ($list as $key => $val) {
			
			//头像获取
			$info = M('t_user')->where(array('id'=>$val['uid']))->find();
			//$info = $this ->bmob(array('where={"id":'.$val['uid'].'}'));
			$val['headimg'] = $info['headimg'] ?: "";
			unset($info);
			if ($val['uid'] == $val['creator_uid']) {
				$creator['uid'] = $val['uid'];
			}
			$n_list[] = $val;
			//$n_list['gen_code'][] = $val['generational_code'];
		}
		//print_r($creator);exit;
		$this ->ajaxSuccess('成功',0,$n_list,$creator);
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
		
		$info = M('t_family')->where(array('id' => $fid)) -> find();

		if ($status == 1) {
			if ($category == 1) {
				if ($info['honorary_chieftain_uid'] =='-1' && $info['chieftain_uid'] != $uid) {
					$update['honorary_chieftain_uid'] = $uid;
				}else{
					$this -> ajaxError('不可同时担任荣誉族长！',2107);
				}
			}
			if ($category == 2) {
				if ($info['chieftain_uid'] =='-1'&& $info['honorary_chieftain_uid'] != $uid){
					$update['chieftain_uid'] = $uid;
				}else{

					$this -> ajaxError('不可同时担任族长！',2108);
				}
			}
		}else{
			//
			if ($category == 1) {
				if ($info['honorary_chieftain_uid'] == $uid) {
					$update['honorary_chieftain_uid'] = -1;
				}else{
					$this -> ajaxError('您不是家族名誉族长！',2109);
				}
			}
			if ($category == 2) {
				if ($info['chieftain_uid'] == $uid) {
					$update['chieftain_uid'] = -1;
				}else{
					$this -> ajaxError('您不是家族族长！',2110);
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
	/**
	 * 去重
	 * @access public
	 * @param  category  
	 * @param status   
	 * @return json
	 */
	public function assoc_unique(&$arr, $key) {
		$tmp_arr = array();
		foreach ($arr as $k => $v) {
			if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
				unset($arr[$k]);
			} else {

				$tmp_arr[] = $v[$key];

			}
		}
		sort($arr); //sort函数对数组进行排序
		return $arr;
	}
	/**
	 * 分享的家族及成员列表
	 * @access public
	 * @param  category  
	 * @param status   
	 * @return json
	 */
	public function sharelists(){
		$uid = I('uid');
		
		//
		$family_fids = M('t_family_member')->alias("m")
						->join("left join t_family f on m.fid=f.id")
						->field('m.fid')
						->where(array('m.uid' => $uid))
						->group('m.fid')
						->select();
		if (!$family_fids) {
			$this ->ajaxSuccess('成功');
		}
		//print_r($family_fids);exit;
		$family_fids = array_column($family_fids,'fid');
		$where = array();
		$where['m.fid']  = array('in',$family_fids);
		$where['m.status'] = 1;
		$where['m.del_flg'] = 1;
		$where['m.uid']  = array('neq',$uid);
		$members = M('t_family_member')->alias("m")
					->join("left join t_family f on m.fid=f.id")
					->field('m.id,m.uid,m.name,m.generational_code,m.fid,f.family_name')
					->where($where)
					->select();
		//$arr = array();
		foreach ($members as $k => $v) {
			//头像获取
			$info = M('t_user')->where(array('id'=>$v['uid']))->find();
			//$info = $this ->bmob(array('where={"id":'.$v['uid'].'}'));
			$members[$k]['headimg'] = $info['headimg'] ?: "";
			unset($info);
			//$arr[] = $
		}
		$this ->assoc_unique($members, 'uid');
		//print_r($members);exit;
		$this ->ajaxSuccess('成功',0,$members);
	}

	/**
	 * 家族列表 父系 母系 配偶父系 配偶母系
	 * @access public
	 * @param  category  
	 * @param status   
	 * @return json
	 */
	public function indexlist(){
		$uid = I('uid');
		//$fid = I('fid');

		//是否是家族创建者
		$family = M('t_family')->where(array('creator_uid' => $uid))->find();
		//print_r($family);exit;
		//查询我的配偶
		$spouse = M('t_family_member')->where(array('uid' => $uid))->find();
		//print_r($spouse);
		//我的配偶是否是家族创建者
		$family_sp = M('t_family')->where(array('creator_uid' => $spouse['spouse_id']))->find();
		//print_r($family_sp);exit;
		//父系
		$where = array();
		$where['f.status'] = 0;
		$where['m.status'] = 1;
		if (isset($family['category']) && $family['category'] == 0) {
			//echo 123;exit;
			$where['m.creator_uid'] = $uid;
		}else{
			$where['m.uid'] = $uid;
		}
		$where['m.category'] = 0;
		$list1 = M('t_family_member')->alias("m")
				->join("left join t_family f on m.fid=f.id")
				->join("left join t_user u on m.creator_uid=u.id")
				->field('m.fid,f.family_name,f.honorary_chieftain_uid,f.chieftain_uid,f.creator_uid,f.category,u.headimg')
				->where($where)
				->group('m.fid')
				->find();
		//echo M('t_family_member')->_sql();exit;
		//print_r($list1);exit;
		if (!$list1) {
			$list1 = array(
					'fid'=>-1,
					'family_name'=>'',
					'honorary_chieftain_uid'=>-1,
					'honorary_chieftain_name'=>'',
					'honorary_chieftain_headimg'=>'',
					'chieftain_uid'=>-1,
					'chieftain_name'=>'',
					'chieftain_headimg'=>'',
					'creator_uid'=>-1,
					'category'=>0,
					'uname'=>"",
					'headimg'=>""
				);
		}else{
			//名誉族长，族长的名字，头像
			$ho_info = M('t_family_member')->where(array('uid'=>$list1['honorary_chieftain_uid'],'fid'=>$list1['fid']))->find();
			$ho_headimg = M('t_user')->where(array('id'=>$list1['honorary_chieftain_uid']))->find();
			//$ho_headimg = $this ->bmob(array('where={"id":'.$list1['honorary_chieftain_uid'].'}'));
			$ch_info = M('t_family_member')->where(array('uid'=>$list1['chieftain_uid'],'fid'=>$list1['fid']))->find();
			$ch_headimg = M('t_user')->where(array('id'=>$list1['chieftain_uid']))->find();
			//$ch_headimg = $this ->bmob(array('where={"id":'.$list1['chieftain_uid'].'}'));
			$list1['honorary_chieftain_name'] = $ho_info['name'] ?: "";
			$list1['honorary_chieftain_headimg'] = $ho_headimg['headimg'] ?: "";
			$list1['chieftain_name'] = $ch_info['name'] ?: "";
			$list1['chieftain_headimg'] = $ch_headimg['headimg'] ?: "";
			$cr_info = M('t_family_member')->where(array('uid'=>$list1['creator_uid'],'fid'=>$list1['fid']))->find();
			$list1['uname'] = $cr_info['name'] ?: "";
		}
		//母系
		$where = array();
		$where['f.status'] = 0;
		$where['m.status'] = 1;
		if ($family['category'] == 1) {
			unset($where['m.creator_uid']);
			$where['m.creator_uid'] = $uid;
		}else{
			unset($where['m.uid']);
			$where['m.uid'] = $uid;
		}
		$where['m.category']  = 1;
		$list2 = M('t_family_member')->alias("m")
				->join("left join t_family f on m.fid=f.id")
				->join("left join t_user u on m.creator_uid=u.id")
				->field('m.fid,f.family_name,f.honorary_chieftain_uid,f.chieftain_uid,f.creator_uid,f.category,u.headimg')
				->where($where)
				->group('m.fid')
				->find();
		//echo M('t_family_member')->_sql();exit;
		if (!$list2) {
			$list2 = array(
					'fid'=>-1,
					'family_name'=>'',
					'honorary_chieftain_uid'=>-1,
					'honorary_chieftain_name'=>'',
					'honorary_chieftain_headimg'=>'',
					'chieftain_uid'=>-1,
					'chieftain_name'=>'',
					'chieftain_headimg'=>'',
					'creator_uid'=>-1,
					'category'=>1,
					'uname'=>"",
					'headimg'=>""
				);
		}else{
			//名誉族长，族长的名字，头像
			$ho_info = M('t_family_member')->where(array('uid'=>$list2['honorary_chieftain_uid'],'fid'=>$list2['fid']))->find();
			$ho_headimg = M('t_user')->where(array('id'=>$list2['honorary_chieftain_uid']))->find();
			//$ho_headimg = $this ->bmob(array('where={"id":'.$list2['honorary_chieftain_uid'].'}'));
			$ch_info = M('t_family_member')->where(array('uid'=>$list2['chieftain_uid'],'fid'=>$list2['fid']))->find();
			$ch_headimg = M('t_user')->where(array('id'=>$list2['chieftain_uid']))->find();
			//$ch_headimg = $this ->bmob(array('where={"id":'.$list2['chieftain_uid'].'}'));
			$list2['honorary_chieftain_name'] = $ho_info['name'] ?: "";
			$list2['honorary_chieftain_headimg'] = $ho_headimg['headimg'] ?: "";
			$list2['chieftain_name'] = $ch_info['name'] ?: "";
			$list2['chieftain_headimg'] = $ch_headimg['headimg'] ?: "";
			$cr_info = M('t_family_member')->where(array('uid'=>$list2['creator_uid'],'fid'=>$list2['fid']))->find();
			$list2['uname'] = $cr_info['name'] ?: "";
		}
		//配偶父系
		$where = array();
		$where['f.status'] = 0;
		$where['m.status'] = 1;
		
		//print_r($family_sp);
		
		$where['m.uid'] = $uid;

		$where['m.category']  = 2;
		$list3 = M('t_family_member')->alias("m")
				->join("left join t_family f on m.fid=f.id")
				->join("left join t_user u on m.creator_uid=u.id")
				->field('m.fid,f.family_name,f.honorary_chieftain_uid,f.chieftain_uid,f.creator_uid,f.category,u.headimg')
				->where($where)
				->group('m.fid')
				->find();
		//echo M('t_family_member')->_sql();exit;
		if (!$list3) {
			$list3 = array(
					'fid'=>-1,
					'family_name'=>'',
					'honorary_chieftain_uid'=>-1,
					'honorary_chieftain_name'=>'',
					'honorary_chieftain_headimg'=>'',
					'chieftain_uid'=>-1,
					'chieftain_name'=>'',
					'chieftain_headimg'=>'',
					'creator_uid'=>-1,
					'category'=>2,
					'uname'=>"",
					'headimg'=>""
				);
		}else{
			$list3['category'] = 2;
			//名誉族长，族长的名字，头像
			$ho_info = M('t_family_member')->where(array('uid'=>$list3['honorary_chieftain_uid'],'fid'=>$list3['fid']))->find();
			$ho_headimg = M('t_user')->where(array('id'=>$list3['honorary_chieftain_uid']))->find();
			//$ho_headimg = $this ->bmob(array('where={"id":'.$list3['honorary_chieftain_uid'].'}'));
			$ch_info = M('t_family_member')->where(array('uid'=>$list3['chieftain_uid'],'fid'=>$list3['fid']))->find();
			$ch_headimg = M('t_user')->where(array('id'=>$list3['chieftain_uid']))->find();
			//$ch_headimg = $this ->bmob(array('where={"id":'.$list3['chieftain_uid'].'}'));
			$list3['honorary_chieftain_name'] = $ho_info['name'] ?: "";
			$list3['honorary_chieftain_headimg'] = $ho_headimg['headimg'] ?: "";
			$list3['chieftain_name'] = $ch_info['name'] ?: "";
			$list3['chieftain_headimg'] = $ch_headimg['headimg'] ?: "";
			$cr_info = M('t_family_member')->where(array('uid'=>$list3['creator_uid'],'fid'=>$list3['fid']))->find();
			$list3['uname'] = $cr_info['name'] ?: "";
		}
		//配偶母系
		$where = array();
		$where['f.status'] = 0;
		$where['m.status'] = 1;
		$where['m.uid'] = $uid;
		$where['m.category']  = 3;
		$list4 = M('t_family_member')->alias("m")
				->join("left join t_family f on m.fid=f.id")
				->join("left join t_user u on m.creator_uid=u.id")
				->field('m.fid,f.family_name,f.honorary_chieftain_uid,f.chieftain_uid,f.creator_uid,f.category,u.headimg')
				->where($where)
				->group('m.fid')
				->find();
		if (!$list4) {
			$list4 = array(
					'fid'=>-1,
					'family_name'=>'',
					'honorary_chieftain_uid'=>-1,
					'honorary_chieftain_name'=>'',
					'honorary_chieftain_headimg'=>'',
					'chieftain_uid'=>-1,
					'chieftain_name'=>'',
					'chieftain_headimg'=>'',
					'creator_uid'=>-1,
					'category'=>3,
					'uname'=>"",
					'headimg'=>""
				);
		}else{
			$list4['category'] = 3;
			//名誉族长，族长的名字，头像
			$ho_info = M('t_family_member')->where(array('uid'=>$list4['honorary_chieftain_uid'],'fid'=>$list4['fid']))->find();
			$ho_headimg = M('t_user')->where(array('id'=>$list4['honorary_chieftain_uid']))->find();
			//$ho_headimg = $this ->bmob(array('where={"id":'.$list4['honorary_chieftain_uid'].'}'));
			$ch_info = M('t_family_member')->where(array('uid'=>$list4['chieftain_uid'],'fid'=>$list4['fid']))->find();
			$ch_headimg = M('t_user')->where(array('id'=>$list4['chieftain_uid']))->find();
			//$ch_headimg = $this ->bmob(array('where={"id":'.$list4['chieftain_uid'].'}'));
			$list4['honorary_chieftain_name'] = $ho_info['name'] ?: "";
			$list4['honorary_chieftain_headimg'] = $ho_headimg['headimg'] ?: "";
			$list4['chieftain_name'] = $ch_info['name'] ?: "";
			$list4['chieftain_headimg'] = $ch_headimg['headimg'] ?: "";
			$cr_info = M('t_family_member')->where(array('uid'=>$list4['creator_uid'],'fid'=>$list4['fid']))->find();
			$list4['uname'] = $cr_info['name'] ?: "";
		}
		//数据整理

		

		$arr = array(
			0 => $list1,
			1 => $list2,
			2 => $list3,
			3 => $list4,
			);
		

		$this ->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 退出家族 如果是族长,名誉族长,创建者 家族全部解散
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function quit(){
		$id = I('id');
		$fid = I('fid');
		$type = I('type'); //1 退出 2 踢出
		$info = M('t_family_member')->where(array('id' => $id,'fid'=>$fid))->find();
		if ($info && ($info['uid'] == $info['creator_uid'])) {
			$leader = 1;
			if ($type == 2) {
				$this->ajaxError('操作非法！');
			}
			
		}
		//家族的族长,名誉族长
		$family = M('t_family')->where(array('id' => $fid))->find();
		if ($family['honorary_chieftain_uid'] == $uid ) {
			//$leader = 1
			//最新逻辑 族长名誉族长 卸任退出 2018-9-25
			M('t_family')->where(array('id' => $fid))->save(array('honorary_chieftain_uid' => -1));	
		}
		if ($family['chieftain_uid'] == $uid) {
			M('t_family')->where(array('id' => $fid))->save(array('chieftain_uid' => -1));
		}

		//更新家族及家族成员
		if (isset($leader)) {


			//删除家族，即更新状态
			//$update = array();
			//$update['status'] = 1;
			//M('t_family')->where(array('id' => $fid))->save($update);
			M('t_family')->where(array('id' => $fid))->delete();

			//更新家族成员
			$members = M('t_family_member')->where(array('fid' => $fid))->select();
			$db = M('t_family_member');
			$db2 = M('t_family_message');
			foreach ($members as $key => $val) {
				//$db->where(array('id' => $val['id']))->save(array('del_flg' => 2));
				$db->where(array('id' => $val['id']))->delete();
				$db2->where(array('fid' => $fid))->delete();
			}
			//$res = $this -> batch_update('t_family_member',$up,'del_flg');
			//print_r($res);exit;
			
			//$this->ajaxSuccess('成功');	
		}else{
			$update = array();
			$update['del_flg'] = 2;
			$update['update_time'] = $this -> get_millisecond();

			//$res = M('t_family_member')->where(array('uid' => $uid,'fid' =>$fid))->save($update);
			$res = M('t_family_member')->where(array('id' => $id,'fid' =>$fid))->delete();
			if (!$res) {
				$this->ajaxError('操作失败！');
			}
		}
		
		$this->ajaxSuccess('成功',0,$update);
			
	}
}