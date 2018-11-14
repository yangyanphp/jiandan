<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class GoodsController extends HomebaseController{

    protected $page_num = 10;  //分页每页数据

//	http://jiandan2.tudingsoft.com/portal/Goods/index
//自营商品首页
public function index(){
    $page = I('page'); //页码
    $where = array();

    if(I('brand_id')){ //品牌检索条件
       $where['g.brand_id'] = I('brand_id');
    }
    if(I('cate_id')){     //商品分类检索条件
        $where['g.cate_id'] = I('cate_id');
    }
    if($page==''){
        $limit = "0,$this->page_num";
    }else{
        $p = ($page-1)*$this->page_num;
        $limit = "$p,$this->page_num";
    }
    $field = 'g.id,g.goods_name,g.price,g.cate_id,g.pk';
    $goods_list = M('goods')->alias('g')->where($where)->field($field)->limit($limit)->select();

    foreach($goods_list as $k=>$v){  //追加商品图片
       $goods_list[$k]['path'] = M('file')->where(array('pid'=>$v['id']))->getField('path');
    }

    return_result(1,1000,'自营商品首页',$goods_list);
}


//	http://jiandan2.tudingsoft.com/portal/Goods/a_goods
//点击一件商品查看详情
public function a_goods(){
    $id = I('goods_id'); //商品id
    $data = array();
    $id = 48;
    $goods_info = M('goods')->alias('g')->where(array('id'=>$id))->field('goods_name,brand_id,pk,cate_id')->find();
    $data['brand'] = M('brand')->where(array('id'=>$goods_info['brand_id']))->getField('name');  //品牌名称
    $data['goods_name'] = $goods_info['goods_name']; //商品名
    $data['pk'] = $goods_info['pk'];             //pk券

    $data['products'] =  M('products')->where(array('g_id'=>$id))->field('id g_id,guige,p_price,img')->select();  //商品规格

    $others = M('goods')->where(array('cate_id'=>$goods_info['cate_id']))->field('id,goods_name,price,pk')->select();     //立即购买下面的商品
//var_dump($others);die;
    foreach($others as $k=>$v){
        $others[$k]['path'] = M('file')->where(array('pid'=>$v['id']))->getField('path'); //追加商品图片
    }
    $data['others'] = $others;
    return_result(1,1000,'商品详情',$data);

}




}