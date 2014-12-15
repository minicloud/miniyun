<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 14-10-9
 * Time: 上午11:17
 */
class MetaDataService extends MiniService{

    public function miniyun(){
        $sharedPath = MiniHttp::getParam('shared_path','');
        $urlManager = new MUrlManager();
        $urlArray = $urlManager->parseActionFromUrl();
        $metadataController = new MMetadataController();
       $metadataController -> invoke($urlArray["uri"],$sharedPath);
    }
}