<?php
/**
 * Miniyun 常量主要处理部分
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MConst
{
    /*
     * ============================================================
     * 每台设备对应oauth 生成token, secret 长度： 目前只有存2条数据
     * ============================================================
     * */
    const USER_METAS_LENGTH  = 2;
    /*
     * ============================================================
     * oauth 生成token, secret 长度
     * ============================================================
     * */
    const TOKEN_LENGTH  = 32;
    const SECRET_LENGTH = 32;
    /*
     * ============================================================
     * 服务器返回给客户端的错误信息
     * ============================================================
     * */

    // 用户空间不足
    const NOT_ENOUGH_SPACE                        = 8;
    // 用户被禁用
    const USER_BE_FORBIDDEN                       = 9;
    // 用户过期
    const USER_EXPIRED                            = 10;
    // 父目录不存在
    const DONT_EXIST_PARENT                       = 11;
    // 用户设备不存在
    const DONT_EXIST_DEVICE                       = 14;
    // 目录下还存在对象
    const EXIST_CHILD                             = 16;

    // 检查block的signature不存在 (此不需要错误处理)
    const DONT_EXIST_BLOCK_SIGNATURE              = 18;
    
    // 结束block事务失败
    const FINISH_BLOCK_FAILURE                    = 21;
    // 字符串长度不符合要求
    const WRONG_STRING_LENGTH                     = 23;
    // 不是共享目录
    const NOT_SHARE_DIRECTORY                     = 24;
    // 没有找到共享目录使用者
    const NO_USER_IN_SHARE_DIRECTORY              = 25;
    // 更新共享目录大小失败
    const UPDATE_SHARE_DIR_SIZE_FAILER            = 26;
    // 出现block缺失的情况
    const BLOCK_LOST                              = 28;
    // 远程数据不存在
    const DONT_EXIST_REMOTE_DATA                  = 29;
    // 根据block获取的ids与object_versions.block_ids不匹配 TODO:  (此不需要错误处理)
    const BLOCKS_NOT_MATCH_VERSION                = 30;
    // 创建事件失败
    const CREATE_EVENT_FAILURE                    = 31;
    // json异常
    const JSON_EXCEPTION                          = 32;
    // 未找到根目录
    const DONT_EXIST_ROOT_OBJECT                  = 33;
    // try catch捕捉到异常
    const EXCEPTION                               = 34;
    // 用户没有操作父目录权限
    const DONT_HAVE_PERMISSION                    = 35;
    // 重命名失败
    const RENAME_FAILURE                          = 37;
    // 对象类型不符合要求
    const OBJECT_TYPE_DONT_MEET_REQUIREMENT       = 40;
    // 目录正在被使用
    const DIRECTORY_IS_BEING_USED                 = 41;
    // 目录不为空
    const DIRECTORY_ISNOT_EMPTY                   = 42;
    // 用户session
    const SESSION_EXCEPTION                       = 43;
    // 用户尚未开始服务
    const USER_DONT_START_SERVICE                 = 44;
    //  没有找到version
    const DONT_EXIST_VERSION                      = 45;

    // 同时修改文件，产生冲突
    const MODIFY_FILE_CONFLICT                    = 61;

    //===============================================================
    // 已经使用的错误信息
    // TODO: 对接老系统定义的错误信息
    
    // 服务器返回的uploading_id < 1
    // TODO: php版本的protoc buffer协议暂时没有支持负数，需要后面进行处理
    const ERROR_UPLOADIND_ID                        = 102;
    // 没有父亲目录的uuid
    const ERROR_NO_PARENT_UUID                      = -1;
    // 正确执行
    const ERROR_SUCCESS                             = 0;
    // 重复创建 success = true
    // 元数据在系统中已经存在，不需要再进行上行操作
    const ERROR_VALID_OBJECT_UPLOADED               = 2;
    // 接收参数为空
    const ERROR_NULL_PARAM                          = 5;
    // 用户不存在
    const ERROR_USER_DONT_EXIST                     = 7;
    // 对象不存在
    const ERROR_DONT_EXIST_OBJECT                   = 13;
    // 检查block的signature存在 (此不需要错误处理)success = true
    const ERROR_EXIST_BLOCK_SIGNATURE               = 17;
    // 检查对象signature不存在 (此不需要错误处理)
    const ERROR_DONT_EXIST_OBJECT_SIGNATURE         = 20;
    // 没有找到对应的block
    const ERROR_DONT_EXIST_BLOCK                    = 22;
    // 参数动作类型错误
    const ERROR_WRONG_ACTION                        = 27;
    // 出现异常
    const ERROR_EXCEPTION                           = 34;
    // 非法请求
    const ERROR_ILLEGAL_REQUEST                     = 39;
    //
    // 创建文件，系统中存在同样的文件名称，且其hash值不一致
    // 服务器端需要发生重命名事件 success = true
    //
    const ERROR_RENAME_OBJECT                       = 49;
    // 父目录uuid为空，请求非法
    const ERROR_PARENT_UUID_IS_NULL                 = 53;
    // 错误的元数据类型，系统中已经存在命名为a的文件夹，上传一个命名为a的文件
    const ERROR_INVALID_OBJECT_TYPE                 = 100;
    
    //
    // TODO: 后面需要定义的错误信息
    //
    const ERROR_TO_BE_DONE                          = 1000;
    
    //******************************************************************************
    //                      定义上传, 下载文件失败的错误code
    //******************************************************************************
    const UPLOAD_FILE_FAILS                       = 7100;
    const DOWNLOAD_FILE_FAILS                     = 7200; 
    /*
     * ============================================================
     * 元数据相关属性
     * ============================================================
     * */
    //
    // 根目录uuid变量
    // "-1"是系统中特殊常量，表明用户的根目录，此记录在数据库中不存在
    //
    const ROOT_UUID                             = "-1";
    const ROOT_UUID_INT                         = -1;
    const ROOT_PATH                             = "\\";
    
    //
    // 元数据对象类型
    //
    const OBJECT_TYPE_BESHARED                  = 3;
    const OBJECT_TYPE_SHARED                    = 2;
    const OBJECT_TYPE_DIRECTORY                 = 1;
    const OBJECT_TYPE_FILE                      = 0;

    //
    // 元数据uuid长度
    //
    const LEN_OBJECT_UUID                       = 32;
    //
    // 事件uuid长度 32+14
    //
    const LEN_EVENT_UUID                        = 46;
    
    /*
     * ============================================================
     * 上行操作逻辑分组处理
     * ============================================================
     * */
    //
    // 需要查找uuid
    //
    const OPERATION_UP_UUIDS                    = 0;
    //
    // 上行创建操作
    //
    const OPERATION_UP_CREATE                   = 1;
    const OPERATION_CHILDS                      = 2;
    //
    // 上行文件操作需要处理其hash值信息
    //
    const OPERATION_UP_SIGNATURES               = 3;
    //
    // 单独处理meta的操作逻辑
    // 针对修改文件（夹）名称、移动文件（夹）等操作，需要单独对数据库进行处理操作
    //
    const OPERATION_UP_SINGLE                   = 4;
    //
    // 上行删除操作
    //
    const OPERATION_UP_DELETES                  = 5;
    //
    // 上行list_file列表
    //
    const OPERATION_UP_LISTS                    = 6;
    //
    // 上行操作所有列表
    //
    const OPERATION_TOTAL_ACTIONS               = 7;
    //
    // 产生事件列表
    //
    const OPERATION_EVENTS                      = 8;

     /*
     * ============================================================
     * 验证
     * ============================================================
     * */
    //
    // 不存在任何地方
    //
    const DB_STATUS_NOT_EXIST                  = 0;
    //
    // 存在blcok表中
    //
    const DB_STATUS_EXIST_BLOCK                = 1;
    //
    // 存在uploading表中
    //
    const DB_STATUS_EXIST_UPLOADING            = 2;
    /*
     * ============================================================
     * 对文件请求操作进行签名的常量
     * ============================================================
     * */
    const ACCESS_KEY                            = "Fw5GmdDY8h3JmBnN";
    const EXPIRATION_DATE                       = "expiration_date";
    const ACCESS_KEY_ID                         = "\${access.key.id}";
    
    /*
     * ============================================================
     * 文案
     * ============================================================
     * */
    const SUCCESS_COPY                          = 'success';
    const LOGIN_FAIL_COPY                       = 'Parse login request failed';
    const USER_NOT_EXIST_COPY                   = 'User Dont Exist';
    const ASSEMBLY_PROTOC_EXCEPTION_COPY        = 'Assembly Protocol Exception';
    const UNAUTHORIZED                          = 'Unauthorized';
    const PARAMS_ERROR                          = "Bad input parameter.";
    const FILE_NOT_EXIST                        = "File don't exist.";
    const UPLOAD_OVER                           = "Upload file success.";
    const INVLID_REQUEST                        = "Bad Request.";
    const UPLOAD_FILE_ERROR                     = "Upload file error.";
    const NOT_FOUND                             = "NOT FOUND.";
    const INTERNAL_SERVER_ERROR                 = "Internal Server Error";
    const REQUEST_MOTHOD_ERROR                  = "Request method not expected (generally should be GET or POST).";
    const PATH_ERROR                            = "Request path is null.";
    const NOT_ACCEPTABLE                        = "Not acceptable";
    const CONTINUE_OPTION                       = "Continue";
    const RETRY_WITH                            = 'Retry With';
    /*
     * ============================================================
     * 下载文件是安全机制，组装post的端类型
     * ============================================================
     * */
    const PHONE     = 1;
    const WEB       = 2;
    const PCCLIENT  = 3;
    
    //
    // 表示请求事件列表时，对应事件uuid
    //
    const EVENT_UUID_ZERO           = 0;
    /*
     * ============================================================
     * 文件/目录事件
     * ============================================================
     * */
    const CREATE_DIRECTORY       = 0;  // 创建目录
    const DELETE                 = 1;  // 删除目录、文件
    const MOVE                   = 2;  // 移动目录、文件,或者重命名
    const CREATE_FILE            = 3;  // 创建文件
    const MODIFY_FILE            = 4;  // 修改文件
    const SHARE_FOLDER           = 5;  // 创建共享
    const CANCEL_SHARED          = 6;  // 取消共享
    const READONLY_SHARED        = 7;  // 创建只读共享
    const UPDATE_SPACE_SIZE      = 8;  // 修改用户空间大小
    const CAN_READ               = 12; // 设置为可读状态
    const CAN_NOT_READ           = 13; // 设置为不可读状态
    const DEFAULT_PERMISSION_CHANGE_TO_CAN_READ     = 14; // 默认权限从不能读变化为能读
    const DEFAULT_PERMISSION_CHANGE_TO_CAN_NOT_READ = 15; // 默认权限从能读变化为不能读
    const SHARED_ICON            = 16; // 共享或者公共目录创建事件，客户端只修改图标
    const GROUP_MOVE             = 17; // 用户组移动目录、文件,或者重命名
    const COPY                   = 128;
    const RENAME                 = 256; // 重命名---数据库不记录该值
     
    /*
     *=======================
     *  处理版本历史里面的action
    */
    const RESTORE               = 6; //  文件版本恢复
    
    /*
     * ============================================================
     * meta常量
     * ============================================================
     */
    const CURRENT_SIZE   = "current_size";
    const MIME_TYPE      = "mime_type";
    const VERSION        = "version";
    const SHARED_FOLDERS = "shared_folders";
    
    /*
     * ============================================================
     * HTTP ERROR CODE 常量
     * ============================================================
     */
    
    const HTTP_CODE_200 = "200";
    const HTTP_CODE_301 = "301";
    const HTTP_CODE_304 = "304";
    const HTTP_CODE_303 = "303";        // 表示继续
    const HTTP_CODE_400 = "400";
    const HTTP_CODE_401 = "401";
    const HTTP_CODE_402 = "402";        //  创建文件夹时存在文件
    const HTTP_CODE_403 = "403";        //  文件夹已存在，
    const HTTP_CODE_404 = "404";
    const HTTP_CODE_405 = "405";
    const HTTP_CODE_406 = "406";
    const HTTP_CODE_407 = "407";        //  用户已禁用
    const HTTP_CODE_409 = "409";        //  用户无写入权限
    const HTTP_CODE_410 = "410";        //  用户设备不足
    const HTTP_CODE_440 = "440";        //  时间过期
    const HTTP_CODE_441 = "441";        //  域名错误
    const HTTP_CODE_442 = "442";        //  ministor错误
    
    const HTTP_CODE_411 = "411";
    const HTTP_CODE_415 = "415";
    const HTTP_CODE_449 = "449";        // Retry With，代表请求应当在执行完适当的操作后进行重试。
    const HTTP_CODE_500 = "500";
    const HTTP_CODE_503 = "503";
    const HTTP_CODE_507 = "507";
    /*
     * ============================================================
     * 请求链接地址，开放api
     * ============================================================
     */
    const API = "/api.php/1/";
     /*
     * ============================================================
     * API版本1
     * ============================================================
     */
    const VERSION_1 = "/1/";
    /*
     * ============================================================
     * 文件操作，最大限制数
     * ============================================================
     */
    const MAX_FILES_COUNT = 10000;
    /*
     * ============================================================
     * 冲突文件，命名字符串
     * ============================================================
     */
    const CONFLICT_FILE_NAME = "(冲突文件)";
    /*
     * ============================================================
     * 默认文件下载输出方式
     * ============================================================
     */
    const DEFAULT_DOWNLOAD_MIME_TYPE = "application/force-download";
    const DEFAULT_FILE_MIME_TYPE     = "application/octet-stream";
    /*
     * ============================================================
     * 图片生成缩略图的最大值，超过这个值将不会生成缩略图 20M (参考dropbox)
     * ============================================================
     */
    const MAX_IMAGE_SIZE = 20971520; // 20 * 1024 * 1024
    
    //
    // sql语句最大执行长度1M
    //
    const MAX_SQL_STRING_LENGTH = 1048576;
    const MAX_VERSION_CONUNT = 100;
    
    
    //
    // 设备类型 
    //
    const DEVICE_WEB     = 1;
    const DEVICE_MAC     = 2;
    const DEVICE_WINDOWS = 3;
    const DEVICE_ANDROID = 4;
    const DEVICE_IPHONE  = 5;
    const DEVICE_IPAD    = 6;

    //
    // 登陆注销
    //
    const LOGIN  = 0;
    const LOGOUT = 1;
    /*
     * ============================================================
     * 用户登录错误代码
     * ============================================================
     * @since 1.1.1
     */
    const ERROR_NONE             = 0;
    //用户名不存在
    const ERROR_USERNAME_INVALID = 1;
    //密码不正确
    const ERROR_PASSWORD_INVALID = 2;
    //用户被冻结
    const ERROR_USER_DISABLED    = 3;
    // 设备限制
    const ERROR_DEVICE_LIMIT     = 4;
    // 用户数限制
    const ERROR_USER_LIMIT       = 5;
    // domain限制
    const ERROR_DOMAIN_LIMIT     = 6;
    // time限制
    const ERROR_TIME_LIMIT       = 7;
    //smtp连接错误
    const ERROR_SMTP_CONNECT     = 101; 
}
