<?php
/**
 * Miniyun 用户转存接口，一个用户发送到另一个用户
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MSendController extends MCopyController {
    /**
     * (non-PHPdoc)
     * @see MCopyController::beforeInvoke()
     */
    protected function beforeInvoke() {
        $this->obtain_user();
        //
        // 获取用户数据，如user_id
        //
        $user                       = MUserManager::getInstance()->getCurrentUser();
        $device                     = MUserManager::getInstance()->getCurrentDevice();
        $this->owner                = $user["user_id"];
        $this->master               = $this->owner;
        $this->user_nick            = $user["user_name"];
        $this->_user_device_id      = $device["device_id"];
        $this->_user_device_name    = $device["user_device_name"];
    }
    /**
     * 
     * 获取用户
     */
    private function obtain_user() {
        $params    = $_REQUEST;
        $user_id   = null;
        #
        # 检查用户
        #
        $keys = array('id', 'user_name');
        foreach ($keys as $key) {
            $$key = isset($params[$key]) ? $params[$key] : null;
            if (!$$key) {
                continue;
            }
            if ($key=='id') {
                $attributes = array('id'=>$$key);
            } else {
                $attributes = array('user_name'=>$$key);
            }
            $user = User::model()->findByAttributes($attributes);
            if ($user)
                $user_id = $user->id;
        }
        if (!$user_id) {
            throw new MFileopsException(
                                        Yii::t('api','Did not find the target users.'),
                                        MConst::HTTP_CODE_404);
        }
        $this->_user_id = $user_id;
    }
}