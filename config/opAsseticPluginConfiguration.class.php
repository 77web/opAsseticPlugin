<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    if(sfConfig::get('sf_app')=='pc_frontend' && sfConfig::get('sf_environment')=='prod')
    {
      $this->dispatcher->connect('response.filter_content', array($this, 'listenToResponse'));
    }
  }
  
  public function listenToResponse(sfEvent $event, $content)
  {
    $response = sfContext::getInstance()->getResponse();
    $webDir = sfConfig::get('sf_web_dir');
    
    $assetsCss = '';
    foreach($response->getStylesheets() as $file => $options)
    {
      $path = $webDir.$file;
      $assetsCss .= file_get_contents($path);
    }
    //pending: compress $assetsCss here
    $styles = '<style type="text/css">'.$assetsCss.'</style>';
    
    //pending: remove <link> tags for css between <head> and </head>
    //preg_replace();
    $content = str_replace('</head>', $styles.'</head>', $content);
    
    $assetsJs = '';
    foreach($response->getJavascripts() as $file => $options)
    {
      $path = $webDir.$file;
      $assetsJs .= file_get_contents($path);
    }
    //pending compress $asstsJs here
    $scripts = '<script type="text/javascript">'.$assetsJs.'</script>';
    //pending: remove <script> tags between <head> and </head>
    //preg_replace();
    $content = str_replace('</head>', $scripts.'</head>', $content);
    
    return $content;
  }
}