<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  protected $enableStylesheets = false;
  protected $enableJavascripts = false;
  protected $compressStylesheets = false;
  protected $compressJavascripts = false;
  protected $stylesheets = '';
  protected $javascripts = '';
  
  public function initialize()
  {
    if(sfConfig::get('sf_app')=='pc_frontend' && sfConfig::get('sf_environment')=='prod')
    {
      //pending: read settings from SnsConfig table?
      $this->enableStylesheets = sfConfig::get('opAsseticPlugin_enable_css', true);
      $this->compressStylesheets = sfConfig::get('opAsseticPlugin_compress_css', true);
      $this->enableJavascripts = sfConfig::get('opAsseticPlugin_enable_js', true);
      $this->compressJavascripts = sfConfig::get('opAsseticPlugin_compress_js', true);
      
      $this->dispatcher->connect('opView.pre_decorate', array($this, 'listenToViewDecorate'));
      $this->dispatcher->connect('response.filter_content', array($this, 'listenToResponseFilterContent'));
    }
  }
  
  public function listenToResponseFilterContent(sfEvent $event, $content)
  {
    $head = '';
    if('' !== $this->javascripts)
    {
      $head .= $this->javascripts;
    }
    if('' !== $this->stylesheets)
    {
      $head .= $this->stylesheets;
    }
    if('' !== $head)
    {
      $content = str_replace('</head>', $head.'</head>', $content);
    }
    return $content;
  }
  
  public function listenToViewDecorate(sfEvent $event)
  {
    $response = sfContext::getInstance()->getResponse();
    
    if($this->enableStylesheets)
    {
      $this->embedStylesheets($response);
    }
    
    if($this->enableJavascripts)
    {
      $this->embedJavascripts($response);
    }
  }
  
  protected function embedStylesheets(sfResponse $response)
  {
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
        $filename = $file;
        if(strpos($file, '.css')===false)
        {
          $filename .= '.css';
        }
        
        if(substr($file, 0, 1)!='/')
        {
          $filename = '/css/'.$filename;
        }
        $path = $webDir.$filename;
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
      $response->removeStylesheet($file);
    }
    if($this->compressStylesheets)
    {
      foreach($assetsCss as $mediaType => $css)
      {
        $assetsCss[$mediaType] = Minify::minifyStylesheet($css);
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
    
    if('' !== $styles)
    {
      $this->stylesheets = $styles;
    }
  }
  
  protected function embedJavascripts(sfResponse $response)
  {
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
        $filename = $file;
        if(strpos($file, '.js')===false)
        {
          $filename .= '.js';
        }
        
        if(substr($file, 0, 1)!='/')
        {
          $filename = '/js/'.$filename;
        }
        $path = $webDir.$filename;
      }
      $assetsJs .= file_get_contents($path);
      $response->removeJavascript($file);
    }
    if($this->compressJavascripts)
    {
      $assetsJs = Minify::minifyJavascript($assetsJs);
    }
    
    if('' !== $assetsJs)
    {
      $this->javascripts = '<script type="text/javascript">'.$assetsJs.'</script>';
    }
  }
}