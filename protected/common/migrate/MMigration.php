<?php
/**
 *
 * 数据迁移
 * 
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
class MMigration {

    public $connectionID = "db";

    /**
     * 执行具体数据迁移操作
     *
     * @since 1.0.4
     */
    public function up($module = "core")
    {
        $tokens = explode(" ", "yiic migrate up");
        $commandPath = Yii::app()->getBasePath().DIRECTORY_SEPARATOR.'commands';
        $commandPathYii = Yii::getPathOfAlias('system.cli.commands');

        $runner = new CConsoleCommandRunner();

        $modulePaths = array();
        if ($module != "core"){
            $modulePaths[$module] = "application.modules.{$module}.migrations";
            $migratePath  =Yii::getPathOfAlias($modulePaths[$module]);
            if (!is_dir($migratePath)){
                return true;
            }
        }

        $yiicCommandMap = array(
            'migrate' => array(
                'class' => 'application.extensions.migrate.EMigrateCommand',
                'migrationPath' => 'application.migrations',
                'applicationModuleName' => 'core',
                'modulePaths' => $modulePaths,
                'disabledModules' => array(),
                'connectionID'=>$this->connectionID,
        )
        );

        $runner->commands=$yiicCommandMap;
        $runner->addCommands($commandPath);
        $runner->addCommands($commandPathYii);

        $tokens[] = "--interactive=0";
        $tokens[] = "--migrationTable=".DB_PREFIX."_migration";
        $tokens[] = "--module={$module}";

        ob_start();
        $runner->run($tokens);
    }
}
