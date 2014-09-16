/**
 * Created with JetBrains WebStorm.
 * User: miniyun
 * Date: 13-11-7
 * Time: 下午3:33
 * To change this template use File | Settings | File Templates.
 */



//自适应
function iFrameHeight() {
    var ifm = document.getElementById("document");
    var subWeb = document.frames ? document.frames["document"].document : ifm.contentDocument;
    if (ifm != null && subWeb != null) {
        ifm.height = subWeb.body.scrollHeight;
    }
}


var reduceobj = document.getElementById("reduce-btn");
var nobj = document.getElementById("numobj");
nobj.innerHTML = "100%";        //右下角百分数
var amplifyobj = document.getElementById("amplify-btn");
var oCss = document.styleSheets[1].cssRules || document.styleSheets[1].rules;
var i = 1;


//缩小文档
reduceobj.onclick = function () {
    if (parseInt(oCss[32].style.left) > 45) {
        oCss[32].style.left = parseInt(oCss[32].style.left) - 2 + "px";
    } else if (parseInt(oCss[32].style.left) > 0) {
        oCss[32].style.left = parseInt(oCss[32].style.left) - 5 + "px";
    }
    var j = i - 0.1;
    if (j <= 0.1) {
        j = 0.1;
    }
    //判断浏览器类型
    ua = navigator.userAgent;
    ua = ua.toLocaleLowerCase();
    if (ua.match(/msie/) != null || ua.match(/trident/) != null) {
        browserType = "IE";
        browserVersion = ua.match(/msie ([\d.]+)/) != null ? ua.match(/msie ([\d.]+)/)[1] : ua.match(/rv:([\d.]+)/)[1];//检测IE11
        if(navigator.userAgent.indexOf("MSIE 8.0")>0){
            document.getElementById("canvas").style.marginTop = document.getElementById("canvas").offsetTop+'px';
            var ow = document.getElementById("canvas").style.marginLeft;
            ow = document.getElementById("canvas").offsetLeft+ow/2+'px';
            document.getElementById("canvas").style.zoom = j;
        }
        document.getElementById("canvas").style.transform = "scale(" + j + ")";
        document.getElementById("canvas").style.transformOrigin = "top";
        document.getElementById("canvas").style.msTransform = "scale(" + j + ")";
        document.getElementById("canvas").style.msTransformOrigin = "top";
    } else if (ua.match(/firefox/) != null) {
        document.getElementById("canvas").style.transform = "scale(" + j + ")";
        document.getElementById("canvas").style.transformOrigin = "top";
    } else{
        document.getElementById("canvas").style.webkitTransform = "scale(" + j + ")";
        document.getElementById("canvas").style.webkitTransformOrigin = "top";
    }
    nobj.innerHTML = parseInt(j * 100) + "%";
    return i = j;
};


//放大文档
amplifyobj.onclick = function () {
    if (parseInt(oCss[32].style.left) < 45) {
        oCss[32].style.left = parseInt(oCss[32].style.left) + 5 + "px";
    } else if (parseInt(oCss[32].style.left) < 90) {
        oCss[32].style.left = parseInt(oCss[32].style.left) + 2 + "px";
    }
    var j = i + 0.1;
    if (j >= 3.4) {
        j = 3.3;
    }
    //判断浏览器类型
    ua = navigator.userAgent;
    ua = ua.toLocaleLowerCase();
    if (ua.match(/msie/) != null || ua.match(/trident/) != null) {
        browserType = "IE";
        browserVersion = ua.match(/msie ([\d.]+)/) != null ? ua.match(/msie ([\d.]+)/)[1] : ua.match(/rv:([\d.]+)/)[1];//检测IE11
        if(navigator.userAgent.indexOf("MSIE 8.0")>0){
            document.getElementById("canvas").style.marginTop = document.getElementById("canvas").offsetTop+'px';
            var ow = document.getElementById("canvas").style.marginLeft;
            ow = document.getElementById("canvas").offsetLeft+ow/2+'px';
            document.getElementById("canvas").style.zoom = j;
        }
        document.getElementById("canvas").style.transform = "scale(" + j + ")";
        document.getElementById("canvas").style.transformOrigin = "top";
        document.getElementById("canvas").style.msTransform = "scale(" + j + ")";
        document.getElementById("canvas").style.msTransformOrigin = "top";
    } else if (ua.match(/firefox/) != null) {
        document.getElementById("canvas").style.transform = "scale(" + j + ")";
        document.getElementById("canvas").style.transformOrigin = "top";
    } else {
        document.getElementById("canvas").style.webkitTransform = "scale(" + j + ")";
        document.getElementById("canvas").style.webkitTransformOrigin = "top";
    }
    nobj.innerHTML = parseInt(j * 100) + "%";
    return i = j;
};