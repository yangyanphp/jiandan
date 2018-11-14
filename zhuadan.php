<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>抓单页面</title>
    
</head>
<body>
	抓单页面，请勿关闭（减单APP）
</body>
<script typet="text/javascript" src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>
 <script>
    $(function(){

       setInterval(function(){check()}, 600000);  //十分钟查询一次订单
    })
    function check(){
        var ts = Math.round(new Date().getTime()/1000).toString();
           window.open("http://app.shop-d.cn/index.php?g=portal&m=member&a=getTaoBaoOrder&time="+ts, "_blank");
    }
</script>
</html>