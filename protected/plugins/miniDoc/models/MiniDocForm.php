<?php
/**
 *      [迷你云] (C)2009-2012 南京恒为网络科技.
 *   软件仅供研究与学习使用，如需商用，请访问www.miniyun.cn获得授权
 * 
 */
?>
<?php

class MiniDocForm extends CFormModel {

    public $ip;    public $port;    public $id;    public $isModify;
    
    public function rules(){
        return array(
            array('ip,port','required'),
            array('id','checkID'),
            array('ip', 'checkIP_Port'),
        );
    }

    public function checkID(){

        if(empty($this->id)){
            $this->isModify = false;
        }else{
            $this->isModify = true;
        }
    }

    
    public function checkIP_Port(){
        $serverIP = MiniDocNode::getInstance()->getByIP($this->ip);
        if(!preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->ip)) {
            $this->addError("ip", Yii::t('MiniDocModule.I18N', 'IPError'));
            return false;
        }
        if(!preg_match("/^[0-9]{2,}$/", $this->port)) {
            $this->addError("port", Yii::t('MiniDocModule.I18N', 'portError'));
            return false;
        }
        if(isset($serverIP)){
            if($this->isModify){
                if($this->ip == $serverIP['ip'] && !empty($this->id)){
                    return true;
                }
            }
            if(empty($this->id)){
                $this->addError("ip", Yii::t('MiniDocModule.I18N','ipExisted1'));
            }else{
                $this->addError("ip", Yii::t('MiniDocModule.I18N', 'ipExisted2'));
            }
            return false;
        }
        return true;
    }

    
    public function attributeLabels()
    {
        return  array(
            'ip'             => Yii::t('MiniDocModule.I18N', 'ip'),
            'port'           => Yii::t('MiniDocModule.I18N', 'port'),
        );
    }

    
    public function save(){
        if($this->validate()){
            if(($this->isModify)){
                return MiniDocNode::getInstance()->modifyServer($this->ip,$this->port,$this->id);
            }else{
                return MiniDocNode::getInstance()->create($this->ip,$this->port);
            }
        }
        return false;
    }


    
    public function downloadConfig($id){
        $config = new ServerConfig();
        $config->download($id);
    }
}


class ServerConfig
{
    private $id;
    private $url;
    private $ip;
    private $port;

    private function init(){
        $server = MiniDocNode::getInstance()->getByID($this->id);
        $this->url = Yii::app()->params['app']['absoluteUrl'];
        $this->ip = $server["ip"];
        $this->port = $server["port"];
    }
    private function load($id)
    {
        $this->id = $id;
        $this->init();
        $template = $this->getTemplate();
        $template = str_replace("@url",$this->url,$template);
        $template = str_replace("@ip",$this->ip,$template);
        $template = str_replace("@port",$this->port,$template);
        return $template;

    }
    public function download($id){
        $content = $this->load($id);
        header("Content-type:application/ini");
        header("Content-Disposition:attachment; filename=\"minidoc.ini\"");
        echo($content);
        exit;
    }
    private function getTemplate(){
        $path = dirname(__FILE__)."/../assets/minidoc.ini";
        $file = fopen($path, "r");
        $lines = array();
        while (!feof($file)) {
            $lines[] = fgets($file);
        }
        fclose($file);
        return implode("",$lines);
    }
}