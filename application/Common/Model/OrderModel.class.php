<?php
namespace Common\Model;
use Common\Model\CommonModel;
use think\Db;
class OrderModel extends CommonModel
{


    static public function mk_order_mode($uid, $addr_id)
    {


        $modle = M('cart');
        $sql = "select * from jd_cart  as c left join jd_products as p ON c.products_id =p.id left join jd_goods as g ON c.goods_id=g.id where c.user_id = $uid";
        $p_data = $modle->query($sql);

        $data['total_moeny'] = 0; //订单总价格
        $data['total_num'] = 0;   //订单货品数量
        foreach ($p_data as $k => $v) {
            $data['goods_name'] = $v['goods_name'];

            $data['total_moeny'] += ($v['p_price'] ? $v['p_price'] : $v['price']) * $v['buy_num'];   //price 为td_products表的价格
            if (($v['p_stock'] ? $v['p_stock'] : $v['stock']) < $v['buy_num']) {     //库存为td_products表数据
                return array('status' => 0, 'msg' => '该商品已经库存不足');
            }

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
                $guige = M("products")->field('guige')->where(["id" => $val['products_id']])->find();
                $res = array(
                    'oid' => $order,  //订单id
                    'product_id' => $val['products_id'],  //商品ID
                    'product_num' => $val['buy_num'],   //商品数量
                    'product_price' => $val['p_price'], //价格
                    'product_name' => $val['name'], //商品名
                    'goods_id' => $val['gid'],
                    'guige' => $guige['guige'],
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
            return array('status' => 1, 'msg' => '请求成功', 'data' => $data);


        }
    }
}


