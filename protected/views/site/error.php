<html>
<head>
    <meta http-equiv="CONTENT-TYPE" content="text/html;charset=utf-8" />
    <title>迷你云访问出错</title>
</head>
<style type="text/css">
    #tip-body{
        margin: 150px auto;
        width:600px;
    }
    #tip-title{
        font-size: 30px;
        color:#ffa800;
    }
    .gray{
        color:#656565;
    }
</style>
<body>
<div id="tip-body">
    <img src="<?php echo MiniHttp::getMiniHost()?>statics/images/tip-browse.png?v=<?php echo(Yii::app()->params['app']['version'])?>"  style="margin-left:120px;"/>
    <p id="tip-title">尊敬的用户，迷你云访问出现了问题</p>
    <div class="gray" style="font-size: 24px;">请不要着急，请按下面方法一步一步检测</div>
    <p class="gray"><img src="<?php echo MiniHttp::getMiniHost()?>statics/images/circle.png?v=<?php echo(Yii::app()->params['app']['version'])?>" width="12px"/><span style="font-size: 18px;">&nbsp;1.请确认，本机浏览器能正常访问http://statics.miniyun.cn</span></p>
    <p class="gray"><img src="<?php echo MiniHttp::getMiniHost()?>statics/images/circle.png?v=<?php echo(Yii::app()->params['app']['version'])?>" width="12px"/><span style="font-size: 18px;">&nbsp;2 请管理员帮助确认：</span></p>
    <ol type="a" class="gray">
        <li>{protected/runtime}无写的权限</li>
        <li>Mysql数据库无法链接</li>
    </ol>
</div>
</body>
</html>
