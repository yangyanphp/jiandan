<?php
/**
 * 后台首页
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class IndexController extends AdminbaseController {
	protected $member_model;
	protected $order_model;
	protected $cash_model;
	
	public function _initialize() {
		$this->member_model = M("Member");
		$this->order_model = M("Order");
		$this->cash_model = M("Cash");
		$admin_id=session('ADMIN_ID');
		if(empty($admin_id)){
			redirect(U("admin/public/login"));
		}
	}
    /**
     * 后台框架首页
     */
    public function index() {
       	$this->display();
    }

    public function index_right() {
    	$status = I("get.status",0,'intval');//0、全部 1、付款 2、失效 3、结算
    	$time = I("get.time",0,'intval');//1、今天 2、昨天 3、30天 4、60天 5、时间段
    	$start_time = I("get.start_time",'');
    	$end_time = I("get.end_time",'');
        $search=I('search');
        //echo $search;exit;
        $where1='';
        if($search){
            //$where1['nickname']=['like','%'.$search.'%'];
            //$where1 = 'm.nickname '.'like %'.$search.'%'; 
            $where1 = " m.nickname  like '%$search%'";
        }
        //echo $where1;
    	//用户
    	$today = strtotime(date("Y-m-d"));
    	$todayMemberCount= $this->member_model->where('create_time>='.$today)->count();
    	$memberCount= $this->member_model->count();
    	
    	/*
    	 * 代理返现和总返现 
    	 * 总返现=总代理返现+总自购返现+总平台收益
    	 */
    	$dailifanxian = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    	$fanxianCount = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    	//提现
    	$tixian = $this->cash_model->where('add_time>='.$today.' and type=1')->sum('cash_money');
    	$tixianCount = $this->cash_model->where('type=1')->sum('cash_money');
    	//总自购返现和总平台收益
    	$zigou_money = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    	$commission = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    	$ptsy = $commission-$zigou_money-$dailifanxian;
    	
    	$today = strtotime(date("Y-m-d"));//今天
    	$yesterday = strtotime(date("Y-m-d",strtotime("-1 day")));//昨天
    	$month_front = strtotime(date("Y-m-d",strtotime("-1 month")));//一个月
    	$month_two = strtotime(date("Y-m-d",strtotime("-2 month")));//两个月
    	//订单列表


    	if(empty($status)){
    		if(empty($time)){


	    		$list = $this->order_model
	    		->alias("o")
	    		->field('o.*,m.nickname')
	    		->join("jd_member m ON m.id = o.mid")
	    		->where('o.deleteed=0 and o.mid<>0 ')
                ->where($where1)
                ->page($_GET['p'].',10')
	    		->order("o.add_time DESC")->select();
                //echo $where1;
                //echo M()->getLastSql();
                //var_dump($list);exit;
                $count=$this->order_model
                    ->alias("o")
                    ->field('o.*,m.nickname')
                    ->join("jd_member m ON m.id = o.mid")
                    ->where('o.deleteed=0 and o.mid<>0')
                    ->where($where1)->count();
                $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
                $show       = $Page->show();// 分页显示输出
    		}else{
    			if($time==1){
    				$where = 'o.add_time>='.$today;
    			}else if($time==2){
    				$where = 'o.add_time>='.$yesterday.' and o.finish_time<='.$today;
    			}else if($time==3){
    				$where = 'o.add_time>='.$month_front.' and o.finish_time<='.$today;
    			}else if($time==4){
    				$where = 'o.add_time>='.$month_two.' and o.finish_time<='.$today;
    			}else if($time==5){
    				$where = 'o.add_time>='.strtotime($start_time).' and o.finish_time<='.strtotime($end_time);
    			}else{
    				$where = '1=1';
    			}


    			$list = $this->order_model
    			->alias("o")
    			->field('o.*,m.nickname')
    			->join("jd_member m ON m.id = o.mid")
    			->where('o.deleteed=0 and o.mid<>0 and '.$where)
                ->where($where1)
                ->page($_GET['p'].',10')
    			->order("o.add_time DESC")->select();
                $count=$this->order_model
                    ->alias("o")
                    ->field('o.*,m.nickname')
                    ->join("jd_member m ON m.id = o.mid")
                    ->where('o.deleteed=0 and o.mid<>0 and '.$where)
                    ->where($where1)
                    ->count();
                $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
                $show       = $Page->show();// 分页显示输出

    			
    		}
    	
    	}else{
    		if($status==3){
    			if($time==1){
    				$where = 'o.finish_time>='.$today;
    			}else if($time==2){
    				$where = 'o.finish_time>='.$yesterday.' and o.finish_time<='.$today;
    			}else if($time==3){
    				$where = 'o.finish_time>='.$month_front.' and o.finish_time<='.$today;
    			}else if($time==4){
    				$where = 'o.finish_time>='.$month_two.' and o.finish_time<='.$today;
    			}else if($time==5){
    				$where = 'o.finish_time>='.strtotime($start_time).' and o.finish_time<='.strtotime($end_time);
    			}else{
    				$where = '1=1';
    			}
    		}else{
    			
    			if($time==1){
    				$where = 'o.add_time>='.$today;
    			}else if($time==2){
    				$where = 'o.add_time>='.$yesterday.' and o.add_time<='.$today;
    			}else if($time==3){
    				$where = 'o.add_time>='.$month_front.' and o.add_time<='.$today;
    			}else if($time==4){
    				$where = 'o.add_time>='.$month_two.' and o.add_time<='.$today;
    			}else if($time==5){
    				$where = 'o.add_time>='.strtotime($start_time).' and o.add_time<='.strtotime($end_time);
    			}else{
    				$where = '1=1';
    			}
    		}


    		$list = $this->order_model
    		->alias("o")
    		->field('o.*,m.nickname')
    		->join("jd_member m ON m.id = o.mid")
    		->where("o.status=".$status.' and o.deleteed=0 and o.mid<>0 and '.$where)
            ->where($where1)
                ->page($_GET['p'].',10')
    		->order("o.add_time DESC")->select();
            $count=$this->order_model
                ->alias("o")
                ->field('o.*,m.nickname')
                ->join("jd_member m ON m.id = o.mid")
                ->where("o.status=".$status.' and o.deleteed=0 and o.mid<>0 and '.$where)
                ->where($where1)
                ->count();
            $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
            $show       = $Page->show();// 分页显示输出
    	}
    	/*
    	foreach($list as $key=>$value){
    		$goodsid = $value['goods_id'];
    		//$curl='https://api.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2013%40taobao_h5_1.0.0&type=jsonp&dataType=jsonp&data=%7B%22itemNumId%22%3A%22'.$goodsid.'%22%7D';
    		$curl='https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7Bitem_num_id%3A'.urlencode($goodsid).'%7D&type=json&dataType=json';
    		$arr_img = file_get_contents($curl);
    		$arr_img = json_decode($arr_img,true);
    		$img = $arr_img['data']['images'][0];
    		$list[$key]['img'] = $img;
    		echo $img."<br>";
    	}
    	*/

//    	var_dump($show);
//    	var_dump($list);exit;
    	if($time==1 || $time==0){
    		$todayOrderCount= $this->order_model->where('add_time>='.$today.' and deleteed=0 and mid<>0')->count();//总订单
    		$payorderCount= $this->order_model->where('add_time>='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
    		$invalidCount= $this->order_model->where('add_time>='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
    		$finishCount= $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    	}else if($time==2){
    		$todayOrderCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
    		$payorderCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
    		$invalidCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
    		$finishCount= $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    	}else if($time==3){
    		$todayOrderCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
    		$payorderCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
    		$invalidCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
    		$finishCount= $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    	}else if($time==4){
    		$todayOrderCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
    		$payorderCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
    		$invalidCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
    		$finishCount= $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    		 
    	}else if($time==5){
    		$todayOrderCount= $this->order_model->where('add_time>='.strtotime($start_time).' and add_time<='.strtotime($end_time).' and deleteed=0 and mid<>0')->count();//总订单
    		$payorderCount= $this->order_model->where('add_time>='.strtotime($start_time).' and add_time<='.strtotime($end_time).' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
    		$invalidCount= $this->order_model->where('add_time>='.strtotime($start_time).' and add_time<='.strtotime($end_time).' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
    		$finishCount= $this->order_model->where('finish_time>='.strtotime($start_time).' and finish_time<='.strtotime($end_time).' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    	}
    	$this->assign("count_ziygou",empty($zigou_money)?'0.0':$zigou_money);//总自购返现
    	$this->assign("ptsy",empty($ptsy)?'0.0':$ptsy);//总自购返现
    	$this->assign("fanxian",empty($dailifanxian)?'0.0':$dailifanxian);
    	$this->assign("fanxianCount",empty($fanxianCount)?'0.0':$fanxianCount);
    	$this->assign("tixian",empty($tixian)?'0.0':$tixian);
    	$this->assign("tixianCount",empty($tixianCount)?'0.0':$tixianCount);
    	$this->assign("todayMemberCount",$todayMemberCount);
    	$this->assign("memberCount",$memberCount);
    	$this->assign("todayOrderCount",$todayOrderCount);
    	$this->assign("payorderCount",$payorderCount);
    	$this->assign("invalidCount",$invalidCount);
    	$this->assign("finishCount",$finishCount);
    	$this->assign("list",$list);
    	$this->assign("time",$time);
    	$this->assign("start_time",$start_time);
    	$this->assign("end_time",$end_time);
        $this->assign('page',$show);// 赋值分页输出
    	$this->display();
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
    function getData(){
    	$type = $_POST['type'];
    	$val = $_POST['val'];
    	$start = empty($_POST['start'])?'':strtotime($_POST['start']);
    	$end = empty($_POST['end'])?'':strtotime($_POST['end']);
    	//用户
    	$today = strtotime(date("Y-m-d"));//今天
    	$yesterday = strtotime(date("Y-m-d",strtotime("-1 day")));//昨天
    	$month_front = strtotime(date("Y-m-d",strtotime("-1 month")));//一个月
    	$month_two = strtotime(date("Y-m-d",strtotime("-2 month")));//两个月
    	
    	if($type==1){
    		if($val==1){
    			$count= $this->member_model->where('create_time>='.$today)->count();
    		}else if($val==2){
    			$count= $this->member_model->where('create_time>='.$yesterday.' and create_time<'.$today)->count();
    		}else if($val==3){
    			$count= $this->member_model->where('create_time>='.$month_front.' and create_time<'.$today)->count();
    		}else if($val==4){
    			$count= $this->member_model->where('create_time>='.$month_two.' and create_time<'.$today)->count();
    		}else if($val==5){
    			$count= $this->member_model->where('create_time>='.$start.' and create_time<='.$end)->count();
    		}
    		echo json_encode(array('count'=>$count));
    		exit;
    	}else if($type==2){
    		if($val==1){
    			$zigou_money = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    			$commission = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    			$dailifanxian = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$ptsy = $commission-$zigou_money-$dailifanxian;
    		}else if($val==2){
    			$zigou_money = $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    			$commission = $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    			$dailifanxian = $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$ptsy = $commission-$zigou_money-$dailifanxian;
    		}else if($val==3){
    			$zigou_money = $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    			$commission = $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    			$dailifanxian = $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$ptsy = $commission-$zigou_money-$dailifanxian;
    		}else if($val==4){
    			$zigou_money = $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    			$commission = $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    			$dailifanxian = $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$ptsy = $commission-$zigou_money-$dailifanxian;
    		}else if($val==5){
    			$zigou_money = $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->sum('zigou_return');
    			$commission = $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    			$dailifanxian = $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$ptsy = $commission-$zigou_money-$dailifanxian;
    		}
    		echo json_encode(array('count_ziygou'=>empty($zigou_money)?'0.0':$zigou_money,'ptsy'=>empty($ptsy)?'0.0':$ptsy));
    		exit;
    	}else if($type==3){
    		if($val==1){
    			$todayOrderCount= $this->order_model->where('add_time>='.$today.' and deleteed=0 and mid<>0')->count();//总订单
		    	$payorderCount= $this->order_model->where('add_time>='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
		    	$invalidCount= $this->order_model->where('add_time>='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
		    	$finishCount= $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    		}else if($val==2){
    			$todayOrderCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
		    	$payorderCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
		    	$invalidCount= $this->order_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
		    	$finishCount= $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    		}else if($val==3){
    			$todayOrderCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
		    	$payorderCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
		    	$invalidCount= $this->order_model->where('add_time>='.$month_front.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
		    	$finishCount= $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    		}else if($val==4){
    			$todayOrderCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and deleteed=0 and mid<>0')->count();//总订单
		    	$payorderCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
		    	$invalidCount= $this->order_model->where('add_time>='.$month_two.' and add_time<='.$today.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
		    	$finishCount= $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
		    	
    		}else if($val==5){
    			$todayOrderCount= $this->order_model->where('add_time>='.$start.' and add_time<='.$end.' and deleteed=0 and mid<>0')->count();//总订单
		    	$payorderCount= $this->order_model->where('add_time>='.$start.' and add_time<='.$end.' and status=1 and deleteed=0 and mid<>0')->count();//付款订单
		    	$invalidCount= $this->order_model->where('add_time>='.$start.' and add_time<='.$end.' and status=2 and deleteed=0 and mid<>0')->count();//失效订单
		    	$finishCount= $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->count();//结算订单
    		}
    		
    		echo json_encode(array('todayOrderCount'=>$todayOrderCount,'payorderCount'=>$payorderCount,'invalidCount'=>$invalidCount,'finishCount'=>$finishCount));
    		exit;
    	}else if($type==4){
    		if($val==1){
    			$fanxianMoney = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$sumreturn = $this->order_model->where('finish_time>='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    		}else if($val==2){
    			$fanxianMoney = $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$sumreturn = $this->order_model->where('finish_time>='.$yesterday.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    		}else if($val==3){
    			$fanxianMoney = $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$sumreturn = $this->order_model->where('finish_time>='.$month_front.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    		}else if($val==4){
    			$fanxianMoney = $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$sumreturn = $this->order_model->where('finish_time>='.$month_two.' and finish_time<='.$today.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    		}else if($val==5){
    			$fanxianMoney = $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->sum('daili_return');
    			$sumreturn = $this->order_model->where('finish_time>='.$start.' and finish_time<='.$end.' and status=3 and deleteed=0 and mid<>0')->sum('commission');
    		}
    		echo json_encode(array('fanxianMoney'=>empty($fanxianMoney)?'0.0':$fanxianMoney,'sumreturn'=>empty($sumreturn)?'0.0':$sumreturn));
    		exit;
    	}else if($type==5){
    		if($val==1){
    			$tixianMoney = $this->cash_model->where('add_time>='.$today.' and type=1')->sum('cash_money');
    		}else if($val==2){
    			$tixianMoney = $this->cash_model->where('add_time>='.$yesterday.' and add_time<='.$today.' and type=1')->sum('cash_money');
    		}else if($val==3){
    			$tixianMoney = $this->cash_model->where('add_time>='.$month_front.' and add_time<='.$today.' and type=1')->sum('cash_money');
    		}else if($val==4){
    			$tixianMoney = $this->cash_model->where('add_time>='.$month_two.' and add_time<='.$today.' and type=1')->sum('cash_money');
    		}else if($val==5){
    			$tixianMoney = $this->cash_model->where('add_time>='.$start.' and add_time<='.$end.' and type=1')->sum('cash_money');
    		}
    		echo json_encode(array('tixianMoney'=>empty($tixianMoney)?'0.0':$tixianMoney));
    		exit;
    	}
    }


    //客服热线
    public function customer_service(){

        $data = M('customer_service')->where(array('id'=>1))->getField('service_phone');
        if(IS_POST){

            $res = M('customer_service')->where(array('id'=>1))->setField('service_phone',I('phone'));

            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'设置成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'设置失败，请重试'));
            }
        }


        $this->assign('phone',$data);

        $this->display();
    }
}

