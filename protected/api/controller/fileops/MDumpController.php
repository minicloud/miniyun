<?php
/**
 * Miniyun 用户转存接口，即一个用户copy到另一个用户
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MDumpController extends MCopyController {
    /**
     * (non-PHPdoc)
     * @see MCopyController::beforeInvoke()
     */
    protected function beforeInvoke() {
        parent::beforeInvoke();
        $owner = $this->obtain_user();
        $this->owner = $owner;
        $this->master = $owner;
    }
    
    /**
     * 
     * 获取用户
     * @param mixed $params
     */
    private function obtain_user() {
        $params    = $_REQUEST;
        $user_id   = null;
        # 检查用户
        $keys = array('target', 'username');
        foreach ($keys as $key) {
            $$key = isset($params[$key]) ? $params[$key] : null;
            if (!$$key) {
                continue;
            }
            if ($key=='target') {
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
        return $user_id;
    }
}