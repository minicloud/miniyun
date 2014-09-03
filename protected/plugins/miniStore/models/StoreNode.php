<?php
/**
 *
 * stor Model
 * @author Kindac-win7
 *
 */
class StoreNode extends CMiniyunModel {
    
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public function tableName() {
        return Yii::app()->params['tablePrefix'] . 'store_nodes';
    }
}