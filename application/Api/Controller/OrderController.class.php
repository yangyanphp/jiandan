<?php
namespace Api\Controller;
use Common\Controller\HomebaseController;


use Api\Controller\GoodsController;
use think\cache;
class OrderController extends HomebaseController{


   protected $page_num = 10;  //分页每页数据
    protected $AppKey = 2018110501;
    protected $AppSecret = 'e73236ccce2088a39c20ee7a162d9eb3';
    protected $username = '15945370222';
    protected $password = 'guan1982';

//授权
    public function _initialize() {
        $oauth=S('get_oauth_data');
        if(!$oauth){
            $this->oauth=$this->oauthGoods();
            S('get_oauth_data',$this->oauth,180);
        }  else{

            return  $this->oauth=$oauth;
        }
    }
    function getMillisecond() {//获取毫秒级时间戳
        list($usec, $sec) = explode(" ", microtime());
        $usec = str_replace('.', '', $usec);
        $ctime=substr($sec.$usec,0,17);
        return $ctime;
    }


    public function oauthGoods(){
         $data=[
             'app_key'=>2018110501,
             'grant_type'=>"password",
             "username"=>15945370222,
             'password'=>strtoupper(md5('guan1982')),
             'loginway'=>1,
             'stamp'=> sprintf('%0.0f', ($this->getMillisecond() +3600 * 8 * 10000000)  + 621355968000000000),
         ];
         ksort($data);
         $str='';
         if($data){
             foreach ($data as $key=>$value){
                 $str .=$key.$value;
             }
         }

         $sign=strtoupper(md5($str.''.$this->AppSecret));
         $data['sign']=$sign;
         $url="http://open.hznzcn.com/token";

         $ret=$this->curl($url,$ispost=true,http_build_query($data),$in='utf8',$out='utf8',$cookie='');
        return  json_decode($ret);
    }

    function curl($url,$ispost=false,$data='',$in='utf8',$out='utf8',$cookie='')
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

   //购物车生成订单
    public function mk_order(){
        $uid = I('user_id');  //根据uid查看购物车
        $addr_id=I('addr_id');
        $uid=1116;
        $addr_id=1;
        if(empty($uid) || empty($addr_id)){
            return return_result(-1,200,'参数不全',array());
        }


        $result = $this->mk_order_mode($uid,$addr_id);
        if($result['status']==1){
            return return_result(1,200,'请求成功',$result['data']);
        }else{
            return return_result(-1,200,'请求失败',$result['data']);
        }
    }
    //订单列表
    public function orderList(){
        $uid = I('user_id');  //有id个人订单，没有全部
        if(empty($uid)){
            $where=[];
        }else{
            $where=['user_id'=>$uid];
        }
        $result = M('zy_order')->where($where)->select();
        if($result){
            return return_result(1,200,'请求成功',$result);
        }else{
            return return_result(-1,200,'请求失败',$result);
        }

    }
    //订单详情
    public function orderDetail(){
        $order_id = I('order_id');  //orderid
        $where=[
            'oid'=>$order_id
        ];
        $result = M('zy_order')->where($where)->select();
        if($result){
            return return_result(1,200,'请求成功',$result);
        }else{
            return return_result(-1,200,'请求失败',$result);
        }
    }



