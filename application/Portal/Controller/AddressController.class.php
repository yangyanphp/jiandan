<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class AddressController extends HomebaseController {
    //获取省地址 http://local.jiandan.com/portal/address/getAddressTree
    public function getAddressTree(){
        $province = M('addr')->where ( array('pid'=>1) )->select ();
        //print_r($province);
        if(empty($province)){
            return return_result(-1,200,'请求失败',array());
        }else{
            return return_result(1,200,'请求成功',$province);
        }

    }
    //获取联动地址  http://local.jiandan.com/portal/address/getRegion?type=2&id=3
    //
    public function getRegion(){
        $map['pid'] = I("id");
        $map['type']=I("type");
        $list=M('addr')->where($map)->select();
        if(empty($list)){
            return return_result(-1,200,'请求失败',array());
        }else{
            return return_result(1,200,'请求成功',$list);
        }
    }

    //收货地址列表
    public function addressList(){
        $user_id = I("user_id");
        $addressList = M('user_address')->where(["address_id"=>$user_id])->select();
        if(empty($addressList)){
            return return_result(-1,200,'请求失败',array());
        }else{
            return return_result(1,200,'请求成功',$addressList);
        }
    }

    //设置默认地址
    public function setAddressDefault(){
        $address_id=I("address_id");
        $user_id=I("user_id");
        $addr = M('user_address')->where(["address_id"=>$address_id])->save(['is_default'=>1]);
        $map['user_id']=$user_id;
        $map['address_id']=['NEQ',$address_id];
        M('user_address')->where($map)->save(['is_default'=>0]);
        if(empty($addr)){
            return return_result(-1,200,'设置成功',array());
        }else{
            return return_result(1,200,'设置成功',array());
        }
    }

    //添加收货地址
    public function addUserAddress(){
        $data['user_id'] = I("user_id");
        $data['user_name']=I("user_name");
        $data['email']=I("email");
        $data['province']=I("province");
        $data['city']=I("city");
        $data['district']=I("district");
        $data['address']=I("address");
        $data['zipcode']=I("zipcode");
        $data['mobile']=I("mobile");
        $address_default= M('user_address')->where(["user_id"=>I("user_id")])->find();
        if(!$address_default){
            $data['is_default']=1;
        }
        if(empty($data['mobile']) || !preg_match("/^1[345678]{1}\d{9}$/",$data['mobile'])){
            return return_result(-1,200,'手机号不能为空或手机格式不正确!',array());
        }
        if(empty($data['province'])){
            return return_result(-1,200,'请填写省份!',array());
        }
        if(empty($data['city'])){
            return return_result(-1,200,'请填写市!',array());
        }
        if(empty($data['district'])){
            return return_result(-1,200,'请填写区!',array());
        }
        if(empty($data['address'])){
            return return_result(-1,200,'请填详细地址!',array());
        }
        if(empty($data['user_name'])){
            return return_result(-1,200,'请填收货人!',array());
        }
        $user_address = M('user_address')->add($data);

        if(empty($user_address)){
            return return_result(-1,200,'添加失败!',array());

        }else{
            return return_result(1,200,'添加成!',$data);
        }
    }

}

