<?php namespace Controller;

class Overview extends \App\Controller {
  use configuration;
  
  
  public function GETindex($id = null) {

    $layout   = new \App\View('layout.html');
    
    if ($id) {
      $m = new \Model\Item($id);
    }
    
    return $layout->render(['items' => \Model\Item::list('/items/item'), 'title' => 'Working Draft']);
  }
  
  protected function GEThelp(\Model\Person $person) {
    print_r($person['@title']);
  }
  
  
}