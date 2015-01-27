# 迷你文档插件

`迷你文档` 插件支持文档在线浏览，包括：doc/docx/xls/xlsx/ppt/pptx/pdf类型

`迷你文档` 工作原理是把指定[迷你文档]服务器，通过后台定时任务，把指定的文件类型转换为PDF，并提取PDF的第一页作为文档封面

 浏览器使用[pdf.js]加载pdf文件，这样的上述多种文档即可在线浏览，[pdf.js]不支持IE8及其以下版本IE浏览器


# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你文档 插件

# 接口1：分页获得指定类型的文件列表
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```


## 输入参数
```html
   {
    route:'module/miniDoc/list',//接口路由地址
    access_token:xxxx,当前登录的access_token
    sign:yyyy,当前会话签名
    page_size:16,//每页的大小，默认是每页展示16个文档
    page:1,//当前页码，
    mime_type:application/msword,//文档类型，可选4种类型：application/msword application/mspowerpoint application/msexcel application/pdf
   }
  ```
# 接口2：下载文件内容
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```


## 输入参数
```html
   {
    route:'module/miniDoc/download',//接口路由地址
    hash:xxxx,//文件hash值
   }
  ```
## 输出

文件流

# 接口3：文件转换PDF成功消息报俊
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```

## 输入参数
```html
   {
    route:'module/miniDoc/report',//接口路由地址
    hash:xxxx,//文件hash值
    status:0,//0表示文档转换失败，1表示文档转换成功
   }
  ```

文件流
[pdf.js]:https://github.com/mozilla/pdf.js