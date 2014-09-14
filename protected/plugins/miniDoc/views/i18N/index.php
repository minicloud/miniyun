<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
var Mt = {};
Mt = {
    t: function(str,replace){
    for(key in replace){
        str = str.replace(key, replace[key]);
    }
    return str;
    }
}

if(T==null){
    var T = {};
}

T.MiniDocModule = {};

<?php $messages = CUtils::inc($this->module, 'js_messages');
foreach ($messages as $key => $value) : ?>
    T.MiniDocModule.<?php echo $key?> = "<?php echo $value?>";
<?php endforeach;?>
