<?php

class asseticActions extends sfActions
{
  public function executeStylesheets(sfWebRequest $request)
  {
    $mediaType = $request->getParameter('media', 'screen');
    $type = extension_loaded('zlib') ? 'compress' : 'raw';
    $allStylesheets = $this->getUser()->getAttribute('opAsseticPlugin_styles', array());
    $stylesheets = isset($allStylesheets[$mediaType]) ? $allStylesheets[$mediaType] : array();
    
    $webDir = sfConfig::get('sf_web_dir');
    $styles = '';
    foreach($stylesheets as $file)
    {
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
      $styles .= $css;
    }
    $this->getUser()->setAttribute('opAsseticPlugin_styles', null);
    if('' == $styles)
    {
      return sfView::NONE;
    }
    
    $styles = $this->compressStylesheets($styles);
    $this->text = $styles;
    
    $this->getResponse()->setContentType("text/css");
    $this->setTemplate($type);
  }
  
  public function executeJavascripts(sfWebRequest $request)
  {
    $type = extension_loaded('zlib') ? 'compress' : 'raw';
    $scriptFiles = $this->getUser()->getAttribute('opAsseticPlugin_scripts', array());
    
    $webDir = sfConfig::get('sf_web_dir');
    $scripts = '';
    foreach($scriptFiles as $file)
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
      $scripts .= file_get_contents($path);
    }
    $this->getUser()->setAttribute('opAsseticPlugin_scripts', null);
    if('' == $scripts)
    {
      return sfView::NONE;
    }
    
    $scripts = $this->compressJavascripts($scripts);
    $this->text = $scripts;
    
    $this->getResponse()->setContentType("text/javascript");
    $this->setTemplate($type);
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