<?php namespace App;

# CONFIGURE (Apache and php.ini are where majority of configuration occurs)
define('ID', explode(':', (getenv('ID') ?: ':::')));

# REQUIRE application files
foreach (['structure', 'dom', 'io', 'locus', 'mvc'] as $file) require_once "../app/{$file}.php";

# AUTOLOAD non-essential classes organized by namespace
spl_autoload_register(function ($classname) {
  @include '../' . str_replace('\\', '/', strtolower($classname)) . '.php';
});

# INSTANTIATE the request. 
$request  = new Request( Method::New(getenv('REQUEST_METHOD') ?: 'CLI') );
$response = new Response($request);

# HANDLE different scenarios
$response->handle('http', function($request) {
  $controller = $request->delegate($this, 'overview', 'index');
  $ajax = false; // Full layout unnecessary w/ ajax
  if (!$ajax) {
    $this->setTemplate(new View('layout/full.html'));
    // add before/after filter to move html around     
  }
  return $controller;
});


$response->handle('console', function ($request) {
  return $request->delegate($this, 'overview', 'examine');
});


# RESPOND with some output
try {
  
  $controller = $response->prepare();
  $output     = $response->compose($controller->data);
  
  // $debugging
  if ($output instanceof \DOMDocument) {
    $timestamp = microtime(true) - $request->method->start;
    $output->documentElement->appendChild(new \DOMElement('script', "console.log({$timestamp});"));
  }
  
} catch (\TypeError | \ReflectionException | \InvalidArgumentException | \Exception | \Error $e) {
  
  if ($request->method == 'CLI') {
    print_r($data);
    exit();
  }
  $data = [
    'trace'   => array_reverse($e->getTrace()),
    'file'    => $e->getFile(),
    'line'    => $e->getLine(),
    'message' => $e->getMessage(),
  ];
  
  $response->setContent(View::content('error')->render($data));
  $response->setTemplate(View::layout('basic'));
  $response->compose();

} finally {
  // produce output
  echo $response;
}



/*
TODO

[ ] Show XML errors in a helpful way (revisit the solution in pedagogy)
[ ] use an anonymous class to create a controller en the event of an error new class($response) extends Controller;
[/] Determine a factory/configuration class that acts as a way to construct/instantiate common objects (Notably Models and views)
[ ] Work on calendaring
[X] Throw an exception if a view template is not found or improper
[ ] Show Sunrise/Sunset/Weather
[ ] Determine how models accept and merge input
[ ] The `Model::sign` method should ~accept~ and return a token
[ ] The token cookie value should represent the model it is storing a value for
[/] Use TRY/CATCH/FINALLLY to render output
[ ] Should not be able to go to login page if already authenticated, and not unless sent there by the application
[ ] Consider using typecasted controller params instantiating the classes they represent... `function GETEdit(\person $user, $type, \Factory $id)
[X] Controller should return a partial view. Application (index.php) can determine layout and merge rendered view.
[ ] Look up http headers on sending custom data.
[X] Use __callStatic in a factory context. now, Model::Make($type, $id) goes to Model::$type($id);  
[X] Rethinking all the request/response shuffling around. Response is tied to a controller, but that seems unnecessary
[ ] Set up HTTPS cert and document thouroughly
[ ] Create data repo on server
[ ] add version control class to inspect data evolution

io.php, Response Object
[ ] Consider implementing a way users and guests can see same page, with users having rendered session vars
[ ] response should be in control of filtering/reordering DOM presentation?
[ ] response should be in charge of caching, Apache in charge of getting cached page

structure.php, Data Class
[ ] devise way so that when invoked without an index number, it returns the first item. (this would be a nodelist tho)
[ ] PAIR function should deal with missing key and throw an exception

*/