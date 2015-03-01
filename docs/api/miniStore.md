迷你存储API，迷你存储是独立于迷你云单独存在的服务。当前尚未开源。

**目录**

[TOC]

#文件秒传
## 访问地址
    访问地址来源就是客户端向迷你云发送秒传接口的返回值
	http://st1.miniyun.cn/api.php

## 输入
| 名称        | 类型   |  默认值或说明  |
| :--------   | :-----  | :----  |
| route      | string   |   file/sec     |
| signature        |   string   |   文件内容sha1编码   |
| size        |    int    |  文件大小，单位：字节  |
| callback        |    string    |  秒传成功后的回调地址，回调地址来源迷你云秒传接口的返回值  |

## 输出格式json
### 秒传失败返回值
| 名称        | 类型   |  默认值或说明  |
| :--------   | :-----  | :----  |
| success      | boolean   |   false表示秒传失败，需上传文件     |
| offset        |   int   |   当前在迷你存储断点文件大小，单位：字节   |
### 秒传成功返回值
    TODO 返回文件信息
    
#文件块上传

    文件块上传，用于PC、Android、iOS等客户端。
    服务器端将根据signature寻找到断点文件，如不存在，则新建文件。如存在，则把文件块append到断点文件后面。
    如服务器端文件与size一致，则认为文件上传完成。
    
## 访问地址

	http://st1.miniyun.cn/api.php

## 输入
| 名称        | 类型   |  默认值或说明  |
| :--------   | :-----  | :----  |
| route      | string   |   file/upload     |
| signature        |   string   |   文件内容sha1编码   |
| size        |    int    |  文件大小，单位：字节  | 
| file        |    file    |  文件块内容  |
| callback        |    string    |  秒传成功后的回调地址，回调地址来源迷你云秒传接口的返回值  |


## 输出格式json
### 文件块上传成功
| 名称        | 类型   |  默认值或说明  |
| :--------   | :-----  | :----  |
| status      | int   |   1，表示该文件块上传成功     | 

### 文件所有块上传成功
    TODO返回文件信息