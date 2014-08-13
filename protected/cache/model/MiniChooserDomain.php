<?php
/**
 * 缓存miniyun_chooser_domains表的记录，V1.2.0该类接管所有miniyun_的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniChooserDomain extends MiniCache{
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.MiniChooserDomain";

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
     *      数据库值序列化
     */
    private function db2list($items){
        $data=array();
        foreach($items as $item) {
            array_push($data, $this->db2Item($item));
        }
        return $data;
    }
    public function db2Item($item){
        if(empty($item)) return NULL;
        $value                     = array();
        $value["id"]               = $item->id;
        $value["chooser_id"]          = $item->chooser_id;
        $value["domain"]           = $item->domain;
        $value['created_at']       = $item->created_at;
        $value["updated_at"]       = $item->updated_at;
        return $value;
    }
    /**
     * @param $id  根据id来获取域名数据
     * @return mixed
     */
    private  function getDomain($id){
       $data   = ChooserDomain::model()->findByPk($id);
        return $data;
   }
    /**
     * @param $chooserId 通过chooserId来获取域名
     * @return mixed
     */
    public function getByChooserId($chooserId){
        $items    = ChooserDomain::model()->findAll("chooser_id=:chooserId",array(":chooserId"=>$chooserId));
        if(empty($items)){
         return null;
        }
        return  $this->db2list($items);
    }

    /**
     * 补全domain信息
     * @param $domain
     * @return string
     */
    private function getFullDomain($domain){
        if(preg_match("/(https?):\/\//",$domain)==0){
            $domain = "http://".$domain;
        }
        return $domain;
    }
    /**
     * @param $chooserId
     * @param $domain 创建domain
     * @return bool
     */
    Public function create($chooserId,$domain){
        $domain = $this->getFullDomain($domain);
        // 删除 cache
        $this->deleteCache(MiniChooserDomain::$CACHE_KEY);
        $model       =  ChooserDomain::model()->find("chooser_id=:chooserId and domain=:domain",array(":chooserId" => $chooserId,":domain"=>$domain));
        if (empty($model)) {
            $model = new ChooserDomain();
        }
        $model["chooser_id"]     = $chooserId;
        $model["domain"]      = $domain;
        $model->save();
        return true;
    }
    /**
     * 删除 domain
     */
    public  function deleteDomain($id) {
        $data = $this->getDomain($id);
        if(isset($data)){
            $data->delete();
        }
        return true;
    }
    /**
     * 根据Domain来获取记录
     */
    public function getByDomain($chooserId,$domain){
        $domain = $this->getFullDomain($domain);
        $criteria = new CDbCriteria();
        $criteria->condition = 'chooser_id=:chooser_id and domain=:domain';
        $criteria->params = array(':chooser_id'=>$chooserId,':domain'=>$domain);
        $item = ChooserDomain::model()->find($criteria);
        return $this->db2Item($item);
    }
    /**
     * 根据id来获取domain数据
     */
    public function getById($id){
        $item = ChooserDomain::model()->findByPk($id);
        return $this->db2Item($item);
    }
}
