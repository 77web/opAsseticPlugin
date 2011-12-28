<?php

class opAsseticPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect('op_action.post_execute', array($this, 'listenToPostExecute'));
  }
  
  public function listenToPostExecute(sfEvent $event)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('opAsseticPlugin'));
  }
}