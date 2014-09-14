/**
 * Created with JetBrains WebStorm.
 * User: miniyun
 * Date: 13-12-2
 * Time: 上午10:52
 * To change this template use File | Settings | File Templates.
 */


var $id = function (id) {
    return document.getElementById(id);
};
/**
 *
 * @param item
 * @returns {*}
 */
var getUrl = function (item) {
    var id = item.getAttribute("id");
    for (var index in sheetItems) {
        var url = sheetItems[index][1];
        if ("sheet" + index == id) {
            return url
        }
    }
};
var showLoadingImage = function () {
    var gifUrl = document.getElementById("gif-url").value;
    var b = document.getElementsByTagName("body");
    var divObj = document.createElement("div");
    var imgObj = document.createElement("img");
    divObj.setAttribute("id","show-loading-image");
    divObj.style.width = "40px";
    divObj.style.height = "40px";
    divObj.style.position = "absolute";
    divObj.style.top = document.documentElement.clientHeight/2-50+"px";
    divObj.style.left = document.documentElement.clientWidth/2-50+"px";
    imgObj.src = gifUrl;
    b[0].appendChild(divObj);
    divObj.appendChild(imgObj);
};
/**
 *显示对于item的内容
 *param titleItem
 */
var renderSheet = function (item) {
    //rest sheets default style
    for (var index in sheetItems) {
        var titleItem = $id("sheet" + index);
        titleItem.style.backgroundColor = "#dcdcdc";
    }

    // set current sheet selected
    item.style.backgroundColor = "#ffffff";
    // render grid
    renderSheetGrid(item);
    // set global item
    selectedItem = item;
};
/**
 * render grid line
 * @param sheetItems
 */
var renderSheetGrid = function (item) {
    var isShow = item.getAttribute("showGrid");
    var percentage = item.getAttribute("percentage");
    //reset percentage
    resetPercentage(percentage);
    //reset checkbox
    var checkButton = $id('checkbox-btn');
    if (isShow == "1") {
        checkButton.checked = true;
    } else {
        checkButton.checked = false;
    }
    showGrid(item, isShow, percentage);
}
var resetPercentage = function (percentage) {
    percentage = parseFloat(percentage) * 100;
    var percentageBar = $id("percentage-bar");
    percentageBar.innerHTML = percentage + "%";
    var oCss = document.styleSheets[0].cssRules || document.styleSheets[0].rules;
    oCss[29].style.left = percentage * 0.45 + "px";

}
var showGrid = function (item, isShow, percentage){
    //add time to force refresh html
    var ifm = $id("content-box");
    if (navigator.userAgent.indexOf("MSIE 8.0") > 0) {
        ifm.style.width = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) + 60 + "px";
        ifm.style.height = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight) + 60 + "px";
    } else {
        showLoadingImage();
        ifm.style.width = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) + "px";
        ifm.style.height = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight) + "px";
    }
    var url = getUrl(item);
    if (isShow == "1") {
        url = url + "?showGrid=1";
    } else {
        url = url + "?showGrid=0";
    }
    url = url + "&percentage=" + percentage;
    ifm.setAttribute("src", url);
}

//加载sheet表格
var renderTitles = function (sheetItems) {
    var titleBar = $id("title-bar");
    var firstSheet;
    for (var index in sheetItems) {
        var sheet = sheetItems[index];
        var title = sheet[0];
        var url = sheet[1];
        var titleItem = document.createElement("a");
        titleItem.setAttribute("id", "sheet" + index);
        titleItem.setAttribute("showGrid", "0");
        titleItem.setAttribute("url", url);
        titleItem.setAttribute("onclick", "renderSheet(this)");
        titleItem.setAttribute("percentage", "1");
        titleItem.setAttribute("class", "title-bar-style");
        titleItem.setAttribute("className", "title-bar-style");
        titleItem.innerHTML = title;
        titleBar.appendChild(titleItem);
        if (title.length > 11) {
            titleItem.style.width = 200 + "px";
        } else {
            titleItem.style.width = title.length * 18 + "px";
        }
        if (index == 0) {
            firstSheet = titleItem;
        }
    }
    //selected first page
    if (sheetItems.length > 0) {
        renderSheet(firstSheet);
    }
};
/**
 * show grid checkbox event
 */
$id('checkbox-btn').onclick = function () {
    refreshSheet();
}
/**
 *load gridLine and set width/height
 */
