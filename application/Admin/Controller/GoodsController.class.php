<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;
use think\db;
use helper\Helper;
class GoodsController extends AdminbaseController
{
    //商品首页
    public function index(){


        $join = array('left join jd_brand brand on brand.id=g.brand_id','left join jd_categories cate on cate.id=g.cate_id','left join jd_file f on f.pid=g.id ');
        $good_list = M('goods')->alias('g')->join($join)->field('g.*,brand.name brand_name,cate.name cate_name,f.path')->select();

        foreach($good_list as $k=>$v){
            if(!isset($goods[$v['id']])){
                $v['img_path'][] = $v['path'];
                $goods[$v['id']] = $v;
            }else{
               $goods[$v['id']]['img_path'][] = $v['path'];
            }
        }

        $this->assign('goods',$goods);
        $this->display();
    }

    //商品分类
    public function goodscat()
    {
        $list = get_list_to_tree();
//var_dump($list);die;
        $this->assign('list', $list);
        $this->display();

    }




    //商品分类添加修改
    public function addGoodsCat(){
//        var_dump($_SERVER);die;


        $data=get_list_to_tree();
        $id = $_GET['id'];
        $list=[];

//var_dump(I());var_dump($_FILES);die;
        if($id){  //编辑

            $list=M("categories")->where(['id'=>$id])->find();

            $this->assign('list',$list); //该个分类信息
            $this->assign('p_id',$list['p_id']);
            $this->assign('edit_id',$id);
        }else{
            if(IS_POST){
                $arr=[
                    'name'=>I('name'),     //分类名
                    'p_id'=>I('p_id'),     //上级分类id
                    'sort_order'=>I('sort_order'),   //排序
                    'description'=>I('description'),  //商品分类描述
                    'is_show'=>I('is_show')           //是否显示
                ];
                $edit_id=I('edit_id');

                if($edit_id>0){
                    //编辑
                    if(!empty($_FILES['img']['name'])){  //有更新图片
                        $img = deal_more_img($_FILES['img']);
                        $arr['image'] = img_upload_all($img)[0];

                    }

                    $add=M("categories")->where(array('id'=>$edit_id))->save($arr);


                }else{
                    if(empty($_FILES['img'])){ $this->ajaxReturn(array('status'=>0,'msg'=>'请上传分类图片'));}
                    $img = deal_more_img($_FILES['img']);
                    $arr['image'] = img_upload_all($img)[0];

                    //添加
                    $add=M("categories")->add($arr);
                }
                if($add){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'保存成功','url'=>U('Goods/goodscat')));
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'保存失败,请重试'));
                }
            }
        }

        $this->assign('data',$data);
        $this->display();
    }







    //编辑商品
    public function goods_save(){

        $brand_tree = get_list_to_tree(0,$res=[],0,'brand');  //商品品牌
        $cate_tree = get_list_to_tree(0,$ews=[],0,'categories'); //商品分类

        $id = I('get.id')?I('get.id'):null;  //编辑时 接受 商品id

        if($id){  //编辑

           $one_good =  M('goods')->where(array('id'=>$id))->find();
           $goods_img = M('file')->where(array('pid'=>$id))->field('id,path')->select();
            $this->assign('goods',$one_good);
            $this->assign('goods_img',$goods_img);
//           var_dump($one_good,$goods_img);die;

        }else{  //添加


            if(IS_POST){

                $arr = array(
                    'id'         => I('edit_id')?I('edit_id'):'',
                    'goods_name' => I('goods_name'),  //商品名
                    'brand_id'   => I('brand_id'),    //品牌id
                    'cate_id'    => I('cate_id'),     //所属分类id
                    'stock'      => I('stock'),       //库存
                    'price'      => I('price'),       //价格
                    'pk'         => I('pk')           //pk券
                );

                M()->startTrans();

                if(!empty($arr['id'])){ //是跟新提交数据

                    $res = M('goods')->save($arr);  //更新

                    renew_stock($arr['id']);  //更新库存


                }else{  //新增提交数据

                    $res = M('goods')->add($arr); //返回商品id
                    renew_stock($res);    ////更新库存

                }
                if($res===false){
                    M()->rollback();
                    $this->ajaxReturn(array('status'=>0,'msg'=>'商品设置失败，请重试'));
                }

                $img = $_FILES['img'];

                if($img['name'][0]==''&&$arr['id']==null){  //新增，且没有上传图片

                    $this->ajaxReturn(array('status'=>0,'msg'=>'请上传图片'));

                }elseif($img['name'][0]==''&&$arr['id']!=null) { //编辑时没有提交图片

                    M()->commit();

                    $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','url'=>U('Goods/index')));

                }else{ //有提交图片（包括新增和编辑情况）

                    $img = deal_more_img($img);
                    $img_url = img_upload_all($img);

                    foreach($img_url as $k=>$v){
                        $data_img[$k]['pid'] = $res?$res:$arr['id'];   //新增是$res 编辑是$arr['id']  (商品id)
                        $data_img[$k]['path'] = $v;
                    }

                    $result = M('file')->addAll($data_img);   //图片入库

                    if(!$result){
                        M()->rollback();
                        $this->ajaxReturn(array('status'=>0,'msg'=>'图片添加失败，请重试'));
                    }else{
                        M()->commit();
                        $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','url'=>U('Goods/index')));
                    }
                }
                M()->commit();
                $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','url'=>U('Goods/index')));
            }
        }




        $this->assign('cate_tree',$cate_tree);     //分类
        $this->assign('brand_tree',$brand_tree);   //品牌
        $this->display();

    }

    //编辑时删除商品图片
    public function del_goods_img(){
        $id = I('id');

        $res = M('file')->where(array('id'=>$id))->delete();
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败，请重试'));
        }
    }


    //删除商品
    public function delete_goods(){
        $id= I('id');
        $res =  M('goods')->where(array('id'=>$id))->delete();  //删除goods表的商品
        if(!$res){ $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败请重试'));}
        M('products')->where(array('g_id'=>$id))->delete();  //删除 products表的对应商品规格
        M('file')->where(array('pid'=>$id))->delete();   //删除商品图片
        $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
    }










    //设置商品颜色
    public function set_color(){

        $data_color = M('color')->where(array('id'=>1))->getField('color');
        if(IS_POST){
            $color = trim(I('color'));
            if($color==''){ $this->ajaxReturn(array('status'=>0,'msg'=>'请设置颜色'));}
            $color  = str_replace('，',',',$color);

            $res =  M('color')->where(array('id'=>1))->setField('color',$color);

            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'设置成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'设置出错，请重试'));
            }
        }
        $this->assign('color',$data_color);
        $this->display();
    }


    //设置商品规格页面
    public function guige(){
        $id = I('get.id');  //商品id

        $one_goods = M('goods')->where(array('id'=>$id))->find(); //商品
        $cate = M('categories')->join('left join jd_attribute attr on attr.cate_id=jd_categories.id')->where(array('jd_categories.id'=>$one_goods['cate_id']))->field('name,val')->find(); //所属分类
        $cate['guige_val'] = explode(',',$cate['val']);
        $color = explode(',',M('color')->where(array('id'=>1))->getField('color'));  //颜色

        $products_info = M('products')->where(array('g_id'=>$id))->select();

        if (!empty($products_info)) {
            foreach ($products_info as $key => $val) {

                $arr_1[] = (explode(",", $val['guige'])[0]);
                $arr_2[] = (explode(",", $val['guige'])[1]);
            }

            $color = explode(',',M('color')->where(array('id'=>1))->getField('color'));

            if(!empty(array_intersect($arr_1,$color))){  //匹配两个数组是否有交集
                $data_r['color'] = array_unique($arr_1);
                $data_r['guige'] = array_unique($arr_2);
            }else{
                $data_r['guige'] = array_unique($arr_1);
            }

        }

        if(IS_POST){

            $goods_id = I('goods_id');  //商品id

            $img = $_FILES['image'];

            $img = deal_more_img($img);

            $data = array(
                'guige_id'   => I('guige_id')?I('guige_id'):null,  //products表的id
                'guige'=>I('guige'),    //规格
                'p_price'=>I('price'),  //价格
                'p_stock'=>I('stock')   //库存
            );

            $data = deal_more_img($data);

            $img_url = img_upload_all($img);
//var_dump($data,$img_url);die;
            foreach($data as $k=>$v){         //删除掉有guige_id的
                if(isset($v['guige_id'])){
                    unset($data[$k]);
                }
            }
            $data = array_merge($data);  //数组键名重零开始
            foreach($data as $k=>$v){
                $data[$k]['img'] = $img_url[$k];
                $data[$k]['g_id'] = $goods_id;
            }

            $res = M('products')->addAll($data);
            if($res){
                renew_stock($goods_id); //更新库存
                $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','url'=>U('Goods/index')));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'添加失败，请重试'));
            }

        }





//        var_dump($data_r);die;
        $this->assign('data_r',$data_r);         // 编辑时显示商品颜色或者规格
        $this->assign('id',$id);  //商品id
        $this->assign('product',$products_info); // 商品规格

        $this->assign('goods',$one_goods);
        $this->assign('color',$color);
        $this->assign('cate',$cate);             //商品 分类名 和 规格参数

        $this->display();
    }


    //修改商品规格（ajax提交）
    public function save_guige(){
        if(IS_POST){
            $goods_id = I('goods_id');

            $arr = array(
                'id' => I('guige_id'),  //products表id
                'guige'=>I('guige'),
                'p_price'=>I('p_price'),
                'p_stock'=>I('p_stock'),
                'img' =>  substr(I('img'),strpos(I('img'),'/Uploads'))
            );

            if(empty($arr['id'])){$this->ajaxReturn(array('status'=>0,'msg'=>'更新失败，请重试'));}
            if($arr['img']==''){  //更新 有重新上传图片
                $img = deal_more_img($_FILES['img']);
                $arr['img'] = img_upload_all($img)[0];
            }
//            var_dump($goods_id,$arr);die;
            $res = M('products')->save($arr);
//            var_dump($res);die;
            if($res){

                $res = renew_stock($goods_id);  //跟新库存

                $this->ajaxReturn(array('status'=>1,'msg'=>'跟新成功',));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'跟新失败'));
            }
        }
    }

    //编辑商品规格时删除
    public function delete_guige(){

        $goods_id = I('goods_id');  //商品di
        $guige_id = I('guige_id');  //products表id

        $res = M('products')->where(array('id'=>$guige_id))->delete();
        if($res){
            renew_stock($goods_id);  //更新库存
            $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败，请重试'));
        }
    }



    //商品分类添加修改
