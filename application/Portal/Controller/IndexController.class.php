<?php
namespace Portal\Controller;
use Common\Controller\HomebaseController; 
/**
 * 
 */
class IndexController extends HomebaseController {
	
    
	public function index() {
		echo "<script>window.location.href='/admin'</script>";
    	//$this->display(":index");
    }

}


