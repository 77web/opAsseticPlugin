<?php

class opAsseticPluginMinifyScriptsTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace        = 'opAsseticPlugin';
    $this->name             = 'minify-scripts';
    $this->briefDescription = 'Generate minified cache of scripts before users\' access';
    $this->detailedDescription = 'call it with [./symfony opAsseticPlugin:minify-scripts]';
  }
  
  protected function execute($arguments = array(), $options = array())
  {
    $pluginList = array();
    $pluginsDir = dir(sfConfig::get('sf_plugins_dir'));
    while(($plugin = $pluginsDir->read()) != false)
    {
      $prefix = substr($plugin, 0, 2);
      if('op' == $prefix || 'sf' == $prefix)
      {
        $pluginList[] = $plugin;
      }
    }
    
    $dirs = array();
    foreach($pluginList as $pluginName)
    {
      $pluginWebDir = sfConfig::get('sf_plugins_dir').'/'.$pluginName.'/web';
      if(is_dir($pluginWebDir))
      {
        $dirs[] = $pluginWebDir;
        
        $pluginJsDir = $pluginWebDir.'/js';
        if(is_dir($pluginJsDir))
        {
          $dirs[] = $pluginJsDir;
        }
      }
    }
    
    $scripts = array();
    foreach($dirs as $dirPath)
    {
      $dir = dir($dirPath);
      if($dir)
      {
        while(($file = $dir->read()) != false)
        {
          if(substr($file, -3, 3)=='.js')
          {
            $scripts[] = $dirPath.'/'.$file;
          }
        }
        $dir->close();
      }
    }
    
    $fs = new sfFileSystem();
    $cacheDir = sfConfig::get('sf_cache_dir').'/opAsseticPlugin';
    if(!is_dir($cacheDir))
    {
      $fs->mkdirs($cacheDir, 0755);
    }
    foreach($scripts as $scriptPath)
    {
      file_put_contents($cacheDir.'/'.md5($scriptPath).'.min.js', Minify::minifyJavascript(file_get_contents($scriptPath)));
    }
  }
}