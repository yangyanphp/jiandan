<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class GoodsCateController extends HomebaseController{



//自营商品分类首页
    public function index(){

        $data = array();

       $brand = M('brand')->where(array('p_id'=>0))->field('id,name,brand_img brand_cate_img')->select();  //查询所有品牌的分类

       foreach($brand as $k=>$v){
          $brand[$k]['brands'] = M('brand')->where(array('p_id'=>$v['id']))->limit('5')->field('id brand_id,brand_img')->select();   //每个品牌分类下的品牌
       }
//       var_dump($brand);die;
        $data['00'] = $brand;                                                             //品牌推荐
        $data['11'] = M('categories')->field('id cate_id,name,image')->select();   //分类检索

       return return_result(1,1000,'自营首页',$data);

    }






}