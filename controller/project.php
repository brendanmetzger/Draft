<?php namespace Controller;


class Project extends \App\Controller {
  use configuration;
  
  public function GETindex() {
    // make two sections, business and pleasure
    return new \App\View('line-ball.html');
  }

  public function GETnougatory() {
    return new \App\View('jesus.html');
  }
}