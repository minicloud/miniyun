var miniApi = require('./miniApi.js');
var host = "http://t.miniyun.cn";
miniApi.login(host,"admin","admin",function(){
    var data = {
        params:{
            route:"module/miniDoc/list",
            page_size:10,
            mime_type:"application/msword",
            page:1,
        },
        success:function(data){
            console.log("success"+JSON.stringify(data));
        },
        error:function(data){
            console.log("error"+JSON.stringify(data));
        }
    };
    miniApi.request("/api.php",data);

});
