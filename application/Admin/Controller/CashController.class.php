<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class CashController extends AdminbaseController{
    
//	protected $tixian_model;
//
//	public function _initialize() {
//		parent::_initialize();
//		$this->tixian_model = D("Common/Tixian");
//
//	}
//
	//提现编辑
	public function index(){


	    $join = array('left join jd_member m on m.id=m_l.mid');
	    $field = 'm.nickname,m.avatar,m.cash_total,m_l.money,m.total_return,m.share_add_time,m_l.*';
	    $count = count(M('money_log')->group('mid')->select());

        $Page       = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出


        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = M('money_log')->alias('m_l')->join($join)->field($field)->group('m_l.mid')->order('m_l.add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();


        foreach($list as $k=>$v){
            $list[$k]['fans_count'] = M('member')->where(array('share_id'=>$v['mid']))->count();
        }
//        var_dump($list);die;

        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板

	}

	//查询该用户的粉丝
	public function fans(){
	    $mid = I('mid'); //用户id
//	    var_dump($mid);die;
        $count = M('member')->where(array('share_id'=>$mid))->count();

        $Page       = new \Think\Page($count,25);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出

        $list =  M('member')->where(array('share_id'=>$mid))->order('share_add_time desc')->field('id,avatar,nickname,cash_total,total_return')->limit($Page->firstRow.','.$Page->listRows)->select();
//        var_dump($list);die;
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->display(); // 输出模板
    }


	
	// 提现编辑提交
//	public function edit_post(){
//
//		if (IS_POST) {
//			$id = $_POST['id'];
//			$tixian['day'] = $_POST['day'];
//			$tixian['tixian_money'] = $_POST['tixian_money'];
//			if(!empty($_POST['black_name'])){
//				$tixian['black'] = implode(",",$_POST['black_name']);
//			}else{
//				$tixian['black'] = "";
//			}
//
//			$tixian['remark'] = $_POST['remark'];
//
//			$res = $this->tixian_model->where('id='.$id)->save($tixian);
//			if ($res) {
//				$this->success("保存成功！", U("admin/tixian/index"));
//			} else {
//				$this->error("保存失败！");
//			}
//
//		}
//	}
//
//	public function checkBlack(){
//		$id = $_POST['mid'];
//		$item=$this->tixian_model->where(array('id'=>1))->find();
//		if(empty($item['black'])){
//			echo 1;
//		}else{
//			$arr = explode(',',$item['black']);
//			$flag = in_array($id,$arr);
//			if($flag){
//				echo 2;
//			}else{
//				echo 1;
//			}
//		}
//	}
	
}