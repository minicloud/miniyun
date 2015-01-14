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
        //TODO 获得迷你云地址
		$miniHost = "http://gitserver.miniyun.cn";
    	//报俊地址
    	$reportUrl = $miniHost."/a.php/1/docConvert/report";
    	//下载文件地址
    	$downloadUrl = $miniHost."/a.php/1/docConvert/download"; 
        if(count($versions)>0){
        	$data = array("report_success_url"=>$reportUrl);
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
        $data = http_build_query($data);
        $opts = array (
            'http' => array (
                'method' => 'POST',
                'header'=> "Content-type: application/x-www-form-urlencodedrn" .
                    "Content-Length: " . strlen($data) . "rn",
                'content' => $data
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);
        return $result;
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
    	if(empty($versions)) return;

    	$params = $this->getReadyConvertList($versions);
    	echo(json_encode($params));

    	//TODO 向迷你文档服务器发送转换请求
        $url = 'http://minidoc.miniyun.cn:8090/convert';
        $result = $this->post($url,$params);
        echo json_encode($result);
    	//修改文档的转换状态为转换中
    	foreach ($versions as $version) {
    		MiniVersion::getInstance()->updateDocConvertStatus($version["file_signature"],1);
    	}
    }
}