    //单件商品下单（立即购买下单）  //http://local.jiandan.com/Api/Order/one_mk_order
    public function one_mk_order(){

//        $uid = I('post.uid');    //用户id、
//        $products_id =  I('post.products_id');  //货品id(products表的id)
//        $goods_num = I('post.num');   //商品数量
//        $addr_id=I('post.addr_id');  //用户地址id
          $onsalespec=I('spec');//规格
        //测试
        $uid = 1;
        $products_id=474740;
        $goods_num=3;
        $addr_id = 1;
        //测试
        $data = array();
        if(empty($uid) || empty($products_id) || empty($goods_num) || empty($addr_id)){
            return return_result(-1,200,'参数不全',$data);
        }
        $hzproduct=new GoodsController();
        $products_info = $hzproduct->gethzproductlistreturn($products_id);
        $addr=M("user_address")->where(array('address_id'=>$addr_id))->find();  //该用户的收货地址信息
        if (empty($addr)) {
            return array('status' => 0, 'msg' => '收货地址不能为空');
        }

        $arr = array(
            'user_id'       =>$uid,              //用户id
            'order_sn'      =>'JD'.date('Ymd',time()).rand(10000,99999),   //订单号
            'create_time'   => time(),             //下单时间
            'total_moeny'   => ($products_info->Items['0']->TakePrice),  // 订单价格
            'total_num'     => $goods_num,                     //商品数量
            'order_status'  =>0,                                 //订单状态  待付款
            'pay_status'    =>0,                                   //支付状态
            'consignee'=>$addr['user_name'],                   //收货人
            'province' =>$addr['province'],     //省
            'city'     =>$addr['city'],         //市
            'district' =>$addr['district'],    //区县镇
            'address'  =>$addr['address'],     //详细地址
            'mobile'   =>$addr['mobile'],
            'goods_name'=>$products_info->Items['0']->ProductName, //默认取一个商品名称
            'goods_img'=>$products_info->product_pic['0']  //默认商品图
        );

       M()->startTrans();

        $order = M('zy_order')->add($arr);  //数据插入order表返回id
        if($order){
            $res = array(
                'oid' => $order,  //订单id
                'goods_id'=>0,      //商品id（goods表id）
                'product_id'=>$products_id,        //货品id（products表id）
                'product_num' => $goods_num,      //商品数量
                'product_price' => $products_info->Items['0']->TakePrice, //价格
                'product_name' => $products_info->Items['0']->ProductName, //商品名
                'guige'=>$onsalespec,                              //商品规格
                'pic'=>$products_info->product_pic['0'],
            );

            $result = M('zy_order_detail')->add($res); //插zy_order_detail表 返回插入第一条数据的id
            $jieguo=$this->chuan_order($res,$order);
            if(!$result){
               M()->rollback();
                return return_result(-1,200,'下单失败，请重试',$data);
            }else{
                M()->commit();
                if($jieguo){
                  return return_result(1,200,'下单成功，跳转支付',$arr);  //返回订单信息  
              }
            }

        }
    }

    /**
     *传给第三方的订单
     * yangyan
     **/

    public function chuan_order($res,$orderid){
        $res['guige']='瓷红,L';
        $j_data='[';
       if(count($res) == count($res,1)){
           $j_data .="{'proid':".$res['product_id'].",'prospecifications':'".$res['guige']."','pronum':".$res['product_num']."}";
       }else{

        foreach($res as $key=>$value){
            $j_data .="{
                'proid':".$res['product_id'].",
                'prospecifications':".$res['guige']?$res['guige']:"'瓷红,L',
                'pronum':".$res['product_num']."
               },";
        }

       }
        $j_data .=']';

       //获取订单收货地址
        $addrs=M("zy_order")->where(["order_id"=>$orderid])->field('consignee,province,city,district,address,zipcode,mobile')->find();
        //获取快递id
        $kuaidi_id=$this->getDistrictCode($addrs['province'],$addrs['city'],$addrs['district']);
        $ConsigneeAddress="{'receiver':'".$addrs['consignee']."','phone':".$addrs['mobile'].",'province':".$addrs['province'].",'city':".$addrs['city'].",'district':".$addrs['district'].",'addressdetail':'".$addrs['address']."'}";
            $data=[
                'app_key'=>2018110501,
                'orderfrom'=>99,
                'outerordercode'=>$orderid,
                'orderdetaillist'=>$j_data,
                'stamp'=> sprintf('%0.0f',($this->getMillisecond() +3600 * 8 * 10000000)  + 621355968000000000),
                'consigneeaddress'=>$ConsigneeAddress,
                'access_token' =>$this->oauth->access_token,
                'remark'=>'减单测试下单',
                'sendmode'=>0,
                'packagetype'=>1,
                'innerpackagetype'=>99,
                'packnum'=>1,
                'favourcommoncard '=>2,
                'deliveryareaid'=>$kuaidi_id,
                'sendmode'=>0,
                'needseniorqc'=>0,
                'needticket'=>0
            ];
            ksort($data);
            $str='';
            if($data){
                foreach ($data as $key=>$value){
                    $str .=$key.$value;
                }
            }
            $data['sign']=strtoupper(md5($str.''.$this->AppSecret));
            $url="http://open.hznzcn.com/order/place";
            $hz_order=$this->curl($url,$ispost=true,http_build_query($data),$in='utf8',$out='utf8',$cookie='');
            $xiadan=(json_decode($hz_order));
            if($xiadan->Data >0){
                return 'true';
            }else{
                 return return_result(-1,200,'下单失败，请重试',$hz_order);
            }
            //获取商品图片
    }

    //获取快递id
    public  function getDistrictCode($province,$city,$district){
        $cache_list=S('get_hz_districtode'.$province.$city.$district);

        if(!$cache_list) {
            $data = [
                'app_key' => 2018110501,
                'theshopid' => 1,
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'stamp' => sprintf('%0.0f', ($this->getMillisecond() + 3600 * 8 * 10000000) + 621355968000000000),
                'access_token' => $this->oauth->access_token,
            ];
            ksort($data);
            $str = '';
            if ($data) {
                foreach ($data as $key => $value) {
                    $str .= $key . $value;
                }
            }
            $data['sign'] = strtoupper(md5($str . '' . $this->AppSecret));
            $url = "http://open.hznzcn.com/base/deliver/list";
            $addr = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            $ret = json_decode($addr);
            //存缓存
            S('get_hz_districtode'.$province.$city.$district,36000);
            return $ret->Data[0]->DeliveryAreaID;
        }else{
            return $cache_list;
        }
    }

