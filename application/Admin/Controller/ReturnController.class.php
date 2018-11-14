<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ReturnController extends AdminbaseController{
    
	protected $return_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->return_model = D("Common/ReturnLevel");
		
	}
	
	// 返现等级列表
	public function index(){
		$data=$this->return_model->select();
		$this->assign("data",$data);
		$this->display();
	}
	
	//返现等级添加
	public function add(){
		$this->display();
	}
	
	//返现等级添加提交
	public function add_post(){
		if(IS_POST){
		$fanxian['level_name'] = $_POST['level_name'];
		$fanxian['zigou_return'] = $_POST['zigou_return'];
		$fanxian['daili_return'] = $_POST['daili_return'];
		$fanxian['is_default'] = $_POST['is_default'];
		$id=$this->return_model->add($fanxian);
		if ($id) {
			if($_POST['is_default']==1){
				$data['is_default']=0;
				$this->return_model->where('id!='.$id)->save($data);
			}
			//$this->success(L('ADD_SUCCESS'), U("admin/return/index"));
			redirect(U("admin/return/index"));
		} else {
			redirect(U("admin/return/index"));
		}
			
		}
	}
	
	//返现等级编辑
	public function edit(){
		$id=I("get.id",0,'intval');
		$item=$this->return_model->where(array('id'=>$id))->find();
		$this->assign($item);
		$this->display();
	}
	
	// 返现等级编辑提交
	public function edit_post(){
		
		if (IS_POST) {
			$id = $_POST['id'];
			$fanxian['level_name'] = $_POST['level_name'];
			$fanxian['zigou_return'] = $_POST['zigou_return'];
			$fanxian['daili_return'] = $_POST['daili_return'];
			$fanxian['is_default'] = $_POST['is_default'];
			
			$res = $this->return_model->where('id='.$id)->save($fanxian);
			if ($res) {
				if($fanxian['is_default']==1){
					$data['is_default']=0;
					$this->return_model->where('id!='.$id)->save($data);
				}
				$this->success("保存成功！", U("admin/return/index"));
			} else {
				$this->error("保存失败！");
			}
			
		}
	}
	
	// 等级删除
	public function delete(){
		$id = I("post.id",0,"intval");
		$item=$this->return_model->where(array('id'=>$id))->find();
		if($item['is_default']==1){
			echo 1;die;
		}else{
			$this->return_model->delete($id);
			echo 2;die;
		}	
	}
	
	
}