//    public function addGoodsCat(){
//        $data=get_list_to_tree();
//        $id = $_GET['id'];
//        $list=[];
//        if($id){
//            $list=M("categories")->where(['id'=>$id])->find();
//            $this->assign('list',$list);
//            $this->assign('p_id',$list['p_id']);
//            $this->assign('edit_id',$id);
//        }else{
//            if(IS_POST){
//                $arr=[
//                    'name'=>I('name'),
//                    'p_id'=>I('p_id'),
//                    'sort_order'=>I('sort_order'),
//                    'description'=>I('description'),
//                    'is_show'=>I('is_show')
//                ];
//                $edit_id=I('edit_id');
//                if($edit_id>0){
//                    //编辑
//                    $add=M("categories")->where(["id"=>$edit_id])->save($arr);
//                }else{
//                    //添加
//                    $add=M("categories")->add($arr);
//                }
//                if($add){
//                    $this->redirect(U("goods/goodscat"));
//                }
//            }
//        }
//        $this->assign('data',$data);
//        $this->display();
//    }


    //商品 参数ID 和模型
    public function deletecat(){

            $id = I('id');
            if($id){
                $son=M("categories")->where(['p_id'=>$id])->find();
                if($son){
                    echo 0;die;
                }
            }
            $del=M("categories")->delete($id);
            if($del){
                echo 1;die;
            }

    }

    //商品品牌列表
    public function brand_list(){

        $brand = M('brand')->order('p_id')->select();
//        var_dump($brand);die;
        foreach($brand as $k=>$v){
            if($v['p_id']==0){

                $v['p_name'] = '品牌分类名';
                $v['img'] = $v['brand_img'];   //图片格式转化
                $tmp[$k] = $v;
            }else{
                $v['img'] = $v['brand_img'];
                $v['p_name'] = M('brand')->where(array('id'=>$v['p_id']))->getField('name');
                $tmp[$k] = $v;
            }
        }

        $this->assign('brand',$tmp);
        $this->display();
    }




    //编辑商品品牌
    public function brand_save(){

        $id = I('id')?I('id'):null;
        $brand_list = get_list_to_tree('brand');  //品牌 树结构

        if($id){ //编辑

            $brand_one = M('brand')->where(array('id'=>$id))->find();

            if(IS_POST){
                $brand_name = I('brand_name');  //品牌分类名称
                $pid = I('pid');                //父级id;
                $arr = array(
                    'id'         =>$id,
                    'pid'        => $pid,   //父级id
                    'name' => $brand_name,    //品牌名称
                );

                $img = $_FILES['img']; //分类图片

                if($img['name']){ //有更换图片
                    $img = deal_more_img($img);
                    $img_url = img_upload_all($img);
                    $arr['brand_img'] = $img_url[0];
                }

                $res = M('brand')->save($arr);

                if(!$res){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'修改失败，请重试'));
                }else{
                    $this->ajaxReturn(array('status'=>1,'msg'=>'修改成功','url'=>U('Goods/brand_list')));
                }
            }
