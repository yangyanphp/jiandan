<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;
use Portal\Controller\MemberController;
include 'extend/taobao/TopSdk.php';
header("Content-type: text/html; charset=utf-8");
class ListController extends HomebaseController {

	protected $push_model;
	protected $member_model;
	protected $cash_model;
	protected $order_model;
	protected $tixian_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->push_model = M("Push");
		$this->member_model = M("Member");
		$this->cash_model = M("Cash");
		$this->order_model = M("Order");
		$this->tixian_model = M("Tixian");
	}


	//会员中心未读 数量
	function get_list_count(){

        $mid=I('post.mid');  //用户id
        $data = array();
        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,0);
        }


        //更新用返现订单
        $obj = new MemberController;
        $obj->updateInfo($mid);

//未读消息
        $data[0]['type'] = 0;
        $data[0]['count'] = $this->push_model->where(array('mid'=>$mid,'status'=>0))->count();     //未读系统消息数量
        $data[0]['info'] = $this->push_model->join('left join jd_push_cat p_c on p_c.id=jd_push.push_id')->where(array('jd_push.mid'=>$mid,'jd_push.status'=>0))->field('p_c.title')->order('jd_push.send_time desc')->getField('title');     //最新的一条通知

        //返现订单
        $data[1]['type'] = 1;
        $data[1]['count'] = $this->order_model->where('mid='.$mid.' and is_read=0')->count();       //未读返现订单数量
        $data[1]['info'] = $this->order_model->where('mid='.$mid.' and is_read=0')->order('add_time desc')->getField('goods_title');  //最新返现订单标题

        $my_fans = M('member')->where(array('share_id'=>$mid))->getField('id');                         //我的下级id
        $proxy = M('order')->where(array('id'=>$my_fans,'tixian_status2'=>0,'status'=>3))->select();         //下级id完成的订单

        //余额提现
        $data[2]['type'] = 2;
        $data[2]['count'] = (string)($data[1]['fanxian_order_count']+count($proxy));                 //余额提现未读数量 （自购返现+代理返现）

        $zigou =   $this->order_model->where('mid='.$mid.' and is_read=0')->order('add_time desc')->getField('zigou_return');
        $daili = $this->order_model->where('mid='.$mid.' and is_read=0')->order('add_time desc')->getField('daili_return');
        $data[2]['info'] = isset($zigou)&&!empty($zigou)?"您获得最新自购返现".$zigou."元":(isset($daili)&&!empty($daili)?"您获得最新代理返现".$daili."元":null);


        //我的粉丝
        $data[3]['type'] = 3;
        $data[3]['count'] = $this->member_model->where('share_id='.$mid)->count();   //未读新增粉丝数量
        $data[3]['info'] = $this->member_model->where('share_id='.$mid.' and is_read=0')->order('share_add_time desc')->getField('nickname');   //最新增加的粉丝

