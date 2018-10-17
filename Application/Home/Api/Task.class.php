<?php
/**
 * 任务
 * @since   2018/08/24 创建
 * @author  lingxiao
 */

namespace Home\Api;


use Home\ORG\Str;

class Task extends Base {
	public function index() {
	   
	}


	/**
	 * 创建家族任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function add(){
		$postData = I('post.');

		$uid = I('uid');
		//权限todu

		//数据整理todu
		/*$cover = $_FILES['cover'];
		//封面
		if($cover['tmp_name']){
			$file = uploadfiles('home',array(),$cover);
			if($file['code'] != 0){
				Response::error(ReturnCode::EMPTY_PARAMS, '封面:'.$file['error_m']);
			}
		}*/

		//最多十个任务
		if ($type != 1) {
			$rows = M('t_family_task')->where(array('fid' => $postData['fid'],'type' => 1))->count();
			if ($rows >= 10) {
				$this -> ajaxError('任务上限,不可创建！'); 
			}	
		}
		
		$arr = array();
		$arr['name'] = $postData['name'];
		$arr['start_time'] = $postData['start_time'];
		$arr['end_time'] = $postData['end_time'];
		$arr['type'] = $postData['type'];
		$arr['post_uid'] = $uid;
		$arr['cover'] = $postData['cover'];
		$arr['content'] = $postData['content'];
		$arr['fid'] = $postData['fid'];
		$arr['create_time'] = $this -> get_millisecond();
		$arr['update_time'] = 0;
		$arr['status'] = 0;
		$arr['url'] = $postData['url'] ?: '';
		$arr['weight'] = $postData['weight'] ?: '';
		$arr['locks'] = 0;

