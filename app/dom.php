<?php namespace App;

libxml_use_internal_errors(true);

/****          *************************************************************************************/
class Document extends \DOMDocument {
  const DECLARATIONS = LIBXML_COMPACT|LIBXML_NOBLANKS|LIBXML_NOENT|LIBXML_NOXMLDECL;

  private $xpath = null,
          $opts  = [ 'preserveWhiteSpace' => false, 'formatOutput'    => true , 'encoding' => 'UTF-8', 
                     'resolveExternals'   => true , 'validateOnParse' => false ];
  
  function __construct($xml, $opts = []) {
    parent::__construct('1.0', 'UTF-8');
    
    foreach (array_replace($this->opts, $opts) as $p => $v) $this->{$p} = $v;
    foreach (['Element','Comment','Text','Attr'] as $c) $this->registerNodeClass("\\DOM{$c}", "\\App\\{$c}");
    
    if ($xml instanceof Element) 
      $this->loadXML($xml->ownerDocument->saveXML($xml), self::DECLARATIONS);
    else
      $this->load($xml, self::DECLARATIONS);

  }

  public function save($path = null) {
    return file_put_contents($path ?? $this->filepath, $this->saveXML(), LOCK_EX);
  }

  public function find(string $path, \DOMElement $context = null): \DOMNodeList {
    return ($this->xpath ?: ($this->xpath = new \DOMXpath($this)))->query($path, $context);
  }

  public function errors() {
    return libxml_get_errors();
  }
  
  public function __toString() {
    return $this->saveXML();
  }
}

/****      *************************************************************************************/
class Text extends \DOMText {
  static public function hasPrefix(string $prefix) {
    $prefix = trim($prefix);
    $length = strlen($prefix);
    return function($text) {
      return substr($text, 0, $length) == $prefix;
    };
  }
  public function __invoke(?string $string): self {
    $this->nodeValue = strip_tags($string);
    return $this;
  }
  
  public function __toString(): string {
    return $this->nodeValue;
  }
  
}

/****      *************************************************************************************/
class Attr extends \DOMAttr {
  
  public function __invoke(?string $value) {
    $this->value = $value;
    return $this;
  }
  
  public function __toString() {
    return $this->value;
  }
}

/****         *************************************************************************************/
class Comment extends \DOMComment {
  
}

/****          *************************************************************************************/
class Nodelist extends \DOMNodeList {
  // implement a method that automatically gets the first element if not specified, so you can use a list as an element!
}


/****         *************************************************************************************/
class Element extends \DOMElement implements \ArrayAccess {

  public function select(string $tag, int $offset = 0): self {
    $nodes = $this->selectAll($tag);
    return $nodes->length <= $offset ? $this->appendChild(new self($tag)) : $nodes[$offset]; 
  }
  
  public function selectAll(string $path) {
    return $this->ownerDocument->find($path, $this);
  }
  
  public function merge(array $data) {
    // TODO figure out merge strategy
  }

  public function offsetExists($offset) {
    return $this->selectAll($offset)->length > 0;
  }

  public function offsetGet($offset) {
    if ($offset[0] === '@') {
      return $this->getAttribute(substr($offset, 1)) ?: $this->setAttribute($offset, '');
    } else {
      // TODO 
      //  [ ] create recursive function to deal with paths insead of tags, ie. ->select('a/b[@c]') if !exist must create/append <a><b c=""/></a>
      //  [ ] this should be responsible for making an empty node if necessary, possibly with a created="(now)" updated="0" attributes
      return $this->select($offset);
    }
  }

  public function offSetSet($offset, $value) {
    return $this[$offset]($value);
  }

  public function offsetUnset($offset) {
    throw new \Exception("TODO: implement deleting node values, (deal with attributes and elements)", 1);
    return false;
  }

  public function __toString() {
    return $this->nodeValue;
  }
  
  public function __invoke(string $string): self {
    $this->nodeValue = htmlentities($string, ENT_COMPAT|ENT_XML1, 'UTF-8', false);
    return $this;
  }
  
}