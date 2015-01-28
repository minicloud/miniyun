var miniApi = require('./miniApi.js');
var host = "http://t.miniyun.cn";
miniApi.login(host,"admin","admin",function(){
    // var data = {
    //     params:{
    //         route:"module/miniDoc/list",
    //         page_size:10,
    //         mime_type:"application/msword",
    //         page:1,
    //     },
    //     success:function(data){
    //         console.log("success"+JSON.stringify(data));
    //     },
    //     error:function(data){
    //         console.log("error"+JSON.stringify(data));
    //     }
    // };
    ////获得文档列表
    //miniApi.postRequest(data);
    //var data = {
    //        params:{
    //            route:"module/miniDoc/previewContent",
    //            path:"/1/test.doc",
    //            type:"png",
    //        }
    //    };
    // //获得文档封面图片地址，获得后可直接访问即可
    //console.log(miniApi.getRequestUrl(data));
});