$id('content-box').onload = function () {
    var ifm = $id("content-box");
    ifm.setAttribute("class", "iframe-page");
    ifm.setAttribute("className", "iframe-page");
    ifm.setAttribute("frameborder", "0");
    ifm.setAttribute("scrolling", "no");
    ifm.setAttribute("style", "display");
    ifm.setAttribute("style", "width");
    ifm.setAttribute("style", "height");
    ifm.style.width = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) + "px";
    ifm.style.height = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight) + "px";
    ifm.style.display = "block";
    document.getElementsByTagName("body")[0].removeChild(document.getElementById("show-loading-image"));
};
var refreshSheet = function () {
    var isShow = $id('checkbox-btn').checked ? "1" : "0";
    selectedItem.setAttribute("showGrid", isShow);
    var percentage = selectedItem.getAttribute("percentage");
    showGrid(selectedItem, isShow, percentage);
}
var getZoomPos = function () {
    var oCss = document.styleSheets[0].cssRules || document.styleSheets[0].rules;
    var ori = parseInt(oCss[29].style.left);
    return ori;
};
var setZoomPos = function (percentage) {
    var oCss = document.styleSheets[0].cssRules || document.styleSheets[0].rules;
    oCss[29].style.left = percentage * 0.45 + "px";
    selectedItem.setAttribute("percentage", percentage / 100);

    refreshSheet();
}
$id("first-page").onclick = function () {
    renderSheet($id("sheet0"));
};
$id("previous-page").onclick = function () {
    var currentId = selectedItem.id;
    for (var index in sheetItems) {
        if ("sheet" + index == currentId) {
            var pos = index - 1;
            if (pos < 0) {
                pos = 0;
            }
            renderSheet($id("sheet" + pos));
        }
    }
};
$id("next-page").onclick = function () {
    var currentId = selectedItem.id;
    var length = sheetItems.length;
    for (var index in sheetItems) {
        if ("sheet" + index == currentId) {
            var pos = parseInt(index) + 1;
            if (pos > length - 1) {
                pos = length - 1;
            }
            renderSheet($id("sheet" + pos));
        }
    }
};
$id("last-page").onclick = function () {
    renderSheet($id("sheet" + (sheetItems.length - 1)));
};
//缩小文档
$id("reduce-btn").onclick = function () {
    var percentageBar = $id("percentage-bar");
    var beforeValue = getZoomPos();
    beforeValue = beforeValue - 9;
    if (beforeValue <= 0) {
        beforeValue = 0;
    }
    var percentage = beforeValue / 45 * 100;
    setZoomPos(percentage);
    percentageBar.innerHTML = percentage + "%";
};
//放大文档
$id("amplify-btn").onclick = function () {
    var percentageBar = $id("percentage-bar");
    var beforeValue = getZoomPos();
    beforeValue = beforeValue + 9;
    if (beforeValue >= 90) {
        beforeValue = 90;
    }
    var percentage = beforeValue / 45 * 100;
    setZoomPos(percentage);
    percentageBar.innerHTML = percentage + "%";
};
/**
 *
 */
var adaptionBrowserSize = function () {
    var mainBox = $id("main-box");
    mainBox.style.height = document.documentElement.clientHeight - 20 + "px";
};
/**
 * 如目标服务器不存在目标数据，则进行跳转以便第二次转换
 * @param items
 * @returns {boolean}
 */
var checkStatus = function (items) {
    if (success(items)) {
        return true;
    }
    var key = "&action=again";
    var url = window.location.href;
    //避免不断循环
    if (url.indexOf(key) < 0) {
        window.location.href = window.location.href + "&action=again";
    }
    return false;
}
/**
 * 判断是否执行成功
 * @param items
 * @returns {boolean}
 */
var success = function (items) {
    if (items.length == 0) {
        return false;
    }
    return true;
};
function crossDomainAjax(url, successCallback) {

    // IE8 & 9 only Cross domain JSON GET request
    if ('XDomainRequest' in window && window.XDomainRequest !== null) {

        var xdr = new XDomainRequest(); // Use Microsoft XDR
        xdr.open('get', url);
        xdr.onload = function () {
            var dom = new ActiveXObject('Microsoft.XMLDOM'),
                JSON = $.parseJSON(xdr.responseText);

            dom.async = false;

            if (JSON == null || typeof (JSON) == 'undefined') {
                JSON = $.parseJSON(data.firstChild.textContent);
            }

            successCallback(JSON); // internal function
        };
        xdr.onprogress = function () {
        };
        xdr.onerror = function () {
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
        sheetItems = data;
        selectedItem = "";
        if (checkStatus(sheetItems)) {
            adaptionBrowserSize();
            renderTitles(sheetItems);
        }
    });
};
window.onresize = function () {
    adaptionBrowserSize();
};