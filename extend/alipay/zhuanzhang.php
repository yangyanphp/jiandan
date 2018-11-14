<?php
include('AopSdk.php');
$aop = new AopClient ();
$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
$aop->appId = '2017100709182284';
$aop->rsaPrivateKey = 'MIIEpAIBAAKCAQEAxDaA/1gyIkMHPHpWzzeLavmdmBuZ2XtPzHXr5zxpi+SZFUKZxOStC9rxHSakdOuMuP8QFuAO1kS9HMQ0XV8nO41n/84Ov1Qwhr8irLgJXtQUnoq7QN48KhYDhvMPdkjJtWEoGzvd70bacx69RBK3FublMPqlab/L1LP5eHv5qBH/08NxuqVLqAChuHTC4EnXSCxyFPsA8hOmiF4feaTvLDlashwDhUvjUNlBgkgTrgj/SP2+DuiqTDve8ulHTccK4keabXqUgB81KAQszsXNEglJSgl8TtDpHLZyuK8Cjr6v7vcrBeFGxsIUy6VZCqarg/zHxfXGP7RdA8v5lREioQIDAQABAoIBADoXVOvEZdtk8uCB6++fp0Q9sN3W1h7gdki3ZOdqKGmFfZkgxbvYZC9NW/NgfHItRtWClnXfUiU35rF8mXBHeqsT+4VtsUoOF+vc7NwsBIIx0gT6V+Qlp7RiHhs3HQ3NEQMFR8WAXP25gXVx1WExFUnPhG2S16ROZ3+K5UI5mjmazTH7nqtnuGVLNnVZUmPLRglLFtMdRf7NB4Lssel1qLb5K6wxzC+fOALPX5pNlScnh2IwQxDMNMXXL86Zddo1qdmlSmPVuc9zT672shN4hp8G9LicjNXR0TDrOJbZikfqOb9bvtrwXfTfoug5S0YVzM+m/iH7XR/i6VEujzZnGV0CgYEA+JUafMfAWcTxxuDo3hUKuJZqfvmuKjtxgVV+DQizPnR5Z3t2stjDgHBRLqBd+oCZ8eKzzdO0l8B9XKIzToZSI2saOd40Hy8PX9iROVlVMulxs8xTzY1qdZS3x8gKo0aDIHlz0D2v1QGK2k3bgoOiAYlU8brakeVSCRWnGIGsT7MCgYEAyhFa09hbOvDnTZFzVItQTO5odExJnjQG+08UuxUvxzfWSrkt60l31TOHZTq/AFLh0YQ3lRnEQqZlcS3m5tpiDKRFnSGDoSuunooC71BN8NWzab753MwNXcqQwaHpqhyRTGMWh28hvsDT2vunrC7yyabMVUWZ50w6BJuQwo8U+lsCgYEAlYcrPa/ydo1PWnBj42MI5ewk92g9ac4EAuZoQnLfT0xE0wijaAWX5CSr0L5Kiard73CM89zLHxV800IGVs/ZjNCaIAEXnUJznxXolXS1GUDvUlYwes78IOpqelRMgdaifeBQ2AyjPiAFZDe9OQ7xXrc7T4U0gNpOtIQ/1S/7dJ0CgYEAgwIBezvY2jv6GuZkebnhFB+2BUC4siNVK3Y4IJs54NWoz8WDqfp2APppnA4ca59Q3T/1sWuFPRkYx+pUu/N2gm+22osyBjqF+i/Me0/7WFuU+Mhiwu5g9CAy/fd1wV7ILVhI8QHyRPRL5rwmF5JQwsCr1dVMVROswfQCRMHzfeUCgYAF587LWGN3KYP8RZEXAa3xl2o9+imXezjVJPb2Q322CC3vdLB11N1l7aR7qzkJHsPnd5nq7A7HZYkFavHYdaoPvKjReC21ERvl35ofhL8yTYv6ycwQba1vIEsNNaV5g7QP0vi1a73vib6gAn2cpBfPVUJmiDPaLbkllg8XPr+vdQ==';
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
$time = time();
$request->setBizContent("{" .
"\"out_biz_no\":\"".$time."\"," .
"\"payee_type\":\"ALIPAY_LOGONID\"," .
"\"payee_account\":\"strong0503@163.com\"," .
"\"amount\":\"0.1\"," .
"\"payer_show_name\":\"测试转账\"," .
"\"payee_real_name\":\"李壮\"," .
"\"remark\":\"测试转账备注\"" .
"}");
//$request->setBizContent($canshu);
$result = $aop->execute ( $request);
$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
$resultCode = $result->$responseNode->code;
var_dump($resultCode);
