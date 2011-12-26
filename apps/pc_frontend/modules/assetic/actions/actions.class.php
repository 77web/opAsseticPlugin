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
    
    $styles = Minify::minifyStylesheet($styles);
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
    
    $scripts = Minify::minifyJavascript($scripts);
    $this->text = $scripts;
    
    $this->getResponse()->setContentType("text/javascript");
    $this->setTemplate($type);
  }
}