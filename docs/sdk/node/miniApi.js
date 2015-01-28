var miniApi={
    //appKey，由管理员事先分配好
    appKey:"d6n6Hy8CtSFEVqNh",
    appSecret:"e6yvZuKEBZQe9TdA",
    accessToken:"",
    host:"",
    /**
     * 密码DES加密
     * @param password
     * @returns {string}
     */
    encrypt:function(password){
        var crypto = require('crypto');
        var key  = new Buffer(miniApi.appKey.substr(0,8));
        var des = crypto.createCipheriv("des", key, key);
        des.update(password, "utf8", "base64");
        var encryptedStr =des.final('base64');
        var hex="";
        for(var i=0;i<encryptedStr.length;i++){
            hex+=(encryptedStr[i].charCodeAt(0)).toString(16) ;
        }
        return hex.toUpperCase();
    },
    /**
     * 对地址签名
     * @url 迷你云接口服务器地址
     */
    sign:function(url){
        var urlInfo = require('url').parse(url);
        var host = urlInfo.host;
        var port = urlInfo.port;
        if(port == null){
            port = 80;
        }
        var param = {
            access_token:miniApi.accessToken,
            client_id:miniApi.appKey,
            client_secret:miniApi.appSecret
        };
        var crypto = require('crypto');
        var url = encodeURI(host + ':' + port + urlInfo.path + '?' + require('querystring').stringify(param));
        return crypto.createHash('md5').update(url).digest("hex");
    },
    /**
     * 以POST方式访问服务器接口
     * @param route
     * @param requestData
     */
    postRequest:function(requestData){
        //拼接要访问的服务器地址
        var url = miniApi.host+"/api.php"; 
        var urlInfo = require('url').parse(url);
        //如果不是登录操作，需补充sign与access_token参数
        if(requestData.params.route!="user/oauth2"){
            requestData.params.sign = miniApi.sign(url);
            requestData.params.access_token=miniApi.accessToken;
        }
        var postData = require('querystring').stringify(requestData.params);
        var http = require('http');
        var https = require('https');
        var protocol = {
        "http:":http,
        "https:":https
        };
        var httpOptions = {
            host:urlInfo.hostname,
            port:urlInfo.port,
            method:'POST',
            path:urlInfo.path,
            headers:{
                'Content-Type': 'application/x-www-form-urlencoded',
                'Content-Length': postData.length
            }
        }; 
        var postRequest = protocol[urlInfo.protocol].request(httpOptions, function(res) {
            res.setEncoding('utf8');
            res.on('data', function (chunk) {
                //异步方式返回值
                requestData.success(JSON.parse(chunk.toString()));
            });
            res.on('end',function(){
                if(res.statusCode!=200){ 
                    requestData.error(res.statusMessage);
                }                       
            });
        });
        postRequest.write(postData);
        postRequest.end();
    },
    /**
     * 以GET方式访问服务器接口 
     * @param requestData
     */
    getRequestUrl:function(requestData){
        //拼接要访问的服务器地址
        var url = miniApi.host+"api.php"; 
        //如果不是登录操作，需补充sign与access_token参数
        if(requestData.params.route!="user/oauth2"){
            requestData.params.sign = miniApi.sign(url);
            requestData.params.access_token=miniApi.accessToken;
        }
        return url+"?"+require('querystring').stringify(requestData.params);
    },
    /**
     * 登录
     * @param host
     * @param name
     * @param password
     * @param success
     * @param error
     */
    login:function(host,name,password,success,error){
        //设置迷你云的host
        miniApi.host = host; 
        var requestData = {
            params:{
                route:"user/oauth2",
                username:name,
                password:miniApi.encrypt(password),
                device_type:2,
                device_name:'third',
                device_info:name+"third",
                grant_type:'password',
                client_id:miniApi.appKey,
                client_secret:miniApi.appSecret
            },
            success:function(data){
                console.log(data);
                miniApi.accessToken = data.access_token;
                success(data);
            },
            error:function(data){ 
                console.log(data);
                error(data);
            }
        };
        miniApi.postRequest(requestData);
    },
};
module.exports = miniApi;