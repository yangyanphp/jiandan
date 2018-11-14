<?php
namespace Portal\Controller;

use Common\Controller\HomebaseController; 
// use app\td\model\User;
 //use think\Db;
// use think\Request;
// use app\api\model\Pk;

class PkController extends HomebaseController {

    protected $pk_model;
    protected $member_model;
    protected $page_num = 20;  //分页每页数据
    public function _initialize() {
        parent::_initialize();
        $this->pk_model = M("Pk");
        $this->member_model = M("Member");
    }

    //pk列表
    public function index()
    {

//        $page = I('page'); //页码
//        if($page==''){
//            $limit = "0,$this->page_num";
//        }else{
//            $p = ($page-1)*$this->page_num;
//            $limit = "$p,$this->page_num";
//        }

         //未开始
        //$jiaoc = Db::table('td_pk')->where('status',0)->field("id,create_id,join_id,create_name,create_avatar,join_avatar,join_name")->select();
        //$pk_list['list'] =  $this->pk_model->where(array('status'=>0))->limit($limit)->select();
        $pk_list['list'] =  $this->pk_model->where(array('status'=>0))->select();
        $pk_list['quan']=[
            '0'=>10,
            '1'=>50,
            '2'=>100,
            '3'=>200,
            '4'=>300,
            '5'=>500
        ];
        $pk_list['member']=100;

      
        return return_result(1,1000,'成功',$pk_list);

    }

    //pk创建
    public function pkCreate()
    {
        $user_id=I('user_id');
        $num=I('num');
//        $user_id=114;
//        $num=100;
        if(empty($user_id)){
            return return_result(0,1002,'用户ID不能为空','');
        }
        if(empty($num)){
            return return_result(0,1002,'pk券不能为空','');
        }
        //查询用户有多少pk券
        $pk = $this->member_model->field('id,pk_num,nickname,avatar')->where(array('id'=>$user_id))->find();
        
        if($pk['pk_num']<$num){
            return return_result(0,1002,'pk券不足','');
        }
        $data=[
            'create_id'=>$user_id,
            'create_name'=>$pk['nickname'],
            'create_avatar'=>$pk['avatar'],
            'num'=>$num
        ];
        M()->startTrans();
        $r_id=$this->pk_model->add($data);
        if($r_id >0) {
            $rt = $this->member_model->where(array("id"=>$user_id))->setDec('pk_num', $num);
            if (!$rt) {
                M()->rollback();
                return return_result(0,1002,'请求失败','');
            }
        }else{

           M()->rollback();
            return return_result(0,1002,'请求失败','');
        }
        M()->commit();
        return return_result(1,1000,'创建成功','');
    }

    //pk加入
    public function  pkAddUser()
    {
        $user_id=I('user_id');
        $num=I('num');
        $pkid=I('pkid');
//        $pkid=3;
//        $user_id=116;

        if(empty($user_id)){
            return return_result(0,1002,'用户ID不能为空','');
        }
        if(empty($pkid)){
            return return_result(0,1002,'pkID不能为空','');
        }

        $pkList=$this->pk_model->where(array('id'=>$pkid))->find();

        if(empty($pkList)){
            return return_result(0,1002,'该PK不存在','');
        }
        if($pkList['status'] ==1){
            return return_result(0,1002,'该PK已结束','');
        }
        if($pkList['join_id'] >0){

            return return_result(0,1002,'该PK人数已满','');
        }

        //查询用户有多少pk券
        $pk = $this->member_model->field('id,pk_num,nickname,avatar')->where(array('id'=>$user_id))->find();
        //判断该用户的pk券是否够进该房间
        if($pk['pk_num']<$pkList['num']){
            return return_result(0,1002,'pk券不足','');
        }
        $data=[
            'join_id'=>$user_id,
            'join_name'=>$pk['nickname'],
            'join_avatar'=>$pk['avatar'],
        ];

        M()->startTrans();
        $r_id=$this->pk_model->where(array('id'=>$pkid))->save($data);
        if($r_id >0) {
            $rt = $this->member_model->where(array("id"=> $user_id))->setDec('pk_num', $pkList['num']);//setDec 减少加入用户的pk券
            if (!$rt) {
                M()->rollback();
                return return_result(0,1002,'请求失败');
            }
        }else{
           M()->rollback();
            return return_result(0,1002,'请求失败');
        }
        M()->commit();
        $r_data=$this->pk_model->where(array('id'=>$pkid))->find();
        return return_result(1,1000,'加入成功',$r_data);
    }

//单击开始
    public function  clickStartPk(){
        $pkid=I('pkid');
        $userid=I('user_id');
//        $pkid=5;
//        $userid=115;
        $rs=$this->pk_model->where(array('id'=>$pkid))->find();
        if(empty($userid)||empty($pkid)){
            return return_result(0,1002,'参数不能为空','');
        }

        if($userid == $rs['create_id']){
           $up=[
             'create_start'=>1
           ];
        }elseif($userid == $rs['join_id']){
            $up=[
                'join_start'=>1
            ];
        }

        $this->pk_model->where(array("id"=>$pkid))->save($up);


        $data=$this->pk_model->where(array("id"=>$pkid))->find();
        if($data['create_id'] ==$userid && $data['join_start'] == 0){
            return return_result(1,1000,'对方未准备',$data);
        }
        if($data['join_id'] ==$userid && $data['create_start'] == 0) {
            return return_result(1, 1000, '对方未准备', $data);
        }
        return return_result(1, 1000, '准备成功', $data);
    }


