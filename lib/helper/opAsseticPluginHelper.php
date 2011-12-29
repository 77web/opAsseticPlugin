<?php

function opAsseticPlugin_include_javascripts()
{
  if(!sfConfig::get('app_opAsseticPlugin_enable_js', false))
  {
    include_javascripts();
    return;
  }
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
    $assetsJs .= file_get_contents($path);
  }
  if(sfConfig::get('app_opAsseticPlugin_minify_js', false))
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
    $css = file_get_contents($path);
    
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
  if(sfConfig::get('app_opAsseticPlugin_minify_css', false))
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