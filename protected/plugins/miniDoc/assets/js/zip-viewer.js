currentPage = 0;
totalSize = 0;
$(document).ready(function() {
    loadData("");
    $("#page-previous").click(function() {
        if(currentPage<=0){
            return;
        }
        currentPage--;
        loadData($("#current-path").val());
    });
    $("#page-after").click(function() {
        if(currentPage>=totalSize-1){
            return;
        }
        currentPage++;
        loadData($("#current-path").val());
    });
});
function loadData(path){
    currentPath = $("#current-path").val();
    if(currentPath!=path){
        currentPage = 0;
        setParentNav(path);
    }
    if (path == ""){
        path = "root";
    }
    $("#current-path").val(path);
    crossDomainAjax(encodeUrl(path), function (data) {
        checkStatus(path,data);
        data = sort(data,"crc",true);
        var bodyHtml = "";
        totalSize =  Math.ceil(data.length/8);
        //设置页码
        $("#page-info").html((currentPage+1)+"/"+totalSize);
        for(var index=currentPage*8;index<data.length && index<(currentPage+1)*8;index++){
            var item = data[index];
            var host = $("#domain").val()
            var trHtml = "<tr>";
            if(isDir(item)){
                trHtml+="<td><img src='"+host+"/statics/images/main/type/folder.gif'/></td>";
                trHtml+="<td class='files-name'><a href='#' onclick=\"loadData('"+item.path+"')\">"+item.name+"</a></td>";
                trHtml+="<td class='files-size'></td>";
                trHtml+="<td class='files-date'>"+item.date+"</td>";
                trHtml+="<td class='files-open'><a href='#' onclick=\"loadData('"+item.path+"')\">"+"打开"+"</a></td>";
            }else{
                trHtml+="<td><img src='"+host+"/statics/images/main/type/undefind.gif'/></td>";
                trHtml+="<td class='files-name'><a href='"+downloadUrl(item.crc,item.name)+"'>"+item.name+"</a></td>";
                trHtml+="<td class='files-size'>"+formatSize(item.size)+"</td>";
                trHtml+="<td class='files-date'>"+item.date+"</td>";
                trHtml+="<td class='files-open'><a href='"+downloadUrl(item.crc,item.name)+"'>"+"下载"+"</a></td>";
            }
            bodyHtml+=trHtml;
        }
        $("#file-list").html(bodyHtml);
    });
}
function sort(data,prop, asc) {
    data = data.sort(function(a, b) {
        if (asc) return (a[prop] > b[prop]) ? 1 : ((a[prop] < b[prop]) ? -1 : 0);
        else return (b[prop] > a[prop]) ? 1 : ((b[prop] < a[prop]) ? -1 : 0);
    });
    return data;
}
function isDir(item){
    var path = item.path;
    var flag = path.substring(path.length-1,path.length);
    if(flag=="/"){
        return true;
    }
    return false;
}
function encodeUrl(path){
    var host = $("#host").val()
    var hash = $("#hash").val()
    var type = $("#type").val()
    var url = host+"/zip/meta/"+type+"/"+hash+"/";
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        path = encodeURI(path);
    }
    url += replaceAll("/","%2f",path);
    return url;
}

/**
 * 检查状态，如果服务器没有数据则要进行重新转回
 * 判断根目录返回的数据是否是空记录集
 * @param path 请求的相对路径
 * @param response 异步返回的值
 * @returns {boolean}
 */
function checkStatus(path,response){
    if(path!="root"){
        return true;
    }
    if(response.length>0){
        return true;
    }
    var key = "&action=again";
    var url = window.location.href;
    //避免不断循环
    if(url.indexOf(key)<0){
        url = url+"&action=again";
        window.location.href = url;
    }
    return false;
}
function crossDomainAjax (url, successCallback) {
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {
        var xdr = new XDomainRequest(); // Use Microsoft XDR
        xdr.open('get', url);
        xdr.onload = function () {
            var values = $.parseJSON(xdr.responseText);
            successCallback(values); // internal function
        };
        xdr.onprogress = function() {};
        xdr.onerror = function() {
            //_result = false;
        };
        xdr.send();
    }
    // Do normal jQuery AJAX for everything else
    else {
        $.ajax({
            url: url,
            dataType: 'json',
            type: 'GET',
            async: false, // must be set to false
            success: function (data, success) {
                successCallback(data);
            }
        });
    }
}
/**
 * 获得父亲目录路径
 * @param path
 * @returns {*}
 */
function setParentNav(path) {
    var parents = [];
    var dirs = path.split("/");
    var value = "";
    if(dirs.length>1){
        item = {
            name: "-",
            path: "root"
        };
        parents.push(item);
        for(var i=0;i<dirs.length-2;i++){
            value    = value + dirs[i]+ "/";
            var item = {
                name: dirs[i],
                path: value
            };
            parents.push(item);
        }
    }
    var navHtml="";
    for(var index in parents){
        var item = parents[index];
        navHtml+="<li><a href='#'"+" onclick=\"loadData('"+item.path+"')\">"+item.name+"</a></li>";
    }
    $("#parent-nav").html(navHtml);
}
/**
 * 具体文件的下载地址
 * @param path
 * @returns {*}
 */
downloadUrl = function(crc,name) {
    var host = $("#host").val()
    var hash = $("#hash").val()
    var type = $("#type").val()
    var url  = host+"/zip/content/"+type+"/"+hash+"/"+name+"/"+crc;
    return url;
}
/**
 * 格式化文件大小
 * @param path
 * @returns {*}
 */
formatSize = function(size) {
    var unit = 1024*1024*1024;
    var value = size/unit;
    if(value>1){
        return value.toFixed(2)+"GB";
    }
    unit = 1024*1024;
    value = size/unit;
    if(value>1){
        return value.toFixed(2)+"MB";
    }
    unit = 1024;
    value = size/unit;
    if(value>1){
        return value.toFixed(2)+"KB";
    }
    return size+"Byte"
}
/**
 * 替换字符串，在二次传值，如果保留/符号，系统会被转义
 * @param find
 * @param replace
 * @param str
 * @returns {XML|string|void|y.replace|*|derivedSyncDirective.replace}
 */
function replaceAll(find, replace, str) {
    return str.replace(new RegExp(find, 'g'), replace);
}