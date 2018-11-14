<?php
namespace Common\Model;
use Common\Model\CommonModel;
use think\Db;
class CartModel extends CommonModel{
    //购物车列表
    static public function getCartList($uid){
        $goodsList= M("cart")->where(["user_id"=>$uid])->select();
        if(empty($goodsList)){
            $goodsList=[];
            $total_price="购物车无商品";
        }else{
            $total_price=0;
            foreach ($goodsList as $key=>$value){
                $total_price +=$value['price'] * $value['buy_num'];
            }
        }
        $goodsList['total_price']=$total_price;
        return $goodsList;
    }

    //商品详情
    static public function  getGoodsDetails($gid,$pid=0){

        $goods= M("goods")->where(["id"=>$gid])->find();

        if($pid>0){
            $goods['products']= M("products")->where(["id"=>$pid])->find();
        }else{
            $goods['products']=[];
        }

        return $goods;
    }

    //购物车加减
    static  public function getGoodsChangeNum($id,$changeNum){
        $cart = M("cart")->where(["id"=>$id])->find();
        //获取当前商品最新价格
        //$new_goods=Db::table("td_products")->where("id",$cart['products_id'])->find();
        if($changeNum <0){
            if($cart['buy_num'] < abs($changeNum)){
                return ['status' => 0, 'msg' => $cart['name'].'商品数量不能小于减少数','result' => []];
            }
            M("cart")->where(["id"=>$id])->setDec('buy_num',$changeNum);//setDec 减

        }else{
            M("cart")->where(["id"=>$id])->setInc('buy_num',$changeNum);//setInc 加
        }

        return true;
    }

    //购物车删除
    static  public  function deleteGoodsCart($id,$uid){
        if($id){
            //单个商品
            $cart = M("cart")->where(["id"=>$id])->delete();
            return true;
        }else{
            //清空购物车
            $cart_all = M("cart")->where(["user_id"=>$uid])->delete();
        }
        return true;
    }


}