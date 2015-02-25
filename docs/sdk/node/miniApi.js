var querystring = require('querystring');
var http = require('http');
var fs = require('fs');

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
                console.log(chunk.toString());
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
        var url = miniApi.host+"/api.php"; 
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
    /**
     * 上传文件到迷你存储
     * @param url 迷你存储地址
     * @param param  参数
     */
    upload2MiniStore:function(url, param){
        var params ={
                        route:"file/sec",
                        signature:param.signature, 
                        size:param.size,
                        callback:param.callback, 
                    }; 
        var urlInfo = require('url').parse(url);
        //发送请求到迷你存储
        var postData = require('querystring').stringify(params);
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
                var secInfo = JSON.parse(chunk.toString()); 
                var data = {
                    "localPath":param.localPath,
                    "offset":secInfo.offset,
                    "size":param.size,
                    "accessToken":param.accessToken,
                    "remotePath":param.remotePath,
                    "signature":param.signature,
                    "uploadedSize":0,
                    "callback":param.callback
                };
                miniApi.uploadLoop(url,data); 
            });
            res.on('end',function(){
                if(res.statusCode!=200){ 
                    
                }                       
            });
        });
        postRequest.write(postData);
        postRequest.end();
    },
    /**
     * 递归方式上传文件
     * @param url 迷你存储地址
     * @param param  参数
     */
    uploadLoop:function(url, param){ 
        //以4M为单位，进行文件上传
        var blockSize = 4*1024*1024;
        var end = param['offset']+blockSize-1;

        //判断是否新上传的文件块超出了文件的大小
        if(end>(param['size']-1)){
            end = (param['size']-1);
        }
        if(param['offset']>=end){
            //文件上传成功
            var successCallback = param['success'];
            if(typeof successCallback!='undefined'){
                successCallback();
            }
            return;
        } 
        //调用httpClient，上传文件
        var httpClient = require('./httpClient.js');
        var urlParam = {//提交的元数据结构
                route:'file/upload',
                access_token: param['accessToken'], 
                path: param['remotePath'],
                signature: param['signature'],
                size: param['size'],
                offset: param['offset'],
                callback:param["callback"],
                sign:miniApi.sign(miniApi.host+"/api.php"),
            };
        var options = {
            url: url+"?"+querystring.stringify(urlParam),
            file:{
                data:fs.createReadStream(param.localPath, {
                    start:param['offset'],
                    end:end,
                }), 
            },
            progress: function(chunk){                
                param['uploadedSize'] +=chunk;
                var percent = param.uploadedSize*100/param.size;
                console.log(percent+"% "+param.localPath);
            },
            success:function(data){
                console.log(data);
                //递归上传第二个4M文件
                param['offset'] += blockSize;
                //这里是反向推出来的，每一块文件上传成功后把已经上传的大小减1
                param['uploadedSize'] --;
                miniApi.uploadLoop(url, param);
            },
            error: function(data){
                console.log(data);
                //文件上传失败
                var errorCallback = param['error'];
                if(typeof errorCallback!='undefined'){
                    errorCallback(data);
                }
            }, 
        };
        httpClient(options);
    }
};
module.exports = miniApi;