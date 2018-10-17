<?php
/**
 * 用户
 * @since   2018/09/19 创建
 * @author  
 */

namespace Home\Api;


use Home\ORG\Str;

class User extends Base {
	
	public function index() {
		
	}

	/**
	 * 我的书友列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function tree_friends() {
		$uid = I('uid');

		$where = array();
		$where['uid'] = $uid;
		$list = M('t_follow')->alias("d")
				->join("left join t_user u on d.uid=u.id")
		        ->field('d.id,d.uid,d.to_uid')
		        ->where($where)
		        ->order("d.create_time desc")
		        //->limit($page_size)
		        ->select();
		$db = M('t_user');
		foreach ($list as $key => $val) {
			$info = $db->where(array('id' => $val['to_uid']))->find();
			$list[$key]['to_name'] = $info['nickname'] ?: '';
			$list[$key]['to_headimg'] = $info['headimg'] ?: '';
			$list[$key]['to_sign'] = $info['sign'] ?: '';
		}

		$this->ajaxSuccess('成功',0,$list);
	}

	/**
	 * 关注/取消关注书友
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function follow() {
		$uid = I('uid');
		$to_uid = I('to_uid');
		$status = I('status');

		if ($status == 1) {
			//关注
			$arr = array();
			$arr['uid'] = $uid;
			$arr['to_uid'] = $to_uid;
			$arr['create_time'] = $this ->get_millisecond();
			M('t_follow')->add($arr);

			//notic表
			$user = M('t_user')->where(array('id'=>$uid))->find();
			$notice = array();
			$notice['uid'] = $uid;
			$notice['type'] = 2;
			$notice['oid'] = '';
			$notice['status'] = 1;
			$notice['create_time'] = $this -> get_millisecond();
			$notice['to_uid'] = $to_uid;
			$notice['title'] = "关注了您";
			M('t_notice')->add($notice);

			$this->ajaxSuccess('成功');
		}else{
			M('t_follow')->where(array('uid'=>$uid,'to_uid'=>$to_uid))->delete();

			$this->ajaxSuccess('成功');
		}
	}

	/**
	 * 用户信息
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function user(){
		$uid = I('uid');
		$follow_uid = I('follow_uid');

		$info = M('t_user')->field('id,nickname as uname,headimg,phone,sex,sign')->where(array('id' => $uid))->find();
		if (!$info) {
			$this -> ajaxError('用户不存在！');
		}
		//是否关注follow_uid
		$follow = M('t_follow')->where(array('uid' => $follow_uid,'to_uid' => $uid))->find();
		if ($follow) {
			$info['follow'] = 1;
		}else{
			$info['follow'] = 0;
		}

		if ($info) {
			$this -> ajaxSuccess('成功',0,$info);
		}else{
			$this -> ajaxError('用户不存在！');
		}
	}

	/**
	 * 我的关注人的动态，文章列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function followlist(){
		$uid = I('uid');

		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;
		//$pagesize = !empty($postData['pagesize']) ? $postData['pagesize'] : 10;
		//查询该用户关注的所有人
		//where
		$where = array();
		
		if ($page_id) {
			$where['a.id'] = array('lt',$page_id);
		}
		
		//
		$uids = M('t_follow')->field('to_uid')->where(array('uid' => $uid))->select();
		// 
		$uids = array_column($uids, 'to_uid');
		if (!$uids) {
			$this -> ajaxSuccess('成功');
		}
		//总数
		/*$rows  = 0;
		$count = M('t_share_count')->where($where)->count();
		//print_r($count);exit;
		if ($count > 10) {
			$rows = $count - 10;
			//返回空数组
		}*/
		$where['a.uid'] = array('in', $uids);
		$list_article = M('t_article')->alias("a")
				->join("left join t_user u on a.uid=u.id")
		        ->field('a.id,a.uid,a.title,a.cover,a.create_time,u.nickname as uname,u.headimg')
		        ->where($where)
		        ->order("a.create_time desc")
		        ->limit($page_size)
		        ->select();
		
		foreach ($list_article as $key => $val) {
			$res = hex2bin($val['title']);
			if ($res !== false) {
				$list_article[$key]['title'] = $res;
				unset($res);
			}
			$list_article[$key]['type'] = 1;
		};
		$list_dynamic = M('t_dynamic')->alias("a")
				->join("left join t_user u on a.uid=u.id")
		        ->field('a.id,a.uid,a.title,a.content,a.create_time,u.nickname as uname,u.headimg,a.share_num,a.reply_num')
		        ->where($where)
		        ->order("a.create_time desc")
		        ->limit($page_size)
		        ->select();
		//        echo M('t_family_task')->_sql();
		//print_r($list);exit;
		$db_zan = M('t_obj_relation_dynamic');
		foreach ($list_dynamic as $key => $val) {
			$zan = $db_zan->where(array('did' => $val['id']))->count();
			$list_dynamic[$key]['zan_num'] = $zan;
			unset($zan);
			if ($uid) {
				$is_zan = $db_zan->where(array('did'=>$val['id'],'uid'=>$uid))->find();
				if ($is_zan) {
					$list_dynamic[$key]['zan'] = 1;
				}else{
					$list_dynamic[$key]['zan'] = 0;
				}
			}else{
				$list_dynamic[$key]['zan'] = 0;
			}
			
			
			$res = $this -> is_not_json($val['content']);
			if (!$res) {
				$list_dynamic[$key]['content'] = json_decode($val['content'],1);
			}
			$res = hex2bin($val['title']);
			if ($res !== false) {
				$list_dynamic[$key]['title'] = $res;
				unset($res);
			}
			$list_dynamic[$key]['type'] = 2;
		}
		//print_r($list_dynamic);exit;
		$data = array_merge($list_article,$list_dynamic);
		//print_r($data);exit;
		foreach ($data as $key => $row)
		{
			$volume[$key]  = $row['create_time'];
			//$edition[$key] = $row['edition'];
		}

		array_multisort($volume, SORT_DESC, $data);
		//print_r($data);exit;
		$data = array_slice($data,0,$page_size);

		$this->ajaxSuccess('成功',0,$data);

	}

	function is_not_json($str){
    	return is_null(json_decode($str));
	}
}