<?php
//控制台加载的配置文件与迷你云是一样的
//控制台加载的配置文件新增对应的command位置
$config = include dirname(__FILE__)."/main.php";
//读取每个插件下的commands按yii约定的方式形成array
$pluginRootPath = dirname(__FILE__)."/../plugins/";
$commandMap = array();
if(($dir=@opendir($pluginRootPath))) {
    $commands=array();
    while(($pluginName=readdir($dir))!==false)
    {
        //比如读取{plugins/miniDoc/commands/DocConvertCommand.php}解析为
        //{'docConvert'=>array('class'=>'application.plugins.miniDoc.commands.DocConvertCommand')}
        $commandsPath = $pluginRootPath.DIRECTORY_SEPARATOR.$pluginName.DIRECTORY_SEPARATOR."commands";
        if(file_exists($commandsPath)){
            if(($commandDir=@opendir($commandsPath))) {
                while(($fileName=readdir($commandDir))!==false)
                {
                    $file=$commandsPath.DIRECTORY_SEPARATOR.$fileName;
                    if(!strcasecmp(substr($fileName,-11),'Command.php') && is_file($file)){
                        $commandName = substr($fileName,0,strlen($fileName)-11);
                        $commandMap[$commandName] = array(
                            'class'=>"application.plugins.".$pluginName.".commands.".$commandName."Command"
                        );
                    }

                }
                closedir($commandDir);
            }
        }

    }
    closedir($dir);
}
$config["commandMap"] = $commandMap;
return $config;