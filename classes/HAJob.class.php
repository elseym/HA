<?
/**
 * This Class represents a / an HAJob
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAChain.class.php");

class HAJob
{
  private $id;
  private $switches;
  
  function __construct($input) {
    if ($input instanceof HAChain) {
      $this->setSwitches($input->getSwitches());
    } elseif ($input instanceof HASwitch) {
      $this->setSwitches($input);
    } else {
      throw new HAJobException("HAJob can only be created from an instance HAChain or HASwitch.");
    }
    $this->generateId();
  }
  
  public function getId() { return $this->id; }
  private function generateId() {
    $this->id = sha1(uniqid());
    return $this;
  }
  
  public function getSwitches() { return $this->switches; }
  public function setSwitches($switches = null) {
    if (!is_array($switches)) {
      if ($switches instanceof HASwitch) {
        $switches = array($switches);
      } else {
        throw new HAJobException("setSwitches must be provided with either an instance of HASwitch or an array of instances of HASwitches.");
      }
    }
    $this->switches = $switches;
    return $this;
  }
}

class HAJobException extends Exception {}

?>