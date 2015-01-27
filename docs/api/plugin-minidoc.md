# 迷你文档插件

`迷你文档` 插件支持文档在线浏览，包括：doc/docx/xls/xlsx/ppt/pptx/pdf类型
`迷你文档` 工作原理是把指定文件类型转换为PDF，并且提取PDF的第一页图片作为文档封面
 浏览器使用PDF.js进行加载，因此不支持IE8及其以下浏览器，IE9+/Chrome/Firefox都能有效插件
 该限制是PDF.js的限制，在IE9浏览器可能有些特性还不支持，详情见[pdf.js]

# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你文档 插件

# 获得文件列表
* `event` Event


[pdf.js]:https://github.com/mozilla/pdf.js