//支付宝支付
    public function order_pay()
    {

        $id = I('order_id')?I('order_id'):37;  //订单id
        $order_info = M('zy_order')->where(array('id'=>$id))->find();  //订单详情
        $order_num = $order_info['order_sn']; //订单号
        $money = $order_info['total_money'];  //订单金额

        $money = 0.01;

        Vendor('alipay.aop.AopClient');
        Vendor('alipay.aop.request.AlipayTradeAppPayRequest');
        $aop = new \AopClient;

        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = '2017100709182284';
        $aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
        $aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";

        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        $request->setNotifyUrl("http://jiandan2.tudingsoft.com/portal/Order/notify");      //设置异步回掉地址

        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"减单自营下单\","
            . "\"subject\": \"减单自营商品XXX\","
            . "\"out_trade_no\": \"$order_num\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$money\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute

        $response = $aop->sdkExecute($request);
        echo $response;
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        return $this->jsonSuccess($response);
//        return return_result(1,200,'下单成功，跳转支付',$arr);  //返回订单信息
    }



    //异步回调
    public function notify(){
        $postData = $_POST;
        $postData = 'asdasdasdasdas手动阀手动阀撒旦发顺丰地方asdfasdfsdf';
        file_put_contents("./alipay_log.txt", var_export($postData, true), FILE_APPEND);
//        if($postData['status'] == 0){
//            $i = $postData['order_sn'];
//            if(1){
//                return false;
//            }
//
//        }
        var_dump($_POST);die;
    }



    public function mk_order_mode($uid, $addr_id)
    {
        $modle = M('cart');
        $sql = "select * from jd_cart   where user_id = $uid";
        $p_data = $modle->query($sql);

        $data['total_moeny'] = 0; //订单总价格
        $data['total_num'] = 0;   //订单货品数量
        foreach ($p_data as $k => $v) {
            $data['goods_name'] = $v['name'];

            $data['total_moeny'] += ($v['price']) * $v['buy_num'];   //price

            $data['total_num'] += $v['buy_num'];
        }

        $data['user_id'] = $uid; //用户id
        $data['order_sn'] = 'JD' . date('Ymd', time()) . rand(10000, 99999);    //订单号
        $data['create_time'] = time();  //下单时间

        //查询收货地址
        $addr = M("user_address")->where(["address_id" => $addr_id])->find();

        if (empty($addr)) {
            return array('status' => 0, 'msg' => '收货地址不能为空');
        }
        //查询收货地址
        $data['consignee'] = $addr['user_name'];  //收货人
        $data['province'] = $addr['province'];  //省
        $data['city'] = $addr['city'];  //市
        $data['district'] = $addr['district'];  //区县镇
        $data['address'] = $addr['address'];  //详细地址
        $data['mobile'] = $addr['mobile'];

        M()->startTrans();
        $order = M('zy_order')->add($data); //数据插入order表返回id

        if ($order) {
            foreach ($p_data as $k => $val) { //每个商品 在order_detail表都有一行记录
                $res = array(
                    'oid' => $order,  //订单id
                    'product_id' => $val['products_id'],  //商品ID
                    'product_num' => $val['buy_num'],   //商品数量
                    'product_price' => $val['price'], //价格
                    'product_name' => $val['name'], //商品名
                    'goods_id' => $val['gid'],
                    'guige' => $val['spec'],
                    'pic'=>$val['pic'],
                );

                $result = M('zy_order_detail')->add($res); //插order_detail表 返回插入第一条数据的id

            }

            //支付成功后减少库存
//            if ($result) {
//                foreach ($p_data as $k => $val) { //循环每件商品，并减库存(商品规格表)
//                    $dec = Db::table('td_product')->where(['id'=>$val['products_id']])->setDec('p_stock', $val['buy_num']); //下单成功件库存
//                    if (!$dec) {
//                        Db::rollback();
//                        return array('status' => 0, 'msg' => '请求失败');
//
//                    }
//                }
//            }
            //整个购物车下单流程 完成需要delete掉该用户的购物车
            $del_car = M("cart")->where(['user_id' => $uid])->delete();
            if (!$del_car) {
                M()->rollback();
                return array('status' => 0, 'msg' => '请求失败');

            }
            M()->commit();
            $jieguo=$this->chuan_order($p_data,$order);
            return array('status' => 1, 'msg' => '请求成功', 'data' => $data);


        }
    }











}