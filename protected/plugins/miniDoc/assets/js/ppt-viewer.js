/**
 * Created with JetBrains WebStorm.
 * User: DELL
 * Date: 13-11-25
 * Time: 上午12:39
 * To change this template use File | Settings | File Templates.
 */

var $id = function (id) {
    return document.getElementById(id);
};
var $create = function (e) {
    return document.createElement(e);
};
//IE8及以下兼容document.getElementsByClassName
var $class = function (classStr, tagName) {
    if (document.getElementsByClassName) {
        return document.getElementsByClassName(classStr)
    } else {
        var nodes = document.getElementsByTagName(tagName), ret = [];
        for (var i = 0; i < nodes.length; i++) {
            if (hasClass(nodes[i], classStr)) {
                ret.push(nodes[i])
            }
        }
        return ret;
    }
};

function hasClass(tagStr, classStr) {
    var arr = tagStr.className.split(/\s+/); //这个正则表达式是因为class可以有多个,判断是否包含
    for (var i = 0; i < arr.length; i++) {
        if (arr[i] == classStr) {
            return true;
        }
    }
    return false;
}

function getURLParameter(name) {
    return decodeURI(
        (RegExp(name + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]
    );
}

//兼容各浏览器
var sys = {};
var ua = navigator.userAgent.toLowerCase();
var s;
(s = ua.match(/msie ([\d.]+)/)) ? sys.ie = s[1] :
    (s = ua.match(/firefox\/([\d.]+)/)) ? sys.firefox = s[1] :
        (s = ua.match(/chrome\/([\d.]+)/)) ? sys.chrome = s[1] :
            (s = ua.match(/opera.([\d.]+)/)) ? sys.opera = s[1] :
                (s = ua.match(/version\/([\d.]+).*safari/)) ? sys.safari = s[1] : 0;

//动态加载左边图片
var leftImageLoading = function () {
    var i;
    for (i = 0; i < imageList.length; i++) {
        var leftContent = $id("left-image-box");
        var leftDiv = $create("div");
        var leftSpanObj = $create("span");
        var leftImageObj = $create("img");
        leftDiv.setAttribute("class", "self-imgbox");
        leftImageObj.setAttribute("class", "thumbnail");
        leftImageObj.setAttribute("src", imageList[i]);
        leftSpanObj.textContent = i + 1;
        leftContent.appendChild(leftDiv);
        leftDiv.appendChild(leftSpanObj);
        leftDiv.appendChild(leftImageObj);
    }
};

//动态加载右边图片
var rightImageLoading = function () {
    var i;
    var rightContent = $id("right-image-box");
    var rightTabObj = $create("table");
    var rightDiv = $create("div");
    var rightPanelObj = $create("div");
    var trObj = $create("tr");
    var tdObj = $create("td");
    imgObj = $create("img");
    imgObj.setAttribute("src", imageList[0]);
    imgObj.setAttribute("id", "right-image-show");
    imgObj.setAttribute("style", "height");
    imgObj.setAttribute("style", "width");
    rightTabObj.setAttribute("id", "right-table");
    rightDiv.setAttribute("id", "parent-div");
    rightPanelObj.setAttribute("id", "right-panel");
    rightPanelObj.setAttribute("style", "height");
    rightPanelObj.setAttribute("style", "width");
    rightContent.appendChild(rightTabObj);
    rightTabObj.appendChild(trObj);
    trObj.appendChild(tdObj);
    tdObj.appendChild(imgObj);
    rightContent.appendChild(rightDiv);
    rightDiv.appendChild(rightPanelObj);
    sImgBox = $class('self-imgbox', 'div');
    sImgBox[0].className = "self-imgbox selected";
    for (i = 0; i < imageList.length; i++) {
        (function (i) { //因为每次都会得到循环的最后一个，所以使用了闭包
            sImgBox[i].onclick = function () {
                for (var j = 0; j < sImgBox.length; j++) {
                    sImgBox[j].className = "self-imgbox";
                    imgObj.setAttribute("src", imageList[i]);
                }
                sImgBox[i].className = "self-imgbox selected";
            };
        })(i);
    }
};

//动态加载toolBar
var toolBarLoading = function () {
    var bodyObj = document.getElementsByTagName("body");
    var headBarObj = $create("div");
    var playBtnObj = $create("a");
    var playIconObj = $create("span");
    var playTxtObj = $create("span");
    var playCurBtnObj = $create("a");
    var playCurIconObj = $create("span");
    var playCurTxtObj = $create("span");
    headBarObj.setAttribute("id", "head");
    headBarObj.setAttribute("class", "head-bar");
    playBtnObj.setAttribute("class", "play");
    playIconObj.setAttribute("class", "icon");
    playTxtObj.setAttribute("class", "label");
    playCurBtnObj.setAttribute("class", "playcur");
    playCurIconObj.setAttribute("class", "icon");
    playCurTxtObj.setAttribute("class", "label");
    bodyObj[0].appendChild(headBarObj);
    headBarObj.appendChild(playBtnObj);
    playBtnObj.appendChild(playIconObj);
    playBtnObj.appendChild(playTxtObj);
    headBarObj.appendChild(playCurBtnObj);
    playCurBtnObj.appendChild(playCurIconObj);
    playCurBtnObj.appendChild(playCurTxtObj);
};
//弹出菜单
var showTopBar = function () {
    var runMenu = $id("head");
    var speed = 1;
    var timer = null;
    var alpha = 30;
    runMenu.onmouseover = function () {
        showToolbar(70);
    };
    runMenu.onmouseout = function () {
        showToolbar(0);
    };
    function showToolbar(target) {
        clearInterval(timer);
        timer = setInterval(function () {
            if (target > alpha) {
                speed = 5;
            } else {
                speed = -5;
            }

            if (alpha == target) {
                clearInterval(timer);
            }
            else {
                alpha = alpha + speed;
                runMenu.style.filter = 'alpha(opacity=' + alpha + ')';
                runMenu.style.opacity = alpha / 100;
            }
        }, 5)
    }
};

//动态加载页面
var headBarLoading = function () {
    var p = 0;
    var bodyObj = document.getElementsByTagName("body");
    //设置弹出背景层
    bacDivObj = $create("div");
    bacImgObj = $create("img");
    bacImgObj.setAttribute("src", imageList[p]);
    bacDivObj.setAttribute("style", "color");
    bacDivObj.setAttribute("style", "height");
    bacDivObj.setAttribute("style", "width");
    bacDivObj.setAttribute("id", "popup-background");
    bacImgObj.setAttribute("id", "popup-image");
    bodyObj[0].appendChild(bacDivObj);
    bacDivObj.appendChild(bacImgObj);
    bacDivObj.style.position = "absolute";
    bacDivObj.style.top = "0";
    bacDivObj.style.left = "0";
    bacDivObj.style.backgroundColor = "#000000";
    bacDivObj.style.zIndex = 201;
    bacDivObj.style.display = "none";
    bacDivObj.style.overflow = "hidden";
    bacDivObj.style.height = document.documentElement.clientHeight + "px";
    bacDivObj.style.width = document.documentElement.clientWidth + "px";
    bacImgObj.style.height = "100%";
    bacImgObj.style.width = "80%";
};


//从第一张开始播放
var viewFirstPPT = function () {
    bacImgObj.setAttribute("src", imageList[0]);
    var p = 0;
    var playFirObj = $class('play', 'a');
    playFirObj[0].onclick = function () {
        bacDivObj.style.display = "block";
        bacDivObj.onclick = function () {
            if (p < imageList.length) {
                bacImgObj.setAttribute("src", imageList[p]);
                p++;
            } else {
                bacDivObj.style.display = "none";
            }
        };
        document.onkeydown = function (e) {
            var o = e || event;
            var currKey = o.keyCode || o.which || o.charCode;
            if (p <= imageList.length && p >= 0) {
                if (currKey == 37 || currKey == 38) {
                    p--;
                    if (p < 0) {
                        p = 0;
                    }
                    bacImgObj.setAttribute("src", imageList[p]);
                } else if (currKey == 39 || currKey == 40) {
                    p++;
                    if (p >= imageList.length) {
                        p = imageList.length - 1;
                    }
                    bacImgObj.setAttribute("src", imageList[p]);
                } else if (currKey == 27) {
                    bacDivObj.style.display = "none";
                }
            } else {
                bacDivObj.style.display = "none";
            }
        }
        return p = 0;
    };
};


//从当前张开始播放
var viewThisPPT = function () {
    var playCurObj = $class('playcur', 'a');
    playCurObj[0].onclick = function () {
        var p1;
        for (var p = 0; p < imageList.length; p++) {
            if (sImgBox[p].className == "self-imgbox selected") {
                bacImgObj.setAttribute("src", imageList[p]);
                p1 = p;
            }
        }
        bacDivObj.style.display = "block";
        bacDivObj.onclick = function () {
            if (p1 < imageList.length) {
                bacImgObj.setAttribute("src", imageList[p1]);
                p1++;
            } else {
                bacDivObj.style.display = "none";
            }
        };
        document.onkeydown = function (e) {
            var o = e || event;
            var currKey = o.keyCode || o.which || o.charCode;
            if (p1 <= imageList.length && p1 >= 0) {
                if (currKey == 37 || currKey == 38) {
                    p1--;
                    if (p1 < 0) {
                        p1 = 0;
                    }
                    bacImgObj.setAttribute("src", imageList[p1]);
                } else if (currKey == 39 || currKey == 40) {
                    p1++;
                    if (p1 >= imageList.length) {
                        p1 = imageList.length - 1;
                    }
                    bacImgObj.setAttribute("src", imageList[p1]);
                } else if (currKey == 27) {
                    bacDivObj.style.display = "none";
                }
            } else {
                bacDivObj.style.display = "none";
            }

        }

    };
};


//鼠标滑轮滚动事件
var useMouseWheel = function () {
//取得滚动值
    function getWheelValue(e) {
        e = e || event;
        return ( e.wheelDelta ? e.wheelDelta / 120 : -( e.detail % 3 == 0 ? e.detail / 3 : e.detail ) );
    }

    function stopEvent(e) {
        e = e || event;
        if (e.preventDefault)e.preventDefault();
        e.returnValue = false;
    }

//绑定事件,这里对mousewheel做了判断,注册时统一使用mousewheel
    function addEvent(obj, type, fn) {

        var isFirefox = typeof document.body.style.MozUserSelect != 'undefined';
        if (obj.addEventListener)
            obj.addEventListener(isFirefox ? 'DOMMouseScroll' : type, fn, false);
        else
            obj.attachEvent('on' + type, fn);
        return fn;
    }

//移除事件,这里对mousewheel做了兼容,移除时统一使用mousewheel
    function deleteEvent(obj, type, fn) {
        var isFirefox = typeof document.body.style.MozUserSelect != 'undefined';
        if (obj.removeEventListener)
            obj.removeEventListener(isFirefox ? 'DOMMouseScroll' : type, fn, false);
        else
            obj.detachEvent('on' + type, fn);
    }

    /*限制范围函数,
     参数是三个数字,如果num 大于 max, 则返回max， 如果小于min，则返回min,如果在max和min之间，则返回num
     */
    function range(num, max, min) {
        return Math.min(max, Math.max(num, min));
    }

    var tar = $id("right-image-box");
    var dir = 0;//
    addEvent(tar, 'mousewheel', function (e) {
        stopEvent(e);
        var delta = getWheelValue(e);


//因为tar.offsetTop 越大，滑块就越往下，所以delta又需要反转回来，向上是负的，向下是正的，所以乘以-1
        tar.scrollTop = range(tar.scrollTop - ( delta * (document.documentElement.clientHeight) ), 26118, 0);
        var leftContent = $id("left-image-box");
        if (e.wheelDelta) {  //判断浏览器IE，谷歌滑轮事件
            if (e.wheelDelta > 0) { //当滑轮向上滚动时
                dir = dir - 1;
                if (dir < 0) {
                    dir = 0;
                }
                imgObj.setAttribute("src", imageList[dir]);
                for (var i = 0; i < imageList.length; i++) {
                    sImgBox[i].className = "self-imgbox";
                }
                leftContent.scrollTop = leftContent.scrollTop - 174;
                sImgBox[dir].className = "self-imgbox selected";
                return dir;
            }
            if (e.wheelDelta < 0) { //当滑轮向下滚动时

                dir = dir + 1;
                if (dir >= imageList.length) {
                    dir = imageList.length - 1;
                }
                imgObj.setAttribute("src", imageList[dir]);
                for (var k = 0; k < imageList.length; k++) {
                    sImgBox[k].className = "self-imgbox";
                }
                leftContent.scrollTop = leftContent.scrollTop + 174;
                sImgBox[dir].className = "self-imgbox selected";
                return dir;
            }
        } else if (e.detail) {  //Firefox滑轮事件
            if (e.detail < 0) { //当滑轮向上滚动时
                dir = dir - 1;
                if (dir < 0) {
                    dir = 0;
                }
                for (i = 0; i < imageList.length; i++) {
                    sImgBox[i].className = "self-imgbox";
                }
                leftContent.scrollTop = leftContent.scrollTop - 174;
                sImgBox[dir].className = "self-imgbox selected";
                imgObj.setAttribute("src", imageList[dir]);
                return dir;
            }
            if (e.detail > 0) { //当滑轮向下滚动时
                dir = dir + 1;
                if (dir >= imageList.length) {
                    dir = imageList.length - 1;
                }
                for (i = 0; i < imageList.length; i++) {
                    sImgBox[i].className = "self-imgbox";
                }
                leftContent.scrollTop = leftContent.scrollTop + 174;
                sImgBox[dir].className = "self-imgbox selected";
                imgObj.setAttribute("src", imageList[dir]);
                return dir;
            }
        }
    });
};
//自适应
var pageAutoResize = function () {
    var lImgBoxObj = $id("left-image-box");
    lImgBoxObj.style.height = document.documentElement.clientHeight + "px";
    var rImgBoxObj = $id("right-image-box");
    rImgBoxObj.style.height = document.documentElement.clientHeight + "px";
    rImgBoxObj.style.width = document.documentElement.clientWidth - 300 + "px";
    var headBarObj = $id("head");
    var rightTabObj = $id("right-table");
    var imgObj = $id("right-image-show");
    headBarObj.style.width = document.documentElement.clientWidth + "px";
    rightTabObj.style.height = document.documentElement.clientHeight + "px";
    rightTabObj.style.width = document.documentElement.clientWidth - 320 + "px";
    if (sys.ie) {
        imgObj.height = document.documentElement.clientHeight * "0.89";
        imgObj.width = document.documentElement.clientWidth * "0.89";
    } else if (sys.firefox || sys.chrome) {
        imgObj.height = document.documentElement.clientHeight * "0.83";
        imgObj.width = document.documentElement.clientWidth * "0.83";
    } else if (sys.safari) {
        imgObj.height = document.documentElement.clientHeight * "0.80";
        imgObj.width = document.documentElement.clientWidth * "0.80";
    }
    var rPanelObj = $id("right-panel");
    var pDivObj = $id("parent-div");
    pDivObj.style.height = rImgBoxObj.style.height;
    rPanelObj.style.width = document.documentElement.clientWidth - 317 + "px";
    rPanelObj.style.width = document.documentElement.clientWidth - 217 + "px";
    rPanelObj.style.height = (document.documentElement.clientHeight) * imageList.length + "px";
    var pBacObj = $id("popup-background");
    if (pBacObj) {
        pBacObj.style.height = document.documentElement.clientHeight + "px";
        pBacObj.style.width = document.documentElement.clientWidth + "px";
    }
    var pImgObj = $id("popup-image");
    pImgObj.height = document.documentElement.clientHeight;
    pImgObj.width = pImgObj.height * 8 / 5;
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
        imageList = data;
        if (checkStatus(imageList)) {
            toolBarLoading();
            showTopBar();
            leftImageLoading();
            rightImageLoading();
            headBarLoading();
            viewFirstPPT();
            viewThisPPT();
            useMouseWheel();
            pageAutoResize();
        }
    });
};
window.onresize = function () {
    pageAutoResize();
};