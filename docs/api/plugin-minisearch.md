# 迷你搜索插件

`迷你搜索` 插件是[迷你文档]配对的插件，它提供text/doc/docx/xls/xlsx/ppt/pptx/pdf文件内容的全文检索

`迷你搜索` 工作原理是把文档提取出文本内容，通过后台定时任务把文本内容使用[sphinx]编制索引。

用户输入关键字后，[sphinx]检索出文件与摘要信息，并输出到客户端。它需要[迷你文档]插件开启。


# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你搜索 插件

# 一次性把系统的已经有的文档拉取

进入迷你云代码安装包所在位置，直接运行
./console PluginDocPullTxt

# 接口1：搜索(POST方式)
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

[sphinx]:http://sphinxsearch.com/
[迷你文档]:https://github.com/MiniYun/php-server/blob/minidoc/docs/api/plugin-minidoc.md