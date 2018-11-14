<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;
use Common\Model\OrderModel;
use extend\alipay\aop\request\AlipayTradeAppPayRequest;
use Think\Log;
class OrderController extends HomebaseController{
   //购物车生成订单
    public function mk_order(){
        $uid = I('user_id');  //根据uid查看购物车
        $addr_id=I('addr_id');
        $uid=1116;
        $addr_id=1;
        if(empty($uid) || empty($addr_id)){
            return return_result(-1,200,'参数不全',array());
        }

        $result = OrderModel::mk_order_mode($uid=1116,$addr_id);
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



    //单件商品下单（立即购买下单）
    public function one_mk_order(){
        $uid = I('post.uid');    //用户id、
        $products_id =  I('post.products_id');  //货品id(products表的id)
        $goods_num = I('post.num');   //商品数量
        $addr_id=I('post.addr_id');  //用户地址id

        $uid = 1;
        $products_id=21;
        $goods_num=3;
        $addr_id = 1;

        $data = array();
        if(empty($uid) || empty($products_id) || empty($goods_num) || empty($addr_id)){
            return return_result(-1,200,'参数不全',$data);
        }

        $products_info = M('products')->where(array('id'=>$products_id))->find();  //商品规格详情

        $goods_info = M('goods')->where(array('id'=>$products_info['g_id']))->find(); //商品信息

        if($products_info['p_stock']<$goods_num){
            return return_result(-1,200,'该商品库存不足',$data);
        }
        $addr=M("user_address")->where(array('address_id'=>$addr_id))->find();  //该用户的收货地址信息

        $arr = array(
            'user_id'       =>$uid,              //用户id
            'order_sn'      =>'JD'.date('Ymd',time()).rand(10000,99999),   //订单号
            'create_time'   => time(),             //下单时间
            'total_moeny'   => ($products_info['p_price']?$products_info['p_price']:$goods_info['price'])*$goods_num,  // 订单价格
            'total_num'     => $goods_num,                     //商品数量
            'order_status'  =>0,                                 //订单状态  待付款
            'pay_status'    =>0,                                   //支付状态
            'consignee'=>$addr['user_name'],                   //收货人
            'province' =>$addr['province'],     //省
            'city'     =>$addr['city'],         //市
            'district' =>$addr['district'],    //区县镇
            'address'  =>$addr['address'],     //详细地址
            'mobile'   =>$addr['mobile'],
            'goods_name'=>$goods_info['goods_name'], //默认取一个商品名称
            'goods_img'=>$products_info['img']  //默认商品图
        );

        M()->startTrans();

        $order = M('zy_order')->add($arr);  //数据插入order表返回id


        if($order){

            $guige=M("products")->field('guige')->where(array('id'=>$products_id))->getField('guige');
            $res = array(
                'oid' => $order,  //订单id
                'goods_id'=>$goods_info['id'],      //商品id（goods表id）
                'product_id'=>$products_id,        //货品id（products表id）
                'product_num' => $goods_num,      //商品数量
                'product_price' => $products_info['p_price']?$products_info['p_price']:$goods_info['price'], //价格
                'product_name' => $goods_info['goods_name'], //商品名
                'guige'=>$guige                              //商品规格
            );
            $result = M('zy_order_detail')->add($res); //插zy_order_detail表 返回插入第一条数据的id

            if(!$result){
               M()->rollback();

                return return_result(-1,200,'下单失败，请重试',$data);
            }else{
                M()->commit();
                return return_result(1,200,'下单成功，跳转支付',$arr);  //返回订单信息
            }

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













}