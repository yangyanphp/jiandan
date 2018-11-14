<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class  RedController extends AdminbaseController{
//

    //提现编辑
    public function index(){
// var_dump(I('red_val'));die;
       $data = M('red')->find();
        if(IS_POST){

            $res = M('red')->where(array('id'=>1))->setField('red_val',I('red_val'));

            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'设置成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'设置失败，请重试'));
            }
        }


        $this->assign('red',$data['red_val']);

        $this->display();
    }

//    // 提现编辑提交
//    public function edit_post(){
//
//        if (IS_POST) {
//            $id = $_POST['id'];
//            $tixian['day'] = $_POST['day'];
//            $tixian['tixian_money'] = $_POST['tixian_money'];
//            if(!empty($_POST['black_name'])){
//                $tixian['black'] = implode(",",$_POST['black_name']);
//            }else{
//                $tixian['black'] = "";
//            }
//
//            $tixian['remark'] = $_POST['remark'];
//
//            $res = $this->tixian_model->where('id='.$id)->save($tixian);
//            if ($res) {
//                $this->success("保存成功！", U("admin/tixian/index"));
//            } else {
//                $this->error("保存失败！");
//            }
//
//        }
//    }
//
//    public function checkBlack(){
//        $id = $_POST['mid'];
//        $item=$this->tixian_model->where(array('id'=>1))->find();
//        if(empty($item['black'])){
//            echo 1;
//        }else{
//            $arr = explode(',',$item['black']);
//            $flag = in_array($id,$arr);
//            if($flag){
//                echo 2;
//            }else{
//                echo 1;
//            }
//        }
//    }

}