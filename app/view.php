<?php 
namespace app;
/**
* View
* 
* [ ] should accept a valid template
* [ ] should deal with fragments
* [x] placeholders can be scoped/nested 
* [ ] falsy data should just put in an empty value
* [ ] absent data should delete components in view
*     [ ] regression: absent data cannot be removed immediately from template, as it will effect renderability of going forward
*     [ ] an array of removals containting getNodePath()'s could be used to target removals and looped/reset effciently
*     [ ] this would have to be done in reverse, as document would change as removals are processed
* [x] should swap placeholder values with real values

*/

class View
{
  const xml_options = LIBXML_COMPACT|LIBXML_NOBLANKS|LIBXML_NOXMLDECL|LIBXML_NOENT;
  
  // TODO: These will be protected/private
  public $document;
  public $slugs = [];
  private $xpath;
  
  function __construct(string $path)
  {
    $this->document = new \DOMDocument;
    $this->document->formatOutput = true;
    $this->document->preserveWhitespace = true;
    $this->document->load($path, self::xml_options);
    $this->xpath = new \DomXpath( $this->document );
  }
  
  public function import(self $mill)
  {
    $view = $this->document->importNode($mill->document->documentElement, true);
    $this->document->documentElement->appendChild($view);
  }
  
  
  /*
    TODO
    [ ] controlled by response object
    [ ] remove nodes that have been slated for demo
    [ ] run before/after filters
  */
  public function render()
  {
    return $this->document->saveXML();
  }
  
  /*
    TODO 
    [ ] Make an element of the document object
  */
  public function getStubs($prefix)
  {
    $query = "./descendant::comment()[ starts-with(normalize-space(.), '%s') ]";
    
    if ( $prefix == 'iterate' ) {
      $query = substr($query, 0, -1) . 'and not(./ancestor::*/preceding-sibling::comment()[iterate]) ]';
    }
    return $this->xpath->query(sprintf($query, $prefix));
  }
  
  /*
    TODO
    [ ] make a method of the element object (as this is finding elements);
  */
  public function getSlugs()
  {
    return $this->slugs || (function (&$slugs) {
      
      $query = "substring(.,1,1)='[' and contains(.,'\$') and substring(.,string-length(.),1)=']' and not(*)";
      
      foreach ( $this->xpath->query("//*[ {$query} ] | //*/@*[ {$query} ]") as $placeholder ) {        
        $placeholder->nodeValue = substr($placeholder->nodeValue, 1,-1); // trim opening brackets
        preg_match_all('/\$+[\@a-z\_\:0-9]+\b/i', $placeholder->nodeValue, $matches, PREG_OFFSET_CAPTURE);
        
        foreach (array_reverse($matches[0]) as $m) { // start from end b/c of numerical offsets
          $slug = $placeholder->firstChild->splitText($m[1])->splitText(strlen($m[0]))->previousSibling;
          $slug->nodeValue = substr($slug->nodeValue, 1);
          if ($slug->nodeValue[0] != '$') {
            $slugs[] = [ 'node' => $slug, 'scope' => explode(':', $slug->nodeValue) ];
          }
        }
      }
      
      return $slugs;
    })( $this->slugs );
  }
}