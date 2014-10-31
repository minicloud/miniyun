<?php
/**
 * 迷你云支持2级缓存，降低对db的多次读开销，降低对分布式cache的读与反序列化开销
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MiniCache2{
	/**
	 * 是否启用2级缓存
	 * @var boolean
	 */
	protected   $hasCache2    = false;
	
	public function MiniCache2(){
		 
	}
}