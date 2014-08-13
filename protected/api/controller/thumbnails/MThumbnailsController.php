<?php
/**
 * Miniyun 图片缩略图服务主要入口地址,实现输出图片缩略图
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MThumbnailsController extends MApplicationComponent implements MIController {
     /**
     * 控制器执行主逻辑函数
     * @example URL Structure http://www.miniyun.cn/api/thumbnails/<root>/<path>
     * @version 0
     * @method GET
     * @param format 
     * <p>JPEG (default) or PNG. For images that are photos, JPEG should be preferred, while PNG is better for screenshots and digital art.</p>
     * @param size
     * <p>One of the following values (default small):</p>
     * <p>value    dimensions (px)</p>
     *   <p>small    32x32</p>
     *   <p>medium    64x64</p>
     *   <p>large    128x128</p>
     *   <p>s    64x64</p>
     *   <p>m    128x128</p>
     *   <p>l    640x480</p>
     *   <p>xl    1024x768</p>
     * @return mixed $value 返回最终需要执行完的结果
     */
    public function invoke($uri=null) {
        // 调用父类初始化函数，注册自定义的异常和错误处理逻辑
        parent::init();
        // 初始化缩略图处理类
        $thumbnail_base = MThumbnailBase::initMThumbnailBase($uri);
        $thumbnail_base->create();
        $thumbnail_base->render();
    }
}