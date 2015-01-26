<?php

/**
 * 获得所有转换成功的word/excel/pdf的内容到数据库
 * Class PullTxtCommand
 */
class PluginDocPullTxtCommand extends CConsoleCommand
{

    /**
     * 获得所有转换成功的word/excel/pdf的内容到数据库
     */
    public function actionIndex()
    { 
    	$versions = PluginMiniDocVersion::getInstance()->getDocConvertList(2);
        if(empty($versions)) {
            echo("no doc to pull txt!");
            return;
        }
        $count = 0;
        foreach($versions as $version){
            $signature = $version["file_signature"];
            //先判断文件的signature是否在search_file存在记录，如果不存在才从迷你文档上拉文本内容
            $searchFile = MiniSearchFile::getInstance()->getItemBySignature($signature);
            if(!empty($searchFile)){
                continue;
            }
            $url = PluginMiniSearchOption::getInstance()->getMiniDocHost()."/".$signature."/".$signature.".txt";
            $http = new HttpClient();
            $http->get($url);
            $status = $http->get_status();
            if($status=="200"){
                $content = $http->get_body();
                MiniSearchFile::getInstance()->create($signature,$content);
                $count ++;
            }else{
                Yii::log($signature."get txt error",CLogger::LEVEL_ERROR,"doc.convert");
            }
        }
        Yii::log("save txt:".$count." records",CLogger::LEVEL_INFO,"doc.convert");

    }
}