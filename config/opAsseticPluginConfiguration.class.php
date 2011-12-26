<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    if(sfConfig::get('sf_app')=='pc_frontend')
    {
      $this->dispatcher->connect('op_action.pre_execute', array($this, 'listenToPreExecute'));
    }
  }
  
  public function listenToPreExecute(sfEvent $event)
  {
    sfContext::getInstance()->getConfiguration('pc_frontend')->loadHelpers(array('Assetic'));
  }
}