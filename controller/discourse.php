<?php namespace Controller;


class Discourse extends \App\Controller {
  use configuration;
  
  public function GETindex() {
    // make two sections, business and pleasure
    return new \App\View('discourse/overview.html');
  }
}