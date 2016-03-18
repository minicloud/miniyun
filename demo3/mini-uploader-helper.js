function MiniUploaderHelper() {

}
/**
 *获得cookie
 */
MiniUploaderHelper.prototype.getCookie = function(name) {
    //如果是PC客户端，则直接从全局变量获得内容
    if (this.isPcClient()) {
        var value = appInfo.cookie[name]
        if (typeof(value) == "undefined") {
            return ""
        }
        return value
    } else {
        var cookie = document.cookie
        if (cookie.length > 0) {
            var key = name + "="
            var start = cookie.indexOf(key)
            if (start != -1) {
                start = start + key.length
                var end = cookie.indexOf(";", start)
                if (end == -1) {
                    end = cookie.length
                }
                var value = cookie.substring(start, end)
                return decodeURI(value)
            }
        }
        return ""
    }
}
MiniUploaderHelper.prototype.isPcClient = function() {
        var agent = navigator.userAgent;
        return agent.indexOf("miniClient") > 0 ? true : false;
    }
    /**
     *HTTP请求
     */
MiniUploaderHelper.prototype.request = function(url, data, callback) {
        var xhttp = new XMLHttpRequest()
        xhttp.open('POST', url, true)
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState == 4) {
                if (xhttp.status == 200) {
                    //返回值
                    var result = JSON.parse(xhttp.responseText)
                    return callback(null, result)
                } else {
                    //返回错误
                    return callback({
                        status: xhttp.status,
                        message: xhttp.responseText
                    })
                }
            }
        }
        var token = data['access_token']
        if (!token) {
            //默认token源自cookie
            token = this.getCookie('accessToken')
            data['access_token'] = token
        }
        //把外部参数形成FormData
        var form = new FormData()
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                form.append(key, data[key])
            }
        }
        xhttp.send(form)
    }
    /**
     *文件上传前需要预先获得上传策略
     */
MiniUploaderHelper.prototype.start = function(host, path, callback) {
    var data = {
        path: path,
        route: 'upload/start'
    }
    var url = host + 'api.php'
    this.request(url, data, callback)
}
