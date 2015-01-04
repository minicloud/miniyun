<?php

/**
 * 文件锁业务
 */
class LockBiz extends MiniBiz{

    /**
     * 查找文件是否被锁定
     */
    public function search($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getMetaByPath($filePath);
        $isLock = false;
        $userId = $this->user['id'];
        $isSelf = false;//判断是否自己去操作修改
        $index = 0;
        if(count($fileMeta)!=0){
            $metaValues = unserialize($fileMeta['meta_value']);
            $nowTime = time();
            foreach($metaValues as $metaValue){
                if($metaValue['user_id']==$userId){
                    $isSelf = true;
                    break;
                }
                $openTime = strtotime($metaValue['open_time']);
                if($nowTime-$openTime<1800){
                   $index++;
                }
            }
        }
        if(!$isSelf&&$index>0){
            $isLock = true;
        }
        return array('success'=>$isLock);
    }

    /**
     * @param $filePath
     * @return mixed
     */
    public function create($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getMetaByPath($filePath);
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
                $metaValues[] = array('user_id'=>$userId,'open_time'=>date('Y-m-d H:i:s'));
            }
        }else{
            $metaValues[] = array('user_id'=>$userId,'open_time'=>date('Y-m-d H:i:s'));
        }
        return MiniFileMeta::getInstance()->createFileMeta($filePath,'lock',serialize($metaValues));
    }

    /**
     * @param $filePath
     * @return mixed
     */
    public function delete($filePath){
        $fileMeta = MiniFileMeta::getInstance()->getMetaByPath($filePath);
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
            return MiniFileMeta::getInstance()->deleteFileMetaByPath($filePath);
        }
        return MiniFileMeta::getInstance()->updateFileMeta($filePath,'lock',serialize($items));
    }
}