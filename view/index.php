<?php
namespace app;

# CONFIGURE 
date_default_timezone_set ('America/Chicago');

# REQUIREMENTS
foreach (['mvc', 'dom', 'data', 'io'] as $file) require_once "../app/{$file}.php";

# AUTOLOAD non-essential classes organized by namespace
spl_autoload_register(function ($classname) {
  @include '../' . str_replace('\\', '/', $classname) . '.php';
});

# INSTANTIATE the request.
$request = new Request($_SERVER, $_REQUEST);

$request->listen('http', function ($params) {
  return $this->delegate(array_replace(['overview', 'index'], $this->route), $this->params);
});


$request->listen('repl', function ($params) {
  print_r($this);
  print_r($params);
  return "DONE";
});


try {
  echo (new Response($request))->package();
} catch (\TypeError | \ReflectionException | \InvalidArgumentException $e) {
  /*
   TODO
   [x] PHP 7.1+ allows piping exceptions - employ
   [ ] Show appropriate message when controller not found/incorrect
   [ ] "                        for action
   [ ] "                        for protected methods 
  
  */
  echo "<h1>TYPE ERROR, REFLECTION EXCEPTION</h1><pre>";
  print_r($e);
  echo "</pre>";
} catch (\Exception $e) {
  print_r($e);
}