//var_dump($data);die;
        return return_result(1,1000,'会员中心列表信息未读数量',$data);

    }



    //获取未读消息数量
    public function noReadCount(){
        $mid=I('post.mid',0,'intval');

        $data = array();
        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,0);
        }
        $time = time();
        $count= $this->push_model->where(array('mid'=>$mid,'status'=>0))->count();

        return return_result(1,1000,'成功',$count);
    }

	//推送列表
	public function getPushList() {
	    $mid=I('get.mid',0,'intval');
	    $page=I('get.page',1,'intval');
	    $num=I('get.num',10,'intval');
		$data = array();
		$array = array();
		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
	    if($page>0){
			$page = $page-1;
		}else{
			$page = 0;
		}
		$page=$page*$num;

        $res = $this->push_model
	    		->alias("p")
	    		->field('p.*,j.title,j.img,j.content,j.remark')
	    		->join("jd_push_cat j ON j.id = p.push_id")
	    		->where('p.mid='.$mid)->limit($page,$num)->order("p.send_time DESC")->select();

	    $count = $this->push_model
	    		->alias("p")
	    		->field('p.*,j.title,j.img,j.content')
	    		->join("jd_push_cat j ON j.id = p.push_id")
	    		->where('p.mid='.$mid)->count();
	    if(!empty($res)){
		    foreach($res as $key=>$value){
		    	$arr = array(
		    		'pid'=>$value['push_id'],
		    		'title'=>$value['title'],
		    		'img'=>	$value['img'],
		    		'content'=>	$value['remark'],
		    		'send_time'=> date("Y-m-d H:i:s",$value['send_time']),
		    		'status'=>$value['status']
		    	);
		    	array_push($array,$arr);
		    }
		    
	    }
	    $data['count'] = $count;
	    $data['list'] = $array;
	    return return_result(1,1000,'成功',$data);
	}
	
	//查询单个推送信息
	public function getPushInfo(){

		$pid=I('post.pid',0,'intval');
		$mid=I('post.mid',0,'intval');


		$data = array();

		if(empty($pid) || empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		$info= $this->push_model
				->alias("p")
				->field('p.*,j.title,j.img,j.content,j.remark')
				->join("jd_push_cat j ON j.id = p.push_id")
				->where(array('p.push_id'=>$pid,'p.mid'=>$mid))->find();

		foreach($info as $k=>$v){    //将消息中没有的图片和内容删除
		    if(isset($info['content'])){
		        $data_content = json_decode($info['content'],true);
//		        var_dump($data_content);die;
		        foreach($data_content as $kk=>$vv){
		            if(empty($data_content[$kk]['img'])&&empty($data_content['content'])){
		                unset($data_content[$kk]);
                    }
                }
            }
        }

		$arr = array(
				'id'=>$info['id'],
				'title'=>$info['title'],
				'content'=>$info['remark'],
				'company'=>'黑龙江百上视达电子商务有限公司',
				'send_time'=> date("Y-m-d H:i:s",$info['send_time']),
				'status'=>$info['status'],
				'list'=>$data_content

		);

		$res = $this->push_model->where('id='.$info['id'])->save(array('status'=>1));
		return return_result(1,1000,'成功',$arr);
	}

	//删除消息
	public function deletePush(){
		$mid=I('post.mid',0,'intval');
		$pid=I('post.pid',0,'intval');
		$data = array();
//		var_dump($mid,$pid);die;
		if(empty($pid) || empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		$info= $this->push_model->where(array('push_id'=>$pid,'mid'=>$mid))->find();
		if(empty($info)){
			$msg = '没有查询到数据';
			return return_result(0,1013,$msg,$data);
		}
		$this->push_model->delete($info['id']);
		$count= $this->push_model->where(array('mid'=>$mid,'status'=>0))->count(); //返回未读数据
		return return_result(1,1000,'成功',$count);
	}
	
	//粉丝列表
	public function getShareList() {
		$mid=I('get.mid',0,'intval');
		$page=I('get.page',1,'intval');
		$num=I('get.num',10,'intval');
		$data = array();
		$array = array();
		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		if($page>0){
			$page = $page-1;
		}else{
			$page = 0;
		}
		$page=$page*$num;
		$res = $this->member_model->where('share_id='.$mid)->limit($page,$num)->order("create_time DESC")->select();   //我的粉丝
        $count = $this->member_model->where('share_id='.$mid)->count();
        $sort_num=(intval($count) -($page));
		if(!empty($res)){
			foreach($res as $key=>$value){
				$arr = array(
						'id'=>$value['id'],
						'avatar'=>$value['avatar'],
						'nickname'=>$value['nickname'],
						'total_return'=>$value['total_return'],   //返现总额
                        'sort_num'=>($sort_num--),
				);
				array_push($array,$arr);

				$this->member_model->where(array('id'=>$value['id']))->setField('is_read',1);      //更新粉丝已读状态
			}
	
		}
		$data['count'] = $count;
		$data['list'] = $array;

		return return_result(1,1000,'成功',$data);
	}

    //获取粉丝总人数
    public function get_fans_allcount(){

        $mid=I('get.mid',80,'intval');

        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,0);
        }
        $fans_all_count = $this->member_model->where('share_id='.$mid)->count();
        return return_result(1,1000,'成功',$fans_all_count);
    }

    //余额提现的 返现总额和账户余额
    public function withdraw(){
        $mid=I('get.mid',0,'intval');

        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,0);
        }
       $money = $this->member_model->where(array('id'=>$mid))->field('total_return,use_money')->find();
        $money['no_account_num'] = $this->cash_model->where('mid='.$mid.' and is_read=0')->count();  //提现未读取数量


        return return_result(1,1000,'成功',$money);

    }

	//余额提现（账户明细）
	public function getCashList() {
		$mid=I('post.mid',0,'intval');
		$page=I('post.page',1,'intval');
		$num=I('post.num',10,'intval');
		$data = array();
		$array = array();

		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		if($page>0){
			$page = $page-1;
		}else{
			$page = 0;
		}
		$page=$page*$num;
		
		//更新用户订单返现等信息
		$time = time();
		$black = $this->tixian_model->where(array('id'=>1))->find();
		if(!empty($black['black'])){
			$black_arr = explode(',',$black['black']);
			
		}else{
			$black_arr = array();
		}

			if(!in_array($mid, $black_arr)){  //不再黑名单 能提现

			    //先计算自购返现
				$orderList = $this->order_model->where(array('mid'=>$mid,'tixian_status1'=>0,'status'=>3))->select();

				if(!empty($orderList)){
					foreach($orderList as $key=>$value){
						$orderinfo = $this->order_model->where(array('id'=>$value['id']))->find();
						if($orderinfo['tixian_status1']==0){  //自购返现未提取
							$fxtime = $value['finish_time']+$black['day']*24*60*60;
							$minfo = $this->member_model->where(array('id'=>$mid))->find();
							if($time>=$fxtime){
								$cdata['mid'] = $mid;
								$cdata['cash_money'] = $value['zigou_return'];
								$cdata['use_money'] = $value['zigou_return']+$minfo['use_money'];
								
								$cdata['add_time'] = $fxtime;
								$cdata['type'] = 2;
								$cdata['order_id'] = $value['id'];
								$this->cash_model->add($cdata);
								$this->order_model->where("id=".$value['id'])->save(array('tixian_status1'=>1,'is_read'=>1));  //更改自购返现状态和返现已读状态

								$this->member_model->where("id=".$mid)->save(array('use_money'=>$cdata['use_money'],'total_return'=>$value['zigou_return']+$minfo['total_return']));
							}
						}
					}
				}
				//计算代理返现
				$fansList = $this->member_model->where(array('share_id'=>$mid))->select();

				if(!empty($fansList)){
					foreach ($fansList as $key=>$value){
						$orderList = $this->order_model->where(array('mid'=>$value['id'],'tixian_status2'=>0,'status'=>3))->select();

						if(!empty($orderList)){
							foreach($orderList as $k=>$v){
								$orderinfo = $this->order_model->where(array('id'=>$v['id']))->find();
								if($orderinfo['tixian_status2']==0){
									$fxtime = $v['finish_time']+($black['day']*24*60*60);
									$minfo = $this->member_model->where(array('id'=>$mid))->find();
									if($time>=$fxtime){
										$cdata['mid'] = $mid;
										$cdata['cash_money'] = $v['daili_return'];
										$cdata['use_money'] = $v['daili_return']+$minfo['use_money'];
										
										$cdata['add_time'] = $fxtime;
										$cdata['type'] = 3;
										$cdata['order_id'] = $v['id'];
										$this->cash_model->add($cdata);
										$this->order_model->where("id=".$v['id'])->save(array('tixian_status2'=>1,'is_read'=>1));  //更改代理返现状态和返现已读状态
										$this->member_model->where("id=".$mid)->save(array('use_money'=>$cdata['use_money'],'total_return'=>$v['daili_return']+$minfo['total_return']));
									}
								}
							}
						}
					}
				}
			}
		
		$res = $this->cash_model->where('mid='.$mid)->limit($page,$num)->order("add_time DESC")->select();
		$count = $this->cash_model->where('mid='.$mid)->count();
        $this->cash_model->where('mid='.$mid)->setField('is_read',1);  //将cash表的状态值 改为1  表示用户已读取


		if(!empty($res)){
			foreach($res as $key=>$value){
				if($value['type']==2){
					$type = 2;
					$info = $this->member_model->where('id='.$mid)->find();
					$nickname = $info['nickname'];
					$avatar = $info['avatar'];
				}else if($value['type']==3){
					$type = 2;
					$info= $this->order_model
					->alias("o")
					->field('m.nickname,m.avatar')
					->join("jd_member m ON m.id = o.mid")
					->where(array('o.id'=>$value['order_id']))->find();
					$nickname = $info['nickname'];
					$avatar = $info['avatar'];
				}else{
					$info = $this->member_model->where('id='.$mid)->find();
					$nickname = $info['nickname'];
					$avatar = $info['avatar'];
					$type = 1;
				}
				
				$arr = array(
						'id'=>$value['id'],
						'nickname'=>$nickname,
						'avatar'=>$avatar,
						'cash_money'=>	$value['cash_money'],
						'use_money'=>	$value['use_money'],
						'time'=> date("Y-m-d",$value['add_time']),
						'type'=>$value['type']
				);
				
				array_push($array,$arr);
			}
	
		}
		$data['count'] = $count;
		$data['list'] = $array;
       //var_dump($data);die;
		return return_result(1,1000,'成功',$data);
	}

	//视频
	public function getVideo(){
		$arr = M("Video")->order("id ASC")->select();
		$data['course'] = WEB_URL.$arr[0]['video_url'];//视频教程
		$data['background'] = WEB_URL.$arr[1]['video_url'];//背景视频
		return return_result(1,1000,'成功',$data);
	}

    //返现订单列表
    public function getOrderList() {

        $mid=I('mid',0);
        $page=I('page',1);
        $num=I('num',10);

        //更新淘宝最新订单
        //$this->getTaoBaoOrder();
        ///////
        $data = array();
        $array = array();
        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,$data);
        }




        if($page>0){
            $page = $page-1;
        }else{
            $page = 0;
        }
        $page=$page*$num;
        $black = $this->tixian_model->where(array('id'=>1))->find();
        /*
        $res = $this->order_model
        ->alias("o")
        ->field('o.*,c.cash_money')
        ->join("jd_cash c ON c.order_id = o.id")
        ->where('o.mid='.$mid)->limit($page,$num)->order("c.add_time DESC")->select();
        $count = $this->order_model
                ->alias("o")
                ->field('o.*,c.cash_money')
                ->join("jd_cash c ON c.order_id = o.id")
                ->where('o.mid='.$mid)->count();
        */
        $res = $this->order_model->where('mid='.$mid.' and deleteed=0')->limit($page,$num)->order("add_time DESC")->select();
        $count = $this->order_model->where('mid='.$mid.' and deleteed=0')->count();

        if(!empty($res)){
            foreach($res as $key=>$value){
                $goodsid = $value['goods_id'];
                /*
                $curl='https://api.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2013%40taobao_h5_1.0.0&type=jsonp&dataType=jsonp&data=%7B%22itemNumId%22%3A%22'.$goodsid.'%22%7D';
                $arr_img = file_get_contents($curl);
                $arr_img = json_decode($arr_img,true);
                $img = "http:".$arr_img['data']['item']['images'][0];
                */
//				$tgw=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=link&id='.$goodsid.'&pid=mm_25721409_23184092_77026539');
//				$tgwArray=$this->is_json($tgw);
//				$img = "http:".$tgwArray['pictUrl'];


                //查找商品图片
                $c=new \TopClient();
                $c->appkey = '24682605';
                $c->secretKey = '230291803e881263fc3716b6202b6876';

                $req = new \TbkItemInfoGetRequest;
                $req->setNumIids($goodsid);
                $req->setFields('pict_url');
                $resp = $c->execute($req);

                $data_taobao=$this->ObjectToArray($resp);

                if(!empty($data_taobao['results'])) {
                    $img = $data_taobao['results']['n_tbk_item']['pict_url'];
                }else{
                    //淘宝没有该商品后默认图片
                    $img = "www.baidu.com";
                }

                if($value['status']==3){
                    $timearr = $this->diffBetweenTwoDays(time(),$value['finish_time']+$black['day']*24*60*60);
                    if(time()>$value['finish_time']+$black['day']*24*60*60){
                        $flag = 2;//已过期
                    }else{
                        $flag = 1;//正常
                    }
                }else{
                    $timearr['day'] = 0;
                    $timearr['hour'] = 0;
                    $timearr['minute'] = 0;
                    if($value['status']==1){
                        $flag = 3;//订单付款
                    }else if($value['status']==2){
                        $flag = 4;//失效
                    }
                }
                $this->order_model->where(array('id'=>$value['id']))->setField('is_read',1);    //返现订单 更新为已读状态
                $arr = array(
                    'id'=>$value['id'],
                    'img'=>$img,
                    'money'=>empty($value['zigou_return'])?'0.00':$value['zigou_return'],
                    'status'=>	$value['status'],//1、订单付款 2、订单失效 3、订单结算
                    'title'=>	$value['goods_title'],
                    'time'=> date("Y-m-d H:i",$value['add_time']),
                    'flag'=>$flag,
                    'day'=>$timearr['day']<0?0:$timearr['day'],
                    'hour'=>$timearr['hour']<0?0:$timearr['hour'],
                    'minute'=>$timearr['minute']<0?0:$timearr['minute']
                );
                array_push($array,$arr);
            }

        }
        $data['count'] = $count;
        $data['list'] = $array;

        return return_result(1,1000,'成功',$data);
    }
    //转换成数组
    public function ObjectToArray($o) {
        if(is_object($o)) $o = get_object_vars($o);
        if(is_array($o))
            foreach($o as $k=>$v) $o[$k] = $this->ObjectToArray($v);
        return $o;
    }

    //订单列表1
	public function getOrderList1() {


		$mid=I('post.mid',0,'intval');
		$page=I('post.page',1,'intval');
		$num=I('post.num',10,'intval');
		$data = array();
		$array = array();
		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		if($page>0){
			$page = $page-1;
		}else{
			$page = 0;
		}
		$page=$page*$num;
		$black = $this->tixian_model->where(array('id'=>1))->find();
		/*
		$res = $this->order_model
		->alias("o")
		->field('o.*,c.cash_money')
		->join("jd_cash c ON c.order_id = o.id")
		->where('o.mid='.$mid)->limit($page,$num)->order("c.add_time DESC")->select();
		$count = $this->order_model
				->alias("o")
				->field('o.*,c.cash_money')
				->join("jd_cash c ON c.order_id = o.id")
				->where('o.mid='.$mid)->count();
		*/
		$res = $this->order_model->where('mid='.$mid.' and deleteed=0')->limit($page,$num)->order("add_time DESC")->select();
		$count = $this->order_model->where('mid='.$mid.' and deleteed=0')->count();
		if(!empty($res)){
			foreach($res as $key=>$value){
				$goodsid = $value['goods_id'];
				/*
				$curl='https://api.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2013%40taobao_h5_1.0.0&type=jsonp&dataType=jsonp&data=%7B%22itemNumId%22%3A%22'.$goodsid.'%22%7D';
				$arr_img = file_get_contents($curl);
				$arr_img = json_decode($arr_img,true);
				$img = "http:".$arr_img['data']['item']['images'][0];
				*/
				$tgw=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=link&id='.$goodsid.'&pid=mm_25721409_23184092_77026539');
				$tgwArray=$this->is_json($tgw);
				$img = "http:".$tgwArray['pictUrl'];
				if($value['status']==3){
					$timearr = $this->diffBetweenTwoDays(time(),$value['finish_time']+$black['day']*24*60*60);
					if(time()>$value['finish_time']+$black['day']*24*60*60){
						$flag = 2;//已过期
					}else{
						$flag = 1;//正常
					}
				}else{
					$timearr['day'] = 0;
					$timearr['hour'] = 0;
					$timearr['minute'] = 0;
					if($value['status']==1){
						$flag = 3;//订单付款
					}else if($value['status']==2){
						$flag = 4;//失效
					}
				}
				$arr = array(
						'id'=>$value['id'],
						'img'=>$img,
						'money'=>empty($value['zigou_return'])?'0.00':$value['zigou_return'],
						'status'=>	$value['status'],//1、订单付款 2、订单失效 3、订单结算
						'title'=>	$value['goods_title'],
						'time'=> date("Y-m-d H:i",$value['add_time']),
						'flag'=>$flag,
						'day'=>$timearr['day']<0?0:$timearr['day'],
						'hour'=>$timearr['hour']<0?0:$timearr['hour'],
						'minute'=>$timearr['minute']<0?0:$timearr['minute']
				);
				array_push($array,$arr);
			}

		}
		$data['count'] = $count;
		$data['list'] = $array;
		return return_result(1,1000,'成功',$data);
	}
	


	//获取粉丝和订单未读数量
	function getReadCount(){
		$mid=I('post.mid',0,'intval');
//		$mid = 80;
		$data = array();
		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,0);
		}
		$time = time();
		$data['shareCount'] = $this->member_model->where('share_id='.$mid.' and is_read=0 and share_add_time<='.$time)->count();
		$data['orderCount'] = $this->order_model->where('mid='.$mid.' and is_read=0 and add_time<='.$time)->count();

		return return_result(1,1000,'成功',$data);
	}




	//更新粉丝和订单未读数量
	function updateReadCount(){
		$mid=I('post.mid',0,'intval');
		$data = array();
		if(empty($mid)){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,0);
		}
		$info = $this->member_model->where(array('id'=>$mid))->find();
		if(empty($info)){
			$msg = '用户不存在';
			return return_result(0,1002,$msg,0);
		}
		$time = time();
		$this->member_model->where('share_id='.$mid)->save(array('is_read'=>1));
		$this->order_model->where('mid='.$mid)->save(array('is_read'=>1));
		return return_result(1,1000,'成功',$data);
	}

	//用户提现
	public function tixian(){
		
		$mid = isset($_POST['mid'])?$_POST['mid']:'';
		$money = isset($_POST['money'])?$_POST['money']:'';
		$pwd = isset($_POST['pwd'])?md5($_POST['pwd']):'';
		$sign = isset($_POST['sign'])?$_POST['sign']:'';
		$data = array();
    	if(empty($mid) || empty($money) || empty($pwd)){   
    		$msg = '参数不能为空';
    		return return_result(0,1002,$msg,$data);
    	}
    	$item['mid'] = $mid;
    	$item['money'] = $money;
    	$item['pwd'] = md5($pwd);
    	$item['sign'] = $sign;
    	$flag = check_sign($item);
//    	if(!$flag){
//    		return return_result(0,1014,'签名错误',$data);
//    	}

    	if($money<0.1){
    		return return_result(0,1002,'提现最低额度为0.1元',$data);
    	}
    	$info= $this->member_model->where(array('id'=>$mid))->find();

    	if(!empty($info)){
    		if($pwd==$info['pwd']){
	    		$time = time();
	    		$black = $this->tixian_model->where(array('id'=>1))->find();
	    		if(!empty($black['black'])){
	    			$black_arr = explode(',',$black['black']);
	    		}else{
	    			$black_arr = array();
	    		}
	    		if(in_array($mid, $black_arr)){
	    			return return_result(0,1002,'用户处在黑名单中',$data);
	    		}else{
	    			if(empty($black['tixian_money'])){  //每日提现金额上限
	    				if($money<=$info['use_money']){
	    					//支付宝提现
	    					Vendor('alipay.AopSdk');
							$aop = new \AopClient();
	    					$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
	    					$aop->appId = '2017100709182284';
	    					$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
	    					$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
	    					$aop->apiVersion = '1.0';
	    					$aop->signType = 'RSA2';
	    					$aop->postCharset='utf-8';
	    					$aop->format='json';
	    					$request = new \AlipayFundTransToaccountTransferRequest ();
	    					/*
	    					 $arr = array('out_biz_no'=>'123456789',
	    					 		'payee_type'=>'ALIPAY_LOGONID',
	    					 		'payee_account'=>'strong0503@163.com',
	    					 		'amount'=>'0.1',
	    					 		'payer_show_name'=>'测试转账',
	    					 		'payee_real_name'=>'李壮',
	    					 		'remark'=>'测试转账备注');
	    					$canshu = json_encode($arr,JSON_UNESCAPED_UNICODE);
	    					*/
	    					$num = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	    					$request->setBizContent("{" .
	    							"\"out_biz_no\":\"".$num."\"," .
	    							"\"payee_type\":\"ALIPAY_LOGONID\"," .
	    							"\"payee_account\":\"".$info['account']."\"," .
	    							"\"amount\":\"".$money."\"," .
	    							"\"payer_show_name\":\"减单APP\"," .
	    							"\"payee_real_name\":\"".$info['realname']."\"," .
	    							"\"remark\":\"余额提现\"" .
	    							"}");
	    					$result = $aop->execute ( $request);
	    					$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
							$resultCode = $result->$responseNode->code;
	    					if($resultCode=="10000"){
	    						$m['use_money'] = $info['use_money']-$money;
	    						$m['cash_total'] = $info['cash_total']+$money;
	    						$this->member_model->where('id='.$mid)->save($m);
	    						$tx['mid'] = $mid;
	    						$tx['cash_money'] = $money;
	    						$tx['use_money'] = $m['use_money'];
	    						$tx['add_time'] = time();
	    						$tx['type'] = 1;
	    						$this->cash_model->add($tx);
	    						return return_result(1,1000,'提现成功到账',$data);
	    					}else{
	    						return return_result(0,1002,'提现失败',$data);
	    					}
	    				}else{
	    					return return_result(0,1002,'账户余额不足',$data);
	    				}
	    			}else{
	    				$time_start = strtotime(date('Y-m-d', time()));
						$time_end = $time_start + 24 * 60 * 60;
	    				$sum = $this->cash_model->where('mid='.$mid.' and add_time<='.$time_end." and add_time>=".$time_start.' and type=1')->sum("cash_money");
						if(empty($sum)){
							$sum = 0;
						}
						
	    				if($sum<=$black['tixian_money']){
	    					if($money<=$info['use_money']){
	    						//支付宝提现
	    						//include 'extend/alipay/common.php';
								Vendor('alipay.AopSdk');
								$aop = new \AopClient();
	    						$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
	    						$aop->appId = '2017100709182284';
	    						$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
	    						$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
	    						$aop->apiVersion = '1.0';
	    						$aop->signType = 'RSA2';
	    						$aop->postCharset='utf-8';
	    						$aop->format='json';
	    						$request = new \AlipayFundTransToaccountTransferRequest ();
	    						/*
	    						 $arr = array('out_biz_no'=>'123456789',
	    						 		'payee_type'=>'ALIPAY_LOGONID',
	    						 		'payee_account'=>'strong0503@163.com',
	    						 		'amount'=>'0.1',
	    						 		'payer_show_name'=>'测试转账',
	    						 		'payee_real_name'=>'李壮',
	    						 		'remark'=>'测试转账备注');
	    						$canshu = json_encode($arr,JSON_UNESCAPED_UNICODE);
	    						*/
	    						$num = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	    						$request->setBizContent("{" .
	    								"\"out_biz_no\":\"".$num."\"," .
	    								"\"payee_type\":\"ALIPAY_LOGONID\"," .
	    								"\"payee_account\":\"".$info['account']."\"," .
	    								"\"amount\":\"".$money."\"," .
	    								"\"payer_show_name\":\"减单APP\"," .
	    								"\"payee_real_name\":\"".$info['realname']."\"," .
	    								"\"remark\":\"余额提现\"" .
	    								"}");
										
	    						$result = $aop->execute ( $request);
	    						$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
	    						$resultCode = $result->$responseNode->code;
	    						if($resultCode=="10000"){
	    							$m['use_money'] = $info['use_money']-$money;
	    							$m['cash_total'] = $info['cash_total']+$money;
	    							$this->member_model->where('id='.$mid)->save($m);
	    							$tx['mid'] = $mid;
	    							$tx['cash_money'] = $money;
	    							$tx['use_money'] = $m['use_money'];
	    							$tx['add_time'] = time();
	    							$tx['type'] = 1;
	    							$this->cash_model->add($tx);
	    							return return_result(1,1000,'提现成功到账',$data);
	    						}else{
	    							return return_result(0,1015,'支付宝账号或姓名输入有误，请核对',$data);
	    						}
	    					}else{
	    						return return_result(0,1002,'账户余额不足',$data);
	    					}
	    				}else{
	    					return return_result(0,1002,'超过当天提现额度',$data);
	    				}
	    			}
	    		}
    		}else{
    			return return_result(0,1002,'提现密码错误',$data);
    		}
    	}else{
    		return return_result(0,1003,'用户不存在',$data);
    	}
	}
	
	public function deleteOrder(){
		$mid=I('post.mid',0,'intval');
		$id=I('post.id',0,'intval');
		$data = array();
    	if(empty($mid) || empty($id)){   
    		$msg = '参数不能为空';
    		return return_result(0,1002,$msg,$data);
    	}
    	$m['deleteed'] = 1;
    	$res = $this->order_model->where('id='.$id.' and mid='.$mid)->save($m);
    	return return_result(1,1000,'删除成功',$data);
	}


	function execcurl($url,$ispost=false,$data='',$in='utf8',$out='utf8',$cookie='')
	{
		$fn = curl_init();
		curl_setopt($fn, CURLOPT_URL, $url);
		curl_setopt($fn, CURLOPT_TIMEOUT, 60);
		curl_setopt($fn, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($fn, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($fn, CURLOPT_REFERER, $url);
		curl_setopt($fn, CURLOPT_HEADER, 0);
		if($cookie)
			curl_setopt($fn,CURLOPT_COOKIE,$cookie);
		if($ispost){
			curl_setopt($fn, CURLOPT_POST, TRUE);
			curl_setopt($fn, CURLOPT_POSTFIELDS, $data);
		}
	
		$fm = curl_exec($fn);
		curl_close($fn);
		if($in!=$out){
			$fm = Newiconv($in,$out,$fm);
		}
		return $fm;
	}
	
	function is_json($string){
		try{
			$data = json_decode($string,true);
		}catch(Exception $e){
			return  $string;
		}
		return $data;
	}
	
	/**
	 * 求两个日期之间相差的天数
	 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
	 * @param string $day1
	 * @param string $day2
	 * @return number
	 */
	function diffBetweenTwoDays($begin_time,$end_time){
		
		if($begin_time < $end_time){ 
        	$starttime = $begin_time; 
        	$endtime = $end_time; 
	     }else{ 
	        $starttime = $end_time; 
	        $endtime = $begin_time; 
	     } 
	     $timediff = $endtime-$starttime; 
	     $days = intval($timediff/86400); 
	     $remain = $timediff%86400; 
	     $hours = intval($remain/3600); 
	     $remain = $remain%3600; 
	     $mins = intval($remain/60); 
	     $secs = $remain%60; 
	     $res = array("day" => $days,"hour" => $hours,"minute" => $mins,"sec" => $secs);
	     return $res;
	}
	
	function test(){
		$begin_time = time();
		$end_time = '1511107200';
		if($begin_time < $end_time){ 
        	$starttime = $begin_time; 
        	$endtime = $end_time; 
	     }else{ 
	        $starttime = $end_time; 
	        $endtime = $begin_time; 
	     } 
	     $timediff = $endtime-$starttime; 
	     $days = intval($timediff/86400); 
	     $remain = $timediff%86400; 
	     $hours = intval($remain/3600); 
	     $remain = $remain%3600; 
	     $mins = intval($remain/60); 
	     $secs = $remain%60; 
	     $res = array("day" => $days,"hour" => $hours,"minute" => $mins,"sec" => $secs);
		
		print_r($res);
	}
	
	function tixian2(){
		
		$mid = isset($_POST['mid'])?$_POST['mid']:'210';
		$money = isset($_POST['money'])?$_POST['money']:'0.1';
		$pwd = isset($_POST['pwd'])?$_POST['pwd']:'1';
//		$sign = isset($_POST['sign'])?$_POST['sign']:'aa296fa72701f26fa6123c0ca4f38c32';
		$data = array();
		
    	if(empty($mid) || empty($money) || empty($pwd)){   
    		$msg = '参数不能为空';
    		return return_result(0,1002,$msg,$data);
    	}
    	$item['mid'] = $mid;
    	$item['money'] = $money;
    	$item['pwd'] = $pwd;
//    	$item['sign'] = $sign;
    	$flag = check_sign($item);
    	
    	if($money<0.1){
    		return return_result(0,1002,'提现最低额度为0.1元',$data);
    	}
		
    	$info= $this->member_model->where(array('id'=>$mid))->find();
    	if(!empty($info)){
    		if($pwd==1){
	    		$time = time();
	    		$black = $this->tixian_model->where(array('id'=>1))->find();
	    		if(!empty($black['black'])){
	    			$black_arr = explode(',',$black['black']);
	    		}else{
	    			$black_arr = array();
	    		}
				
	    		if(in_array($mid, $black_arr)){
	    			return return_result(0,1002,'用户处在黑名单中',$data);
	    		}else{
					
	    			if(empty($black['tixian_money'])){  //每天提现上线（无上限）
						
	    				if($money<=$info['use_money']){
							
	    					//支付宝提现
	    					Vendor('alipay.AopSdk');
							$aop = new \AopClient();
	    					$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
	    					$aop->appId = '2017100709182284';
	    					$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
	    					$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
	    					$aop->apiVersion = '1.0';
	    					$aop->signType = 'RSA2';
	    					$aop->postCharset='utf-8';
	    					$aop->format='json';
	    					$request = new \AlipayFundTransToaccountTransferRequest ();
	    					/*
	    					 $arr = array('out_biz_no'=>'123456789',
	    					 		'payee_type'=>'ALIPAY_LOGONID',
	    					 		'payee_account'=>'strong0503@163.com',
	    					 		'amount'=>'0.1',
	    					 		'payer_show_name'=>'测试转账',
	    					 		'payee_real_name'=>'李壮',
	    					 		'remark'=>'测试转账备注');
	    					$canshu = json_encode($arr,JSON_UNESCAPED_UNICODE);
	    					*/
	    					$num = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	    					$request->setBizContent("{" .
	    							"\"out_biz_no\":\"".$num."\"," .
	    							"\"payee_type\":\"ALIPAY_LOGONID\"," .
	    							"\"payee_account\":\"".$info['account']."\"," .
	    							"\"amount\":\"".$money."\"," .
	    							"\"payer_show_name\":\"减单APP\"," .
	    							"\"payee_real_name\":\"".$info['realname']."\"," .
	    							"\"remark\":\"余额提现\"" .
	    							"}");
	    					$result = $aop->execute ( $request);
	    					$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
							$resultCode = $result->$responseNode->code;
	    					if($resultCode=="10000"){
	    						$m['use_money'] = $info['use_money']-$money;
	    						$m['cash_total'] = $info['cash_total']+$money;
	    						$this->member_model->where('id='.$mid)->save($m);
	    						$tx['mid'] = $mid;
	    						$tx['cash_money'] = $money;
	    						$tx['use_money'] = $m['use_money'];
	    						$tx['add_time'] = time();
	    						$tx['type'] = 1;
	    						$this->cash_model->add($tx);
	    						return return_result(1,1000,'提现成功到账',$data);
	    					}else{
	    						return return_result(0,1002,'提现失败',$data);
	    					}
	    				}else{
	    					return return_result(0,1002,'账户余额不足',$data);
	    				}
	    			}else{
						
	    				$time_start = strtotime(date('Y-m-d', time()));
						$time_end = $time_start + 24 * 60 * 60;
	    				$sum = $this->cash_model->where('mid='.$mid.' and add_time<='.$time_end." and add_time>=".$time_start.' and type=1')->sum("cash_money");
						if(empty($sum)){
							$sum = 0;
						}
						
	    				if($sum<=$black['tixian_money']){  //此次提现金额小于每日提现上线
	    					if($money<=$info['use_money']){
								
	    						//支付宝提现
	    						//include 'extend/alipay/common.php';
								Vendor('alipay.AopSdk');
								
								$aop = new \AopClient();
	    						$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
	    						$aop->appId = '2017100709182284';
	    						$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
	    						$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
	    						$aop->apiVersion = '1.0';
	    						$aop->signType = 'RSA2';
	    						$aop->postCharset='utf-8';
	    						$aop->format='json';
	    						$request = new \AlipayFundTransToaccountTransferRequest ();
								
	    						/*
	    						 $arr = array('out_biz_no'=>'123456789',
	    						 		'payee_type'=>'ALIPAY_LOGONID',
	    						 		'payee_account'=>'strong0503@163.com',
	    						 		'amount'=>'0.1',
	    						 		'payer_show_name'=>'测试转账',
	    						 		'payee_real_name'=>'李壮',
	    						 		'remark'=>'测试转账备注');
	    						$canshu = json_encode($arr,JSON_UNESCAPED_UNICODE);
	    						*/
	    						$num = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	    						$request->setBizContent("{" .
	    								"\"out_biz_no\":\"".$num."\"," .
	    								"\"payee_type\":\"ALIPAY_LOGONID\"," .
	    								"\"payee_account\":\"".$info['account']."\"," .
	    								"\"amount\":\"".$money."\"," .
	    								"\"payer_show_name\":\"减单APP\"," .
	    								"\"payee_real_name\":\"".$info['realname']."\"," .
	    								"\"remark\":\"余额提现\"" .
	    								"}");
								//$tx_array = array("out_biz_no"=>$num,"payee_type"=>"ALIPAY_LOGONID","payee_account"=>$info['account'],"amount"=>$money,"payer_show_name"=>"减单APP","payee_real_name"=>$info['realname'],"remark"=>"余额提现");
								//$tx_json = json_encode($tx_array,JSON_UNESCAPED_UNICODE);
								//$request->setBizContent($tx_json);
								
	    						$result = $aop->execute($request);
							
	    						$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
								
	    						$resultCode = $result->$responseNode->code;
								
	    						if($resultCode=="10000"){
	    							$m['use_money'] = $info['use_money']-$money;      //账户余额
	    							$m['cash_total'] = $info['cash_total']+$money;    //总提现
	    							$this->member_model->where('id='.$mid)->save($m);  //更新member表

	    							$tx['mid'] = $mid;                  //用户
	    							$tx['cash_money'] = $money;         //提现金额
	    							$tx['use_money'] = $m['use_money']; //所剩余额
	    							$tx['add_time'] = time();            //提现时间
	    							$tx['type'] = 1;                     //类型（1、提现 2、自购返现3、代理返现）
	    							$this->cash_model->add($tx);
	    							return return_result(1,1000,'提现成功到账',$data);
	    						}else{
	    							return return_result(0,1015,'支付宝账号或姓名输入有误，请核对',$data);
	    						}
	    					}else{
	    						return return_result(0,1002,'账户余额不足',$data);
	    					}
	    				}else{
	    					return return_result(0,1002,'超过当天提现额度',$data);
	    				}
	    			}
	    		}
    		}else{
    			return return_result(0,1002,'提现密码错误',$data);
    		}
    	}else{
    		return return_result(0,1003,'用户不存在',$data);
    	}
	}

	//客服电话
	public function get_service_phone(){

	    $data = M('customer_service')->where(array('id'=>1))->getField('service_phone');

        return return_result(1,1000,'成功',$data);
    }

}
