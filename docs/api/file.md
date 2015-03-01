# 文件

`文件` 包括有文件上传/下载/列表/移动/删除基本操作接口 



# 接口1：秒传(POST方式)

文件秒传适用于客户端(包括：PC/移动客户端)，文件上传之前先计算文件的sha1值，通过秒传接口检查sha1值在系统中是否存在。如存在，则不用上传，直接为该用户生成元数据即可。否则返回上一次文件已上传文件的偏移量。客户端根据偏移量上传剩余文件内容。

## 访问地址

```html
http://demo.miniyun.cn/api.php
其中demo.miniyun.cn可替换为自己的迷你云地址

```


## 输入参数
```html
   {
    route:'file/sec',//接口路由地址
    access_token:xxxx,//当前登录的access_token
    sign:yyyy,//当前会话签名
    path:/1/abc/test.doc,//上传到云端的绝对路径，绝对路径包括有用户ID以及最后的文件名
    hash:xxxx,//文件内容的sha1值
    size:1234,//文件大小，单位字节 
   }
  ```
## 输出
### 迷你存储秒传失败
 ```html
   {
    success:false,//是否秒传成功
    store_type:"miniStore",存储类型如果不为空，则表示第3房存储载体，如果为空，说明是默认存储载体
    callback:"http://test.miniyun.cn/xxxxx",//如不为空，向url地址上传内容时，需要带上的url地址。当store_type="miniStore"时有效
    url:"http://test.miniyun.cn/api.php",//如success=false,文件上传的目标地址 
   }
  ```
### 默认存储秒传失败
 ```html
   {
    success:false,//是否秒传成功 
    url:"http://test.miniyun.cn/api.php",//文件上传的目标地址 
   }
  ```
### 秒传成功
 ```html
   {
    success:true,//秒传成功  
   }
  ```
# 错误码说明
 
