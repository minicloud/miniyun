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
    <p class="gray"><img src="<?php echo MiniHttp::getMiniHost()?>statics/images/circle.png?v=<?php echo(Yii::app()->params['app']['version'])?>" width="12px"/><span style="font-size: 18px;">&nbsp;1.请确认，本机浏览器能正常访问<a href="http://statics.miniyun.cn" target="_blank">http://statics.miniyun.cn</a></span></p>
    <p class="gray"><img src="<?php echo MiniHttp::getMiniHost()?>statics/images/circle.png?v=<?php echo(Yii::app()->params['app']['version'])?>" width="12px"/><span style="font-size: 18px;">&nbsp;2.请管理员帮助确认下面2个问题：</span></p>
    <ul  class="gray">
        <li>{protected/runtime}需要有写权限</li>
        <li>Mysql数据库正常启动</li>
    </ul>
    <p class="gray"><img src="<?php echo MiniHttp::getMiniHost()?>statics/images/circle.png?v=<?php echo(Yii::app()->params['app']['version'])?>" width="12px"/><span style="font-size: 18px;">&nbsp;3.如上述2个方法还没有成功，请访问社区。举报问题，快速解决。<a target="_blank" href="http://bbs.miniyun.cn/forum.php?mod=viewthread&tid=2&extra=page%3D1">点击访问迷你云社区>></a></span></p>
</div>
</body>
</html>
