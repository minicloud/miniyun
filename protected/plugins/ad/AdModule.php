<?php
/**
 * AD插件
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2015 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html
 * @since 1.8
 */
/**
 *
 * ldap模块 ldap用户源插件
 *
 */
class AdModule extends MiniPluginModule {
	/**
	 *
	 * @see CModule::init()
	 */
	public function init()
	{
		$this->setImport(array(
                'ad.models.*',
                'ad.biz.*',
                'ad.service.*',
		));
		add_action('third_user_source', array($this, "adSource"));
	}
	/**
	 * ldap 用户源验证
	 *
	 * @create time 2012-12-5
	 * @since 1.0.0
	 */
	public function adSource(){
		return  new CAdUserSource();
	}
}

