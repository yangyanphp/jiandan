<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
/**
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class PublicController extends AdminbaseController {

    public function _initialize() {
        C(S('sp_dynamic_config'));//加载动态配置
    }
    
    //后台登陆界面
    public function login() {
        $admin_id=session('ADMIN_ID');
    	if(!empty($admin_id)){//已经登录
    		redirect(U("admin/index/index"));
    	}else{
    	    $site_admin_url_password =C("SP_SITE_ADMIN_URL_PASSWORD");
    	    $upw=session("__SP_UPW__");
    	 
    		if(!empty($site_admin_url_password) && $upw!=$site_admin_url_password){
    			redirect(__ROOT__."/");
    		}else{
    			$this->display(":login");

    		}
    	}
    }
    
    public function logout(){
    	//session('ADMIN_ID',null);
    	session_unset();
    	session_destroy();
    	
    	redirect(__ROOT__."/");
    }
    
    public function dologin(){
       
    	$name = I("post.username");
    	if(empty($name)){
    		echo 1;
    		die;
    	}
    	$pass = I("post.password");
    	if(empty($pass)){
    		echo 1;
    		die;
    	}
    		$user = D("Common/Admin");
    		
    		$where['username']=$name;
    		$where['password']=md5($pass);
    		$result = $user->where($where)->find();
    		if($result){
    				//登入成功页面跳转
    				session('ADMIN_ID',$result["id"]);
    				session('name',$result["username"]);
    				$user->save($result);
    				cookie("admin_username",$name,3600*24*30);
    				//$this->success(L('LOGIN_SUCCESS'),U("Index/index"));
    				//echo "<script>window.location.href='".U("Index/index")."'</script>";
    				echo 2;
    				die;
    		}else{
    			//$this->error(L('USERNAME_NOT_EXIST'));
    				echo 1;
    				die;
    		
    	}
    }
    
    public function updatePass(){
    	$admin_id=session('ADMIN_ID');
    	$pwd = I("post.pwd");
    	$res = M('Admin')->where('id='.$admin_id)->save(array('password'=>md5($pwd)));
    	redirect(U("admin/index/index"));
    }
}