<?php
/**
 * 标签表
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class Tag extends CMiniyunModel {
    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * (non-PHPdoc)
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'tags';
    }

    /**
     * 判断标签是否属于用户自己的标签
     */
    public function isOwnTag($user_id)
    {
        //
        // 用户id相同，则属于自己的标签
        //
        if ($this->user_id == $user_id)
        return true;

        //
        // 系统标签页属于自己可以拥有的标签
        //
        if ($this->user_id == 0)
        return true;

        return false;
    }

    /**
     * @summary 判断标签是否是系统标签
     *
     * @since 1.0.7
     * */
    public function isSystemTag()
    {
        return $this->user_id == 0;
    }

    /**
     * 删除指定用户的所有标签
     *
     * @param $user_id 用户编号
     *
     * @since 1.0.7
     */
    public function deleteUserAllTag($user_id)
    {
        $tags = $this->findAll('user_id=:user_id', array(':user_id'=>$user_id));
        foreach ($tags as $tag){
            //删除文件标签关系
            FileTag::model()->deleteAll('tag_id=:tag_id', array(':tag_id'=>$tag["id"]));
            //删除用户标签
            $tag->delete();
        }
    }

}