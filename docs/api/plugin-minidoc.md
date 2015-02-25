# 迷你文档插件

`迷你文档` 插件支持文档在线浏览，包括：doc/docx/xls/xlsx/ppt/pptx/pdf类型

`迷你文档` 工作原理是把指定迷你文档服务器，通过后台定时任务，把指定的文件类型转换为PDF，并提取PDF的第一页作为文档封面

 浏览器使用[pdf.js]加载pdf文件，这样的上述多种文档即可在线浏览，[pdf.js]不支持IE8及其以下版本IE浏览器


# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你文档 插件

# 接口1：获得迷你文档节点列表信息(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```


## 输入参数
```html
   {
    route:'module/miniDoc/nodeList',//接口路由地址
    access_token:xxxx,当前登录用户的access_token
    sign:yyyy,当前会话签名
   }
  ```
# 接口2：新增或修改迷你文档节点信息(POST方式)

## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
 输入参数解释
```html
   {
    route:'module/miniDoc/node',//接口路由地址
    access_token:xxxx,当前登录用户的access_token
    sign:yyyy,当前会话签名
    safe_code:"xxx",//迷你文档内置的访问钥匙，存储节点默认安装设置为：uBEEAcKM2D7sxpJD7QQEapsxiCmzPCyS
    name:"xxx",//迷你文档节点名称，名称在整个系统中唯一
    host:"xxxx",//迷你文档节点访问地址，比如:http://d1.miniyun.cn或者http://192.168.0.11:8090，前面的http://或https://不能少
   }
  ```
# 接口3：修改迷你文档节点状态(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
输入参数解释
```html
   {
    route:'module/miniDoc/nodeStatus',//接口路由地址
    access_token:xxxx,管理员登录用户的access_token
    sign:yyyy,当前会话签名,
    name:"xxx",//存储节点名称，名称在整个系统中唯一
    status:"-1",//-1表示节点拉下，1表示节点拉上
   }
  ```


# 一次性转换老文档40条记录

进入迷你云代码安装包所在位置，直接运行
./console PluginDocConvert

# 接口4：分页获得指定类型的文件列表(POST方式)
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
# 接口5：获得文档封面图片/文档的PDF稿件(GET方式)

## 访问地址

```html
http://demo.miniyun.cn/api.php?route=module/miniDoc/previewContent&path=/1/test.doc&type=png&access_token=xxxx&sign=xxxx
其中demo.miniyun.cn可替换为自己的迷你云地址

```
 输入参数解释
```html
   {
    route:'module/miniDoc/previewContent',//接口路由地址
    access_token:xxxx,当前登录的access_token
    sign:yyyy,当前会话签名
    path:"/1/test.doc",//要处理文档的的绝对路局
    type:"png",//文档类型，可选4种类型：png与pdf
   }
  ```
# 接口6：下载文件内容(GET方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php&route=module/miniDoc/download&hash=xxxx
其中demo.miniyun.cn可替换为自己的迷你云地址

```
输入参数解释
```html
   {
    route:'module/miniDoc/download',//接口路由地址
    hash:xxxx,//文件hash值
   }
  ```


# 接口7：文件转换PDF成功消息报俊(POST方式)
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

# 错误码说明

100201 在添加/修改迷你文档节点信息，名称/地址/safe_code其中一项为空

100202 在修改迷你文档节点状态，名称一项为空

100203 在修改迷你文档节点状态，没有发现对应的名称的节点

100204 把迷你文档节点拉上时，迷你云服务器无法连接存储节点服务器或者预先填写的safe_code不合法

[pdf.js]:https://github.com/mozilla/pdf.js