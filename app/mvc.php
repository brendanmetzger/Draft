<?php namespace App;

/****       *************************************************************************************/
class Model implements \ArrayAccess {
  
  public function current (  ) {return $this->context;}
  public function key (  ) {}
  public function next (  ) {}
  public function rewind (  ) {}
  public function valid (  ) {}
  
  protected $context;

  public function __construct($context, array $data = [])
  {
   
    if ($context instanceof Element) {
      $this->context = $context;
    } else if (empty($data) && ! $this->context = Data::USE(static::SOURCE)->getElementById($context)){
      throw new \InvalidArgumentException("Unable to locate the requested resource ({$context}). (TODO, better exceptinon type, log this, inform it was logged)", 1);
    } else {
      // TODO determine how to create a new item
    }
    
    if ($data) {
      // Context will be an element, and the element will control the merging, not the model
      $this->context->merge($data);
    }
  }
  
  static public function LIST(?string $path = null): \App\Data {
    return Data::USE(static::SOURCE, $path ?: static::PATH)->map(function($item) {
      // this should be a factory: if path is not standard, may be a different model
      return new static($item);
    });
  }
  
  static public function FACTORY($classname, $params) {
    $classname = "\\Model\\{$classname}";
    return new $classname($params);
  }
  
  public function offsetExists($offset) {
    return ! is_null($this->context);
  }

  public function offsetGet($offset) {
    $method  = "get{$offset}";
    $context = $this->context[$offset];
    return method_exists($this, $method) ? $this->{$method}($context) : $context;
  }

  public function offSetSet($offset, $value) {
    return $this->context[$offset] = $value;
  }

  public function offsetUnset($offset) {
    unset($this->context[$offset]);
    return true;
  }
  
  public function __toString() {
    return $this->context['@id'];
  }
}

/* TODO
[ ] Removal of unspecified nodes needs adjusting: as is, only applied to iterated methonds 
[/] remove nodes that have been slated for demo
[ ] run before/after filters

*/

/****      *************************************************************************************/
class View {
  private $document, $slugs = [], $templates = [];
  
  public function __construct($input) {
    $this->document = new Document($input);
  }
  
  public function render($data = [], bool $parse = true): Document {
    
    foreach ($this->getTemplates('insert') as [$path, $ref]) {
      $this->import((new Self($path))->render($data, false), $ref);
    }
    
    foreach ($this->getTemplates('replace') as [$prop, $ref]) {
      if (isset($this->templates[$prop])) {
        $this->import((new Self($this->templates[$prop]))->render($data, false), $ref->nextSibling);
        $ref -> parentNode -> removeChild($ref);
      }
    } 
    
    foreach ($this->getTemplates('iterate') as [$key, $ref]) {
      $view = new Self( $ref -> parentNode -> removeChild( $ref -> nextSibling ));
      foreach ($data[$key] ?? [] as $datum) {
        $view->cleanup($this->import($view->render($datum), $ref, 'insertBefore'));
      }
      $ref->parentNode->removeChild($ref);
    }
      
    if ($parse) {
      foreach ($this->getSlugs() as [$node, $scope]) { try {
        $node(Data::PAIR($scope, $data));
      } catch (\UnexpectedValueException $e) {
        $this->cleanup($node);
      }}
    }

    return $this->document;
  }
  
  public function set(string $key, $path): self {
    $this->templates[$key] = $path;
    return $this;
  }
  
  private function cleanup(\DOMNode $node): void {
    static $remove = [];
    if ($node instanceof \DOMElement) {
      while($path = array_pop($remove)) {
        $item = $node->ownerDocument->find($path, $node)[0];
        $item->parentNode->removeChild($item);
      }
    } else {
      $remove[] = sprintf('/%s/parent::*', $node->getNodePath());
    }
  }
  
  private function getTemplates($key): iterable {
    $query = "./descendant::comment()[ starts-with(normalize-space(.), '{$key}')"
           . (($key == 'iterate') ? ']' : 'and not(./ancestor::*/preceding-sibling::comment()[iterate])]');

    return (new Data($this -> document -> find( $query )))->map( function ($stub) {
      return [preg_split('/\s+/', trim($stub->nodeValue))[1], $stub];
    });    
  }
  
  private function getSlugs(): iterable {
    return $this->slugs ?: ( function (&$out) {
      $query = "substring(.,1,1)='[' and contains(.,'\$') and substring(.,string-length(.),1)=']' and not(*)";
      foreach ( $this -> document -> find("//*[{$query}]|//*/@*[{$query}]") as $slug ) {        
        preg_match_all('/\$+[\@a-z\_\:0-9]+\b/i', $slug( substr($slug, 1,-1) ), $match, PREG_OFFSET_CAPTURE);
      
        foreach (array_reverse($match[0]) as [$k, $i]) {
          $___ = $slug -> firstChild -> splitText($i) -> splitText(strlen($k)) -> previousSibling;
          if (substr( $___( substr($___,1) ),0,1 ) != '$') $out[] = [$___, explode(':', $___)];
        }
      }
      return $out;

    })($this->slugs);
  }
  
  private function import(Document $import, \DOMNode $ref, $swap = 'replaceChild'): \DOMNode {
    return $ref -> parentNode -> {$swap}( $this -> document -> importNode($import->documentElement, true), $ref );    
  }
  
}

/*************            ***************************************************************************************/
abstract class Controller {
  private $method;
  protected $request;
  abstract  public function GETLogin(?string $model = null, ?string $webhook = null);
  abstract public function POSTLogin(\App\Data $post);
  
  static final public function FACTORY(Request $request, string $class, string $method) {

    $method = new \ReflectionMethod("\controller\\{$class}", $request->method . $method);
    $class  = $method->getDeclaringClass()->name;
    if ($method->isProtected() && ! $request->authenticate($method)) {
      $method = new \ReflectionMethod($class, $request->method . 'login');
    }
    return [(new \ReflectionClass($class))->newInstance($request), $method];
  }
  
  final public function __construct($request) {
    $this->request = $request;
  }
  
  

}