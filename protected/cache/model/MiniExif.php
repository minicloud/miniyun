<?php
/**
 * 缓存file_exifs表的记录，V1.2.0该类接管部分file_exifs的操作
 * 按客户端请求方式，逐渐增加记录到内存
 * 直接查询Cache,而非DB
 * 数据更新首先更新Cache，然后是DB
  * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MiniExif extends MiniCache{

	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY = "cache.model.MiniExif";

	/**
	 *  静态成品变量 保存全局实例
	 *  @access private
	 */
	static private $_instance = null;

	/**
	 *  私有化构造函数，防止外界实例化对象
	 */
	private function  __construct()
	{
		parent::MiniCache();
	}
	/**
	 * 静态方法, 单例统一访问入口
	 * @return object  返回对象的唯一实例
	 */
	static public function getInstance()
	{
		if (is_null(self::$_instance) || !isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
     *
     * 保存图片经纬度信息
     */
    public function create($versionId, $latitude, $longitude) {
        
        $exif                 = FileExif::model()->findByAttributes(array('version_id'=>$versionId));
        if (!$exif) {
            $exif             = new FileExif();
            $exif->version_id = $versionId;
            $exif->longtitude = $latitude;
            $exif->latitude   = $longitude;
            $exif->exif       = '';
            $exif->save();
        }
        if (empty($exif->longtitude) || empty($exif->latitude)) {
            $exif->longtitude = $longitude;
            $exif->latitude   = $latitude;
            $exif->save();
         }
    }
}
?>