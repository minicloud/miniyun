# 迷你搜索插件

`迷你搜索` 插件是[迷你文档]配对的插件，它提供text/doc/docx/xls/xlsx/ppt/pptx/pdf文件内容的全文检索

`迷你搜索` 工作原理是把文档提取出文本内容，通过后台定时任务把文本内容使用[sphinx]编制索引。

用户输入关键字后，[sphinx]检索出文件与摘要信息，并输出到客户端。它需要[迷你文档]插件开启。


# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你搜索 插件

# 接口1：获得迷你搜索节点列表信息(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```

## 输入参数
```html
   {
    route:'module/miniSearch/nodeList',//接口路由地址
    access_token:xxxx,当前登录用户的access_token
    sign:yyyy,当前会话签名
   }
  ```
# 接口2：新增或修改迷你搜索节点信息(POST方式)

## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
 输入参数解释
```html
   {
    route:'module/miniSearch/node',//接口路由地址
    access_token:xxxx,当前登录用户的access_token
    sign:yyyy,当前会话签名
    safe_code:"xxx",//搜索节点内置的访问钥匙，搜索节点默认安装设置为：uBEEAcKM2D7sxpJD7QQEapsxiCmzPCyS
    name:"xxx",//搜索节点名称，名称在整个系统中唯一
    host:"xxxx",//搜索节点访问地址，比如:http://s1.miniyun.cn或者http://192.168.0.11:8090，前面的http://或https://不能少得
   }
  ```
# 接口3：修改迷你搜索节点状态(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
输入参数解释
```html
   {
    route:'module/miniSearch/nodeStatus',//接口路由地址
    access_token:xxxx,管理员登录用户的access_token
    sign:yyyy,当前会话签名,
    name:"xxx",//搜索节点名称，名称在整个系统中唯一
    status:"-1",//-1表示节点拉下，1表示节点拉上
   }
  ```

# 一次性把系统的已经有的文档拉取

进入迷你云代码安装包所在位置，直接运行
./console PluginDocPullTxt

# 接口4：搜索(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```

## 输入参数
```html
   {
    route:'module/miniSearch/search',//接口路由地址
    access_token:xxxx,当前登录的access_token
    sign:yyyy,当前会话签名
    key:'迷你云',//关键字 
   }
  ```
# 错误码说明

100301 在添加/修改迷你搜索节点信息，名称/地址/safe_code其中一项为空

100302 在修改迷你搜索节点状态，名称一项为空

100303 在修改迷你搜索节点状态，没有发现对应的名称的节点

100304 把迷你搜索节点拉上时，迷你云服务器无法连接迷你搜索节点服务器或者预先填写的safe_code不合法

[sphinx]:http://sphinxsearch.com/
[迷你文档]:https://github.com/MiniYun/php-server/blob/minidoc/docs/api/plugin-minidoc.md