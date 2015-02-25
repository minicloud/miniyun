var miniApi = require('./miniApi.js');
var host = "http://t.miniyun.cn";
miniApi.login(host,"admin","admin",function(){
    //文件秒传 
    var remotePath = "/1/package.json";
    var localPath = "./package.json";
    var fs = require('fs');
    var crypto = require('crypto');
    //计算文件sha1值
    fs.open(localPath, 'r',function(err, fd){
        fs.stat(localPath, function(err, stats){
            //异步方式计算文件内容的signature
            var shasum = crypto.createHash('sha1');
            var stream = fs.ReadStream(localPath);
            stream.on('data', function(d) {
                shasum.update(d);
            });
            stream.on('end', function() {
                var signature = shasum.digest('hex');
                //秒传
                var data = {
                     params:{
                         route:"file/sec",
                         path:remotePath,
                         signature:signature,
                         size:stats.size
                     },
                     success:function(secInfo){
                        if(secInfo.success==false){
                            //判断存储类型
                            var storeType = secInfo.store_type;
                            if(typeof storeType!='undefined' && storeType=="miniStore"){
                                //迷你存储
                                var url = secInfo.url;//文件上传地址是秒传接口返回的服务器地址来决定
                                var params = {
                                    localPath:localPath,
                                    signature:signature,
                                    size:stats.size,
                                    callback:secInfo.callback,
                                    nodeAccessToken:secInfo.node_access_token,
                                    accessToken:miniApi.accessToken,
                                    remotePath:remotePath,
                                    success:function(){
                                        console.log(localPath+" upload success");
                                    },
                                    error:function(info){
                                        console.log(localPath+" upload error,"+info);
                                    }
                                };
                                miniApi.upload2MiniStore(url, params);
                            }
                        } 
                     },
                     error:function(data){
                         console.log("error"+JSON.stringify(data));
                     }
                };
                //文件秒传
                miniApi.postRequest(data);
            });
        });
    }); 
},function(error){

});
