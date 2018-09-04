<?php
/**
 * 家族
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

		$arr = array();
		$arr['name'] = $postData['name'];
		$arr['start_time'] = $postData['start_time'];
		$arr['end_time'] = $postData['end_time'];
		$arr['type'] = 0;
		$arr['post_uid'] = $uid;
		$arr['cover'] = $postData['cover'];
		$arr['content'] = $postData['content'];
		$arr['fid'] = $postData['fid'];
		$arr['create_time'] = time();
		$arr['update_time'] = 0;
		$arr['status'] = 0;

		//存入数据
		$res = M('t_family_task') -> add($arr);
		if (!$res) {
			$this->ajaxError('添加失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 编辑家族任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function edit(){
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
		$arr['type'] = 0;
		$arr['cover'] = $file['filepath'];
		$arr['content'] = $postData['content'];
		$arr['update_time'] = time();

		//存入数据
		$res = M('t_family_task') ->where(array('id' => $tid))-> save($arr);
		if (!$res) {
			$this->ajaxError('更新失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

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


		//存入数据
		$res = M('t_family_task') ->where(array('id' => $tid))-> save($update);
		if (!$res) {
			$this->ajaxError('删除失败');
		}
		$this->ajaxSuccess('成功',0,$arr);
	}

	/**
	 * 家族任务列表
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function list(){
		$fid = I('fid');

		//权限校验todu

		//获取数据
		$list = M('t_family_task')->field('id,name,start_time,end_time,cover,content')->where(array('fid' => $fid))->order('create_time desc')->select();

		//数据整理，按照代数分组
		/*$n_list = array();
		foreach ($list as $key => $val) {
			$n_list[$val['generational_code']][] = $val;
		}*/

		$this ->ajaxSuccess('成功',0,array('list' => $list));
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
		$info = M('t_family_task')->field('id,name,start_time,end_time,cover,content')->where(array('id' => $tid))->find();

		//数据整理，按照代数分组
		/*$n_list = array();
		foreach ($list as $key => $val) {
			$n_list[$val['generational_code']][] = $val;
		}*/

		$this ->ajaxSuccess('成功',0,$info));
	}

	/**
	 * 接受任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function accept(){
		$tid = I('tid');
		$uid = I('uid');

		$arr = array();
		//$arr['']
	}

	/**
	 * 完成任务
	 * @access public
	 * @param 
	 * @param array $options  
	 * @return json
	 */
	public function accept(){
		
	}
}