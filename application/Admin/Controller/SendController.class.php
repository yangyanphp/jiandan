<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class SendController extends AdminbaseController{
    
	protected $send_model;
	protected $member_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->send_model = D("Common/Push");
		$this->member_model = D("Common/Member");
		$this->push_model = D("Common/PushCat");
	}
	
	//群发列表
	public function index(){

		$item = D("Common/Tixian")->where('id=1')->find();
		if(!empty($item['black'])){
			$arr = explode(',',$item['black']);
		}else{
			$arr = array();
		}
		$data=$this->member_model->order('create_time DESC')->select();
		
		$this->assign("blackArray",$arr);
		$this->assign("data",$data);
		$this->display();
	}
	
	//群发消息添加
	public function add(){
		$id = $_GET['id'];

		$item=$this->member_model->where('id='.$id)->find();
		
		$black = D("Common/Tixian")->where('id=1')->find();
		if(!empty($black['black'])){
			$arr = explode(',',$black['black']);
		}else{
			$arr = array();
		}
		if(in_array($id, $arr)){
			$is_black = 1;
		}else{
			$is_black = 0;
		}
		$data=$this->member_model->order('create_time DESC')->select();
		$this->assign("is_black",$is_black);
		$this->assign($item);
		$this->display();
	}
	
    public function add_post() {

    	$upload_setting=sp_get_upload_setting();
    	$filetypes=array(
    			'image'=>array('title'=>'Image files','extensions'=>$upload_setting['image']['extensions']),
    			'video'=>array('title'=>'Video files','extensions'=>$upload_setting['video']['extensions']),
    			'audio'=>array('title'=>'Audio files','extensions'=>$upload_setting['audio']['extensions']),
    			'file'=>array('title'=>'Custom files','extensions'=>$upload_setting['file']['extensions'])
    	);
    	$all_allowed_exts=array();
    	foreach ($filetypes as $mfiletype){
    		array_push($all_allowed_exts, $mfiletype['extensions']);
    	}
    	$all_allowed_exts=implode(',', $all_allowed_exts);
    	$all_allowed_exts=explode(',', $all_allowed_exts);
    	$all_allowed_exts=array_unique($all_allowed_exts);
    	
    	 $data = array();
    	 $res = array();
    	 for($i=1;$i<=6;$i++){
	    	$file_extension=sp_get_file_extension($_FILES['img'.$i]['name']);
	    	$upload_max_filesize= 2097152000000;//默认2M
	    	 
	    	$app=I('post.app/s','');
	    	if(!in_array($app, C('MODULE_ALLOW_LIST'))){
	    		$app='default';
	    	}else{
	    		$app= strtolower($app);
	    	}
	    	 
	    	$savepath=$app.'/'.date('Ymd').'/';


             //上传处理类
	    	$config=array(
	    			'rootPath' => './'.C("UPLOADPATH"),
                    'savePath' => $savepath,
	    			 'maxSize' => $upload_max_filesize,
                  'saveName'   =>    array('uniqid',''),
                  'exts'       =>    $all_allowed_exts,
                  'autoSub'    =>    false,
	    	);

	    	$upload = new \Think\Upload($config);//
	    	$info=$upload->upload();

	    	foreach ($info as $key=>$value){
	    		$img = WEB_URL."/data/upload/".$value['savepath'].$value['savename'];
	    		array_push($res,$img);
	    	}
	    	/*
	    	if(empty($_FILES['img'.$i]['name'])){
	    		$res['img'] = '';
	    	}else{
	    		$res['img'] = WEB_URL."/data/upload/".$info['img'.$i]['savepath'].$info['img'.$i]['savename'];
	    	}
	    	*/
	    	
    	}
    	$i=1;
    	$count = count($res);

    	foreach ($res as $key=>$value){
    		$arr['img'] = $value;
    		$arr['content'] = $_POST['content'.$i];
    		$i++;
    		array_push($data,$arr);
    	}
    	if($count<6){
    		for($j=$count;$j<=6;$j++){
    			$arr['img'] = '';
    			$arr['content'] = '';
    			array_push($data,$arr);
    		}
    	}
    	
    	$arr['title'] = $_POST['title'];
    	$arr['remark'] = $_POST['remark'];
    	$arr['content'] = json_encode($data,JSON_UNESCAPED_UNICODE);

    	$pid=$this->push_model->add($arr);

    	$mid = $_POST['id'];
    	$openid = $_POST['openid'];
    	$phone_type = $_POST['phone_type'];

    	include 'extend/jiguang/examples/config.php';

        $send_sms=new \Jpush\Client('9b9a355f0b08db40d4dc4c55','4bafbaaf1b8adb992df9fa8f');

        if($mid){
            $response = $send_sms->push()
                ->setPlatform(array('ios', 'android'))
                ->addAlias($mid)
                ->iosNotification($arr['title'], array(
                    'title' => $arr['remark'],
                    'extras' => array(
                        'pushid' => $pid
                    ),
                ))
                ->androidNotification($arr['title'], array(
                    'title' =>$arr['remark'],
                    'extras' => array(
                        'pushid' => $pid
                    ),
                ))
                ->options(array(
                    'apns_production' => false,
                ))
                ->send();
        }


//        if($phone_type==1){
//
//    		$response = $send_sms->push()
//    		->setPlatform(array('ios', 'android'))
//    		->addAlias($mid)
//    		->iosNotification($arr['content'], array(
//    				'title' => $arr['title'],
//    				'extras' => array(
//    					'pushid' => $pid
//    				),
//    		))
//            ->androidNotification($arr['content'], array(
//                'title' => $arr['title'],
//                'extras' => array(
//                    'pushid' => $pid
//                ),
//            ))
//    		->options(array(
//    				'apns_production' => false,
//    		))
//    		->send();
//
//    	}else{
//
//    		$response = $send_sms->push()
//    		->setPlatform(array('ios', 'android'))
//    		->addAlias($mid)
//    		->message($arr['content'], array(
//    				'title' => $arr['title'],
//    				'extras' => array(
//    						'pushid' => $pid
//    				),
//    		))
//    		->options(array(
//    				'apns_production' => false,
//    		))
//    		->send();
//
//    	}
    		$push['sendno'] = $response['body']['sendno'];
    		$push['msg_id'] = $response['body']['msg_id'];
    		$push['mid'] = $mid;
    		$push['send_time'] = time();
    		$push['push_id'] = $pid;

    		$id=$this->send_model->add($push);   //push表

    		//判断是否在黑名单
    		
    		$black = D("Common/Tixian")->where('id=1')->find();
    		if(!empty($black['black'])){
    			$blarr = explode(',',$black['black']);
    		}else{
    			$blarr = array();
    		}
    		
    		$is_black = $_POST['is_black'];
    		
    		if($is_black==1){
	    		if(!in_array($mid, $blarr)){
	    			array_push($blarr,$mid);
	    		}
    		}else{
    			$key = array_search($mid, $blarr);
    			array_splice($blarr, $key, 1);
    		}
    		$blackstr = implode(',',$blarr);
    		
    		D("Common/Tixian")->where('id=1')->save(array('black'=>$blackstr));
    		redirect(U("admin/send/index"));
    	
    }
    //全选发送
    public function sendall(){
    	$this->display();
    }
    
    public function send_all() {
    	 
    	$upload_setting=sp_get_upload_setting();
    	$filetypes=array(
    			'image'=>array('title'=>'Image files','extensions'=>$upload_setting['image']['extensions']),
    			'video'=>array('title'=>'Video files','extensions'=>$upload_setting['video']['extensions']),
    			'audio'=>array('title'=>'Audio files','extensions'=>$upload_setting['audio']['extensions']),
    			'file'=>array('title'=>'Custom files','extensions'=>$upload_setting['file']['extensions'])
    	);
    	$all_allowed_exts=array();
    	foreach ($filetypes as $mfiletype){
    		array_push($all_allowed_exts, $mfiletype['extensions']);
    	}
    	$all_allowed_exts=implode(',', $all_allowed_exts);
    	$all_allowed_exts=explode(',', $all_allowed_exts);
    	$all_allowed_exts=array_unique($all_allowed_exts);
    	 $data = array();
    	 $res = array();
    	for($i=1;$i<=6;$i++){
	    	$file_extension=sp_get_file_extension($_FILES['img'.$i]['name']);
	    	$upload_max_filesize= 2097152000000;//默认2M
	    	 
	    	$app=I('post.app/s','');
	    	if(!in_array($app, C('MODULE_ALLOW_LIST'))){
	    		$app='default';
	    	}else{
	    		$app= strtolower($app);
	    	}

	    	$savepath=$app.'/'.date('Ymd').'/';
	    	//上传处理类
	    	$config=array(
	    			'rootPath' => './'.C("UPLOADPATH"),
	    			'savePath' => $savepath,
	    			'maxSize' => $upload_max_filesize,
	    			'saveName'   =>    array('uniqid',''),
	    			'exts'       =>    $all_allowed_exts,
	    			'autoSub'    =>    false,
	    	);
	    	$upload = new \Think\Upload($config);//
	    	$info=$upload->upload();
	    	
	    	foreach ($info as $key=>$value){
	    		$img = WEB_URL."/data/upload/".$value['savepath'].$value['savename'];
	    		array_push($res,$img);
	    	}
	    	/*
	    	if(empty($_FILES['img'.$i]['name'])){
	    		$res['img'] = '';
	    	}else{
	    		$res['img'] = WEB_URL."/data/upload/".$info['img'.$i]['savepath'].$info['img'.$i]['savename'];
	    	}
	    	*/
	    	
    	}
    	$i=1;
    	$count = count($res);
    	foreach ($res as $key=>$value){
    		$arr['img'] = $value;
    		$arr['content'] = $_POST['content'.$i];
    		$i++;
    		array_push($data,$arr);
    	}
    	if($count<6){
    		for($j=$count;$j<=6;$j++){
    			$arr['img'] = '';
    			$arr['content'] = '';
    			array_push($data,$arr);
    		}
    	}
    	$arr['title'] = $_POST['title'];
    	$arr['remark'] = $_POST['remark'];
    	$arr['content'] = json_encode($data,JSON_UNESCAPED_UNICODE);

        $pid=$this->push_model->add($arr);


        include 'extend/jiguang/examples/config.php';
//        echo 11;exit;
        $send_sms=new \Jpush\Client('9b9a355f0b08db40d4dc4c55','4bafbaaf1b8adb992df9fa8f');

    	 
//    	$response = $send_sms->push()
//    	->setPlatform(array('ios', 'android'))
//    	->addAllAudience()
//    	->message($arr['content'], array(
//    			'title' => $arr['title'],
//    			'extras' => array(
//    					'pushid' => $pid
//    			),
//    	))
//    	->options(array(
//    			'apns_production' => false,
//    	))
//    	->send();
        $response = $send_sms->push()
            ->setPlatform(array('ios', 'android'))
            ->addAllAudience()
            ->iosNotification($arr['title'], array(
                'title' =>$arr['remark'],
                'extras' => array(
                    'pushid' => $pid
                ),
            ))
            ->androidNotification($arr['title'], array(
                'title' => $arr['remark'],
                'extras' => array(
                    'pushid' => $pid
                ),
            ))
            ->options(array(
                'apns_production' => false,
            ))
            ->send();
    	
    	$data=$this->member_model->order('create_time DESC')->select();
    	foreach ($data as $key=>$value){
	    	$push['sendno'] = $response['body']['sendno'];
	    	$push['msg_id'] = $response['body']['msg_id'];
	    	$push['mid'] = $value['id'];
	    	$push['send_time'] = time();
	    	$push['push_id'] = $pid;
	    	$id=$this->send_model->add($push);
    	}
    	redirect(U("admin/send/index"));
    	 
    }
}