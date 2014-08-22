<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-7-2
 * Time: 上午9:11
 */
class EditorManageBiz extends MiniBiz{
    /**
     * 新建editor
     */
    public function createEditor($extend,$firstMethod,$secondMethod,$editorTip){
        $data = array();
        $data['extend'] = $extend;
        $data['first_method'] = $firstMethod;
        if($secondMethod!=''){
            $data['second_method'] = $secondMethod;
        }
        if($editorTip!=''){
            $data['editor_tip'] = $editorTip;
        }
        $data['enabled'] = 1;//正常使用状态为1，冻结状态为0
        $option = MiniOption::getInstance()-> getOptionValue('online_editor');
        if($option == null||empty($option)){
            $options = array();
        }else{
            $options = unserialize($option);
            //判断数据库中是否有重复的扩展名
            if(strpos($extend,'/')){
                $extends = explode('/',$extend);
                foreach($options as $option){
                    $extendStr = $option['extend'];
                    if(strpos($extendStr,"/")){
                        $extendArr = explode('/',$extendStr);
                        $dataFix = array_intersect($extendArr,$extends);
                        if(count($dataFix)!=0){
                            return false;
                        }
                    }else{
                       foreach($extends as $extend){
                           if($extend == $extendStr){
                               return false;
                           }
                       }
                    }
                }
            }else{
                foreach($options as $option){
                    $extendStr = $option['extend'];
                    if(strpos($extendStr,"/")){
                        $extendArr = explode('/',$extendStr);
                        for($i=0;$i<=count($extendArr)-1;$i++){
                            if($extendArr[$i]==$extend){
                                return false;
                            }
                        }
                    }else{
                        if($extendStr==$extend){
                            return false;
                        }
                    }
                }
            }

        }
        $options[] = $data;
        MiniOption::getInstance()->setOptionValue('online_editor',serialize($options));
        return true;
    }
    /**
     * editor list
     */
    public function getEditorList($type){
        $data = array();
        $editors = MiniOption::getInstance()-> getOptionValue('online_editor');
        if($editors != null){
            $editors = unserialize($editors);
        }
        $data['total'] = count($editors);
        $disableTotal = 0;
        foreach($editors as $key=> $editor){
            if($type == "enabled"){
                if($editor['enabled']==0){
                    unset($editors[$key]);
                }
            }
            if($type == 'disabled'){
                if($editor['enabled']==1){
                    unset($editors[$key]);
                }
            }
            if($editor['enabled']==0){
                $disableTotal++;
            }

        }

        $data['editors'] = $editors;
        $data['disableTotal'] = $disableTotal;
        return $data;
    }
    /**
     * disable Editor
     */
    public function changeEditorStatus($extend,$status){
        $editors = MiniOption::getInstance()->getOptionValue('online_editor');
        $editors = unserialize($editors);
        for($i=0;$i<count($editors);$i++){
            if($editors[$i]['extend']== $extend){
                if($status=='disable'){
                    $editors[$i]['enabled'] = 0;
                }
               if($status=='enable'){
                   $editors[$i]['enabled'] = 1;
               }
                $data[] = $editors[$i];
                array_splice($editors,$i,1,$data);
                MiniOption::getInstance()->setOptionValue('online_editor',serialize($editors));
                return true;
            }
        }
    }
    /**
     * extend information
     */
    public function getExtendInfo($extend){
        $editors = MiniOption::getInstance()->getOptionValue('online_editor');
        $editors = unserialize($editors);
        foreach($editors as $key =>$editor){
            if($editor['extend']== $extend){
                $data = array_slice($editors,$key,1);
                return $data;
            }
        }
    }
    /**
     * 修改editor
     */
    public function updateEditor($selectExtend,$extend,$firstMethod,$secondMethod,$editorTip){
        $data = array();
        $data['extend'] = $extend;
        $data['first_method'] = $firstMethod;
        if($secondMethod!=''){
            $data['second_method'] = $secondMethod;
        }
        if($editorTip!=''){
            $data['editor_tip'] = $editorTip;
        }
        $editors = MiniOption::getInstance()->getOptionValue('online_editor');
        $editors = unserialize($editors);
        if(strpos($extend,'/')){
            $extends = explode('/',$extend);
            foreach($editors as $key=> $editor){
                if($editor['extend']==$selectExtend){
                    $i = $key;
                    $data['enabled'] = $editor['enabled'];
                    continue;
                }
                $extendStr = $editor['extend'];
                if(strpos($extendStr,"/")){
                    $extendArr = explode('/',$extendStr);
                    $dataFix  = array_intersect($extendArr,$extends);
                    if(count($dataFix)!=0){
                        return false;
                    }
                }else{
                    foreach($extends as $extend){
                        if($extend == $extendStr){
                            return false;
                        }
                    }
                }
            }
        }else{
            foreach($editors as $key=>$editor){
                if($editor['extend']==$selectExtend){
                    $i = $key;
                    $data['enabled'] = $editor['enabled'];
                    continue;
                }
                $extendStr = $editor['extend'];
                if(strpos($extendStr,"/")){
                    $extendArr = explode('/',$extendStr);
                    for($j=0;$j<=count($extendArr)-1;$j++){
                        if($extendArr[$j]==$extend){
                            return false;
                        }
                    }
                }else{
                    if($extendStr==$extend){
                        return false;
                    }
                }
            }
        }
        $items = array();
        $items[] = $data;
        array_splice($editors,$i,1,$items);;
        MiniOption::getInstance()->setOptionValue('online_editor',serialize($editors));
        return true;
    }


}