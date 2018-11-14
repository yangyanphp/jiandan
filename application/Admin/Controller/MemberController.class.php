<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MemberController extends AdminbaseController{
    
	protected $member_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->member_model = D("Common/Member");
		
	}
	
	//用户列表
	public function index(){
		$search=I('search');
        $where=[];
		if($search){
		    $where['nickname']=['like','%'.$search.'%'];
        }
		$data=$this->member_model
			->field('jd_member.*,jd_return_level.level_name')
			->join('jd_return_level on jd_return_level.id = jd_member.level_id')
            ->where($where)
            ->page($_GET['p'].',10')
			->select();

        $count=$this->member_model
            ->field('jd_member.*,jd_return_level.level_name')
            ->join('jd_return_level on jd_return_level.id = jd_member.level_id')
            ->where($where)
            ->count();
        $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
		$this->assign("data",$data);
        $this->assign('page',$show);// 赋值分页输出
		$this->display();
	}
	
	//用户添加
	public function add(){
		$this->display();
	}
	
	//返现等级添加提交
	public function add_post(){
		if(IS_POST){
		$fanxian['level_name'] = $_POST['level_name'];
		$fanxian['order_money'] = $_POST['order_money'];
		$fanxian['zigou_money'] = $_POST['zigou_money'];
		$fanxian['default_money'] = $_POST['default_money'];
		$fanxian['zhiding_money'] = $_POST['zhiding_money'];
		$fanxian['is_default'] = $_POST['is_default'];
		$pwd = $_POST['pwd'];
		if(!empty($pwd)){
			$fanxian['pwd'] = md5($pwd);
		}
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
	
	//用户编辑
	public function edit(){
		$id=I("get.id",0,'intval');
		$item=$this->member_model
			->field('jd_member.*,jd_return_level.level_name')
			->join('jd_return_level on jd_return_level.id = jd_member.level_id')
			->where("jd_member.id=".$id)
			->find();
		$this->assign($item);
		$this->display();
	}
	
	//用户编辑提交
	public function edit_post(){
		
		if (IS_POST) {
			$id = $_POST['id'];
			$fanxian['use_money'] = $_POST['use_money'];
			$fanxian['cash_total'] = $_POST['cash_total'];
			$fanxian['total_return'] = $_POST['total_return'];
			$fanxian['level_id'] = $_POST['level_id'];
			$fanxian['account'] = $_POST['account'];
			$fanxian['realname'] = $_POST['realname'];
			$fanxian['is_manager'] = $_POST['is_manager'];
			$pwd = $_POST['pwd'];
			if(!empty($pwd)){
				$fanxian['pwd'] = md5($pwd);
			}
			$res = $this->member_model->where('id='.$id)->save($fanxian);
			if ($res) {
				if($fanxian['is_manager']==1){
					$data['is_manager']=0;
					$this->member_model->where('id!='.$id)->save($data);
				}
				$this->success("保存成功！", U("admin/member/index"));
			} else {
				$this->error("保存失败！");
			}
			
		}
	}
	
	// 用户删除
	public function delete(){
		$id = I("post.id",0,"intval");
		$this->member_model->delete($id);
	}
	
	
}