//var_dump($brand_one);die;
            $this->assign('data',$brand_one);
        } else { //新增


            if(IS_POST){
                $brand_name = I('brand_name');  //品牌分类名称
                $pid = I('pid');                //父级id
                $img = $_FILES['img']; //分类图片

                if(empty($img['name'])){$this->ajaxReturn(array('status'=>0,'msg'=>'请上传图片'));}
                $img = deal_more_img($img);
                $img_url = img_upload_all($img);
                $arr = array(
                    'p_id'        => $pid,   //父级id
                    'name' => $brand_name,    //品牌名称
                    'brand_img'  => $img_url[0]   //图片
                );

                $res = M('brand')->add($arr);
                if(!$res){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'添加失败，请重试'));
                }else{
                    $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','url'=>U('Goods/brand_list')));
                }
            }
        }

        $this->assign('tree',$brand_list);

        $this->display();
    }

    //删除品牌
    public function delete_brand(){
         
        $id = I('id');
        $res = M('brand')->where(array('id'=>$id))->delete();
        if($res){
            $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
        }
    }

    //删除品牌分类，先检查分类下是否有别的品牌
    public function delete_brand_cate(){
        $id = I('id');
        $res = M('brand')->where(array('p_id'=>$id))->find();
        if($res){
            $this->ajaxReturn(array('status'=>0,'msg'=>'不可删除，该分类下还有别的品牌'));
        }else{
            $this->ajaxReturn(array('status'=>1,'msg'=>'ok'));
        }
    }


    public function attribute_list(){
        //$list = get_list_to_tree();
        $list=M('attribute')
            ->field('jd_attribute.*,jd_categories.name')
            ->join('jd_categories on jd_categories.id = jd_attribute.cate_id')
            ->select();
        $this->assign('list', $list);
        $this->display();
    }


    public function attribute_save(){
        $data=get_list_to_tree();
        $id = $_GET['id'];
        $list=[];
        if($id){
            $list=M("attribute")->field('jd_attribute.*,jd_categories.name')->join('jd_categories on jd_categories.id = jd_attribute.cate_id')->where(['jd_attribute.id'=>$id])->find();
            $this->assign('list',$list);
            $this->assign('p_id',$list['p_id']);
            $this->assign('edit_id',$id);
        }else{
            if(IS_POST){
                $arr=[
                    'val'=>I('val'),
                    'cate_id'=>I('cate_id'),
                ];
                $edit_id=I('edit_id');
                if($edit_id>0){
                    //编辑
                    $add=M("attribute")->where(["id"=>$edit_id])->save($arr);
                }else{
                    $is_has=M("attribute")->where(['cate_id'=>I('cate_id')])->find();
                     if($is_has){
                         redirect(U("goods/attribute_save"),1,'该分类属性值已存在');
                     }
                    //添加
                    $add=M("attribute")->add($arr);
                }
                if($add){
                    $this->redirect(U("goods/attribute_list"));
                }
            }
        }
        $this->assign('data',$data);
        $this->display();
    }
    public function deleteattribute(){
        $id = I('id');
        $del=M("attribute")->delete($id);
        if($del){
            echo 1;die;
        }
    }
    //订单管理
    public  function zy_order_list(){
        $where=[

        ];
        $search=I('search');
        $data = M('zy_order')->where($where)->order(array("create_time"=>"Desc"))->page($_GET['p'].',10')->select();
        if($data){
            foreach($data as $key=>$value){
                  $data[$key]['user_addr']=$this->getaddrbyid($value['province']).$this->getaddrbyid($value['city']).$this->getaddrbyid($value['district']).$value['address'];
            }
        }
        $count  = M('zy_order')->where($where)->order(array("create_time"=>"Desc"))->page($_GET['p'].',10')->count();
        $Page       = new \Think\Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $show       = $Page->show();// 分页显示输出
        $this->assign("data",$data);
        $this->assign('page',$show);// 赋值分页输出
        //var_dump($data);exit;
        $this->display();

    }

    //订单详情
    public function order_details(){
        $order_id = I('order_id');
        $data = M('zy_order_detail')->where(['oid'=>$order_id])->select();
        $this->assign('data',$data);
        $this->display();
    }


    public function getaddrbyid($addr_id){
        if(!$addr_id){
            return '';exit;
        }
        $addr=M('addr')->field('name')->where(['id'=>$addr_id])->find();
       return $addr['name'];
    }

    //价格比例
    public function price_scale(){
           $data = M('price_scale')->where(array('id'=>1))->getField('price_scale');
        if(IS_POST){

            $res = M('price_scale')->where(array('id'=>1))->setField('price_scale',I('price'));

            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'设置成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'设置失败，请重试'));
            }
        }


        $this->assign('phone',$data);

        $this->display();
    }

    //商品种类删选列表
    public function product_screen(){
        $res=M('product_screen')->find();
        $this->assign('id',$res['id']);
        $this->assign('list',$res);
        $this->display();
    }

     //商品种类删选添加
    public function productscreen_add(){
        if($_POST){
           $data = array(
            'theshopid' => isset($_POST['theshopid'])?intval($_POST['theshopid']) : '',
            'orderfiled' => isset($_POST['orderfiled'])?intval($_POST['orderfiled']) : '' ,
            'ordertype' => isset($_POST['ordertype'])?intval($_POST['ordertype']) : '' ,
            'minprice' => isset($_POST['minprice'])?intval($_POST['minprice']) : '' ,
            'maxprice' => isset($_POST['maxprice'])?intval($_POST['maxprice']) : '' ,
            'seasontype' => isset($_POST['seasontype'])?intval($_POST['seasontype']) : '' ,
            'agegrouptype' => isset($_POST['agegrouptype'])?intval($_POST['agegrouptype']) : '' ,
            'isoriginalimg' => isset($_POST['isoriginalimg'])?intval($_POST['isoriginalimg']) : '' ,
            'ispowerbrand' => isset($_POST['ispowerbrand'])?intval($_POST['ispowerbrand']) : '' ,
            'isfactory' => isset($_POST['isfactory'])?intval($_POST['isfactory']) : '' ,
            'isfactory' => isset($_POST['isfactory'])?intval($_POST['isfactory']) : '' ,
            'isrefund' => isset($_POST['isrefund'])?intval($_POST['isrefund']) : '' ,
            );
           $result=M(product_screen)->add($data);
           if($result){
                  return $this->success("添加成功");
           }else{
                  return $this->fail('添加失败，请重试');  
           }
        }
        $this->display();
    }

     //商品种类删选修改
    public function productscreen_save(){
        if($_POST){
           $data = array(
            'theshopid' => isset($_POST['theshopid'])?intval($_POST['theshopid']) : '',
            'orderfiled' => isset($_POST['orderfiled'])?intval($_POST['orderfiled']) : '' ,
            'ordertype' => isset($_POST['ordertype'])?intval($_POST['ordertype']) : '' ,
            'minprice' => isset($_POST['minprice'])?intval($_POST['minprice']) : '' ,
            'maxprice' => isset($_POST['maxprice'])?intval($_POST['maxprice']) : '' ,
            'seasontype' => isset($_POST['seasontype'])?intval($_POST['seasontype']) : '' ,
            'agegrouptype' => isset($_POST['agegrouptype'])?intval($_POST['agegrouptype']) : '' ,
            'isoriginalimg' => isset($_POST['isoriginalimg'])?intval($_POST['isoriginalimg']) : '' ,
            'ispowerbrand' => isset($_POST['ispowerbrand'])?intval($_POST['ispowerbrand']) : '' ,
            'isfactory' => isset($_POST['isfactory'])?intval($_POST['isfactory']) : '' ,
            'isfactory' => isset($_POST['isfactory'])?intval($_POST['isfactory']) : '' ,
            'isrefund' => isset($_POST['isrefund'])?intval($_POST['isrefund']) : '' ,
            );
           $result=M(product_screen)->where(array('id'=>1))->save($data);
           if($result){
                  return $this->success("修改成功");
           }else{
                  return $this->fail('修改失败，请重试');  
           }
        }else{
            //$id=I('id')；
            $res=M(product_screen)->where(array('id'=>1))->find();
            $this->assign('list',$res);
            $this->display();
        }
        
    }


}
