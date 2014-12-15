<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Mac
 * Date: 14-9-22
 * Time: 下午4:30
 * To change this template use File | Settings | File Templates.
 */

class MiniMessage extends MiniCache{
    const LEN_KEY     = 14;
    public static $ANDROID_TYPE = "2";
    /**
     *
     * Cache Key的前缀
     * @var string
     */
    private static $CACHE_KEY = "cache.model.MiniChooser";

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
        $value["content"]             = $item->content;
        $value["status"]          = $item->status;
        $value["user_id"]             = $item->user_id;
        $value["uu_id"]             = $item->uu_id;
        $value['created_at']       = $item->created_at;
        $value['updated_at']       = $item->updated_at;
        return $value;
    }


    public function getMessageList($pageSize,$pageSet,$userId){
        $criteria=new CDbCriteria;
        $criteria->select   ='*';
        $criteria->limit    = $pageSize;
        $criteria->offset   = $pageSet;
        $criteria->condition     = "user_id=:userId";
        $criteria->params        = array(':userId'=>$userId);
        $criteria->order    ='updated_at desc';
        $items = Message::model()->findAll($criteria);
        return $this->db2list($items);
    }

    public function getMessageCount($userId){
        $criteria=new CDbCriteria;
        $criteria->select   ='*';
        $criteria->condition     = "user_id=:userId";
        $criteria->params        = array(':userId'=>$userId);
        $total = Message::model()->count($criteria);
        return $total;
    }

    public function getMessageStatus($userId){
        $criteria=new CDbCriteria;
        $criteria->select   ='*';
        $criteria->condition     = "user_id=:userId and status <1";
        $criteria->params        = array(':userId'=>$userId);
        $criteria->order    ='updated_at desc';
        $items = Message::model()->findAll($criteria);
        return $this->db2list($items);
    }


    public function getMessageStatusCount($userId){
        $criteria=new CDbCriteria;
        $criteria->select   ='*';
        $criteria->condition     = "user_id=:userId and status <1";
        $criteria->params        = array(':userId'=>$userId);
       $total= Message::model()->count($criteria);
        return $total;
    }

    public function updateAllStatus($userId){
        $criteria            = new CDbCriteria();
        $criteria->condition = 'status = -1  and user_id=:userId';
        $attributes          = array(
            'status'=>1
        );
        $criteria->params        = array(':userId'=>$userId);
        Message::model()->updateAll($attributes,$criteria);
        return true;

    }
    public function updateStatus($id,$userId){
        $criteria            = new CDbCriteria();
        $criteria->condition = 'status = -1 and id=:id and user_id=:userId';
        $attributes          = array(
            'status'=>1
        );
        $criteria->params        = array(':id'=>$id,':userId'=>$userId);
        Message::model()->updateAll($attributes,$criteria);
        return true;

    }




}