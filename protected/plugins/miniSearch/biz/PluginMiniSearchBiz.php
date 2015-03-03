<?php
/**
 * 迷你搜索业务层
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.7
 */
class PluginMiniSearchBiz extends MiniBiz
{
    /**
     *根据文件的Hash值下载内容
     * @param string $signature 文件hash值
     * @throws 404错误
     */
    public function downloadTxt($signature){
        $version = MiniVersion::getInstance()->getBySignature($signature);
        if(!empty($version)){
            //根据文件内容输出文件内容
            $meta = MiniVersionMeta::getInstance()->getMeta($version["id"],"doc_id");
            if(!empty($meta)){
                $node = PluginMiniDocNode::getInstance()->getNodeById($meta["meta_value"]);
                if(!empty($node)){
                    $url = $node["host"]."/".$signature."/".$signature.".txt";
                    header( "HTTP/1.1 ".MConst::HTTP_CODE_301." Moved Permanently" );
                    header( "Location: ". $url );
                    exit;
                }
            }
        }
        throw new MFileopsException(
            Yii::t('api','File Not Found'),
            404);

    }
    /**
     *根据文件的Hash值报告索引成功
     * @param string $signature 文件hash值
     * @param string $nodeId 迷你搜索ID
     * @return array
     */
    public function report($signature,$nodeId){
        //编制索引成功
        $buildSuccess = MiniSearchFile::getInstance()->buildSuccess($signature,$nodeId);
        if($buildSuccess){
            //为迷你搜索节点服务器增加索引数
            PluginMiniSearchNode::getInstance()->newBuildFile($nodeId);
            //删除索引任务
            PluginMiniSearchBuildTask::getInstance()->delete($signature,$nodeId);
        }
        return array("success"=>true);
    }
    /**
     * 全文检索
     * @param string $key 关键字
     * @return array
     */
    public function search($key)
    {
        $siteId = MiniSiteUtils::getSiteID();
        $signatures = $this->searchKeyWordAndSiteId($siteId,$key);
        if(empty($signatures)){
            return array();
        }
        //分析迷你搜索返回的signatures列表
        $sc = new SphinxClient();
        $searchIndex = 'main1';
        //将所有符合条件的做了索引的文件都取出来
        $values = array();
        $searchFiles = MiniSearchFile::getInstance()->getBySignatures($signatures);
        foreach ($searchFiles as $searchFile) {//遍历，查询文件signature，根据signature判断当前用户有无浏览该文件权限
            $version = MiniVersion::getInstance()->getBySignature($searchFile["file_signature"]);
            //摘要内容，默认取第1个位置的摘要
            $summary = "";
            $opts = array(//摘要选项
                "before_match" => "<span style='background-color: #ffff00'><b>",
                "after_match" => "</b></span>",
                "chunk_separator" => " ... ",
                "limit" => 100,
                "around" => 20,
            );
            $opts["exact_phrase"] = 0;
            $summaryList = $sc->BuildExcerpts(array($searchFile["content"]), $searchIndex, $key, $opts);
            if (!empty($summaryList)) {
                $summary = $summaryList[0];
            }
            //反向查询系统所有的文件记录
            $fileList = MiniFile::getInstance()->getAllByVersionId($version["id"]);
            foreach ($fileList as $file) {//对具有相同signature的文件进行过滤
                $filePath = $file['file_path'];
                $userId = (int)$this->user['id'];
                $permissionModel = new UserPermissionBiz($filePath, $userId);
                $permission = $permissionModel->getPermission($filePath, $userId);
                if ($permission['permission'] == '000000000' || $permission['permission'] == '011111111') {//没有读权限则不显示出来
                    continue;
                }
                if (empty($permission)) {//如果上面读权限为空，则说明没有共享，这时当前用户只能看见自己的文件
                    $pathArr = explode('/', $filePath);
                    $masterId = $pathArr[1];
                    if ($masterId != $userId) {
                        continue;
                    }
                }
                $item = array();
                $item['signature'] = $searchFile["file_signature"];//相同的signature可能对应多个文件
                $item['file_name'] = $file['file_name'];
                $item['file_path'] = $filePath;
                $item['summary'] = $summary;
                array_push($values, $item);
            }
        }
        return $values;
    }
    /**
     * 根据关键字+SiteId向迷你搜索发送请求
     * @param string $key 关键字
     * @param string $siteId 站点ID
     * @return array 返回signature列表
     */
    private function searchKeyWordAndSiteId($key,$siteId){
        //向迷你搜索服务器发送搜索请求
        $node = PluginMiniStoreNode::getInstance()->getBestNode();
        if(!empty($node)){
            $url = $node["host"].'/api.php?route=file/search';
            $data = array (
                'site_id'=>$siteId,//站点ID
                'key'=>$key,//搜索的关键字
            );
            $http = new HttpClient();
            $http->post($url,$data);
            $result =  $http->get_body();
            $result = json_decode($result,true);
            if($result['status']==1){
                $value = $result["signature_list"];
                if(!empty($value)){
                    $signatures = explode(",",$value);
                    return $signatures;
                }
            }
        }
        return null;
    }
    /**
     *获得迷你搜索节点信息列表
     * @return array
     */
    public function getNodeList(){
        return PluginMiniSearchNode::getInstance()->getNodeList();
    }
    /**
     * 创建迷你搜索节点
     * @param int $id 节点ID
     * @param string $name 节点名称
     * @param string $host 节点域名
     * @param string $safeCode 节点访问的安全码
     * @throws MiniException
     * @return array
     */
    public function createOrModifyNode($id,$name,$host,$safeCode){
        $node = PluginMiniSearchNode::getInstance()->createOrModifyNode($id,$name,$host,$safeCode);
        if(empty($node)){
            throw new MiniException(100305);
        }
        return $node;
    }
    /**
     * 修改迷你搜索节点状态
     * @param string $name 节点名称
     * @param string $status 节点状态
     * @throws MiniException
     */
    public function modifyNodeStatus($name,$status){
        $node = PluginMiniSearchNode::getInstance()->getNodeByName($name);
        if(empty($node)){
            throw new MiniException(100303);
        }
        if($status==1){
            //检查服务器状态，看看是否可以连接迷你搜索服务器
            $nodeStatus = PluginMiniSearchNode::getInstance()->checkNodeStatus($node["host"]);
            if($nodeStatus==-1){
                throw new MiniException(100304);
            }
        }
        return PluginMiniSearchNode::getInstance()->modifyNodeStatus($name,$status);
    }
}