<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class CartController extends HomebaseController
{
    /**
     *购物车列表
     */
    public function index(){

        $uid = I("user_id"); // 用户id
        $uid=1116;
        $cartList = CartModel::getCartList($uid);//用户购物车
        if(empty($cartList)){
            return return_result(-1,200,'购物车为空',array());
        }else{
            return return_result(1,200,'请求成功',$cartList);
        }

    }

    /**
     * ajax 将商品加入购物车
     */
    public function ajaxAddCart()
    {
        $uid = I("uid"); // 用户id
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $products_id = I("products_id"); // 货品id
        $uid =1116; // 用户id
        $goods_id = 46; // 商品id
        $goods_num = 1;// 商品数量
        $products_id = 16; // 货品id


        if(empty($goods_id)){
            return return_result(-1,200,'请选择要购买的商品',array());
        }
        if(empty($goods_num)){
            return return_result(-1,200,'购买商品数量不能为0',array());
        }
        $goods = CartModel::getGoodsDetails($goods_id,$products_id);
        //判断库存
        if($goods["products"]['p_stock'] <$goods_num){
            return return_result(-1,200,'库存不足',array());
        }

        //加入购物车
        $goods["products"]['name']=$goods['goods_name'];
        $goods["products"]['user_id']=$uid;
        $goods["products"]['buy_num']=$goods_num?$goods_num:1;
        $goods["products"]['goods_id']=$goods_id;

        $goods["products"]['market_price']=$goods['p_price']?$goods['p_price']:0;
        $goods["products"]['add_time']=time();
        $goods["products"]['products_id']=$goods["products"]['id']?$goods["products"]['id']:'0';

        $ret=$this->addGoodsToCart($goods["products"]);
        if($ret){
            return return_result(1,200,'加入购物车成功',array());
        }else{
            return return_result(-1,200,'加入购物车失败',array());
        }
    }


    public  function addGoodsToCart($data){

        if (empty($data)) {
            return return_result(-1,200,'购买商品不存在',array());
        }
        if ($data['buy_num'] <= 0) {
            return return_result(-1,200,'购买数量不能小于1',array());
        }
        $userCartCount =M('cart')->where(['user_id' => $data['uid']])->count();//获取用户购物车的商品有多少种
        if ($userCartCount >= 10) {
            return return_result(-1,200,'购物车最多只能放10种商品',array());
        }
        // 查询购物车是否已经存在这商品

        $cart_where = ['user_id' => $data['user_id'], 'goods_id' => $data['goods_id'],'products_id'=>$data['products_id']];
        $goods_inCart= M("cart")->where($cart_where)->find();

        if($goods_inCart){ //存在购物车中

            $userCartGoods['buy_num'] = $goods_inCart['buy_num']? $goods_inCart['buy_num']:0;
            $userWantGoodsNum = $data['buy_num'] + $userCartGoods['buy_num'];//本次要购买的数量加上购物车的本身存在的数量
            //如果价格有变,以最新为准
            $userBuyPrice= $data['p_price'];

            //更新数据库
            $cartResult = M('cart')->where($cart_where)->save(['buy_num' =>$userWantGoodsNum,'price'=>$userBuyPrice]);
        }else{ //不在购物车中
            $cartAddData = array(
                'user_id' => $data['user_id'],   // 用户id
                'goods_id' => $data['goods_id'],   // 商品id
                'name' => $data['name'],   // 商品名称
                'market_price' => $data['market_price'],   // 市场价
                'price' => $data['p_price']?$data['p_price']:$data['price'],  // 存在货品货品价格,不存在商品价格
                'buy_num' => $data['buy_num'], // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 0,   // 0 普通订单,
                'products_id'=>$data['products_id']
            );


            $cartResult = M('cart')->add($cartAddData);
        }
        if ($cartResult === false) {
            return return_result(-1,200,'加入购物车失败',array());
        }
        return  true;

    }
    /**
     *  购物车加减
     */
    public function changeNum(){
        $changeNum = I("changeNum");// 商品数量
        $id = I("id"); // 购物车ID
        $id=5;
        $changeNum=1;
        if (empty($id)) {
            return return_result(-1,200,'请选择要更改的商品',array());
        }
        $cartChange =CartModel::getGoodsChangeNum($id,$changeNum);
        if($cartChange){
            return return_result(1,200,'修改成功',array());
        }
    }

    /**
     * 删除购物车商品
     */
    public function delete(){
        $id = I("id"); // 购物车ID
        $uid = I("user_id"); // 用户id
        //存在id 删除单条商品  不存在清空购物车
        $result =CartModel::deleteGoodsCart($id,$uid);

        if($result !== false){
            return return_result(-1,200,'请选择要更改的商品',array());
        }else{
            return return_result(1,200,'删除成功',array());
        }
    }

}