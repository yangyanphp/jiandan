<?php
namespace Common\Controller;

use Common\Controller\AppframeController;

class HomebaseController extends AppframeController {
	
	public function __construct() {
		$this->set_action_success_error_tpl();
		parent::__construct();
	}

	//token检测
    protected function device_repeat($token){
        if(empty($token)){ return return_result(0,1002,'缺少token','');}
        $res = M('member')->where(array('token'=>$token))->find();
        if(!$res){ //有重复登陆
            return return_result(0,1111,'您已经在另一台设备登录过','');
        }
    }

	function _initialize() {
		parent::_initialize();
		//token检测
        //$this->device_repeat($_POST['token']);
        defined('TMPL_PATH') or define("TMPL_PATH", C("SP_TMPL_PATH"));
		$site_options=get_site_options();
		$this->assign($site_options);
		$ucenter_syn=C("UCENTER_ENABLED");
		if($ucenter_syn){
		    $session_user=session('user');
			if(empty($session_user)){
				if(!empty($_COOKIE['thinkcmf_auth'])  && $_COOKIE['thinkcmf_auth']!="logout"){
					$thinkcmf_auth=sp_authcode($_COOKIE['thinkcmf_auth'],"DECODE");
					$thinkcmf_auth=explode("\t", $thinkcmf_auth);
					$auth_username=$thinkcmf_auth[1];
					$users_model=M('Users');
					$where['user_login']=$auth_username;
					$user=$users_model->where($where)->find();
					if(!empty($user)){
						$is_login=true;
						session('user',$user);
					}
				}
			}else{
			}
		}
		
		if(sp_is_user_login()){
			$this->assign("user",sp_get_current_user());
		}
		
	}
	
	/**
	 * 检查用户登录
	 */
	protected function check_login(){
	    $session_user=session('user');
		if(empty($session_user)){
			$this->error('您还没有登录！',leuu('user/login/index',array('redirect'=>base64_encode($_SERVER['HTTP_REFERER']))));
		}
		
	}
	
	/**
	 * 检查用户状态
	 */
	protected function  check_user(){
	    $user_status=M('Users')->where(array("id"=>sp_get_current_userid()))->getField("user_status");
		if($user_status==2){
			$this->error('您还没有激活账号，请激活后再使用！',U("user/login/active"));
		}
		
		if($user_status==0){
			$this->error('此账号已经被禁止使用，请联系管理员！',__ROOT__."/");
		}
	}
	
	/**
	 * 发送注册激活邮件
	 */
	protected  function _send_to_active(){
		$option = M('Options')->where(array('option_name'=>'member_email_active'))->find();
		if(!$option){
			$this->error('网站未配置账号激活信息，请联系网站管理员');
		}
		$options = json_decode($option['option_value'], true);
		//邮件标题
		$title = $options['title'];
		$uid=session('user.id');
		$username=session('user.user_login');
	
		$activekey=md5($uid.time().uniqid());
		$users_model=M("Users");
	
		$result=$users_model->where(array("id"=>$uid))->save(array("user_activation_key"=>$activekey));
		if(!$result){
			$this->error('激活码生成失败！');
		}
		//生成激活链接
		$url = U('user/register/active',array("hash"=>$activekey), "", true);
		//邮件内容
		$template = $options['template'];
		$content = str_replace(array('http://#link#','#username#'), array($url,$username),$template);
	
		$send_result=sp_send_email(session('user.user_email'), $title, $content);
	
		if($send_result['error']){
			$this->error('激活邮件发送失败，请尝试登录后，手动发送激活邮件！');
		}
	}
	
