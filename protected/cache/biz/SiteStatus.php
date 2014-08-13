<?php
/**
 * 缓存迷你云节点与www.miniyun.cn的关系
 * 一些用户部署在封闭的内网，无法访问miniyun.cn，这会影响到管理后台的使用
 * 在这里进行过滤，让用户使用更透明
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class SiteStatus extends MiniCache{
	/**
	 *
	 * Cache Key的前缀
	 * @var string
	 */
	private static $CACHE_KEY="cache.biz.SiteStatus";
	/**
	 *  静态成品变量 保存全局实例
	 *  @access private
	 */
	private static  $_instance = null;
	
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
	 * 设置整个节点与Miniyun.cn绝缘
	 * 如设置了缓存，则直接写入cache即可
	 * 否则到config目录的miniyun_offline.php文件里
	 */
	public function setOffline(){
		
		if($this->hasCache===true){#写入cache
			Yii::trace(SiteStatus::$CACHE_KEY." set cache ","miniyun.cache1");
			$this->set(SiteStatus::$CACHE_KEY,true);
		}else{#写入文件
			$this->write();
		}
	}
	/**
	 * 判断是否与miniyun.cn离线
	 * @return boolean
	 */
	public function isOffline(){
		if($this->hasCache===true){
			$value = $this->get(SiteStatus::$CACHE_KEY);
			if($value===false){
				return false;
			}else{
				return true;
			}
		}else{
			$configPath   = MINIYUN_PATH."/protected/config/miniyun_offline.php";
			return file_exists($configPath);
		}
	}
	/**
	 * 重置状态
	 * @return boolean
	 */
	public function reset(){
		if($this->hasCache===true){
			$this->deleteCache(SiteStatus::$CACHE_KEY);
		}else{
			$configPath   = MINIYUN_PATH."/protected/config/miniyun_offline.php";
            if(file_exists($configPath)){
			  unlink($configPath);
            }
		}
	}
	/**
	 * 写入配置文件
	 */
	private function write(){
		$aimPath      = MINIYUN_PATH."/protected/config/miniyun_offline.php"; 
		$content      = "<?php\n return 1;\r\n?>";
		$fh           = fopen($aimPath, 'w');
		fwrite($fh, $content);
		fclose($fh);
	} 
}
?>