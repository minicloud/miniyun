/**
 * Created by pc on 13-11-12.
 */
var $id = function (id) {
    return document.getElementById(id);
};
function crossDomainAjax (url, successCallback) {

    // IE8 & 9 only Cross domain JSON GET request
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {

        var xdr = new XDomainRequest(); // Use Microsoft XDR
        xdr.open('get', url);
        xdr.onload = function () {
            var dom  = new ActiveXObject('Microsoft.XMLDOM'),
                JSON = $.parseJSON(xdr.responseText);

            dom.async = false;

            if (JSON == null || typeof (JSON) == 'undefined') {
                JSON = $.parseJSON(data.firstChild.textContent);
            }

            successCallback(JSON); // internal function
        };
        xdr.onprogress = function() {};
        xdr.onerror = function() {
            _result = false;
        };

        xdr.send();
    }

    // IE7 and lower can't do cross domain
    else if (navigator.userAgent.indexOf('MSIE') != -1 &&
        parseInt(navigator.userAgent.match(/MSIE ([\d.]+)/)[1], 10) < 8) {
        return false;
    }

    // Do normal jQuery AJAX for everything else
    else {
        $.ajax({
            url: url,
            cache: false,
            dataType: 'json',
            type: 'GET',
            async: false, // must be set to false
            success: function (data, success) {
                successCallback(data);
            }
        });
    }
}
window.onload = function () {
    var url = $id("content-url").value;
    crossDomainAjax(url, function (data) {
        fileList = data;
        if (checkStatus(fileList)) {
            if(fileList.length>0){
                preloadingIframe(fileList[0]);
            }
        }
    });
};
/**
 * 如目标服务器不存在目标数据，则进行跳转以便第二次转换
 * @param items
 * @returns {boolean}
 */
var checkStatus = function(items){
    if(success(items)){
        return true;
    }
    var key = "&action=again";
    var url = window.location.href;
    //避免不断循环
    if(url.indexOf(key)<0){
        window.location.href=window.location.href+"&action=again";
    }
    return false;
}
/**
 * 判断是否执行成功
 * @param items
 * @returns {boolean}
 */
var success = function (items) {
    if(items.length==0){
        return false;
    }
    return true;
};
/**
 * iframe 预加载
 * @param url
 */
function preloadingIframe(url) {
    var suffix               = data.type;
    iframe                   = document.createElement('iframe');
    iframe.id                = 'document';
    iframe.name              = 'document';
    iframe.scrolling         = 'no';
    iframe.allowtransparency = 'true';
    iframe.height            = "100%";
    iframe.width             = "100%";
    iframe.src               = url;
    iframe.setAttribute("frameborder","0");//去掉IE下iframe边框
    if (suffix=='xls' || suffix == 'xlsx') {
        $("#canvas").css("width", '90%');
    } else if (suffix=='ppt' || suffix == 'pptx') {
        $("#canvas").css("width", '960px');
    }
    $(iframe).load( function() {
        var  height = 0;
        if(document.all) var offset = 1400;
        else var offset = 220;
        if (suffix=='xls' || suffix == 'xlsx') {
            height = $(this).contents().find('frameset').height();
        }
        else if (suffix=='ppt' || suffix == 'pptx') {
            offset = 0;
            height = $(this).contents().find('body').height();
        } else {
            height = $(this).contents().find('body').height();
        }
        $("#canvas").css("height", height + offset);
        iFrameHeight();
    });

    $("#canvas").append(iframe);
    $(iframe).attr("allowFullScreen", "allowFullScreen");
    $(iframe).attr("webkitallowfullscreen", "webkitallowfullscreen");

    $("#canvas").css("display", "block");
}