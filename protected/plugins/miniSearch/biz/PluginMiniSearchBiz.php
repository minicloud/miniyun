<?php
/**
 * Created by PhpStorm.
 * User: gly
 * Date: 15-1-13
 * Time: 上午10:25
 */
class PluginMiniSearchBiz extends MiniBiz{
    /**
     * 全文检索
     * @param $key 关键字
     * @return array
     */
    public function search($key){
        $cl = new SphinxClient();
        $url = PluginMiniSearchOption::getInstance()->getMiniSearchHost();
        $urlInfo = parse_url($url);
        $port = 80;
        if(array_key_exists("port", $urlInfo)){
            $port = $urlInfo['port'];
        }
        $cl->SetServer($urlInfo['host'], $port); //注意这里的主机

        $index='main1';
        $res = $cl->Query($key, $index);
        if((int)$res['total']===0){
            return array();
        }
        $ids = array_keys($res['matches']);
        $ids = join(',',$ids);
        $items = MiniSearchFile::getInstance()->search($ids);//将所有符合条件的做了索引的文件都取出来
        $opts = array(//摘要选项
            "before_match"          => "<span style='background-color: #ffff00'><b>",
            "after_match"           => "</b></span>",
            "chunk_separator"       => " ... ",
            "limit"                         => 100,
            "around"                        => 20,
        );
        $files = array();
        foreach($items as $item){//遍历，查询文件signature，根据signature判断当前用户有无浏览该文件权限
            $docs = array();
            $file['signature']=$item->file_signature;//相同的signature可能对应多个文件
            $fileVersionId = MiniVersion::getInstance()->getVersionIdBySignature($file['signature']);
            $list = MiniFile::getInstance()->getAllByVersionId($fileVersionId);
            foreach($list as $unit){//对具有相同signature的文件进行过滤
                $filePath = $unit['file_path'];
                $userId = (int)$this->user['id'];
                $permissionModel = new UserPermissionBiz($filePath,$userId);
                $permission = $permissionModel->getPermission($filePath,$userId);
                if($permission['permission'] == '000000000'||$permission['permission']=='011111111'){//没有读权限则不显示出来
                    continue;
                }
                if(empty($permission)){//如果上面读权限为空，则说明没有共享，这时当前用户只能看见自己的文件
                    $pathArr = explode('/',$filePath);
                    $masterId = $pathArr[1];
                    if($masterId!=$userId){
                        continue;
                    }
                }
                $file['file_name'] = $unit['file_name'];
                $file['file_path'] = $filePath;
                $file['type'] =
                $file['content']=$item->content;
                array_push($docs,$item->content);
                foreach ( array(0) as $exact ){//获取$entry即文件内容摘要
                    $opts["exact_phrase"] = $exact;
                    $res = $cl->BuildExcerpts ( $docs, $index, $key, $opts );
                    if ( !$res ){
                        die ( "ERROR: " . $cl->GetLastError() . ".\n" );
                    }else{
                        foreach ( $res as $entry )
                        {
                            $file['content']=$entry;
                        }
                    }
                }
                array_push($files,$file);
            }
        }
        return $files;
    }
}