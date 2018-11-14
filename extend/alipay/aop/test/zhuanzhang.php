<?php
include('../AopClient.php');
include('../request/AlipayFundTransToaccountTransferRequest.php');
header("Content-type: text/html; charset=utf-8");
$aop = new AopClient ();
$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
$aop->appId = '2017100709182284';
$aop->rsaPrivateKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxc6/Id/cb653LZ9Ky+1+PrgzSL+/XoR+l88VCAzYP+2wymTpv6nJgn2e5h7N/lwHbDo3p7LbnVucXRgWmVMfh1ANNEDoXA1moCw/DlqiCYowMd/wkZ797Pc6PmwpflchQt5UF7WTW0yN7hAlM/5B+S2r2K5Xasa91g+AmKM1viGoNtIW9flFnCDaysBgw3U8dD1W6xJ5BhWSdhUiF08/VPkY6S72zTfsud6jwc5fYnXYYxjRPg/56JLutcO8yPFCX6ofR2PtQDQaes2r4yoe0G7N4y2CmlaC396T0FzSwmVbRJrM2gGHUoXpU4h8NvbaO/42Fed7V5LuHLl+CXmBswIDAQAB';
$aop->alipayrsaPublicKey='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlnEHZIvYNsQ8u0QJSLjKywEpohZJ6NOy0OsS6DNc+Ig2XE+WEUPksUMEcaLYqEkOxGUyBXeeGUf+9jnWP6BFo+N/tJuu3lpS3EClYFHGJCVuA2b9RSE3B7zcVBwpFCFAnChJJc8UKgbA4wCgqmjzWfe/tBGLE326Hjkm0nRKhslRO45L0K0YxmP91SKRyBm9wAtjqIDela4Jq+thSZNcMMXkOGYWi9BJhR5kJSJaPRum+qegG5huALBIvrzbmFieYydivR3BH5TGDGgZs0X7wtq0RWLEqMG+7bkrS7k9B7IKlorjT2Rf0HU+29rETOmlKireqaatKdEE3ftLzLFxuwIDAQAB';
$aop->apiVersion = '1.0';
$aop->signType = 'RSA2';
$aop->postCharset='utf-8';
$aop->format='json';
$request = new AlipayFundTransToaccountTransferRequest ();
/*
$arr = array('out_biz_no'=>'123456789',				
			 'payee_type'=>'ALIPAY_LOGONID',
			 'payee_account'=>'strong0503@163.com',
			 'amount'=>'0.1',
			 'payer_show_name'=>'测试转账',
			 'payee_real_name'=>'李壮',
			 'remark'=>'测试转账备注');
$canshu = json_encode($arr,JSON_UNESCAPED_UNICODE);
*/

$request->setBizContent("{" .
"\"out_biz_no\":\"123456\"," .
"\"payee_type\":\"ALIPAY_LOGONID\"," .
"\"payee_account\":\"strong0503@163.com\"," .
"\"amount\":\"0.1\"," .
"\"payer_show_name\":\"测试转账\"," .
"\"payee_real_name\":\"李壮\"," .
"\"remark\":\"测试转账备注\"" .
"}");
//$request->setBizContent($canshu);
$result = $aop->execute ( $request); 
var_dump($result);die;
$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
$resultCode = $result->$responseNode->code;
if(!empty($resultCode)&&$resultCode == 10000){
echo "成功";
} else {
echo "失败";
}
