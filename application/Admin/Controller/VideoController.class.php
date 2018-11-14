<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Tuolaji <479923197@qq.com>
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class VideoController extends AdminbaseController {

   
    //视频教程
    public function jiaocheng() {
    	$where = array('id'=>1);
    	$video = M('Video')->where($where)->find();
    	$this->assign($video);
    	$this->display();
    }
    
    //背景视频
	public function background() {
    	$where = array('id'=>2);
    	$video = M('Video')->where($where)->find();
    	$this->assign($video);
    	$this->display();
    }
    
    //视频教程保存
    public function jc_edit_post() {

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
    	
    	$file_extension=sp_get_file_extension($_FILES['video_url']['name']);
		
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
    	
		
    	$shipin['video_title'] = $_POST['video_title'];
    	if(!empty($_FILES['video_url']['name'])){
    		$shipin['video_url'] = "/data/upload/".$info['video_url']['savepath'].$info['video_url']['savename'];
    	}
    	$shipin['remark'] = $_POST['remark'];
    	$res = M('Video')->where('id=1')->save($shipin);
    	redirect(U("admin/video/jiaocheng"));
    }
    //背景视频保存
    public function bj_edit_post() {
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
    	 
    	$file_extension=sp_get_file_extension($_FILES['video_url']['name']);
    	 
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
    	 
    	$shipin['video_title'] = $_POST['video_title'];
    	if(!empty($_FILES['video_url']['name'])){
    		$shipin['video_url'] = "/data/upload/".$info['video_url']['savepath'].$info['video_url']['savename'];
    	}
    	//$shipin['video_url'] = "/data/upload/".$info['video_url']['savepath'].$info['video_url']['savename'];
    	$shipin['remark'] = $_POST['remark'];
    	$res = M('Video')->where('id=2')->save($shipin);
    	redirect(U("admin/video/background"));
    }
}

