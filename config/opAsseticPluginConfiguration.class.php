<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    //disable css for current version
    sfConfig::set('opAsseticPlugin_enable_css', false);
    
    
    if(sfConfig::get('sf_app')=='pc_frontend' && sfConfig::get('sf_environment')=='prod')
    {
      $this->dispatcher->connect('response.filter_content', array($this, 'listenToResponse'));
    }
  }
  
  public function listenToResponse(sfEvent $event, $content)
  {
    $response = sfContext::getInstance()->getResponse();
    $webDir = sfConfig::get('sf_web_dir');
    
    if(sfConfig::get('opAsseticPlugin_enable_css', false))
    {
      $assetsCss = '';
      foreach($response->getStylesheets() as $file => $options)
      {
        $path = $webDir.$file;
        //pending: css media type
        $assetsCss .= file_get_contents($path);
      }
      //pending: compress $assetsCss here
      $styles = '<style type="text/css">'.$assetsCss.'</style>';
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
      $content = str_replace('</head>', $styles.'</head>', $content);
    }
    
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
    //pending compress $asstsJs here
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
    $content = str_replace('</head>', $scripts.'</head>', $content);
    
    return $content;
  }
}