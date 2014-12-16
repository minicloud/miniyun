<?php
/**
 * 事件业务处理类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class EventBiz extends MiniBiz
{
    /**
     * 获取时间
     */
    private function getTime($time)
    {
        if ($time != "-1") {
            $time = date("Y-m-d", $time);
        }
        return $time;
    }

    /**
     *   获取events数据
     */
    public function getList($path, $time, $deviceUuid, $pageSize, $currentPage)
    {
        $user = $this->user;
        $userId = $user['id'];
        $time = $this->getTime($time);
        if ($path != "") {
            $path = MiniUtil::joinPath($path) . "/%";
        }
        $total = MiniEvent::getInstance()->getTotal($path, $time, $userId, $deviceUuid);
        $totalPage = ceil($total / $pageSize);
        $events = MiniEvent::getInstance()->getByCondition($path, $userId, $time, $deviceUuid, $pageSize, ($currentPage - 1) * $pageSize);
        $itemList = array();
        $data = array();
        foreach ($events as $event) {
            $item = array();
            $device = MiniUserDevice::getInstance()->getUserDevice($event['user_device_id']);
            $item['create_user_id'] = $device['user_id'];
            $item['file_path'] = MiniUtil::getRelativePath($event['file_path']);
            $item['action'] = $event['action'];
            $item ['user_name'] = $user['user_name'];
            $item ['user_device_type'] = $device['user_device_type'];
            if ($device['user_id'] == $userId) {
                $item ['user_self'] = true;
            } else {
                $item ['user_self'] = false;
                $user = MiniUser::getInstance()->getById($device['user_id']);
                $userMetas = MiniUserMeta::getInstance()->getUserMetas($device['user_id']);
                if(isset($userMetas['nick'])){
                    $item['user_name'] = $userMetas['nick'];
                }else{
                    $item['user_name'] = $user['user_name'];
                }

            }
            $item ['created_at'] = MiniUtil::formatTime(strtotime($event['created_at']));
            $item ['user_device_name'] = $device['user_device_name'];
            $item ['context'] =  MiniUtil::getRelativePath($event['context']);
            $item ['device_uuid'] = $device['user_device_uuid'];
            if ($event['action'] == 2) { //判断是否是重命名还是创建
                $fromParent = CUtils::pathinfo_utf($event['file_path']);
                $toParent = CUtils::pathinfo_utf($event['context']);
                if ($fromParent['dirname'] == $toParent['dirname']) {
                    $item ['action'] = MConst::RENAME;
                }
            }
            $itemList[] = $item;
        }
        $data['events'] = $itemList;
        $data['totalPage'] = $totalPage;
        return $data;
    }
}