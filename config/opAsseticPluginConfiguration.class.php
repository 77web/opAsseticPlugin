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
    if(sfConfig::get('sf_app')=='pc_frontend' && sfConfig::get('sf_environment')=='dev')
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
        $assetsCss[$mediaType] = $this->compressStylesheets($css);
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
      $assetsJs = $this->compressJavascripts($assetsJs);
    }
    
    if('' !== $assetsJs)
    {
      $this->javascripts = '<script type="text/javascript">'.$assetsJs.'</script>';
    }
  }
  
  protected function compressJavascripts($script)
  {
    //pending: compress js here
    $params = array();
    $params['js_code'] = $script;
    $params['compilation_level'] = 'SIMPLE_OPTIMIZATIONS';
    $params['output_format'] = 'text';
    $params['output_info'] = 'compiled_code';
    
    $sock = @fsockopen('closure-compiler.appspot.com', 80, $errorno, $errorstr, 30);
    if($sock)
    {
      $param = http_build_query($params);
      
      $post = array();
      $post[] = 'POST /compile HTTP/1.1';
      $post[] = 'Host: closure-compiler.appspot.com';
      $post[] = 'Content-Length: '.strlen($param);
      $post[] = 'Content-Type: application/x-www-form-urlencoded';
      $post[] = 'Connection: close';
      $post[] = '';
      $post[] = $param;
      
      fputs($sock, implode("\r\n", $post));
      $response = '';
      while(!feof($sock))
      {
        $response .= fgets($sock);
      }
      fclose($sock);
      $res = explode("\r\n\r\n", $response);
      $script = $res[1];
    }
    
    return $script;
  }
  
  protected function compressStylesheets($css)
  {
    //remove comments
    $css = preg_replace("/\/\*.+?\*\//is", '', $css);
    //remove whitespaces
    $css = preg_replace("/^\s+/im", '', $css);
    $css = str_replace(array(": ", " {", ", "), array(":", "{", ","), $css);
    //remove \r\n
    $css = str_replace(array("\r", "\n"), '', $css);
    
    return $css;
  }
}