	/**
	 * 加载模板和页面输出 可以返回输出内容
	 * @access public
	 * @param string $templateFile 模板文件名
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 * @param string $content 模板输出内容
	 * @return mixed
	 */
	public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '') {
		parent::display($this->parseTemplate($templateFile), $charset, $contentType,$content,$prefix);
	}
	
	/**
	 * 获取输出页面内容
	 * 调用内置的模板引擎fetch方法，
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件
	 * 默认为空 由系统自动定位模板文件
	 * @param string $content 模板输出内容
	 * @param string $prefix 模板缓存前缀*
	 * @return string
	 */
	public function fetch($templateFile='',$content='',$prefix=''){
	    $templateFile = empty($content)?$this->parseTemplate($templateFile):'';
		return parent::fetch($templateFile,$content,$prefix);
	}
	
	/**
	 * 自动定位模板文件
	 * @access protected
	 * @param string $template 模板文件规则
	 * @return string
	 */
	public function parseTemplate($template='') {
		
		$tmpl_path=C("SP_TMPL_PATH");
		define("SP_TMPL_PATH", $tmpl_path);
		if($this->theme) { // 指定模板主题
		    $theme = $this->theme;
		}else{
		    // 获取当前主题名称
		    $theme      =    C('SP_DEFAULT_THEME');
		    if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
		        $t = C('VAR_TEMPLATE');
		        if (isset($_GET[$t])){
		            $theme = $_GET[$t];
		        }elseif(cookie('think_template')){
		            $theme = cookie('think_template');
		        }
		        if(!file_exists($tmpl_path."/".$theme)){
		            $theme  =   C('SP_DEFAULT_THEME');
		        }
		        cookie('think_template',$theme,864000);
		    }
		}
		
		$theme_suffix="";
		
		if(C('MOBILE_TPL_ENABLED') && sp_is_mobile()){//开启手机模板支持
		    
		    if (C('LANG_SWITCH_ON',null,false)){
		        if(file_exists($tmpl_path."/".$theme."_mobile_".LANG_SET)){//优先级最高
		            $theme_suffix  =  "_mobile_".LANG_SET;
		        }elseif (file_exists($tmpl_path."/".$theme."_mobile")){
		            $theme_suffix  =  "_mobile";
		        }elseif (file_exists($tmpl_path."/".$theme."_".LANG_SET)){
		            $theme_suffix  =  "_".LANG_SET;
		        }
		    }else{
    		    if(file_exists($tmpl_path."/".$theme."_mobile")){
    		        $theme_suffix  =  "_mobile";
    		    }
		    }
		}else{
		    $lang_suffix="_".LANG_SET;
		    if (C('LANG_SWITCH_ON',null,false) && file_exists($tmpl_path."/".$theme.$lang_suffix)){
		        $theme_suffix = $lang_suffix;
		    }
		}
		
		$theme=$theme.$theme_suffix;
		
		C('SP_DEFAULT_THEME',$theme);
		
		$current_tmpl_path=$tmpl_path.$theme."/";
		// 获取当前主题的模版路径
		define('THEME_PATH', $current_tmpl_path);
		
		$cdn_settings=sp_get_option('cdn_settings');
		if(!empty($cdn_settings['cdn_static_root'])){
		    $cdn_static_root=rtrim($cdn_settings['cdn_static_root'],'/');
		    C("TMPL_PARSE_STRING.__TMPL__",$cdn_static_root."/".$current_tmpl_path);
		    C("TMPL_PARSE_STRING.__PUBLIC__",$cdn_static_root."/public");
		    C("TMPL_PARSE_STRING.__WEB_ROOT__",$cdn_static_root);
		}else{
		    C("TMPL_PARSE_STRING.__TMPL__",__ROOT__."/".$current_tmpl_path);
		}
		
		
		C('SP_VIEW_PATH',$tmpl_path);
		C('DEFAULT_THEME',$theme);
		
		define("SP_CURRENT_THEME", $theme);
		
		if(is_file($template)) {
			return $template;
		}
		$depr       =   C('TMPL_FILE_DEPR');
		$template   =   str_replace(':', $depr, $template);
		
		// 获取当前模块
		$module   =  MODULE_NAME;
		if(strpos($template,'@')){ // 跨模块调用模版文件
			list($module,$template)  =   explode('@',$template);
		}
		
		$module =$module."/";
		
		// 分析模板文件规则
		if('' == $template) {
			// 如果模板文件名为空 按照默认规则定位
			$template = CONTROLLER_NAME . $depr . ACTION_NAME;
		}elseif(false === strpos($template, '/')){
			$template = CONTROLLER_NAME . $depr . $template;
		}
		
		$file = sp_add_template_file_suffix($current_tmpl_path.$module.$template);
		$file= str_replace("//",'/',$file);
		if(!file_exists_case($file)) E(L('_TEMPLATE_NOT_EXIST_').':'.$file);
		return $file;
	}
	
	/**
	 * 设置错误，成功跳转界面
	 */
	private function set_action_success_error_tpl(){
		$theme      =    C('SP_DEFAULT_THEME');
		if(C('TMPL_DETECT_THEME')) {// 自动侦测模板主题
			if(cookie('think_template')){
				$theme = cookie('think_template');
			}
		}
		//by ayumi手机提示模板
		$tpl_path = '';
		if(C('MOBILE_TPL_ENABLED') && sp_is_mobile() && file_exists(C("SP_TMPL_PATH")."/".$theme."_mobile")){//开启手机模板支持
			$theme  =   $theme."_mobile";
			$tpl_path=C("SP_TMPL_PATH").$theme."/";
		}else{
			$tpl_path=C("SP_TMPL_PATH").$theme."/";
		}
		
		//by ayumi手机提示模板
		$defaultjump=THINK_PATH.'Tpl/dispatch_jump.tpl';
		$action_success = sp_add_template_file_suffix($tpl_path.C("SP_TMPL_ACTION_SUCCESS"));
		$action_error = sp_add_template_file_suffix($tpl_path.C("SP_TMPL_ACTION_ERROR"));
		if(file_exists_case($action_success)){
			C("TMPL_ACTION_SUCCESS",$action_success);
		}else{
			C("TMPL_ACTION_SUCCESS",$defaultjump);
		}

		if(file_exists_case($action_error)){
			C("TMPL_ACTION_ERROR",$action_error);
		}else{
			C("TMPL_ACTION_ERROR",$defaultjump);
		}
	}




    function getTaoBaoOrder(){

        //十分钟前的订单

        //$start_date=date("Y-m-d H:i:s",time()-60*10);
        //测试时间
        $start_date='2018-06-12 17:08:33';
        $url='https://api.open.21ds.cn/apiv1/gettkorder?apkey=b53be33a-d3e2-5696-9602-1fb8d13b290e&starttime='.urlencode($start_date).'&span=1200&page=1&pagesize=100&tkstatus=1&ordertype=create_time&tbname=0453china';
        $homepage = file_get_contents($url);
        $res=$this->is_json($homepage);

        if($res['code'] == -1 || empty($res['data'])){
            return false;
        }else{
            if(count($res['data']['n_tbk_order']) == count($res['data']['n_tbk_order'], 1)){
                //一件商品
                $info=M("Order")->where(array('order_sn'=>$res['data']['n_tbk_order']['trade_id'],'goods_id'=>$res['data']['n_tbk_order']['num_iid']))->find();
                if(empty($info)){
                    $data['order_sn'] = $res['data']['n_tbk_order']['trade_id'];//订单编号
                    $data['status'] =  $res['data']['n_tbk_order']['tk_status'];//订单状态
                    $data['price'] =  $res['data']['n_tbk_order']['pay_price'];//订单金额
                    $data['goods_title'] =  $res['data']['n_tbk_order']['item_title'];//商品信息
                    $data['goods_id'] =  $res['data']['n_tbk_order']['num_iid'];//商品ID
                    $data['num'] =  $res['data']['n_tbk_order']['item_num'];//商品数量
                    $data['marketprice'] =  $res['data']['n_tbk_order']['price'];//商品单价
                    $checkOrder = substr( $res['data']['n_tbk_order']['order_sn'], -6);

                    $map['checkorder']  = array('eq',$checkOrder);
                    $membercheck = M("Member")->where($map)->find();
                    if(empty($membercheck)){
                        $membercheck = array();
                    }


                    if( $res['data']['n_tbk_order']['order_type']=='天猫'){
                        $order_type = 2;
                    }else{
                        $order_type = 1;
                    }
                    $data['order_type'] = $order_type;//订单类型
                    $data['add_time'] = empty($res['data']['n_tbk_order']['create_time'])?'':strtotime($res['data']['n_tbk_order']['create_time']);//创建时间
                    $data['finish_time'] = empty($res['data']['n_tbk_order']['earning_time'])?'':strtotime($res['data']['n_tbk_order']['earning_time']);//结算时间
                    $data['commission'] = $res['data']['n_tbk_order']['total_commission_fee'];

                    /*
                    $bilv = trim(str_replace('%', '', $value['F11']));
                    $cmoney =  round($value['F13']*$bilv,2);
                    $data['commission'] = $cmoney;
                    */
                    $data['from_meiti'] = $res['data']['n_tbk_order']['site_id'];
                    $data['ad_id'] = $res['data']['n_tbk_order']['adzone_id'];

                    if(empty($membercheck)){
                        $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                        $goodslog = M("Goodslog")->where(array('goodsid'=>$res['data']['n_tbk_order']['num_iid'],'pid'=>$pid))->find();

                        if(!empty($goodslog)){
                            $data['mid'] = $goodslog['mid'];
                        }else{
                            $data['mid'] = '';
                        }
                        if(!empty($data['mid'])){
                            M("Member")->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                        }
                    }else{
                        $data['mid'] = $membercheck['id'];
                    }
                    if(empty($data['commission'])){
                        $data['commission'] = 0;
                    }
                    $id = M("Order")->add($data);
                    var_dump($id);

                }else{
                    if($info['status'] != $res['data']['n_tbk_order']['tk_status']){//订单状态
                        if($res['data']['n_tbk_order']['tk_status']==3){
                            $odata['finish_time'] = strtotime($res['data']['n_tbk_order']['earning_time']);
                        }
                        $odata['status'] = $res['data']['n_tbk_order']['tk_status'];
                        M("Order")->where("id=".$info['id'])->save($odata);
                    }

                }

            }else{
                foreach ($res['data']['n_tbk_order'] as $key=>$value){
                    //多件商品
                    $info=M("Order")->where(array('order_sn'=>$value['trade_id'],'goods_id'=>$value['num_iid']))->find();
                    if(empty($info)){
                        $data['order_sn'] = $value['trade_id'];//订单编号
                        $data['status'] = $value['tk_status'];//订单状态
                        $data['price'] = $value['pay_price'];//订单金额
                        $data['goods_title'] = $value['item_title'];//商品信息
                        $data['goods_id'] = $value['num_iid'];//商品ID
                        $data['num'] = $value['item_num'];//商品数量
                        $data['marketprice'] = $value['price'];//商品单价
                        $checkOrder = substr($data['order_sn'], -6);

                        $map['checkorder']  = array('eq',$checkOrder);
                        $membercheck = M("Member")->where($map)->find();
                        if(empty($membercheck)){
                            $membercheck = array();
                        }


                        if($value['order_type']=='天猫'){
                            $order_type = 2;
                        }else{
                            $order_type = 1;
                        }
                        $data['order_type'] = $order_type;//订单类型
                        $data['add_time'] = empty($value['create_time'])?'':strtotime($value['create_time']);//创建时间
                        $data['finish_time'] = empty($value['earning_time'])?'':strtotime($value['earning_time']);//结算时间
                        $data['commission'] = $value['total_commission_fee'];

                        /*
                        $bilv = trim(str_replace('%', '', $value['F11']));
                        $cmoney =  round($value['F13']*$bilv,2);
                        $data['commission'] = $cmoney;
                        */
                        $data['from_meiti'] = $value['site_id'];
                        $data['ad_id'] = $value['adzone_id'];

                        if(empty($membercheck)){
                            $pid = 'mm_25721409_'.$data['from_meiti'].'_'.$data['ad_id'];
                            $goodslog = M("Goodslog")->where(array('goodsid'=>$value['num_iid'],'pid'=>$pid))->find();

                            if(!empty($goodslog)){
                                $data['mid'] = $goodslog['mid'];
                            }else{
                                $data['mid'] = '';
                            }
                            if(!empty($data['mid'])){
                                M("Member")->where("id=".$data['mid'])->save(array('checkorder'=>$checkOrder));
                            }
                        }else{
                            $data['mid'] = $membercheck['id'];
                        }
                        if(empty($data['commission'])){
                            $data['commission'] = 0;
                        }
                        $id = M("Order")->add($data);
                    }else{
                        if($info['status'] != $value['tk_status']){//订单状态
                            if($value['tk_status']==3){
                                $odata['finish_time'] = strtotime($value['earning_time']);
                            }
                            $odata['status'] = $value['tk_status'];
                            M("Order")->where("id=".$info['id'])->save($odata);
                        }

                    }
                }

            }

        }
        //更新用户订单
        $memberList =  M("Member")->select();
        foreach($memberList as $key=>$value){
            $this->updateInfo($value['id']);
        }

    }



    function updateInfo($mid){
        //更新用户订单等信息
        $info=  M("Member")->where(array('id'=>$mid))->find();
        $returnMoney= M("ReturnLevel")->where(array('id'=>$info['level_id']))->find();

        //判断是否有粉丝
        $fansCount =  M("Member")->where(array('share_id'=>$mid))->count();

        if(empty($fansCount)){
            $orderList = M("Order")->where(array('mid'=>$mid,'tixian_status1'=>0))->select();
            foreach($orderList as $key=>$value){
                $data['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
                M("Order")->where("id=".$value['id'])->save($data);
            }

        }else{
            $fansList = M("Member")->where(array('share_id'=>$mid))->select();

            $orderList = M("Order")->where(array('mid'=>$mid,'tixian_status1'=>0))->select();
            $countMoney = 0;
            //有粉丝自己购物返现
            foreach($orderList as $key=>$value){
                $data2['zigou_return'] = round($value['commission']*($returnMoney['zigou_return']/100),2);//自购返现
                M("Order")->where("id=".$value['id'])->save($data2);
            }

            //有粉丝 粉丝给自己的返现
            foreach ($fansList as $key=>$value){
                $fansOrderList = M("Order")->where(array('mid'=>$value['id'],'tixian_status2'=>0))->select();
                foreach($fansOrderList as $k=>$v){
                    $data3['daili_return'] = round($v['commission']*($returnMoney['daili_return']/100),2);//代理返现
                    M("Order")->where("id=".$v['id'])->save($data3);

                }
            }
        }

    }
	
	
}