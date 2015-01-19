<?php

/**
 * 向迷你文档发送转换请求Convert
 * Class DocConvertCommand
 */
class DocConvertCommand extends CConsoleCommand
{
	/**
	 * 获得要转换文档列表
     * @param $versions 文件版本列表
     * @return array
	 */
	private function getReadyConvertList($versions){ 
        //MINIYUN_HOST来源{protected/config/miniyun-backup.php}
		$miniHost = MINIYUN_HOST; 
        $urlInfo = parse_url($miniHost);
        $port = 80;
        if(array_key_exists("port", $urlInfo)){
            $port = $urlInfo['port'];
        }
    	//报俊地址
    	$reportUrl = $miniHost;
    	//下载文件地址
    	$downloadUrl =$miniHost."/a.php/1/docConvert/download";
        if(count($versions)>0){
        	$data = array("report_success_url"=>$reportUrl,'port'=>$port);
        	$items = array();
        	foreach ($versions as $version) {
        	 	$item = array(
        	 		'hash' => $version["file_signature"],
                    'mime_type' => $version["mime_type"],
        	 		'url' => $downloadUrl."?hash=".$version["file_signature"],
        	 	);
        	 	array_push($items, $item);
        	 } 
        	 $data["list"] = $items;
        	 return $data;
        }
        return NULL;
	}

    /**
     * post方法提交请求
     * @param $url
     * @param $params
     * @return string
     */
    private function post($url,$params){
        $data = array ('task' =>json_encode($params));
        $http = new HttpClient();
        $http->post($url,$data);

        return $http->get_body();
    }
    /**
     * 定时任务入口
     * 先获得要转换的version列表
     * 然后提交到迷你文档服务器进行转换，提交成功后修改该文档的状态
     * 迷你文档转换成功后，将异步方式给迷你云发送成功信息
     */
    public function actionIndex()
    { 
    	$versions = MiniVersion::getInstance()->getReadyDocConvertList();
        if(empty($versions)) {
            echo("no doc to convert!");
            return;
        }
    	$params = $this->getReadyConvertList($versions);
    	echo(json_encode($params));
    	//MINIDOC_HOST来源{protected/config/miniyun-backup.php}
        $url = MINIDOC_HOST.'/convert';
        $result = $this->post($url,$params);
        $result = json_decode($result,true);
        if($result['task']=='received'){
            //修改文档的转换状态为转换中
            foreach ($versions as $version) {
                MiniVersion::getInstance()->updateDocConvertStatus($version["file_signature"],1);
            }
        }

    }
}