<?php
namespace Portal\Controller;
use Behavior\ReadHtmlCacheBehavior;
use Common\Controller\HomebaseController;
header("Content-type: text/html; charset=utf-8"); 
/**
 * 
 */
class MemberController extends HomebaseController {

	protected $member_model;
	protected $return_model;
	protected $push_model;
	protected $goodslog_model;
	protected $order_model;
	protected $tixian_model;



	
	public function _initialize(){

		parent::_initialize();
		$this->member_model = M("Member");
		$this->return_model = M("ReturnLevel");
		$this->push_model = M("Push");
		$this->goodslog_model = M("Goodslog");
		$this->order_model = M("Order");
        //$this->order_model = M("OrderCopy");
		$this->tixian_model = M("Tixian");
	}


	public function testOrder(){
        include 'extend/taobao/TopSdk.php';
        $c=new \TopClient();
        $c->appkey = '24682605';
        $c->secretKey = '230291803e881263fc3716b6202b6876';
        $userinput='2018-06-11 12:33:43';
        $url='https://api.open.21ds.cn/apiv1/gettkorder?apkey=7ba1d985-f0df-3b52-0e9f-b6303debae35&starttime='.urlencode($userinput).'&span=1200&page=1&pagesize=100&tkstatus=1&ordertype=create_time&tbname=0453china';

        $homepage = file_get_contents($url);
        $res=$this->is_json($homepage);
      

      

    }


