<?php
/**
 * 缓存miniyun_file_versions表的记录
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginMiniDocVersion extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.PluginMiniDocVersion";

    /**
     *  静态成品变量 保存全局实例
     *  @access private
     */
    static private $_instance = null;

    /**
     *  私有化构造函数，防止外界实例化对象
     */
    private function  __construct()
    {
        parent::MiniCache();
    }

    /**
     * 静态方法, 单例统一访问入口
     * @return object  返回对象的唯一实例
     */
    static public function getInstance()
    {
        if (is_null(self::$_instance) || !isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * 按照id逐一放入内存
     */
    private function getCacheKey($id){
        return PluginMiniDocVersion::$CACHE_KEY."_".$id;
    }
    /**
     * 把数据库值序列化
     * @param $items version表记录列表
     * @return array
     */
    private function db2list($items){
        $data  = array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    /**
     * 通过id获得记录
     * @param string $id
     * @return array
     */
    private function get4DbById($id){
        $item =  FileVersion::model()->find("id=:id",array("id"=>$id));
        return $this->db2Item($item);
    }
    /**
     * 把数据库值序列化
     * @param $item version表记录
     * @return array
     */
    private function db2Item($item){
        if(!isset($item)) return NULL;
        $value                   = array();
        $value["id"]             = $item->id;
        $value["file_signature"] = $item->file_signature;
        $value["file_size"]      = $item->file_size;
        $value["block_ids"]      = $item->block_ids;
        $value["ref_count"]      = $item->ref_count;
        $value["mime_type"]      = $item->mime_type;
        $value["created_at"]      = $item->created_at;
        $value["createTime"]      = strtotime($item->created_at);
        $value['doc_convert_status'] = $item->doc_convert_status;
        return  $value;
    }
    /**
     * 获得迷你文档需要转换的文件列表，每次返回最多10条记录
     * @param $status
     * @return array
     * doc_convert_status:-1
     * mime_type:
     * application/msword
     * application/mspowerpoint
     * application/msexcel
     * application/pdf
     */
    public function getDocConvertList($status=0){

        $mimeTypeList = array("application/mspowerpoint","application/msword","application/msexcel","application/pdf");
        $data = array();
        foreach ($mimeTypeList as $mimeType){
            $criteria                = new CDbCriteria();
            $criteria->condition     = "doc_convert_status=:doc_convert_status  and  mime_type=:mime_type";
            $criteria->limit         = 10;
            $criteria->offset        = 0;
            $criteria->params        = array(
                "mime_type"=>$mimeType,
                "doc_convert_status"=>$status
            );
            $list = FileVersion::model()->findAll($criteria);
            if(count($list)>0){
                $list = $this->db2list($list);
                foreach($list as $item){
                    array_push($data,$item);
                }

            }
        }
        if($status==0){
            //如果是status=0，需要把系统下的doc_convert_status=1的状态重新提交一下即可
            //如果文件比较多，多运行几次即可
            foreach ($mimeTypeList as $mimeType){
                $criteria                = new CDbCriteria();
                $criteria->condition     = "doc_convert_status=1  and  mime_type=:mime_type";
                $criteria->limit         = 10;
                $criteria->offset        = 0;
                $criteria->params        = array(
                    "mime_type"=>$mimeType,
                    "doc_convert_status"=>$status
                );
                $list = FileVersion::model()->findAll($criteria);
                if(count($list)>0){
                    $list = $this->db2list($list);
                    foreach($list as $item){
                        array_push($data,$item);
                    }

                }
            }
        }
        return $data;

    }
    /**
     * 更改文档转换状态
     * doc_convert_status:-1 表示转换失败
     * doc_convert_status:0 表示尚未转换
     * doc_convert_status:1 表示正在转换
     * doc_convert_status:2 表示转换成功
     * @param $hash 文件内容hash值
     * @param $status 文件转换状态值
     * @return boolean
     */
    public function updateDocConvertStatus($hash,$status){
        $version         =  FileVersion::model()->find("file_signature=:signature",array("signature"=>$hash));
        if(isset($version)){
            $version["doc_convert_status"] = $status;
            $version->save();
            return true;
        }
        return false;

    }
    /**
     * 根据id获得version完整信息
     * @param $id
     * @return array
     */
    public function getVersion($id){
        return $this->get4DbById($id);
    }
    /**
     * 通过signature获得记录
     * @param string $signature
     * @return array
     */
    private function get4DbBySignature($signature){
        $item =  FileVersion::model()->find("file_signature=:signature",array("signature"=>$signature));
        return $this->db2Item($item);
    }
    /**
     * 根据signature获得Version完整信息
     * @param $signature
     * @return array
     */
    public function getBySignature($signature){
        return $this->get4DbBySignature($signature);
    }
    /**
     * 获得要转换文档列表
     * @param $versions 文件版本列表
     * @return array
     */
    private function getReadyConvertList($versions){
        $miniHost = PluginMiniDocOption::getInstance()->getMiniyunHost();
        //报俊地址
        $reportUrl = $miniHost."api.php?route=module/miniDoc/report";
        //下载文件地址
        $downloadUrl =$miniHost."api.php?route=module/miniDoc/download";
        if(count($versions)>0){
            $data = array("report_success_url"=>$reportUrl);
            $items = array();
            foreach ($versions as $version) {
                $item = array(
                    'hash' => $version["file_signature"],
                    'mime_type' => $version["mime_type"],
                    'url' => $downloadUrl."&hash=".$version["file_signature"],
                );
                array_push($items, $item);
            }
            $data["list"] = $items;
            return $data;
        }
        return NULL;
    }
    /**
     * 单个提交转换请求
     * @param $signature
     */
    public function pushConvertSignature($signature){
        $version = $this->getBySignature($signature);
        if(!empty($version)){
            $this->pushConvert(array($version));
        }
    }
    /**
     * 批量提交提交转换请求
     * @param $versions
     */
    public function pushConvert($versions){
        $params = $this->getReadyConvertList($versions);
        $url = PluginMiniDocOption::getInstance()->getMiniDocHost().'/convert';
        $data = array ('task' =>json_encode($params));
        $http = new HttpClient();
        $http->post($url,$data);
        $result =  $http->get_body();
        $result = json_decode($result,true);
        if($result['task']=='received'){
            //修改文档的转换状态为转换中
            foreach ($versions as $version) {
                $this->updateDocConvertStatus($version["file_signature"],1);
            }
        }
    }
}