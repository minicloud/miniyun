迷你存储API，迷你存储是独立于迷你云单独存在的服务。当前尚未开源。
不过我们提供了API文档

**目录**

[TOC]

#文件秒传
## 访问地址

	http://st1.miniyun.cn/api.php

## 输入
| 名称        | 类型   |  默认值或说明  |
| --------   | -----:  | :----:  |
| route      | string   |   file/sec     |
| signature        |   string   |   文件内容sha1编码   |
| size        |    int    |  文件大小，单位：字节  |

## 输出
| 名称        | 类型   |  默认值或说明  |
| --------   | -----:  | :----:  |
| success      | boolean   |   true表示秒传成功，false表示秒传失败，需上传文件     |
| offset        |   int   |   当前在迷你存储断点文件大小，单位：字节   |

