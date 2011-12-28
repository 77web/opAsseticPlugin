<?php

class opAsseticPluginMinifyStylesTask extends sfBaseTask
{
  protected function configure()
  {
    $this->namespace        = 'opAsseticPlugin';
    $this->name             = 'minify-styles';
    $this->briefDescription = 'Generate minified cache of styles before users\' access';
    $this->detailedDescription = 'call it with [./symfony opAsseticPlugin:minify-styles]';
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
        
        $pluginCssDir = $pluginWebDir.'/css';
        if(is_dir($pluginCssDir))
        {
          $dirs[] = $pluginCssDir;
        }
      }
    }
    
    $styles = array();
    foreach($dirs as $dirPath)
    {
      $dir = dir($dirPath);
      if($dir)
      {
        while(($file = $dir->read()) != false)
        {
          if(substr($file, -4, 4)=='.css')
          {
            $styles[] = $dirPath.'/'.$file;
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
    foreach($styles as $stylePath)
    {
      file_put_contents($cacheDir.'/'.md5($stylePath).'.min.css', Minify::minifyStylesheet(file_get_contents($stylePath)));
    }
  }
}