    //pk快速加入
    public function  pkSpeedAddUser()
    {
        $user_id=I('user_id');
        $pkid=I('pkid');
        if(empty($user_id)){
            return return_result(0,1002,'用户ID不能为空','');
        }

        //查询用户有多少pk券
        $pk = $this->member_model->field('id,pk_num,nickname,avatar')->where(array('id'=>$user_id))->find();
        //查询有空位的并且pk小于该用户的厂
          $where=' status =0 and num <='.$pk['pk_num'].' and create_id <> '.$user_id.' and join_id is null ';
          $canAddList=$this->pk_model->field('id,num,create_id')->where($where)->find();
          if(!$canAddList){
              return return_result(0,1002,'没有空余房间请创建','');
          }
          //print_r($canAddList);exit;
        //如果没有自己创建新的pk获取最小的pk厂劵

        M()->startTrans();
//        if(empty($canAddList)){
//            $into=[
//                'create_id'=>$user_id,
//                'create_name'=>$pk['nickname'],
//                'create_avatar'=>$pk['avatar'],
//                'num'=>100
//            ];
//            $r_id=Db::table('td_pk')->insert($into);
//        }else{
            $data=[
                'join_id'=>$user_id,
                'join_name'=>$pk['nickname'],
                'join_avatar'=>$pk['avatar'],
            ];
            $r_id=$this->pk_model->where(array('id'=>$canAddList['id']))->save($data);
       // }

        if($r_id >0) {
            $rt = $this->member_model->where(array("id" => $user_id))->setDec('pk_num', $canAddList['num']);//setDec 减少加入用户的pk券
            if (!$rt) {
                M()->rollback();
                return return_result(0,1002,'请求失败');
            }
        }else{
            //echo 1;exit;
           M()->rollback();
            return return_result(0,1002,'请求失败');
        }
        M()->commit();
        $r_data=$this->pk_model->where(array('id'=>$pkid))->find();
        return return_result(1,1000,'加入成功',$r_data);
    }


    /*点击生成石头剪刀布
     *   自己出
     * 1:石头
     * 2:剪子
     * 3：布
     */

