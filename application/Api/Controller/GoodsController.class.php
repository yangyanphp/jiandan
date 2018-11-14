<?php
namespace Api\Controller;
use Common\Controller\HomebaseController;
use think\cache;

class GoodsController extends HomebaseController{

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
    //获取杭州女装商品
    public function gethzproduct(){
        $pageindex=I('pageindex',0);  //页数
        $classid=I('classid',0);  //页数
        $brandid=I('brandid',0);  //页数
        //获取后台配置
        $seach=M('product_screen')->find();
        //获取价格比例jd_price_scale
        $price_scale=M('price_scale')->find();
        //缓存
        $get_list=S('get_hz_products'.$pageindex.$seach['theshopid'].$seach['orderfiled'].$seach['ordertype'].$seach['seasontype'].$seach['agegrouptype']);
        $ret_data=[];
        if(!$get_list) {
            $data = [
                'app_key' => 2018110501,
                'theshopid' => intval($seach['theshopid']),  // (0：全部 1：杭州 2：濮院 5：广州 6：织里) ,
                'orderfiled '=>intval($seach['orderfiled']),//1:代表 Rating(人气)(默认排序) ，2:代表 Sale7Day(7天销量) ，3:代表 Price(价格) ，4:代表 AutoUp(上新时间) ,
                'ordertype'=>intval($seach['ordertype']),                      //排序方式 （0降序 或1升序）
                'seasontype'=>intval($seach['seasontype']), // 季节类型 0=全部 1=春秋季 , 2=夏装 , 3=冬装 ,
                'agegrouptype'=> intval($seach['agegrouptype']),
                'classid'=> $classid,
                'brandid'=> $brandid,
                'pageindex' => $pageindex,
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
            $url = "http://open.hznzcn.com/product/list";
            $product = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            $product_arr= json_decode($product);
            foreach ($product_arr->Items as $key=>$value){
                $ret_data[$key]=[
                    'Pro_ID'=>$value->Pro_ID,
                    'ProductName'=>$value->ProductName,
                    'SalePrice'=>$value->SalePrice,
                    'TakePrice'=>$value->TakePrice,
                    'ClassName'=>$value->ClassName,
                    'Thumbnail'=>$value->Thumbnail,
                    'JianDanPrice'=>(intval($price_scale['price_scale']  +100) * $value->TakePrice)/100
                ];
            }
            //存缓存
            S('get_hz_products'.$pageindex.$seach['theshopid'].$seach['orderfiled'].$seach['ordertype'].$seach['seasontype'].$seach['agegrouptype'],json_encode($ret_data),3600);

        }else{
            echo  $get_list;exit;
        }

    }

    //获取货品详情
    public function gethzproductlist()
    {
        $productid = I('product_id', 474740);
        //缓存
        //获取价格比例jd_price_scale
        $price_scale=M('price_scale')->find();
        $get_list = S('get_hz_product_list' . $productid);
        if (!$get_list) {
            $data = [
                'app_key' => 2018110501,
                'productids' => $productid,
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
            $url = "http://open.hznzcn.com/product/ids/list";
            $product = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            //获取商品图片
            $res = json_decode($product);
            $res->product_pic = $this->getproductpic($productid);
            //存缓存
            $product_delit=[
                'Pro_ID'=>$res->Items[0]->Pro_ID,
                'ProductName'=>$res->Items[0]->ProductName,
                'BrandID'=>$res->Items[0]->BrandID,
                'ClassName'=>$res->Items[0]->ClassName,
                'SpecificationsDictionary'=>[
                    '颜色'=>$res->Items[0]->SpecificationsDictionary->颜色,
                    '尺码'=>$res->Items[0]->SpecificationsDictionary->尺码
                ],
                'OnSaleSpec'=>$res->Items[0]->OnSaleSpec,
                'TakePrice'=>$res->Items[0]->TakePrice,
                'JianDanPrice'=>(intval($price_scale['price_scale'] +100) * $res->Items[0]->TakePrice)/100,
                'product_pic'=>$res->product_pic
            ];
            S('get_hz_product_list' . $productid, json_encode($product_delit), 3600);
            echo  json_encode($res);
            exit;
        } else {
            echo  $get_list;
            exit;

        }


    }
    public function gethzproductlistreturn(){
        $productid=I('product_id',474740);
        //缓存
        $get_list=S('get_hz_product_list_return'.$productid);

        if(!$get_list){
            $data=[
                'app_key'=>2018110501,
                'productids'=>$productid,
                'stamp'=> sprintf('%0.0f', ($this->getMillisecond() +3600 * 8 * 10000000)  + 621355968000000000),
                'access_token' =>$this->oauth->access_token,
            ];
            ksort($data);
            $str='';
            if($data){
                foreach ($data as $key=>$value){
                    $str .=$key.$value;
                }
            }
            $data['sign']=strtoupper(md5($str.''.$this->AppSecret));
            $url="http://open.hznzcn.com/product/ids/list";

            $product=$this->curl($url,$ispost=true,http_build_query($data),$in='utf8',$out='utf8',$cookie='');
            //获取商品图片
            $res= json_decode($product);
            $res->product_pic=$this->getproductpic($productid);
            //存缓存
            S('get_hz_product_list_return'.$productid,$res,10);
            return $res;exit;

        }else{
            return $get_list;exit;

        }
    }
   // public function  test(){
       // $data=$this->gethzproductlistreturn();
        //var_dump($data);EXIT;
    //}

    //加入购物车
    public function gethzaddnewcart(){
        $uid = I("uid"); // 用户id
        $goods_num = I("goods_num");// 商品数量
        $products_id = I("product_id"); // 货品id
        $is_hangzhou = I("is_hangzhou"); // 是否是杭州女装
        $onsalespec=I('spec');//规格
        $uid =1116; // 用户id
        $goods_num = 1;// 商品数量
       // $products_id = 491081; // 货品id
        $is_hangzhou=1;


        if(empty($products_id)){
            return return_result(-1,200,'请选择要购买的商品',array());
        }
        if(empty($goods_num)){
            return return_result(-1,200,'购买商品数量不能为0',array());
        }
         //获取商品详情
        $goods = $this->gethzproductlistreturn($products_id);
        //var_dump($goods);exit;


        //加入购物车
        $products['name']=$goods->Items[0]->ProductName;
        $products['user_id']=$uid;
        $products['buy_num']=$goods_num?$goods_num:1;
        $products['goods_id']=0;
        $products['market_price']=$goods->Items[0]->SalePrice;
        $products['price']=$goods->Items[0]->TakePrice;
        $products['add_time']=time();
        $products['products_id']=$products_id;
        $products['pic']=$goods->product_pic[0];
        $products['spec']=$onsalespec;
        $ret=$this->addGoodsToCart($products);

        if($ret){
            $res = array("status"=>1,"code"=>200,"msg"=>"加入购物车成功","data"=>'');
            echo json_encode($res,true);
            exit;
        }else{
            $res = array("status"=>-1,"code"=>200,"msg"=>"加入购物车失败","data"=>'');
            echo json_encode($res,true);
            exit;
        }

    }
    
    public  function addGoodsToCart($data){

        if ($data['buy_num'] <= 0) {
            return return_result(-1,200,'购买数量不能小于1',array());
        }
        $userCartCount =M('cart')->where(['user_id' => $data['uid']])->count();//获取用户购物车的商品有多少种
        if ($userCartCount >= 10) {
            return return_result(-1,200,'购物车最多只能放10种商品',array());
        }
        // 查询购物车是否已经存在这商品

        $cart_where = ['user_id' => $data['user_id'], 'products_id'=>$data['products_id']];
        $goods_inCart= M("cart")->where($cart_where)->find();

        if($goods_inCart){ //存在购物车中

            $userCartGoods['buy_num'] = $goods_inCart['buy_num']? $goods_inCart['buy_num']:0;
            $userWantGoodsNum = $data['buy_num'] + $userCartGoods['buy_num'];//本次要购买的数量加上购物车的本身存在的数量
            //如果价格有变,以最新为准
            $userBuyPrice= $data['price'];
            //更新数据库
            $cartResult = M('cart')->where($cart_where)->save(['buy_num' =>$userWantGoodsNum,'price'=>$userBuyPrice]);
        }else{ //不在购物车中
            $cartAddData = array(
                'user_id' => $data['user_id'],   // 用户id
                'goods_id' => $data['goods_id'],   // 商品id
                'name' => $data['name'],   // 商品名称
                'market_price' => $data['market_price'],   // 市场价
                'price' => $data['price'],  // 存在货品货品价格,不存在商品价格
                'buy_num' => $data['buy_num'], // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 0,   // 0 普通订单,
                'products_id'=>$data['products_id'],
                'pic'=>$data['pic'],
                'spec'=>$data['spec']
            );

            $cartResult = M('cart')->add($cartAddData);
        }
        if ($cartResult === false) {
            return return_result(-1,200,'加入购物车失败',array());
        }
        return  true;

    }

    //获取商品图片
    public function getproductpic($product_id){
        //缓存
        $get_list=S('get_hz_product_pic'.$product_id);
        if(!$get_list){
            $data=[
                'app_key'=>2018110501,
                'proid'=>$product_id,
                'iswantalbum'=>1,
                'stamp'=> sprintf('%0.0f', ($this->getMillisecond() +3600 * 8 * 10000000)  + 621355968000000000),
                'access_token' =>$this->oauth->access_token,
            ];
            ksort($data);
            $str='';
            if($data){
                foreach ($data as $key=>$value){
                    $str .=$key.$value;
                }
            }
            $data['sign']=strtoupper(md5($str.''.$this->AppSecret));
            $url='http://open.hznzcn.com/product/get';
            $product_pic = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            $arr=json_decode($product_pic);
            foreach ($arr->Data->ProductAlbumList as $key=>$value){
                if($value->ImgTypeName =='主图'){
                    $imgs[]=$value->ThumbnailAddress;
                }
            }
            //存缓存
            S('get_hz_product_pic'.$product_id,$imgs,36000);
            return  $imgs;
        }else{
            return $get_list;
        }
    }

    //地址接口
    public function getProvinces(){
        $parentid=I('parentid',0);
        $provinces=S('get_hz_provinces'.$parentid);
        if(!$provinces){
            $data=[
                'app_key'=>2018110501,
                'parentid'=>$parentid,
                'iswantalbum'=>1,
                'stamp'=> sprintf('%0.0f', ($this->getMillisecond() +3600 * 8 * 10000000)  + 621355968000000000),
                'access_token' =>$this->oauth->access_token,
            ];
            ksort($data);
            $str='';
            if($data){
                foreach ($data as $key=>$value){
                    $str .=$key.$value;
                }
            }
            $data['sign']=strtoupper(md5($str.''.$this->AppSecret));
            $url='http://open.hznzcn.com/base/provinces/list';
            $addr = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            $arr=json_decode($addr);
            var_dump($arr);exit;
            //存缓存
            S('get_hz_provinces'.$parentid,$arr,36000);
            return  $arr;
        }else{
            return $provinces;
        }

    }

    public function getHzCategories()
    {
        $productclassids = I('productclassids', 0);
        $provinces = S('get_hz_categories111' . $productclassids);
        if (!$provinces) {
            $data = [
                'app_key' => 2018110501,
                'iswantalbum' => 1,
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
            $url = 'http://open.hznzcn.com/product/productclass/list';
            $addr = $this->curl($url, $ispost = true, http_build_query($data), $in = 'utf8', $out = 'utf8', $cookie = '');
            $arr = json_decode($addr);
            $new_arr = [];
            $new_arry = [];
            foreach ($arr->Items as $key => $value) {
                if ($value->FatherId == 0) {
                    $new_arr[$value->FatherId][$value->Cid]['Cid'] = $value->Cid;
                    $new_arr[$value->FatherId][$value->Cid]['Name'] = $value->Name;
                    $new_arr[$value->FatherId][$value->Cid]['Icon'] = $value->Icon;
                    $new_arr[$value->FatherId][$value->Cid]['FatherId'] = $value->FatherId;
                } else {
                    $new_arry[$value->FatherId][$value->Cid]['Cid'] = $value->Cid;
                    $new_arry[$value->FatherId][$value->Cid]['Name'] = $value->Name;
                    $new_arry[$value->FatherId][$value->Cid]['Icon'] = $value->Icon;
                    $new_arry[$value->FatherId][$value->Cid]['FatherId'] = $value->FatherId;
                }

            }
            foreach ($new_arr[0] as $k => $v) {
                $new_arr[0][$k]['next'] = $new_arry[$v['Cid']];
            }
            //存缓存
            $arr = json_encode($new_arr);
            S('get_hz_categories' . $productclassids, $new_arr, 36000);
            echo $arr;
            exit;
        } else {
            echo $provinces;
            exit;
        }
    }

}