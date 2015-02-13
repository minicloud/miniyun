# 迷你存储插件

`迷你存储` 插件支持文件分散存储在多个节点上，支持同一份文件冗余备份到3个节点上。提升迷你云文件上传/下载效率，提高系统可靠性

`迷你存储` 工作原理是把客户端(网页、PC、移动)文件上传时，先向迷你云发送秒传接口，如系统没有存在该文件内容，秒传接口将返回迷你存储地址、签名信息、文件断点信息。

客户端根据断点信息生成新的文件流push到迷你存储地址并带上签名信息即可，约定每次上传最大文件块是4M。

# 开启插件

管理员登录网页版，进入 管理后台，选中 插件管理，启用 迷你存储 插件


# 接口1：获得迷你存储节点列表信息(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```


## 输入参数
```html
   {
    route:'module/miniStore/nodeList',//接口路由地址
    access_token:xxxx,当前登录的access_token
    sign:yyyy,当前会话签名
   }
  ```
# 接口2：新增或修改迷你存储节点信息(POST方式)

## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
 输入参数解释
```html
   {
    route:'module/miniStore/node',//接口路由地址
    access_token:xxxx,当前登录的access_token
    sign:yyyy,当前会话签名
    node_access_token:"xxx",//存储节点内置的访问钥匙，存储节点默认安装设置为：uBEEAcKM2D7sxpJD7QQEapsxiCmzPCyS
    name:"xxx",//存储节点名称，名称在整个系统中唯一
    host:"xxxx",//存储节点访问地址，比如:http://s1.miniyun.cn或者http://192.168.0.11:8090，前面的http://或https://不能少得
   }
  ```
# 接口3：修改迷你存储节点状态(POST方式)
## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```
输入参数解释
```html
   {
    route:'module/miniStore/nodeStatus',//接口路由地址
    access_token:xxxx,管理员登录的access_token
    sign:yyyy,当前会话签名,
    name:"xxx",//存储节点名称，名称在整个系统中唯一
    status:"-1",//-1表示节点拉下，1表示节点拉上
   }
  ```
# 错误码说明
## 100101 在添加/修改迷你存储节点信息，名称/地址/访问Token其中一项为空
## 100102 在修改迷你存储节点状态，名称一项为空
## 100103 在修改迷你存储节点状态，没有发现对应的名称的节点
## 100104 把迷你存储节点拉上时，迷你云服务器无法连接存储节点服务器或者预先填写的node_access_token不合法