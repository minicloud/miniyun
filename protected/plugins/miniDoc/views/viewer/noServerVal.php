<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title></title>
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
        }
        .show-background {
            width: 1920px;
            height:1080px;
            margin: auto;
        }
        .video-content{
            position:relative;
            top:-610px;
            text-align: center;
        }
    </style>
</head>
<body>
<div>
    <div>
        <img id="show-background" class="show-background" src="<?php echo $this->module->assetsUrl ?>/images/warning.jpg"/>
    </div>
    <div class="video-content">
        <a rel="nofollow" class="external text content-main" href="http://v.youku.com/v_show/id_XNjU1NDUwMDg4.html">
            <img id="show-video" class="show-video" src="http://help.miniyun.cn/images/3/33/Video.jpg" alt="Video.jpg">
        </a>
    </div>
</div>
<script type="text/javascript">
    var t = document.getElementById("show-background");
    var sv = document.getElementById("show-video");
    t.style.width = document.body.clientWidth+"px";
</script>
</body>
</html>