    public function  setUserOut()
    {

        $user_id=I('user_id');
        $pkid=I('pkid');      //pk表ID
        $user_out=I('out');   //出牌的人的石头剪子布
//        $user_id= 115;
//        $pkid=1;
//        $user_out= 1;
        if(empty($user_id) || empty($pkid) || empty($user_out)){
            return return_result(0,1002,'参数不全','');
        }
        //获取pk

        $is_pk=$this->pk_model->where(array('id'=>$pkid))->find();
        //print_r($pk);exit;
        //PK胜率
        //$pk_win_rate=$this->member_model->where(array('id'=>$user_id))->field('pk_win_rate')->find();
        //print_r($pk_win_rate);exit;
        if($is_pk['status'] ==1){
            return return_result(1,1000,'比赛已完成！',$is_pk);
        }
        if($is_pk['create_start'] ==0 || $is_pk['join_start'] == 0){
            return return_result(0,1002,'用户未准备开始','');
        }

        if($is_pk['create_id'] == $user_id){
            if($is_pk['create_out'] >0){
                if($is_pk['join_out'] >0){
                    return return_result(1,1000,'你已出牌！',$is_pk);
                }else{
                    return return_result(0,1002,'你已出牌！',$is_pk);
                }

            }else{
                $this->pk_model->where(array('id'=>$pkid))->save(array('create_out'=>$user_out));
            }

        }
        if($is_pk['join_id'] == $user_id){
            if($is_pk['join_out'] >0){
                if($is_pk['create_out'] >0){
                    return return_result(1,1000,'你已出牌！',$is_pk);
                }else{
                    return return_result(0,1002,'你已出牌！',$is_pk);
                }
            }else{
                $this->pk_model->where(array('id'=>$pkid))->save(array('join_out'=>$user_out));
            }
        }
        $pk_ret=$this->pk_model->where(array('id'=>$pkid))->find();

        //两人都完成出牌
        if($pk_ret['create_out'] !=0 && $pk_ret['join_out'] !=0){
            if($pk_ret['create_out'] == $pk_ret['join_out']){
                return return_result(0,1002,'两人相同下一次！',$pk_ret);
            }
            if(($pk_ret['create_out'] == 1 && $pk_ret['join_out'] ==2) || ($pk_ret['create_out'] == 2 && $pk_ret['join_out'] ==3) || ($pk_ret['create_out'] == 3 && $pk_ret['join_out'] ==1)){ //create_id 赢
                $up=array(
                    'victory'=>$pk_ret['create_id'],
                    'status'=>1
                );
                $rew=$this->pk_model->where(array('id'=>$pkid))->save($up);
                $this->member_model->where(array('id'=>$pk_ret['create_id']))->setInc('pk_money', $pk_ret['num']);
            }
            else{

                $up_1=[
                    'victory'=>$pk_ret['join_id'],
                    'status'=>1
                ];
                $this->pk_model->where(array('id'=>$pkid))->save($up_1);
                $this->member_model->where(array('id'=>$pk_ret['join_id']))->setInc('pk_money', $pk_ret['num']);
             }
             $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();

             return return_result(1,1000,'完成pk！',$result);
        }
        return return_result(1,1001,'对方未出牌');
    }



    /*规定时间内未选择出牌系统自动出牌
     * 1:石头
     * 2:剪子
     * 3：布
     */

