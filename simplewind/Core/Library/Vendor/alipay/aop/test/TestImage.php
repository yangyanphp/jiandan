<?php
/**
 * Alipay.com Inc.
 * Copyright (c) 2004-2014 All Rights Reserved.
 */



include('../AlipayMobilePublicMultiMediaClient.php');

header("Content-type: text/html; charset=utf-8");

/**
 *
 * @author wangYuanWai
 * @version $Id: Test.hp, v 0.1 Aug 6, 2014 4:20:17 PM yikai.hu Exp $
 */
class TestImage{


	public $partner_public_key  = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxc6/Id/cb653LZ9Ky+1+PrgzSL+/XoR+l88VCAzYP+2wymTpv6nJgn2e5h7N/lwHbDo3p7LbnVucXRgWmVMfh1ANNEDoXA1moCw/DlqiCYowMd/wkZ797Pc6PmwpflchQt5UF7WTW0yN7hAlM/5B+S2r2K5Xasa91g+AmKM1viGoNtIW9flFnCDaysBgw3U8dD1W6xJ5BhWSdhUiF08/VPkY6S72zTfsud6jwc5fYnXYYxjRPg/56JLutcO8yPFCX6ofR2PtQDQaes2r4yoe0G7N4y2CmlaC396T0FzSwmVbRJrM2gGHUoXpU4h8NvbaO/42Fed7V5LuHLl+CXmBswIDAQAB";
	public $alipay_public_key   = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB";
		//公用变量
	public $serverUrl = 'http://publicexprod.d5336aqcn.alipay.net/chat/multimedia.do';//'http://publicexprod.d5336aqcn.alipay.net/chat/multimedia.do';//'http://i.com/works/photo-sdk/_data/1.jpg';//"http://i.com/works/photo-sdk/_data/publicexprod.php";//"http://publicexprod.d5336aqcn.alipay.net/chat/multimedia.do";
	public $appId = "2017100709182284aaa";

	public $partner_private_key = 'MIIEogIBAAKCAQEAxc6/Id/cb653LZ9Ky+1+PrgzSL+/XoR+l88VCAzYP+2wymTpv6nJgn2e5h7N/lwHbDo3p7LbnVucXRgWmVMfh1ANNEDoXA1moCw/DlqiCYowMd/wkZ797Pc6PmwpflchQt5UF7WTW0yN7hAlM/5B+S2r2K5Xasa91g+AmKM1viGoNtIW9flFnCDaysBgw3U8dD1W6xJ5BhWSdhUiF08/VPkY6S72zTfsud6jwc5fYnXYYxjRPg/56JLutcO8yPFCX6ofR2PtQDQaes2r4yoe0G7N4y2CmlaC396T0FzSwmVbRJrM2gGHUoXpU4h8NvbaO/42Fed7V5LuHLl+CXmBswIDAQABAoIBAG/+EybpqNOucplo858r8msuPRL2RTINT+NBgoXRR+pB9/NPWSyDw6xW1U0yqaxWjHlRKMuQr1iTUp8+kSTz9AjXYPJxwVzE1ZLgGj9VPyKpk4nZYUoBO4EbpGVNNdzrFdDXMcNOsYRifoUHrRiX3uBmZEJEyweQSFDW7Bb/aOEhDEZZVcsgq2zYA/qhOYjYthMu56RMlq5squjxPxZq241EnGKKFatOQbQSfpSlO8wPwLChYltfhTEQPNsiQgVhFeqYvIGJ/EppaUSvTGBhovoA1LDGX/rGC9v3kaFuBmuTizwUHqks6MuqY+YEWjuZa/6GJGBqRm8Bd2z0hwCLXtkCgYEA5v5OqTFjVWCiy9b6ZYCvuOchx52uNLissrxhqAQXYuV7n9nk4lpgehZTOh1O3zEVi8uuhSj0ysrYAtXGzvqZLDjD28n5+EP1CuW8ze7aFIOh3LsNBgRiEaCmF3SpSppzfyAg26C9eL/BTG0LcYARQfjxijj/Chzw6xmguz/PL4UCgYEA2zi8oxp//qQ99vq/R/XV1pemZXojokj8GoDpWoUSTSDihCAO9T9BeEtF53qJnLSwAIfO86X8fCgzTTcVoKOxDvG/1WbyPaV9O6eUbhq97G2oEvWWuq+JlL50j6RRuJCGc8rmVvBJn/kR9R6QhhwF2hwNsXDN/AAbQDoeiTLPBdcCgYABxsLkGjUhWvTljGmwjiTLzzs7RcMpNKJfXYOCv0VOxWUF/a787qT/S1yofcE1hjMcOBzHeWEojhbLLsmwncy/wk//SLZbampgAxRIoSWcMsbz45xeB3qZmBqu437JzBZHS//0sG/ElfTumYgU/18imr+AyJ9tfHt901yhFASL4QKBgDtX/5CzosR/F5sdQ9yqCuodgVjlGHJdCYnvLbxoW4zvgwI7X3E6X3G+Br1j6Y63RxHNsdM5MsE1bLXRXJRw24RtIv4U8SyI+P7GHaM0sAcppB8Fxnjg/gB7Eji0Rb8NuKft3C7au0OH/Dl7vangOiFSCM7o94npSXc4hH2leG9fAoGAPZ/0OU90rUOf1x5JhePa0OEPfdeJhBkM/zpLZDWQz5zxTeXz6rTwKC15o/yYIahO0scUzjqc4G0Dw2RirTXzqm0LtbyQffo814dVl93KRfNEhwDV8VwatPPMn1NCasZ+snp8BZthjEVL5Rsuo7w1igHxQlPh0wQV0hntU+7VqqY=';

