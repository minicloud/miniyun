<?php
/**
 * 用户扩展属性表
 *
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */ 
class UserMeta extends CMiniyunModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return Yii::app()->params['tablePrefix'].'user_metas';
    }

    /**
     *
     * 获得用户基本的Mate的数据，包括：email/is_admin/nick/avatar,用户列表场景
     */
    public function getUserBaseMate($ids){
        if($ids=="") return array();
        return $this->findAll(array('condition'=>"user_id in(".$ids.") and meta_key in('email','avatar','nick','is_admin','phone','space')"));
    }

    /**
     *
     * 删除用户元数据
     */
    public function  deleteUserMeta($userIds){
        if($userIds!='' && strlen($userIds)>0){
            $this->deleteAll("user_id in(".$userIds.")");
        }
    }

    /**
     *
     * 判断给出的userId是否为最后的管理员
     *
     */
    public function isLastAdmin($userIds){
        return $this->count("meta_key='is_admin' and meta_value='1' and user_id not in(".$userIds.")")==0?true:false;
    }


    /**
     * @return array validation rules for model attributes.
     */
//    public function rules()
//    {
//        // NOTE: you should only define rules for those attributes that
//        // will receive user inputs.
//        return array(
//        array('user_id, meta_key, meta_value', 'required'),
//        array('user_id', 'numerical', 'integerOnly'=>true),
//        array('meta_key', 'length', 'max'=>255),
//        // The following rule is used by search().
//        // Please remove those attributes that should not be searched.
//        array('id, user_id, meta_key, meta_value, created_at, updated_at', 'safe', 'on'=>'search'),
//        );
//    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
        //             'user'=>array(self::BELONGS_TO, 'MiniyunUsers', 'id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'user_id' => 'User',
            'meta_key' => '信息',
            'meta_value' => '信息',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search()
    {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria=new CDbCriteria;

        $criteria->compare('id',$this->id);
        $criteria->compare('user_id',$this->user_id);
        $criteria->compare('meta_key',$this->meta_key,true);
        $criteria->compare('meta_value',$this->meta_value,true);
        $criteria->compare('created_at',$this->created_at,true);
        $criteria->compare('updated_at',$this->updated_at,true);

        return new CActiveDataProvider($this, array(
            'criteria'=>$criteria,
        ));
    }
    
    
    /**
     * 
     * 根据path 和 key查询记录
     * @param string $filePath
     * @param string $metaKey
     */
    public function getUserMeta($userId, $metaKey, $all = false) {
        if ($all) {
            return $this->findAll(array('condition' => 'user_id=:user_id and meta_key =:meta_key',
                                        'params'    => array(':user_id'=>$userId, ':meta_key' => $metaKey)));
        } else {
            return $this->find(array('condition' => 'user_id=:user_id and meta_key =:meta_key',
                                     'params'    => array(':user_id'=>$userId, ':meta_key' => $metaKey)));
        }
    }
}