    public function autoSetOut(){

        $pkid=I('pkid');      //pk表ID
        //获取pk
        $pk=$this->pk_model->where(array('id'=>$pkid,'status'=>0))->find();

        if($pk){
            //有机器人，创建者已出牌
            if($pk['is_rebot'] >0 && $pk['create_out'] >0){
                if($pk['create_out'] ==1){
                    $join_out=2;
                }elseif($pk['create_out'] ==2){
                    $join_out=3;
                }
                elseif($pk['create_out'] ==3){
                    $join_out=1;
                }
                $this->pk_model->where(array('id'=>$pkid))->save(array('join_out'=>$join_out));
                $up_1=[
                    'victory'=>$pk['create'],
                    'status'=>1
                ];
                //创建者赢
                $this->pk_model->where(array('id'=>$pkid))->save($up_1);

                $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                return return_result(1,1000,'完成pk！',$result);

            }
            //获取双方用户胜率
            $c_rate=$this->member_model->where(array('id'=>$pk['create_id']))->field('pk_win_rate')->find();
            $j_rate=$this->member_model->where(array('id'=>$pk['join_id']))->field('pk_win_rate')->find();
            $data=array(
                0=>array(
                    'create_out'=>1,
                    'join_out'=>2,
                    'victory'=>$pk['create_id'],
                    'status'=>1
                ),
                1=>array(
                    'create_out'=>2,
                    'join_out'=>3,
                    'victory'=>$pk['create_id'],
                    'status'=>1
                ),
                2=>array(
                    'create_out'=>3,
                    'join_out'=>1,
                    'victory'=>$pk['create_id'],
                    'status'=>1
                ),
            );
            $data_join=array(
                0=>array(
                    'create_out'=>2,
                    'join_out'=>1,
                    'victory'=>$pk['join_id'],
                    'status'=>1
                ),
                1=>array(
                    'create_out'=>3,
                    'join_out'=>2,
                    'victory'=>$pk['join_id'],
                    'status'=>1
                ),
                2=>array(
                    'create_out'=>1,
                    'join_out'=>3,
                    'victory'=>$pk['join_id'],
                    'status'=>1
                ),
            );
           //有机器人，创建者未出牌.同时出牌用户赢
            if($pk['is_rebot'] >0 && $pk['create_out'] ==0){

                $int=rand(0,2);
                $this->pk_model->where(array('id'=>$pkid))->save($data[$int]);
                $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                return return_result(1,1000,'完成pk！',$result);
            }
            $num=rand(1,100);
            //无机器人,真实用户自动出牌.双方都没出牌
            if($pk['is_rebot'] ==0 && $pk['create_out'] ==0 && $pk['join_out'] ==0){
                //谁大已谁胜率为准

                if($c_rate['pk_win_rate'] >$j_rate['pk_win_rate']){
                    if($c_rate['pk_win_rate']  <50){
                        $jisuan=50+($c_rate['pk_win_rate']-$j_rate['pk_win_rate'])/2;
                    }else{
                        $jisuan=$c_rate['pk_win_rate'];
                    }
                    if($num <$jisuan){ //创建者赢
                        $int=rand(0,2);
                        $this->pk_model->where(array('id'=>$pkid))->save($data[$int]);
                        $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                        $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                        return return_result(1,1000,'完成pk！',$result);

                    }else{ //加入者赢
                        $int=rand(0,2);
                        $this->pk_model->where(array('id'=>$pkid))->save($data_join[$int]);
                        $this->member_model->where(array('id'=>$pk['join_id']))->setInc('pk_money', $pk['num']);
                        $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                        return return_result(1,1000,'完成pk！',$result);

                    }

                }else{
                    if($j_rate['pk_win_rate']  <50){
                        $jisuan=50+($j_rate['pk_win_rate']-$c_rate['pk_win_rate'])/2;
                    }else{
                        $jisuan=$j_rate['pk_win_rate'];
                    }
                    if($num <$jisuan){ //加入者赢
                        $int=rand(0,2);
                        $this->pk_model->where(array('id'=>$pkid))->save($data_join[$int]);
                        $this->member_model->where(array('id'=>$pk['join_id']))->setInc('pk_money', $pk['num']);
                        $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                        return return_result(1,1000,'完成pk！',$result);

                    }else{ //加入者赢
                        $int=rand(0,2);
                        $this->pk_model->where(array('id'=>$pkid))->save($data[$int]);
                        $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                        $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                        return return_result(1,1000,'完成pk！',$result);

                    }

                }

            }

            //无机器人,真实用户自动出牌.有一个先出牌
            if($pk['is_rebot'] ==0 && ($pk['create_out'] >0 || $pk['join_out'] > 0)){
                  if($pk['create_out'] >0){  //创建者先出
                      if($c_rate['pk_win_rate'] >$j_rate['pk_win_rate'] &&  $c_rate['pk_win_rate']>50){
                          $jisuan=50+($c_rate['pk_win_rate']-$j_rate['pk_win_rate'])/2;
                      }else{
                          $jisuan=$c_rate['pk_win_rate'];
                      }
                      if($num <$jisuan){ //创建者赢
                         if($pk['create_out'] ==1){
                             $inst_arr=[
                                 'join_out'=>2,
                                 'victory'=>$pk['create_id'],
                                 'status'=>1
                             ];
                         }elseif($pk['create_out'] ==2){
                             $inst_arr=[
                                 'join_out'=>3,
                                 'victory'=>$pk['create_id'],
                                 'status'=>1
                             ];
                         }elseif($pk['create_out'] ==3){
                             $inst_arr=[
                                 'join_out'=>1,
                                 'victory'=>$pk['create_id'],
                                 'status'=>1
                             ];
                         }
                          $this->pk_model->where(array('id'=>$pkid))->save($inst_arr);
                          $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                          $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                          return return_result(1,1000,'完成pk！',$result);

                      }else{ //加入者赢

                          if($pk['create_out'] ==1){
                              $inst_arr=[
                                  'join_out'=>3,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['create_out'] ==2){
                              $inst_arr=[
                                  'join_out'=>1,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['create_out'] ==3){
                              $inst_arr=[
                                  'join_out'=>2,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }
                          $this->pk_model->where(array('id'=>$pkid))->save($inst_arr);
                          $this->member_model->where(array('id'=>$pk['join_id']))->setInc('pk_money', $pk['num']);
                          $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                          return return_result(1,1000,'完成pk！',$result);
                      }

                  }else{//加入者先出了
                      if($c_rate['pk_win_rate'] < $j_rate['pk_win_rate'] && $j_rate['pk_win_rate']>50){
                          $jisuan=50+($j_rate['pk_win_rate']-$c_rate['pk_win_rate'])/2;
                      }else{
                          $jisuan=$c_rate['pk_win_rate'];
                      }

                      if($num <$jisuan){ //加入者赢
                          if($pk['join_out'] ==1){
                              $inst_arr=[
                                  'create_out'=>2,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['join_out'] ==2){
                              $inst_arr=[
                                  'join_out'=>3,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['join_out'] ==3){
                              $inst_arr=[
                                  'join_out'=>1,
                                  'victory'=>$pk['join_id'],
                                  'status'=>1
                              ];
                          }
                          $this->pk_model->where(array('id'=>$pkid))->save($inst_arr);
                          $this->member_model->where(array('id'=>$pk['join_id']))->setInc('pk_money', $pk['num']);
                          $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                          return return_result(1,1000,'完成pk！',$result);

                      }else{ //创建者赢

                          if($pk['join_out'] ==1){
                              $inst_arr=[
                                  'create_id'=>3,
                                  'victory'=>$pk['create_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['join_out'] ==2){
                              $inst_arr=[
                                  'join_out'=>1,
                                  'victory'=>$pk['create_id'],
                                  'status'=>1
                              ];
                          }elseif($pk['join_out'] ==3){
                              $inst_arr=[
                                  'create_id'=>2,
                                  'victory'=>$pk['create_id'],
                                  'status'=>1
                              ];
                          }
                          $this->pk_model->where(array('id'=>$pkid))->save($inst_arr);
                          $this->member_model->where(array('id'=>$pk['create_id']))->setInc('pk_money', $pk['num']);
                          $result = $this->pk_model->where(array('id'=>$pkid,'status'=>1))->find();
                          return return_result(1,1000,'完成pk！',$result);
                      }

                  }
            }

        }

    }




   /*规定时间内未加入系统自动加入
     * 
     * 
     * 
     */

    public function pkAddrobot(){
        $pkid=I('pkid');
        $pkList=$this->pk_model->where(array('id'=>$pkid))->find();
        $where='id <> '.$pkList['create_id'];

        $user=M('member')->where($where)->find();
        if(empty($pkid)){
            return return_result(0,1002,'pkID不能为空','');
        }
        //$pkList=$this->pk_model->where(array('id'=>$pkid))->find();
        if(empty($pkList)){
            return return_result(0,1002,'该PK不存在','');
        }
        if($pkList['status'] ==1){
            return return_result(0,1002,'该PK已结束','');
        }
        if($pkList['join_id'] >0){

            return return_result(0,1002,'该PK人数已满','');
        }
         $data=[
            'join_id'=>$user['id'],
            'join_name'=>$user['nickname'],
            'join_avatar'=>$user['avatar'],
            'is_rebot'=>$user['id'],
             'join_status'=>1,
        ];
        $r_id=$this->pk_model->where(array('id'=>$pkid))->save($data);
        if($r_id >0) {
             return return_result(1,1000,'加入成功','');
        }else{
            return return_result(0,1002,'请求失败','');
        }
    }





}