	public $format = "json";
	public $charset = "utf-8";


	function __construct(){

	}

	public function load() {
		$alipayClient = new AlipayMobilePublicMultiMediaClient(
			$this -> serverUrl,
			$this -> appId,
			$this -> partner_private_key,
			$this -> format,
			$this -> charset
		);
		$response = null;
		$outputStream = null;
		$request = $alipayClient -> getContents() ;

		//200
		//echo( '状态码：'. $request -> getCode() .', ');
		//echo '<hr /><br /><br /><br />';

		$fileType = $request -> getType();
		//echo( '类型：'. $fileType .', ');
		if( $fileType == 'text/plain'){
			//出错，返回 json
			echo $request -> getBody();

		}else{

			$type = $request -> getFileSuffix( $fileType );

			//echo $this -> getParams();
			//exit();

			//返回 文件流
			header("Content-type: ". $fileType ); //类型


			header("Accept-Ranges: bytes");//告诉客户端浏览器返回的文件大小是按照字节进行计算的
			header("Accept-Length: ". $request -> getContentLength() );//文件大小
			header("Content-Length: ". $request -> getContentLength() );//文件大小
			header('Content-Disposition: attachment; filename="'. time() .'.'. $type .'"'); //文件名
			echo $request -> getBody() ;
			exit ( ) ;
		}

		//echo( '内容： , '. $request -> getContentLength()  );

		//echo '<hr /><br /><br /><br />';
		//echo  '参数：<pre>';

		//echo ($request -> getParams());

		//echo '</pre>' ;
	}
	
	//查询转账订单接口
	function getOrder(){
		$aop = new AopClient ();
		$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
		$aop->appId = 'your app_id';
		$aop->rsaPrivateKey = '请填写开发者私钥去头去尾去回车，一行字符串';
		$aop->alipayrsaPublicKey='请填写支付宝公钥，一行字符串';
		$aop->apiVersion = '1.0';
		$aop->signType = 'RSA2';
		$aop->postCharset='GBK';
		$aop->format='json';
		$request = new AlipayFundTransOrderQueryRequest ();
		$request->setBizContent("{" .
				"\"out_biz_no\":\"3142321423432\"," .
				"\"order_id\":\"20160627110070001502260006780837\"" .
				"  }");
		$result = $aop->execute ( $request);
		
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		if(!empty($resultCode)&&$resultCode == 10000){
			echo "成功";
		} else {
			echo "失败";
		}
	}
	
}





//  测试
$test1 = new TestImage();
$test1 -> load();
