<?
/**
 * This Class represents a / an HAElement
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAPersistor.class.php");

abstract class HAElement
{
  protected $dirty = false;
  protected $delete = false;
  
  abstract protected function getId();
  
  final public function sully($dirty = true) {
    $this->dirty = $dirty && !HAPersistor::isLoading();
    return $this;
  }
  
  final public function delete($delete = true) {
    if ($delete) $this->sully();
    $this->delete = $delete;
    return $this;
  }
  
  final public function isDirty() {
    return $this->dirty;
  }
  
  final public function isNew() {
    return substr($this->getId(), 0, strlen(static::UIDPX)) == static::UIDPX;
  }
}

class HAElementException extends Exception {}

?>