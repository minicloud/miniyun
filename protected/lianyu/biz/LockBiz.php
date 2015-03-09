<?php

/**
 * 文件锁业务
 */
class LockBiz extends MiniBiz{
     static private $_instance = null;
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
     private function quickSort($array){
        $len = count($array);
        if($len <= 1)
        {
            return $array;
        }
        $key = strtotime($array[0]['open_time']);
        $left = array();
        $right = array();
        for($i=1; $i<$len; $i++)
        {
            if(strtotime($array[$i]['open_time']) < $key)
            {
                $left[] = $array[$i];
            }
            else
            {
                $right[] = $array[$i];
            }
        }
        $left = $this->quickSort($left);
        $right = $this->quickSort($right);
        return array_merge($left, array($array[0]), $right);
    }
    /**
     * 查找文件是否被锁定
     */
    public function status($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath,'lock');
        $isLock = false;
        $userId = $this->user['id'];
        $isSelf = false;//判断是否自己去操作修改
        $index = 0;
        $minArray = array();
        $effectArray = array();
        if(count($fileMeta)!=0){
            $metaValues = unserialize($fileMeta['meta_value']);
            $nowTime = time();
            foreach($metaValues as $metaValue){
                $openTime = strtotime($metaValue['open_time']);
                if($metaValue['user_id']==$userId&&$nowTime-$openTime<1800){
                    $isSelf = true;
                    break;
                }
                if($nowTime-$openTime<1800){
                    $effectArray[] = $metaValue;
                    $index++;
                }
            }
            $sortArray = $this->quickSort($effectArray);
            if(count($sortArray)!=0){
                $minArray = $sortArray[count($sortArray)-1];
                $user = MiniUser::getInstance()->getById($minArray['user_id']);
                $minArray['user_name'] = $user['nick'];
            }
        }
        if(!$isSelf&&$index>0){
            $isLock = true;
        }
        return array('success'=>$isLock,'data'=>$minArray);
    }
    /**
     * @param $filePath
     * @return mixed
     */
    public function create($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath,'lock');
        $device                = MUserManager::getInstance ()->getCurrentDevice();
        $metaValues = array();
        $userId = $this->user['id'];
        if(count($fileMeta)!=0){
            $values = unserialize($fileMeta['meta_value']);
            //判断有没有重复的数据,如果有重复数据，不添加
            $isDouble = false;
            foreach($values as $value){
                if($value['user_id']==$userId){
                    $value['open_time'] = date('Y-m-d H:i:s');
                    $isDouble = true;
                }
                $metaValues[] = $value;
            }
            if(!$isDouble){
                $metaValues[] = array('user_id'=>$userId,'device_name'=>$device['user_device_name'],'open_time'=>date('Y-m-d H:i:s'));
            }
        }else{
            $metaValues[] = array('user_id'=>$userId,'device_name'=>$device['user_device_name'],'open_time'=>date('Y-m-d H:i:s'));
        }
        return MiniFileMeta::getInstance()->createFileMeta($filePath,'lock',serialize($metaValues));
    }

    /**
     * @param $filePath
     * @return mixed
     */
    public function delete($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getFileMeta($filePath,'lock');
        $items = array();
        $userId = $this->user['id'];
        $metaValues = unserialize($fileMeta['meta_value']);
        foreach($metaValues as $metaValue){
            if($metaValue['user_id']==$userId){
                continue;
            }
            $items[] = $metaValue;
        }
        if(count($items)==0){
            return MiniFileMeta::getInstance()->deleteFileMetaByPath($filePath,"lock");
        }
        return MiniFileMeta::getInstance()->updateFileMeta($filePath,'lock',serialize($items));
    }
}