		//存入数据
		$res = M('t_family_task') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}

		//
		$info = M('t_family_member')->where(array('fid' => $postData['fid'],'uid'=>$postData['uid']))->find();
		$arr['uname'] = $info['surname'].$info['name'];
		$arr['id'] = $res;
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 编辑家族任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	/*public function edit(){
		$tid = I('tid');
		$postData = I('post.');

		//$cover = $_FILES['cover'];
		//封面
		if($cover['tmp_name']){
			$file = uploadfiles('home',array(),$cover);
			if($file['code'] != 0){
				Response::error(ReturnCode::EMPTY_PARAMS, '封面:'.$file['error_m']);
			}
		}

		$arr = array();
		$arr['name'] = $postData['name'];
		$arr['start_time'] = $postData['start_time'];
		$arr['end_time'] = $postData['end_time'];
		$arr['type'] = $postData['type'];;
		$arr['cover'] = $file['filepath'];
		$arr['content'] = $postData['content'];
		$arr['update_time'] = time();
		$arr['url'] = $postData['url'];
		$arr['weight'] = $postData['weight'];

		//存入数据
		$res = M('t_family_task') ->where(array('id' => $tid))-> save($arr);
		if (!$res) {
			$this->ajaxError('更新失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}*/

	/**
	 * 删除家族任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function del(){
		$tid = I('tid');

		$update = array();
		$update['status'] = 1;
		$update['update_time'] = $this -> get_millisecond();

		//存入数据
		$res = M('t_family_task') ->where(array('id' => $tid))-> save($update);
		if (!$res) {
			$this->ajaxError('删除失败');
		}
		$this->ajaxSuccess('成功',0,$update);
	}

	/**
	 * 任务列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function lists(){
		$uid = I('uid');
		$fid = I('fid');
		$type = I('type');
		$page_id = I('page_id');
		$page_size = I('page_size');
		$page_size = !empty($page_size) ? $page_size : 10;
		//权限校验todu

		////需要再lock表插入记录，锁定任务
		if ($type == 1) {
			$info = M('t_family_task')->field('id')->where(array('type' =>1))->order('id asc,weight desc')->select();
			$exit = M('t_task_lock')->where(array('uid' => $uid))->count();
			//print_r($info);
			if (empty($exit)) {
				//echo "string";exit;
				$new = array();
				foreach ($info as $key => $val) {
					$item = array();
					$item['tid'] = $val['id'];
					$item['uid'] = $uid;
					$item['type'] = 1;
					$item['create_time'] = $this -> get_millisecond();
					$new[] = $item;
				}
				array_shift($new);
				//print_r($new);exit;
				M('t_task_lock')->addAll($new);
			}
		}

		//获取数据
		$where = array();
		$where['status'] = array('neq',1);
		//系统任务
		if ($type == 1) {
			$where['type'] = $type;
			$order = "weight desc,create_time desc";
		}else{
			$where['fid'] = $fid;
			$where['type'] = $type;
			$order = "create_time desc";
		}
		
		if ($page_id) {
			$where['id'] = array('lt',$page_id);
		}

		//print_r($where);
		$list = M('t_family_task')
		        ->field('id,name,start_time,end_time,cover,post_uid,url,type,weight,status,fid,create_time')
		        ->where($where)
		        ->order($order)
		        ->limit($page_size)
		        ->select();
		//        echo M('t_family_task')->_sql();
		//print_r($list);exit;
		//数据整理，按照代数分组
		$arr = array();
		$db = M('t_family_member');
		$db_log = M('t_task_lock');
		foreach ($list as $key => $val) {
			//print_r($val);
			$info = $db->where(array('fid' => $val['fid'],'uid'=>$val['post_uid']))->find();
			//print_r($info);exit;
			$list[$key]['uname'] = $info['surname'].$info['name'];
			//查询是否有锁
			if($val['type'] == 1){
				$lock = $db_log->where(array('uid' => $uid,'tid' =>$val['id']))->find();
				if($lock) {
					$lock = 1;
				}else{
					$lock = 0;
				}
				$list[$key]['lock'] = $lock;
			}
			//$list[$key]['id'] = $val['id'];
		}

		$this ->ajaxSuccess('成功',0,$list);
	}

	/**
	 * 家族任务详情
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function info(){
		$tid = I('tid');

		//权限校验todu

		//获取数据
		$info = M('t_family_task')->field('id,name,start_time,end_time,cover,content,url,weight,status,type,post_uid,fid,create_time')->where(array('id' => $tid))->find();

		//数据整理，按照代数分组
		/*$n_list = array();
		foreach ($list as $key => $val) {
			$n_list[$val['generational_code']][] = $val;
		}*/
		//
		$infos = M('t_family_member')->where(array('fid' => $info['fid'],'uid'=>$info['post_uid']))->find();
		$info['uname'] = $infos['surname'].$infos['name'];

		$this ->ajaxSuccess('成功',0,$info);
	}

	/**
	 * 接受任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	/*public function accept(){
		$tid = I('tid');
		$uid = I('uid');

		$arr = array();
		//$arr['']
	}*/

	/**
	 * 完成任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function complete(){
		$tid = I('tid');
		$uid = I('uid');
		$type = I('type');

		$update = array();
		$update['status'] = 2;
		$update['update_time'] = $this -> get_millisecond();
		//$update['locks'] = 1;

		$res = M('t_family_task')->where(array('id' => $tid))->save($update);
		if (!$res) {
			$this->ajaxError('操作失败！');
		}
		
		//记录log,谁完成了任务
		$log = array();
		$log['uid'] = $uid;
		$log['tid'] = $tid;
		$log['type'] = $type;
		$log['create_time'] = $this -> get_millisecond();
		M('t_task_log')->add($log);
		
		//
		$where = array();
		$where['id'] = array('gt', $tid);
		$where['type'] = 1;
		$info = M('t_family_task') -> where($where) -> find();
		//print_r($info);exit;
		$locks = M('t_task_lock')->where(array('uid' => $uid))->count();
		if ($locks == 1) {
			//更新
			M('t_task_lock')->where(array('uid' => $uid,'tid' => $tid))->save(array('uid' => $uid,'tid' => -1));
		}else{
			//删除锁
			M('t_task_lock')->where(array('uid' => $uid,'tid' => $info['id']))->delete();
		}

		//更新下一条的锁定状态
		/*$where = array();
		$where['id'] = array('gt', $tid);
		$where['type'] = 1;
		$info = M('t_family_task') -> where($where) -> find();
		//echo M('t_family_task')->_sql();
		//print_r($info);exit;
		if ($info) {
			M('t_family_task')->where(array('id' => $info['id']))->save(array('locks' => 0));
		}*/
		

		$this->ajaxSuccess('成功',0,$update);
	}

	/**
	 * 置为已读 （查看列表置为已读）
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function read() {
		//$uid = I('uid');
		$fid = I('fid');
		$page_id = I('page_id');
		
		$where = array();
		//$where['to_uid'] = $uid;
		$where['fid'] = $fid;
		$where['id'] = array('egt',$page_id);
		$where['type'] = 0;
		$list = M('t_family_task')
			    //->alias("d")
				//->join("left join t_user u on d.uid=u.id")
		        ->field('id')
		        ->where($where)
		        //->order("create_time desc")
		        //->limit($page_size)
		        ->select();
		//echo M('t_notice')->_sql();exit;
		$ids = array_column($list,'id');

		$update = array();
		$update['is_new'] = 2;
		$update['update_time'] = $this -> get_millisecond();

		$opt = array();
		if ($ids) {
			$opt['id'] = array('in', $ids);

			$res = M('t_family_task')->where($opt)->save($update);
			if (!$res) {
				$this -> ajaxError('提交失败！');
			}
		}
		
		$this -> ajaxSuccess('成功', 0 , $update);
	}

	/**
	 * 未读信息个数
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function count(){
		$fid = I('fid');
	
		
		//权限todu

		//where语句整理
		$where = array();
		$where['fid'] = $fid;
		$where['is_new'] = 1;
		
		
		$count = M('t_family_task')->where($where)->count();
		$id = M('t_family_task')->where($where)->order('id desc')->find();

		$this -> ajaxSuccess('成功',0,array('count' => $count,'id' => $id['id'] ?: -1));
	}
}