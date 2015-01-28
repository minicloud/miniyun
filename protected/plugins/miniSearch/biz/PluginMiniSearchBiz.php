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
     * 全文检索
     * @param $key 关键字
     * @return array
     */
    public function search($key)
    {
        //通过Sphinx远程端口查询数据
        $sc = new SphinxClient();
        $url = PluginMiniSearchOption::getInstance()->getMiniSearchHost();
        $urlInfo = parse_url($url);
        $port = 80;
        if (array_key_exists("port", $urlInfo)) {
            $port = $urlInfo['port'];
        }
        $sc->SetServer($urlInfo['host'], $port); //注意这里的主机

        $searchIndex = 'main1';
        $summaryList = $sc->Query($key, $searchIndex);
        if ((int)$summaryList['total'] === 0) {
            return array();
        }
        $ids = array_keys($summaryList['matches']);
        $ids = join(',', $ids);
        //将所有符合条件的做了索引的文件都取出来
        $values = array();
        $searchContents = MiniSearchFile::getInstance()->search($ids);
        foreach ($searchContents as $searchContent) {//遍历，查询文件signature，根据signature判断当前用户有无浏览该文件权限
            $fileVersionId = MiniVersion::getInstance()->getVersionIdBySignature($searchContent["file_signature"]);
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
            $summaryList = $sc->BuildExcerpts(array($searchContent["content"]), $searchIndex, $key, $opts);
            if (!$summaryList) {
                $summary = $summaryList[0];
            }
            //反向查询系统所有的文件记录
            $fileList = MiniFile::getInstance()->getAllByVersionId($fileVersionId);
            foreach ($fileList as $fileItem) {//对具有相同signature的文件进行过滤
                $filePath = $fileItem['file_path'];
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
                $item['signature'] = $searchContent["file_signature"];//相同的signature可能对应多个文件
                $item['file_name'] = $fileItem['file_name'];
                $item['file_path'] = $filePath;
                $item['summary'] = $summary;
                array_push($values, $item);
            }
        }
        return $values;
    }
}