<?php

function opAsseticPlugin_include_javascripts()
{
  if(!sfConfig::get('app_opAsseticPlugin_enable_js', false))
  {
    include_javascripts();
    return;
  }
  $isUseCache = sfConfig::get('app_opAsseticPlugin_use_minify_cache_js', false) && sfConfig::get('app_opAsseticPlugin_minify_js', false);
  $cacheDir = sfConfig::get('sf_cache_dir').'/opAsseticPlugin';
  
  $response = sfContext::getInstance()->getResponse();
  $webDir = sfConfig::get('sf_web_dir');
  $assetsJs = '';
  foreach($response->getJavascripts() as $file => $options)
  {
    if(strpos($file, '://')!==false)
    {
      $path = $file;
    }
    else
    {
      if(strpos($file, '.js')===false)
      {
        $file .= '.js';
      }
      
      if(substr($file, 0, 1)!='/')
      {
        $file = '/js/'.$file;
      }
      $path = $webDir.$file;
    }
    $cachePath = $cacheDir.'/'.str_replace('/', '_', $path).'.min.js';
    if($isUseCache && file_exists($cachePath))
    {
      $assetsJs .= file_get_contents($cachePath);
    }
    else
    {
      $assetsJs .= file_get_contents($path);
    }
  }
  if(sfConfig::get('app_opAsseticPlugin_minify_js', false) && !$isUseCache)
  {
    $assetsJs = opAsseticPluginMinify::minifyJavascript($assetsJs);
  }
  
  if('' !== $assetsJs)
  {
    echo '<script type="text/javascript">'.$assetsJs.'</script>';
  }
}

function opAsseticPlugin_include_stylesheets()
{
  if(!sfConfig::get('app_opAsseticPlugin_enable_css', false))
  {
    include_stylesheets();
    return;
  }

  $isUseCache = sfConfig::get('app_opAsseticPlugin_use_minify_cache_css', false) && sfConfig::get('app_opAsseticPlugin_minify_css', false);
  $cacheDir = sfConfig::get('sf_cache_dir').'/opAsseticPlugin';

  $response = sfContext::getInstance()->getResponse();
  $webDir = sfConfig::get('sf_web_dir');
  $assetsCss = array('screen'=>'', 'print'=>'', 'all'=>'');
  foreach($response->getStylesheets() as $file => $options)
  {
    $mediaType = isset($options['media']) ? $options['media'] : 'screen';
    
    if(strpos($file, '://')!==false)
    {
      $path = $file;
    }
    else
    {
      if(strpos($file, '.css')===false)
      {
        $file .= '.css';
      }
      
      if(substr($file, 0, 1)!='/')
      {
        $file = '/css/'.$file;
      }
      
      $path = $webDir.$file;
    }
    
    $cachePath = $cacheDir.'/'.str_replace('/', '_', $path).'.min.css';
    if($isUseCache && file_exists($cachePath))
    {
      $css = file_get_contents($cachePath);
    }
    else
    {
      $css = file_get_contents($path);
    }
    
    if(preg_match_all("/url\([^)]+\)/i", $css, $matches, PREG_SET_ORDER))
    {
      $currentPath = dirname($file).'/';
      $parentPath = dirname(dirname($file)).'/';
      foreach($matches as $rawPath)
      {
        $replacedPath = str_replace(array('../', './'), array($parentPath, $currentPath), $rawPath);
        $css = str_replace($rawPath, $replacedPath, $css);
      }
    }
    $assetsCss[$mediaType] .= $css;
  }
  if(sfConfig::get('app_opAsseticPlugin_minify_css', false) && !$isUseCache)
  {
    foreach($assetsCss as $mediaType => $css)
    {
      $assetsCss[$mediaType] = opAsseticPluginMinify::minifyStylesheet($css);
    }
  }
  $styles = '';
  foreach($assetsCss as $mediaType => $css)
  {
    if('' !== $css)
    {
      $styles .= '<style type="text/css" media="'.$mediaType.'">'.$css.'</style>';
    }
  }
  
  echo $styles;
}