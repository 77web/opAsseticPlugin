<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  protected $enableStylesheets = false;
  protected $enableJavascripts = false;
  protected $compressStylesheets = false;
  protected $compressJavascripts = false;
  
  public function initialize()
  {
    if(sfConfig::get('sf_app')=='pc_frontend' && sfConfig::get('sf_environment')=='prod')
    {
      //pending: read settings from SnsConfig table?
      $this->enableStylesheets = sfConfig::get('opAsseticPlugin_enable_css', true);
      $this->compressStylesheets = sfConfig::get('opAsseticPlugin_compress_css', false);
      $this->enableJavascripts = sfConfig::get('opAsseticPlugin_enable_js', true);
      $this->compressJavascripts = sfConfig::get('opAsseticPlugin_compress_js', true);
      
      $this->dispatcher->connect('response.filter_content', array($this, 'listenToResponseFilterContent'));
    }
  }

  public function listenToResponseFilterContent(sfEvent $event, $content)
  {
    $response = sfContext::getInstance()->getResponse();
    
    if($this->enableStylesheets)
    {
      $content = $this->embedStylesheets($response, $content);
    }
    
    if($this->enableJavascripts)
    {
      $content = $this->embedJavascripts($response, $content);
    }
    
    return $content;
  }
  
  
  protected function embedStylesheets(sfResponse $response, $content)
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
      if(''!==$css)
      {
        $styles .= '<style type="text/css" media="'.$mediaType.'">'.$css.'</style>';
      }
    }
    
    if('' !== $styles)
    {
      $csspattern = "/^<link[^>]+rel=\"stylesheet\"[^>]+>$/im";
      if(preg_match_all($csspattern, $content, $matches, PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $tag = $match[0];
          $pattern2 = "/<head>.*".str_replace('/', "\/", $tag).".*<\/head>/ims";
          if(preg_match($pattern2, $content))
          {
            $content = str_replace($tag."\n", '', $content);
          }
        }
      }
      return str_replace('</head>', $styles.'</head>', $content);
    }
    
    return $content;
  }
  
  protected function embedJavascripts(sfResponse $response, $content)
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
    if($this->compressJavascripts)
    {
      $assetsJs = $this->compressJavascripts($assetsJs);
    }
    
    if('' !== $assetsJs)
    {
      $scripts = '<script type="text/javascript">'.$assetsJs.'</script>';
      $jspattern = "/^<script[^<]+?<\/script>$/im";
      if(preg_match_all($jspattern, $content, $matches, PREG_SET_ORDER))
      {
        foreach($matches as $match)
        {
          $tag = $match[0];
          $pattern2 = "/<head>.*".str_replace('/', "\/", $tag).".*<\/head>/ims";
          if(preg_match($pattern2, $content))
          {
            $content = str_replace($tag."\n", '', $content);
          }
        }
      }
      return str_replace('</head>', $scripts.'</head>', $content);
    }
    
    return $content;
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
    //pending: compress css here
    return $css;
  }
}