<?php

class Minify
{
  public static function minifyStylesheet($css)
  {
    //remove comments
    $css = preg_replace("/\/\*.+?\*\//is", '', $css);
    $css = preg_replace("/\/\/.+$/im", '', $css);
    //remove whitespaces
    $css = preg_replace("/^\s+/im", '', $css);
    $css = str_replace(array(": ", " {", ", "), array(":", "{", ","), $css);
    //remove \r\n
    $css = str_replace(array("\r", "\n"), '', $css);
    
    return $css;
  }
  
  public static function minifyJavascript($script)
  {
    require_once dirname(dirname(__FILE__)).'/vendor/jsmin-php/jsmin.php';
    return JSMin::minify($script);
  }
}