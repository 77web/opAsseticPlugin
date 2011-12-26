<?php

function assetic_include_stylesheets()
{
  $context = sfContext::getInstance();
  $sf_response = $context->getResponse();
  $sf_user = $context->getUser();
  
  $styles = array('print'=>array(), 'screen'=>array(), 'all'=>array());
  foreach($sf_response->getStylesheets() as $file => $options)
  {
    $mediaType = isset($options['media']) ? $options['media'] : 'screen';
    $styles[$mediaType][] = $file;
  }
  $sf_user->setAttribute('opAsseticPlugin_styles', $styles);
  
  $html = '';
  foreach($styles as $mediaType => $files)
  {
    $html .= '<link rel="stylesheet" type="text/css" href="'.url_for('assetic/stylesheets?media='.$mediaType).'" media="'.$mediaType.'" />';
  }
  echo $html;
}

function assetic_include_javascripts()
{
  $context = sfContext::getInstance();
  $sf_response = $context->getResponse();
  $sf_user = $context->getUser();
  
  $scripts = array();
  foreach($sf_response->getJavascripts() as $file => $options)
  {
    $scripts[] = $file;
  }
  $sf_user->setAttribute('opAsseticPlugin_scripts', $scripts);
  
  echo '<script type="text/javascript" src="'.url_for('assetic/javascripts').'"></script>';
}