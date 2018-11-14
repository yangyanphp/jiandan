<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class TixianController extends AdminbaseController{
    
	protected $tixian_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->tixian_model = D("Common/Tixian");
		
	}
	
	//提现编辑
	public function index(){
		
		$item=$this->tixian_model->where(array('id'=>1))->find();
		$this->assign($item);
		$this->display();
	}
	
	// 提现编辑提交
	public function edit_post(){
		
		if (IS_POST) {
			$id = $_POST['id'];
			$tixian['day'] = $_POST['day'];
			$tixian['tixian_money'] = $_POST['tixian_money'];
			if(!empty($_POST['black_name'])){
				$tixian['black'] = implode(",",$_POST['black_name']);
			}else{
				$tixian['black'] = "";
			}
			
			$tixian['remark'] = $_POST['remark'];
			
			$res = $this->tixian_model->where('id='.$id)->save($tixian);
			if ($res) {
				$this->success("保存成功！", U("admin/tixian/index"));
			} else {
				$this->error("保存失败！");
			}
			
		}
	}
	
	public function checkBlack(){
		$id = $_POST['mid'];
		$item=$this->tixian_model->where(array('id'=>1))->find();
		if(empty($item['black'])){
			echo 1;
		}else{
			$arr = explode(',',$item['black']);
			$flag = in_array($id,$arr);
			if($flag){
				echo 2;
			}else{
				echo 1;
			}
		}
	}
	
}