    function get_bankcard_info($url){

        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//绕过ssl验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    //淘口令转商品链接
    public function getTkl() {
        //€Yb9T04Dxor3€
        $mid = isset($_REQUEST['mid'])?$_REQUEST['mid']:'';
        $tkl = isset($_REQUEST['tkl'])?$_REQUEST['tkl']:'';
        $filename="a.txt";

        $handle=fopen($filename,"a+");
        $str=fwrite($handle,"{$tkl}\r\n");
        fclose($handle);
        $data = array();
        if(empty($tkl)){

            $msg = '参数不能为空';
            return return_result(0,1002,$msg,$data);
        }

        /*
         * tab:1、网站 2、APP 3、导购 4、软件
        * gcid:0、网站 7、APP 8、导购 7、软件
        */
        /*
        $url_tg = 'http://pub.alimama.com/common/adzone/adzoneManage.json?tab=2&toPage=1&perPageSize=100&gcid=7&t=1508486089958&pvid={pvid}&_tb_token_={token';
        $tgw=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=json&url='.urlencode($url_tg).'&skey=jiandan666');
        $tgwArray=$this->is_json($tgw);

        $tgwdata = array();
        foreach($tgwArray['data']['pagelist'] as $key=>$value){
            array_push($tgwdata,$value['adzonePid']);
        }
        */

        $tgwdata = array('mm_25721409_39272459_146926552','mm_25721409_39272459_146956600','mm_25721409_39272459_146922906','mm_25721409_39272459_146924925','mm_25721409_39272459_146946652',
            'mm_25721409_39272459_146920943','mm_25721409_39272459_146916867');
        $tgwkey = array_rand($tgwdata);


        $pid = $tgwdata[$tgwkey];
        if(empty($mid)){

            $returnMoney= $this->return_model->where(array('is_default'=>1))->find();

        }else{
            $info= $this->member_model->where(array('id'=>$mid))->find();
            $returnMoney = $this->return_model->where(array('id'=>$info['level_id']))->find();
        }
        $tkl = '￥'.$tkl.'￥';

        $URL='https://api.open.21ds.cn/apiv1/getitemgyurlbytpwd?apkey=b53be33a-d3e2-5696-9602-1fb8d13b290e&tpwdcode='.$tkl.'&pid='.$pid.'&tbname=0453china&shorturl=1';

        $homepage = file_get_contents($URL);
        $res=$this->is_json($homepage);

         if($res['code'] <0){
             $data = array(
                 'sub_code' => $res['data']['sub_code'],
                 'msg'=>$res['data']['sub_msg'],
                 'datas'=>$res['data']['request_id'],
                 'status'=>-1
             );
             echo json_encode($data);
             exit;

         }
        $_url='https://api.open.21ds.cn/apiv1/getiteminfo?apkey=b53be33a-d3e2-5696-9602-1fb8d13b290e&itemid='.$res['result']['data']['item_id'];
        $goods = file_get_contents($_url);
        $res_2=$this->is_json($goods);

        $dataList = array();

        if(!empty($res['result']['data']['coupon_info'])){

            $arr['coupon_click_url'] = $res['result']['data']['coupon_click_url'];
            $arr['title'] = $res_2['data']['n_tbk_item']['title'];
            $arr['coupon_info'] =$res['result']['data']['coupon_info'];
            $arr['max_commission_rate'] = $res['result']['data']['max_commission_rate'];
            $arr['pict_url'] = $res_2['data']['n_tbk_item']['pict_url'];
            $arr['goodsid'] = $res['result']['data']['item_id'];
            $arr['item_url']=$res_2['data']['n_tbk_item']['item_url'];
            $arr['zk_final_price']=$res_2['data']['n_tbk_item']['zk_final_price'];
            if($res['result']['data']['coupon_info']){
//                var_dump($arr['coupon_info']);die;
//                $coupon_picre=invtal(mb_substr(explode("减",$arr['coupon_info'])[1],0,-1));

                $coupon_picre=mb_substr(explode("减",$arr['coupon_info'])[1],0,-1);
            }
 
            $arr['commission'] =  round((($res_2['data']['n_tbk_item']['zk_final_price']-$coupon_picre)*$res['result']['data']['max_commission_rate']/100),2);

            $arr['short_url']=$res['result']['data']['short_url'];
            $status = 1;

            if(!empty($mid)) {
                $goodslog = $this->goodslog_model->where(array('mid'=>$mid,'goodsid'=>$arr['goodsid'],'pid'=>$pid))->find();
                if(empty($goodslog)){
                    $goods_data['mid'] = $mid;
                    $goods_data['goodsid'] = $arr['goodsid'];
                    $goods_data['pid'] = $pid;
                    $goods_data['addtime'] = time();
                    $this->goodslog_model->add($goods_data);
                }
            }
            array_push($dataList,$arr);
            $data_array['goodsid'] = $arr['goodsid'];
            $data_array['data'] = $dataList;
        }else{
            $status = 1;
            $arr['coupon_click_url'] = $res['result']['data']['coupon_click_url'];
            $arr['title'] = $res_2['data']['n_tbk_item']['title'];
            $arr['coupon_info'] ='';
            $arr['short_url']='';
            $arr['max_commission_rate'] = $res['result']['data']['max_commission_rate'];
            $arr['pict_url'] = $res_2['data']['n_tbk_item']['pict_url'];
            $arr['goodsid'] = $res['result']['data']['item_id'];
            $arr['item_url']=$res_2['data']['n_tbk_item']['item_url'];
            $arr['zk_final_price']=$res_2['data']['n_tbk_item']['zk_final_price'];
            $arr['commission'] = round(($res_2['data']['n_tbk_item']['zk_final_price']*$res['result']['data']['max_commission_rate']/100),2);
//            $arr['commission'] = round($commission*($returnMoney['zigou_return']/100),2);
            array_push($dataList,$arr);
            $data_array['goodsid'] = $res['result']['data']['item_id'];
            $data_array['data'] = $dataList;
        }

        $data = array(
            'status' => $status,
            'msg'=>'成功',
            'datas'=>$data_array
        );

        echo json_encode($data);
        exit;
    }



    //淘口令转商品链接
    public function getTkl1() {
        //€Yb9T04Dxor3€

        $mid = isset($_REQUEST['mid'])?$_REQUEST['mid']:'';
        $tkl = isset($_REQUEST['tkl'])?$_REQUEST['tkl']:'';
        $filename="a.txt";

        $handle=fopen($filename,"a+");

        $str=fwrite($handle,"{$tkl}\r\n");

        fclose($handle);



        $data = array();
        if(empty($tkl)){

            $msg = '参数不能为空';
            return return_result(0,1002,$msg,$data);
        }

        /*
         * tab:1、网站 2、APP 3、导购 4、软件
        * gcid:0、网站 7、APP 8、导购 7、软件
        */
        /*
        $url_tg = 'http://pub.alimama.com/common/adzone/adzoneManage.json?tab=2&toPage=1&perPageSize=100&gcid=7&t=1508486089958&pvid={pvid}&_tb_token_={token';
        $tgw=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=json&url='.urlencode($url_tg).'&skey=jiandan666');
        $tgwArray=$this->is_json($tgw);

        $tgwdata = array();
        foreach($tgwArray['data']['pagelist'] as $key=>$value){
            array_push($tgwdata,$value['adzonePid']);
        }
        */

        $tgwdata = array('mm_25721409_39272459_146926552','mm_25721409_39272459_146956600','mm_25721409_39272459_146922906','mm_25721409_39272459_146924925','mm_25721409_39272459_146946652',
            'mm_25721409_39272459_146920943','mm_25721409_39272459_146916867');
        $tgwkey = array_rand($tgwdata);


        $pid = $tgwdata[$tgwkey];
        if(empty($mid)){

            $returnMoney= $this->return_model->where(array('is_default'=>1))->find();

        }else{
            $info= $this->member_model->where(array('id'=>$mid))->find();
            $returnMoney = $this->return_model->where(array('id'=>$info['level_id']))->find();
        }
        $tkl = '€'.$tkl.'€';
        $rs=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=taotoken&content='.urlencode($tkl).'&type=1&pid='.$pid);
        $res=$this->is_json($rs);
        $dataList = array();

        if(!empty($res['tkCommFee'])){
            $arr['url'] = empty($res['couponLink'])?$res['clickUrl']:$res['couponLink'];
            $arr['picurl'] = "http:".$res['pictUrl'];
            $arr['startFee'] = $res['zkPrice'];//优惠券起用钱
            $arr['amount'] = $res['couponAmount'];//优惠钱
            $arr['goodsid'] = $res['auctionId'];
            $arr['title'] = $res['auctionTitle'];
            $commission = round(($res['zkPrice']-$res['couponAmount'])*($res['tkRate']/100),2);
            $arr['commission'] = round($commission*($returnMoney['zigou_return']/100),2);
            $status = 1;
            if(!empty($mid)){
                $goodslog = $this->goodslog_model->where(array('mid'=>$mid,'goodsid'=>$arr['goodsid'],'pid'=>$pid))->find();
                if(empty($goodslog)){
                    $goods_data['mid'] = $mid;
                    $goods_data['goodsid'] = $arr['goodsid'];
                    $goods_data['pid'] = $pid;
                    $goods_data['addtime'] = time();
                    $this->goodslog_model->add($goods_data);
                }
            }
            array_push($dataList,$arr);
            $data_array['goodsid'] = $arr['goodsid'];
            $data_array['data'] = $dataList;
        }else{
            $status = 0;
            $arr['url'] = '';
            $arr['picurl'] = '';
            $arr['startFee'] = '';//优惠券起用钱
            $arr['amount'] = '';//优惠钱
            $arr['effectiveStartTime'] = '';//开始时间
            $arr['effectiveEndTime'] = '';//结束时间
            $arr['goodsid'] = '';
            $arr['title'] = '';
            $arr['commission'] = '';
            array_push($dataList,$arr);
            $data_array['goodsid'] = '';
            $data_array['data'] = $dataList;
        }

        $data = array(
            'status' => $status,
            'msg'=>'成功',
            'datas'=>$data_array
        );
        echo json_encode($data);
        exit;
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


    //登陆
    function saveInfo(){

    	$returnDefault= $this->return_model->where(array('is_default'=>1))->find();
    	$arr['openid'] = isset($_POST['openid'])?$_POST['openid']:'';
    	$arr['taobao_sid'] = $_POST['taobao_sid'];
    	$arr['avatar'] = $_POST['avatar'];
    	$arr['nickname'] = $_POST['nickname'];
    	$arr['jg_id'] = $_POST['jg_id'];
    	$arr['create_time'] = time();
    	$arr['phone_type'] = $_POST['phone_type'];//1、安卓 2、IOS
    	$arr['level_id'] = $returnDefault['id'];

        $token = md5(microtime().rand(0000,9999));  //token

        $data = array();
		if(empty($arr['openid'])){
			$msg = '参数不能为空';
			return return_result(0,1002,$msg,$data);
		}
		$info= $this->member_model->where(array('openid'=>$arr['openid']))->find();
		$if_manager = $this->member_model->where('is_manager=1')->find();  //是否是客服
		
		if(empty($info)){    //是第一次登陆
			include 'extend/phpqrcode.php';

            $id=$this->member_model->add($arr);
    		$value = $id; //二维码内容
	    	$errorCorrectionLevel = 'H';//容错级别
	    	$matrixPointSize = 6;//生成图片大小
	    	$qrpath='data/upload/qrcode/';//二维码保存路径
	    	$qrcodename = $id;
	    	$qrcode = new \QRcode();
	    	$qrcode->png($value, $qrpath.$qrcodename.'.png',$errorCorrectionLevel,$matrixPointSize,2);
	    	$qrcode_url='/data/upload/qrcode/'.$qrcodename.'.png';
    		$rwm['qrcode'] = WEB_URL.$qrcode_url;
    		$rwm['invite_code'] = $this->getInvite();
    		$rwm['status'] = 1; //登陆成功标识
    		$rwm['token'] = $token;

    		$res = $this->member_model->where('id='.$id)->save($rwm);
    		$data['mid'] = $id;
    		$data['is_bind'] = 0;

		}else{
			if(empty($info['invite_code'])){
				$mem['invite_code'] = $this->getInvite();
			}

			$mem['avatar'] = $_POST['avatar'];
			$mem['nickname'] = $_POST['nickname'];
			$mem['status'] = 1; //登陆成功标识
			$mem['token'] = $token;    //token
			$res = $this->member_model->where('id='.$info['id'])->save($mem);


			$data['mid'] = $info['id'];
			$data['is_bind'] = empty($info['share_id'])?0:1;
		}
		$data['count'] = $this->push_model->where('mid='.$data['mid'].' and status=0')->count();
		$data['kefuid'] = $if_manager['id'];
		$data['kefu_nickname'] = $if_manager['nickname'];
		$data['kefu_avatar'] = $if_manager['avatar'];
        if(!empty($info['pwd'])){
            $data['pwd_status'] = 1;   //该用户已经设置过 提现密码
        }else{
            $data['pwd_status'] = 0;  //该用户没有设置过 提现密码
        }
        $data['token'] = $token;  //token

        session('mid',$data['mid']);
		$this->updateInfo2($data['mid']);
		return return_result(1,1000,'成功',$data);
    }


    //推出登陆
    function logout(){
	    $mid = I('post.mid');

        $data = array();
        if(empty($mid)){
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,$data);
        }
        $arr = array(             //退出登录清空token改变状态值
            'status'=>0,
            'token' =>''
        );
	   $res =  M('member')->where(array('id'=>$mid))->save($arr);     //推出登陆标识

        return return_result(1,1000,'成功',$data);

    }

    
    function is_json($string){
    	try{
    		$data = json_decode($string,true);
    	}catch(Exception $e){
    		return  $string;
    	}
    	return $data;
    }
    
    function binding(){

    	//$share_id = isset($_REQUEST['share_id'])?$_REQUEST['share_id']:'';
    	$invitecode = isset($_REQUEST['invitecode'])?$_REQUEST['invitecode']:'';
    	$mid = isset($_REQUEST['mid'])?$_REQUEST['mid']:'';
    	if(empty($invitecode) || empty($mid)){
    		$msg = '参数不能为空';
    		return return_result(0,1002,$msg,array());
    	}

        $info1= $this->member_model->where(array('id'=>$mid))->find();
    	$info2= $this->member_model->where(array('invite_code'=>$invitecode))->find();
    	if(empty($info1)){
    		return return_result(0,1003,$msg,array());
    	}
    	if(empty($info2)){
    		return return_result(0,1003,'该邀请码不存在',array());
    	}
//var_dump($info1);die;
    	if(!empty($info1['share_id'])){

    		return return_result(0,1002,'不能重复绑定',array());
    	}

        if($info2['share_id']==$mid){
    		return return_result(0,1002,'对方已是您的粉丝',array());
    	}
    	if($info1['share_id']==$info2['id']){
    		return return_result(0,1002,'对方已是您的粉丝',array());
    	}


        $arr['share_id'] = $info2['id'];  //上级id
    	$arr['share_add_time'] = time();
    	$res = $this->member_model->where('id='.$mid)->save($arr);

        $arr['uid'] = $mid;               //当前用户id
        $arr['nickname'] = $info1['nickname'];
        $arr['avatar'] = $info1['avatar'];
        $group_fans = M('member2')->where(array('share_id'=>$arr['share_id']))->field('group_fans')->select();  //查看该邀请者的邀请人数（或者粉丝人数）

        if(empty($group_fans) && !isset($group_fans)){  //第一次 拉新
            $arr['group_fans'] = 1;
        }else{
            if(count($group_fans)%3==0){
                $arr['group_fans'] = max($group_fans)['group_fans']+1;     
            }else{
                $arr['group_fans'] = max($group_fans)['group_fans'];
            }
        }
        $result = M('member2')->add($arr);
    	return return_result(1,1000,'成功',array());
    }



    //点击生成二维码
    function getInfo(){
    	$mid = isset($_REQUEST['mid'])?$_REQUEST['mid']:'';
    	$data = array();
    	if(empty($mid)){
    		$msg = '参数不能为空';
    		return return_result(0,1002,$msg,$data);
    	}
    	$info= $this->member_model->where(array('id'=>$mid))->find();
    	if(!empty($info)){
    		$data['avatar'] = $info['avatar'];//头像
	    	$data['cash_total'] = empty($info['cash_total'])?'0.00':$info['cash_total'];//总提现
	    	$data['use_money'] = empty($info['use_money'])?'0.00':$info['use_money'];//账户余额
	    	$data['total_return'] = empty($info['total_return'])?'0.00':$info['total_return'];//总返现
	    	//$data['qrcode'] = $info['qrcode'];//二维码
	    	$data['qrcode'] = "http://app.shop-d.cn/public/img/qrcode.png";
	    	$data['iscash'] = empty($info['pwd'])?0:1;//是否设置提现密码
	    	$data['account'] = empty($info['account'])?'':$info['account'];//提现账户
	    	$data['realname'] = empty($info['realname'])?'':$info['realname'];//真实姓名
	    	$data['pwd'] = empty($info['pwd'])?'':$info['pwd'];//提现密码
	    	$data['invite_code'] = $info['invite_code'];//邀请码
	    	$data['yugu_money'] = empty($info['yugu_money'])?'0.00':$info['yugu_money'];//预估提现
	    	$data['nickname'] = empty($info['nickname'])?'':$info['nickname'];//用户昵称
	    	return return_result(1,1000,'成功',$data);
    	}else{
    		$msg = '该用户不存在';
    		return return_result(0,1003,$msg,$data);
    	}
    }
    
    function setCash(){
    	
    	$mid = isset($_POST['mid'])?$_POST['mid']:'';
    	$type = isset($_POST['type'])?$_POST['type']:'';//1、设置密码 2、修改密码 3、修改支付宝帐号和密码
    	$account = isset($_POST['account'])?$_POST['account']:'';
    	$realname = isset($_POST['realname'])?$_POST['realname']:'';
    	$password = isset($_POST['pwd'])?$_POST['pwd']:'';
    	$oldpassword = isset($_POST['oldpwd'])?$_POST['oldpwd']:'';
    	//$sign = isset($_POST['sign'])?$_POST['sign']:'';
    	$data = array();

        if($type !=2){
            if(empty($account) || empty($realname) || empty($password)){
                $msg = '参数不能为空';

                return return_result(0,1002,$msg,$data);
            }
        }

        if($type==2 && $oldpassword==''){  //修改密码 必须有$oldpassword
            $msg = '参数不能为空';
            return return_result(0,1002,$msg,$data);
        }
    	$item['mid'] = $mid;
    	$item['type'] = $type;
    	$item['account'] = $account;
    	$item['realname'] = $realname;
    	$item['pwd'] = $password;
    	$item['oldpwd'] = $oldpassword;
//    	$item['sign'] = $sign;
//    	$flag = check_sign($item);
//    	if(!$flag){
//    		return return_result(0,1014,'签名错误',$data);
//    	}
    	$info= $this->member_model->where(array('id'=>$mid))->find();
    	if(!empty($info)){
	    	if($type==1){
	    		$arr['pwd'] = md5($password);
	    		$arr['account'] = $account;
	    		$arr['realname'] = $realname;
	    		$res = $this->member_model->where('id='.$mid)->save($arr);
	    		return return_result(1,1000,'成功',$data);
	    	}else if($type==2){
	    		if(md5($oldpassword)==$info['pwd']){
	    			$arr['pwd'] = md5($password);
	    			$arr['account'] = $account;
	    			$arr['realname'] = $realname;
	    			$res = $this->member_model->where('id='.$mid)->save($arr);
	    			return return_result(1,1000,'成功',$data);
	    		}else{
	    			$msg = '密码错误';
	    			return return_result(0,1004,$msg,$data);
	    		}
	    	}else if($type==3){
	    		if(md5($password)==$info['pwd']){
	    			$arr['account'] = $account;
	    			$arr['realname'] = $realname;
	    			$res = $this->member_model->where('id='.$mid)->save($arr);
	    			return return_result(1,1000,'成功',$data);
	    		}else{
	    			$msg = '密码错误';
	    			return return_result(0,1004,$msg,$data);
	    		}
	    	}
    	}else{
    		$msg = '该用户不存在';
    		return return_result(0,1003,$msg,$data);
    	}
    	
    }
    //老版
    function getTaoBaoOrder1(){

        //十分钟前的订单

        //$start_date=date("Y-m-d H:i:s",time()-60*10);
        //测试时间
        $start_date='2018-06-11 12:33:43';
        $url='https://api.open.21ds.cn/apiv1/gettkorder?apkey=b53be33a-d3e2-5696-9602-1fb8d13b290e&starttime='.urlencode($start_date).'&span=1200&page=1&pagesize=100&tkstatus=1&ordertype=create_time&tbname=0453china';
        $homepage = file_get_contents($url);
        $res=$this->is_json($homepage);

        if($res['code'] == -1 || empty($res['data'])){
            return false;  exit;
        }else{
            if(count($res['data']['n_tbk_order']) == count($res['data']['n_tbk_order'], 1)){

                $info= $this->order_model->where(array('order_sn'=>$res['data']['n_tbk_order']['trade_id'],'goods_id'=>$res['data']['n_tbk_order']['num_iid']))->find();
                if(empty($info)){
                    $data['order_sn'] = $res['data']['n_tbk_order']['trade_id'];//订单编号
                    $data['status'] = $res['data']['n_tbk_order']['tk_status'];//订单状态
                    $data['price'] = $res['data']['n_tbk_order']['pay_price'];//订单金额
                    $data['goods_title'] = $res['data']['n_tbk_order']['item_title'];//商品信息
                    $data['goods_id'] = $res['data']['n_tbk_order']['num_iid'];//商品ID
                    $data['num'] = $res['data']['n_tbk_order']['item_num'];//商品数量
                    $data['marketprice'] = $res['data']['n_tbk_order']['price'];//商品单价
                    $checkOrder = substr($data['order_sn'], -6);

                    $map['checkorder']  = array('eq',$checkOrder);
                    $membercheck = $this->member_model->where($map)->find();
                    if(empty($membercheck)){
                        $membercheck = array();
                    }


                    if($res['data']['n_tbk_order']['order_type']=='天猫'){
                        $order_type = 2;
                    }else{
                        $order_type = 1;
                    }
                    $data['order_type'] = $order_type;//订单类型
                    $data['add_time'] = empty($res['data']['n_tbk_order']['create_time'])?'':strtotime($res['data']['n_tbk_order']['create_time']);//创建时间
                    $data['finish_time'] = empty($res['data']['n_tbk_order']['earning_time'])?'':strtotime($res['data']['n_tbk_order']['earning_time']);//结算时间
                    $data['commission'] = $res['data']['n_tbk_order']['total_commission_fee'];

                    /*
                    $bilv = trim(str_replace('%', '', $value['F11']));
                    $cmoney =  round($value['F13']*$bilv,2);
                    $data['commission'] = $cmoney;
                    */
                    $data['from_meiti'] = $res['data']['n_tbk_order']['site_id'];
                    $data['ad_id'] = $res['data']['n_tbk_order']['adzone_id'];

                    if(empty($membercheck)){
                        $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                        $goodslog = $this->goodslog_model->where(array('goodsid'=>$res['data']['n_tbk_order']['num_iid'],'pid'=>$pid))->find();

                        if(!empty($goodslog)){
                            $data['mid'] = $goodslog['mid'];
                        }else{
                            $data['mid'] = '';
                        }
                        if(!empty($data['mid'])){
                            $this->member_model->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                        }
                    }else{
                        $data['mid'] = $membercheck['id'];
                    }
                    if(empty($data['commission'])){
                        $data['commission'] = 0;
                    }
                    $id = $this->order_model->add($data);

                }else{
                    if($info['status'] != $res['data']['n_tbk_order']['tk_status']){//订单状态
                        if($res['data']['n_tbk_order']['tk_status']==3){
                            $odata['finish_time'] = strtotime($res['data']['n_tbk_order']['earning_time']);
                        }
                        $odata['status'] = $res['data']['n_tbk_order']['tk_status'];
                        $this->order_model->where("id=".$info['id'])->save($odata);
                    }

                }



            }else{
                foreach ($res['data']['n_tbk_order'] as $key=>$value){
                    $info= $this->order_model->where(array('order_sn'=>$value['trade_id'],'goods_id'=>$value['num_iid']))->find();
                    if(empty($info)){
                        $data['order_sn'] = $value['trade_id'];//订单编号
                        $data['status'] = $value['tk_status'];//订单状态
                        $data['price'] = $value['pay_price'];//订单金额
                        $data['goods_title'] = $value['item_title'];//商品信息
                        $data['goods_id'] = $value['num_iid'];//商品ID
                        $data['num'] = $value['item_num'];//商品数量
                        $data['marketprice'] = $value['price'];//商品单价
                        $checkOrder = substr($data['order_sn'], -6);

                        $map['checkorder']  = array('eq',$checkOrder);
                        $membercheck = $this->member_model->where($map)->find();
                        if(empty($membercheck)){
                            $membercheck = array();
                        }


                        if($value['order_type']=='天猫'){
                            $order_type = 2;
                        }else{
                            $order_type = 1;
                        }
                        $data['order_type'] = $order_type;//订单类型
                        $data['add_time'] = empty($value['create_time'])?'':strtotime($value['create_time']);//创建时间
                        $data['finish_time'] = empty($value['earning_time'])?'':strtotime($value['earning_time']);//结算时间
                        $data['commission'] = $value['total_commission_fee'];

                        /*
                        $bilv = trim(str_replace('%', '', $value['F11']));
                        $cmoney =  round($value['F13']*$bilv,2);
                        $data['commission'] = $cmoney;
                        */
                        $data['from_meiti'] = $value['site_id'];
                        $data['ad_id'] = $value['adzone_id'];

                        if(empty($membercheck)){
                            $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                            $goodslog = $this->goodslog_model->where(array('goodsid'=>$value['num_iid'],'pid'=>$pid))->find();

                            if(!empty($goodslog)){
                                $data['mid'] = $goodslog['mid'];
                            }else{
                                $data['mid'] = '';
                            }
                            if(!empty($data['mid'])){
                                $this->member_model->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                            }
                        }else{
                            $data['mid'] = $membercheck['id'];
                        }
                        if(empty($data['commission'])){
                            $data['commission'] = 0;
                        }
                        $id = $this->order_model->add($data);
                        // var_dump($id);

                    }else{
                        if($info['status'] != $value['tk_status']){//订单状态
                            if($value['tk_status']==3){
                                $odata['finish_time'] = strtotime($value['earning_time']);
                            }
                            $odata['status'] = $value['tk_status'];
                            $this->order_model->where("id=".$info['id'])->save($odata);
                        }

                    }
                }

            }

        }
        //更新用户订单
        $memberList = $this->member_model->select();
        foreach($memberList as $key=>$value){
            $this->updateInfo($value['id']);
        }

        exit;


    }
    public function getzhuquorder($time,$x){
        $start_date=date("Y-m-d H:i:s",$time);
        $url='https://api.open.21ds.cn/apiv1/gettkorder?apkey=b53be33a-d3e2-5696-9602-1fb8d13b290e&starttime='.urlencode($start_date).'&span=1200&page=1&pagesize=100&tkstatus=1&ordertype=create_time&tbname=0453china';
        $homepage = file_get_contents($url);
        $res=$this->is_json($homepage);

        if($res['code'] == -1 || empty($res['data'])){
            return false;  exit;
        }else{

            if(count($res['data']['n_tbk_order']) == count($res['data']['n_tbk_order'], 1)){

                $info= $this->order_model->where(array('order_sn'=>$res['data']['n_tbk_order']['trade_id'],'goods_id'=>$res['data']['n_tbk_order']['num_iid']))->find();
                if(empty($info)){
                    $data['order_sn'] = $res['data']['n_tbk_order']['trade_id'];//订单编号
                    $data['status'] = $res['data']['n_tbk_order']['tk_status'];//订单状态
                    $data['price'] = $res['data']['n_tbk_order']['pay_price'];//订单金额
                    $data['goods_title'] = $res['data']['n_tbk_order']['item_title'];//商品信息
                    $data['goods_id'] = $res['data']['n_tbk_order']['num_iid'];//商品ID
                    $data['num'] = $res['data']['n_tbk_order']['item_num'];//商品数量
                    $data['marketprice'] = $res['data']['n_tbk_order']['price'];//商品单价
                    $checkOrder = substr($data['order_sn'], -6);

                    $map['checkorder']  = array('eq',$checkOrder);
                    $membercheck = $this->member_model->where($map)->find();
                    if(empty($membercheck)){
                        $membercheck = array();
                    }


                    if($res['data']['n_tbk_order']['order_type']=='天猫'){
                        $order_type = 2;
                    }else{
                        $order_type = 1;
                    }
                    $data['order_type'] = $order_type;//订单类型
                    $data['add_time'] = empty($res['data']['n_tbk_order']['create_time'])?'':strtotime($res['data']['n_tbk_order']['create_time']);//创建时间
                    $data['finish_time'] = empty($res['data']['n_tbk_order']['earning_time'])?'':strtotime($res['data']['n_tbk_order']['earning_time']);//结算时间
                    $data['commission'] = $res['data']['n_tbk_order']['total_commission_fee'];

                    $data['from_meiti'] = $res['data']['n_tbk_order']['site_id'];
                    $data['ad_id'] = $res['data']['n_tbk_order']['adzone_id'];

                    if(empty($membercheck)){
                        $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                        $goodslog = $this->goodslog_model->where(array('goodsid'=>$res['data']['n_tbk_order']['num_iid'],'pid'=>$pid))->find();

                        if(!empty($goodslog)){
                            $data['mid'] = $goodslog['mid'];
                        }else{
                            $data['mid'] = '';
                        }
                        if(!empty($data['mid'])){
                            $this->member_model->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                        }
                    }else{
                        $data['mid'] = $membercheck['id'];
                    }
                    if(empty($data['commission'])){
                        $data['commission'] = 0;
                    }
                    $id = $this->order_model->add($data);

                }else{
                    if($info['status'] != $res['data']['n_tbk_order']['tk_status']){//订单状态
                        if($res['data']['n_tbk_order']['tk_status']==3){
                            $odata['finish_time'] = strtotime($res['data']['n_tbk_order']['earning_time']);
                        }
                        $odata['status'] = $res['data']['n_tbk_order']['tk_status'];
                        $this->order_model->where("id=".$info['id'])->save($odata);
                    }

                }

            }else{
                foreach ($res['data']['n_tbk_order'] as $key=>$value){
                    $info= $this->order_model->where(array('order_sn'=>$value['trade_id'],'goods_id'=>$value['num_iid']))->find();
                    if(empty($info)){
                        $data['order_sn'] = $value['trade_id'];//订单编号
                        $data['status'] = $value['tk_status'];//订单状态
                        $data['price'] = $value['pay_price'];//订单金额
                        $data['goods_title'] = $value['item_title'];//商品信息
                        $data['goods_id'] = $value['num_iid'];//商品ID
                        $data['num'] = $value['item_num'];//商品数量
                        $data['marketprice'] = $value['price'];//商品单价
                        $checkOrder = substr($data['order_sn'], -6);

                        $map['checkorder']  = array('eq',$checkOrder);
                        $membercheck = $this->member_model->where($map)->find();
                        if(empty($membercheck)){
                            $membercheck = array();
                        }


                        if($value['order_type']=='天猫'){
                            $order_type = 2;
                        }else{
                            $order_type = 1;
                        }
                        $data['order_type'] = $order_type;//订单类型
                        $data['add_time'] = empty($value['create_time'])?'':strtotime($value['create_time']);//创建时间
                        $data['finish_time'] = empty($value['earning_time'])?'':strtotime($value['earning_time']);//结算时间
                        $data['commission'] = $value['total_commission_fee'];

                        /*
                        $bilv = trim(str_replace('%', '', $value['F11']));
                        $cmoney =  round($value['F13']*$bilv,2);
                        $data['commission'] = $cmoney;
                        */
                        $data['from_meiti'] = $value['site_id'];
                        $data['ad_id'] = $value['adzone_id'];

                        if(empty($membercheck)){
                            $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                            $goodslog = $this->goodslog_model->where(array('goodsid'=>$value['num_iid'],'pid'=>$pid))->find();

                            if(!empty($goodslog)){
                                $data['mid'] = $goodslog['mid'];
                            }else{
                                $data['mid'] = '';
                            }
                            if(!empty($data['mid'])){
                                $this->member_model->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                            }
                        }else{
                            $data['mid'] = $membercheck['id'];
                        }
                        if(empty($data['commission'])){
                            $data['commission'] = 0;
                        }
                        $id = $this->order_model->add($data);
                        // var_dump($id);

                    }else{
                        if($info['status'] != $value['tk_status']){//订单状态
                            if($value['tk_status']==3){
                                $odata['finish_time'] = strtotime($value['earning_time']);
                            }
                            $odata['status'] = $value['tk_status'];
                            $this->order_model->where("id=".$info['id'])->save($odata);
                        }

                    }
                }

            }

        }


    }

    function getTaoBaoOrder(){
         $time=I('time');
         for ($x=0; $x<=504; $x++) {
            $list[$x]=  $this->getzhuquorder($time-$x*1200,$x);
            usleep(3);

         }
         echo time();
    }
    
    function getOrder(){
    	$start_date = date("Y-m-d",strtotime("-60 day"));
    	$end_date = date("Y-m-d");
    	//$start_date = '2017-10-16';
    	//$end_date = '2017-10-16';
    	$tgwArray = $this->getTgw();
    	$rs=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=dingdan&type=0&id=&status=&start='.$start_date.'&end='.$end_date);
    	$res=$this->is_json($rs);
		
		
    	if(isset($res['dingdan'][1])){
    		$count = count($res['dingdan']);
    		
    		$result = array();
    		foreach($res['dingdan'] as $key=>$val){
    			if($key>0){
    				$key = $val['F25'].'_'.$val['F3'];
    				if(!isset($result[$key])){
    					$result[$key] = $val;
    				}else{
    					$result[$key]['F7'] += $val['F7'];
    					$result[$key]['F13'] += $val['F13'];
    					$result[$key]['F14'] += $val['F14'];
    				}
    			}
    		}
    		foreach($result as $key=>$value){
    			if($key>0){
    				//if(in_array($value['F27'].'_'.$value['F29'], $tgwArray)){
    				$info= $this->order_model->where(array('order_sn'=>$value['F25'],'goods_id'=>$value['F4']))->find();
    				
	    			if(empty($info)){
	    				
		    			$data['order_sn'] = $value['F25'];//订单编号
		    			$checkOrder = substr($data['order_sn'], -6);
		    
		    			$map['checkorder']  = array('eq',$checkOrder);
		    			$membercheck = $this->member_model->where($map)->find();
		    			if(empty($membercheck)){
		    				$membercheck = array();
		    			}
		    			
		    			if($value['F9']=='订单付款'){
		    				$status = 1;
		    			}else if($value['F9']=='订单失效'){
		    				$status = 2;
		    			}else if($value['F9']=='订单结算'){
		    				$status = 3;
		    			}else{
		    				$status = 4;
		    			}
		    			$data['status'] = $status;//订单状态
		    			$data['price'] = $value['F13'];//订单金额
		    			$data['goods_title'] = $value['F3'];//商品信息
		    			$data['goods_id'] = $value['F4'];//商品ID
		    			$data['num'] = $value['F7'];//商品数量
		    			$data['marketprice'] = $value['F8'];//商品单价
		    			if($value['F22']=='天猫'){
		    				$order_type = 2;
		    			}else{
		    				$order_type = 1;
		    			}
		    			$data['order_type'] = $order_type;//订单类型
		    			$data['add_time'] = empty($value['F1'])?'':strtotime($value['F1']);//创建时间
		    			$data['finish_time'] = empty($value['F17'])?'':strtotime($value['F17']);//结算时间
		    			$data['commission'] = $value['F19']=='0.00'?$value['F14']:$value['F19'];
		    			
		    			/*
		    			$bilv = trim(str_replace('%', '', $value['F11']));
		    			$cmoney =  round($value['F13']*$bilv,2);
		    			$data['commission'] = $cmoney;
		    			*/
		    			$data['from_meiti'] = $value['F27'];
		    			$data['ad_id'] = $value['F29'];
		    			
		    			if(empty($membercheck)){
		    				$pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
		    				$goodslog = $this->goodslog_model->where(array('goodsid'=>$value['F4'],'pid'=>$pid))->find();
		    				
		    				if(!empty($goodslog)){
		    					$data['mid'] = $goodslog['mid'];
		    				}else{
		    					$data['mid'] = '';
		    				}
		    				if(!empty($data['mid'])){
		    					$this->member_model->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
		    				}
		    			}else{
		    				$data['mid'] = $membercheck['id'];
		    			}
		    			
		    			//print_r($data);
		    			if(empty($data['commission'])){
		    				$data['commission'] = 0;
		    			}
		    			$id = $this->order_model->add($data);
		    			
	    			}else{
	    				
	    				if($value['F9']=='订单付款'){
	    					$status = 1;
	    				}else if($value['F9']=='订单失效'){
	    					$status = 2;
	    				}else if($value['F9']=='订单结算'){
	    					$status = 3;
	    				}else{
	    					$status = 4;
	    				}
	    				if($info['status'] != $status){
	    					if($status==3){
	    						$odata['finish_time'] = strtotime($value['F17']);
	    					}
	    					$odata['status'] = $status;
	    					$this->order_model->where("id=".$info['id'])->save($odata);
	    				}
	    				
	    			}
	    			
    			//}
    		  }
    		}
    	}
    	
    	$memberList = $this->member_model->select();
    	foreach($memberList as $key=>$value){
    		$this->updateInfo($value['id']);
    	}
    //	echo "<script>window.open('','_self','');window.close();</script>";
		exit;
    }

    //更新订单的 自购返现和代理返现
    function updateInfo($mid){
    	//更新用户订单等信息

    	$info= $this->member_model->where(array('id'=>$mid))->find();
    	$returnMoney= $this->return_model->where(array('id'=>$info['level_id']))->find();  //查询用户代理等级的返现比例
    	
    	//判断是否有粉丝
    	$fansCount = $this->member_model->where(array('share_id'=>$mid))->count();
    	
    	if(empty($fansCount)){  //无粉丝
    		$orderList = $this->order_model->where(array('mid'=>$mid,'tixian_status1'=>0))->select();  //查询未领取自购返现
    		foreach($orderList as $key=>$value){
    			$data['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
    			$this->order_model->where("id=".$value['id'])->save($data);
    		}
    		
    	}else{  //有粉丝
    		$fansList = $this->member_model->where(array('share_id'=>$mid))->select();  //查询所有的粉丝
    		
    		$orderList = $this->order_model->where(array('mid'=>$mid,'tixian_status1'=>0))->select(); //查询未领取自购返现
    		$countMoney = 0;
    		//有粉丝自己购物返现
    		foreach($orderList as $key=>$value){
    			$data2['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
    			$this->order_model->where("id=".$value['id'])->save($data2);
    		}
    		
    		//有粉丝 粉丝给自己的返现
    		foreach ($fansList as $key=>$value){
    			$fansOrderList = $this->order_model->where(array('mid'=>$value['id'],'tixian_status2'=>0))->select();  //粉丝给的 未领取的代理返现
    			foreach($fansOrderList as $k=>$v){
    				$data3['daili_return'] = round($v['commission']*($returnMoney['daili_return']/100),2);//代理返现
    				$this->order_model->where("id=".$v['id'])->save($data3);
    				
    			}
    		}
    	}

    }
    
    function updateInfo2($mid){
    	//更新用户订单等信息
    	$info= $this->member_model->where(array('id'=>$mid))->find();
    	$returnMoney= $this->return_model->where(array('id'=>$info['level_id']))->find();
    	 
    	//判断是否有粉丝
    	$fansCount = $this->member_model->where(array('share_id'=>$mid))->count();
    	 
    	if(empty($fansCount)){
    		$orderList = $this->order_model->where(array('mid'=>$mid,'tixian_status1'=>0))->select();
    		foreach($orderList as $key=>$value){
    			$data['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
    			$this->order_model->where("id=".$value['id'])->save($data);
    		}
    
    	}else{
    		$fansList = $this->member_model->where(array('share_id'=>$mid))->select();
    
    		$orderList = $this->order_model->where(array('mid'=>$mid,'tixian_status1'=>0))->select();
    		$countMoney = 0;
    		//有粉丝自己购物返现
    		foreach($orderList as $key=>$value){
    			$data2['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
    			$this->order_model->where("id=".$value['id'])->save($data2);
    		}
    
    		//有粉丝 粉丝给自己的返现
    		foreach ($fansList as $key=>$value){
    			$fansOrderList = $this->order_model->where(array('mid'=>$value['id'],'tixian_status2'=>0))->select();
    			foreach($fansOrderList as $k=>$v){
    				$data3['daili_return'] = round($v['commission']*($returnMoney['daili_return']/100),2);//代理返现
    				$this->order_model->where("id=".$v['id'])->save($data3);
    
    			}
    		}
    	}
    	return true;
    	
    	
    }
    function getTgw(){
    	/*
    	 * tab:1、网站 2、APP 3、导购 4、软件
    	* gcid:0、网站 7、APP 8、导购 7、软件
    	*/
    	$url = 'http://pub.alimama.com/common/adzone/adzoneManage.json?tab=1&toPage=1&perPageSize=40&gcid=0&t=1508486089958&pvid={pvid}&_tb_token_={token';
    	$rs=$this->execcurl('http://111.231.137.169:8886/api?Key=jiandan666&utf=1&getname=json&url='.urlencode($url).'&skey=jiandan666');
    	$res=$this->is_json($rs);
    	$data = array();
    	foreach($res['data']['pagelist'] as $key=>$value){
    		array_push($data,$value['siteid'].'_'.$value['adzoneid']);
    	}
    	return $data;
    }
    
    function qrcode(){
    	$this->display(":invite");
    }
    
    function getInvite(){
    	$num = rand(1000,9999);
    	$count = $this->member_model->where('invite_code='.$num)->count();
    	if($count>0){
    		$this->getInvite();
    		return $num;
    	}
    	return $num;
    }
    
    function close(){
    	echo "<script>window.open('','_self','');window.close();</script>";
    	exit();
    }
	
	function test(){
		$start_date = date("Y-m-d",strtotime("-60 day"));
		echo $start_date;die;
	}

} 


