function getCookie(name) {
    var r = document.cookie.match("\\b" + name + "=([^;]*)\\b");
    return r ? r[1] : undefined;
}
/**
 * @param response json返回值
 */
function successCallback(response)
{
    if (response.status && response.uuid!=null) {
        if(response.progress==100){
            //当进度100的时候，说明miniDoc准备好了所有的数据
            //网页直接浏览即可
            var host = data.callback+"/success";
            $.ajax({
                url      : host,
                type     : "POST",
                dataType : "json",
                data     : {"hash":data.hash,"miniDocServerId":$("#mini-doc-server-id").val()}
            }).done(function(msg) {
                var key = "&action=again";
                var url = window.location.href;
                url = url.replace(key,"");
                window.location.href=url;
            });
            return;
        }else{
            if (response.progress > 0 && response.progress >  $("#progress").attr("aria-valuenow")) {
                $("#progress").attr("aria-valuenow", response.progress );
                $("#progress").css("width", response.progress +"%");
            }
            window.setTimeout(function(){Convert.poll(response.uuid);}, 0);
        }

    } else {
        Convert.onError();
    }
}
var Convert = {
    cursor: null,
    fetch: function(args,convertUrl) {
        args._xsrf = getCookie("_xsrf");
        if (Convert.cursor) args.cursor = Convert.cursor;
        crossDomainAjax(convertUrl,args);
    },

    poll: function(uuid) {
        var args = {"_xsrf": getCookie("_xsrf")};
        if (Convert.cursor) args.cursor = Convert.cursor;
        var subUrl = $("#sub-url").val()
        crossDomainAjax(subUrl + uuid,args);
    },
    beforeSend: function() {
        $("#mask").css("display", "block");
        $("#loading-msg").text(T.MiniDocModule.data_loading);
    },
    onSuccess: function(response) {
        successCallback(response);
    },
    onError: function() {
        var type = $("#convert-type").val();
        if(type=="zip" || type=="rar"){
            $("#canvas").css("display", "none");
        }
        $("#loading-msg").text(T.MiniDocModule.converted);
        $("#loading-msg").addClass("tip");
        $("#progress").attr("aria-valuenow", 0 );
        $("#progress").css("width", "0%");
    },
    onErrorNoServer: function() {
        alert(T.MiniDocModule.noValidServer);
        $("#canvas").css("display", "none");
        $("#loading-msg").text(T.MiniDocModule.noValidServer);
        $("#loading-msg").addClass("tip");
        $("#progress").attr("aria-valuenow", 0 );
        $("#progress").css("width", "0%");
    }
}
function crossDomainAjax(url,args) {

    // IE8 & 9 only Cross domain JSON GET request
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        Convert.beforeSend();
        var xdr = new XDomainRequest();
        xdr.open('post', url);
        xdr.onload = function () {
            Convert.onSuccess($.parseJSON(xdr.responseText));
        };
        xdr.onprogress = function() {};
        xdr.onerror = function() {
            Convert.onError();
        };
        xdr.send($.param(args));

    }else{
        $.ajax({
            url: url,
            cache: false,
            dataType: 'json',
            type: 'post',
            data: $.param(args),
            beforeSend: Convert.beforeSend,
            success: Convert.onSuccess,
            error: Convert.onError
        });
    }
}
$(document).ready(function() {

    var host = data.callback+"/server";
    $.ajax({
        url      : host,
        type     : "POST",
        dataType : "json"
    })
        .done(function(msg) {
            // body高度和宽度获取
            var height = $(window).height();
            var width  = $(window).width();
            $("#mask").css("width", width);
            $("#mask").css("height", height);

            if(msg.success==false){
                Convert.onErrorNoServer();
            }else{
                var convertUrl = "http://"+msg.host+'/api/1/' + data.type + '/' +data.hash
                var subUrl = "http://"+msg.host+'/api/1/sub/'
                $("#sub-url").val(subUrl)
                $("#mini-doc-server-id").val(msg.id)
                // 请求转换
                Convert.fetch(data,convertUrl);
                // 重新计算大小
                $(window).resize(function() {
                    // body高度和宽度获取
                    var height = $(window).height();
                    var width  = $(window).width();
                    $("#mask").css("width", width);
                    $("#mask").css("height", height);
                });
            }

        })
});