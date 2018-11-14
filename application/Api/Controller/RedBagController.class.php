<?php
namespace Api\Controller;

use Common\Controller\HomebaseController;
use function PHPSTORM_META\type;

class RedBagController extends HomebaseController{


    //拉新红包列表页面
    public function index(){

        $uid=I('mid',80);  //用户id


//        $token = I('token');
//        if(empty($uid)||empty($token)){
//            echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'请传用户id和token','data'=>''));exit;
//        }
//
//        $this->device_reprat($token);





        $fans_all = M('member2')->where(array('share_id'=>$uid))->order('share_add_time desc')->select();

        if(empty($fans_all)){
            echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'暂无数据','data'=>''));exit;
        }
//        var_dump($fans_all);die;

        foreach($fans_all as $k=>$v){
            $tmp[$v['group_fans']][] = $v;

        }
        $array = array();
        foreach($tmp as $k=>$val){
            array_push($array, $val);
        }
//var_dump($array);die;
        $peoples = count($fans_all);                                                  //总人数

        $group_num = $peoples%3==0?$peoples/3:ceil($peoples/3);                //用户拉新红包的组数
        $redbag_money = M('red')->where(array('id'=>1))->getField('red_val');   //每个红包的值

        $redbag_money_all = ($peoples%3==0?$peoples%3:floor($peoples/3))*$redbag_money;

//拉新红包总额
        $data = array(
            'red_val'=>M('red')->where(array('id'=>1))->getField('red_val'),

            'all_money' => $redbag_money_all,
            'group_num' => $group_num,
            'peoples'   => $peoples,
            'list' => $array
        );
//        var_dump($data);die;

        echo json_encode(array('status'=>1,'code'=>1000,'msg'=>'拉新红包列表','data'=>$data));exit;
    }


    //拉新3人 送红包
   public function get_redbag(){
       $uid=I('post.mid',0,'intval');  //用户id
       $group = I('post.group',0,'intval');  //组号
//       $uid = 80;
//       $group = 1;
       if(empty($uid)||empty($group)){

           echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'参数不能为空','data'=>''));exit;
       }


       $get_red_status = M('member2')->where(array('share_id'=>$uid,'group_fans'=>$group))->getField('status');  //用户拉新红包领取状态

       if($get_red_status==1){
           echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'该红包已经领取过了','data'=>''));exit;
       }else{
           M()->startTrans();
          $res = M('member2')->where(array('share_id'=>$uid,'group_fans'=>$group))->setField('status',1);

           if($res){
               $red_val = M('red')->where(array('id'=>1))->getField('red_val'); //每个红包的金额大小
               $result = M('member')->where(array('id'=>$uid))->setInc('use_money',$red_val);     //账户余额 加钱

               if($result){

                   M('money_log')->add(array('mid'=>$uid,'money'=>$red_val,'type'=>1,'add_time'=>time()));      //红包写入资金记录表

                   M()->commit();
                   echo   json_encode(array('status'=>1,'code'=>1000,'msg'=>'红包发送成功','data'=>''));exit;

               }else{

                   M()->rollback();
                   echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'红包领取失败，请重试','data'=>''));exit;

               }
          }else{

               M()->rollback();
               echo json_encode(array('status'=>0,'code'=>1002,'msg'=>'红包领取失败，请重试','data'=>''));exit;

           }
       }


    }

    //token检测
    public function device_reprat($token){
        if(empty($token)){ return return_result(0,1002,'缺少token','');}
        $res = M('member')->where(array('token'=>$token))->find();

        if(!$res){ //有重复登陆

            echo json_encode(array('status'=>0,'code'=>1111,'msg'=>'您已经在另一台设备登录过','data'=>''));exit;
        }
    }
}