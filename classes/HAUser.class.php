<?
/**
 * This Class represents a / an HAUser
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAElement.class.php");
require_once("HAChain.class.php");

class HAUser extends HAElement
{
  const DBTBL = "Users";
  const UIDPX = "tmpus_";
  
  private $id;
  private $name;
  
  private $chains;
  
  function __construct($id = null, $name = null) {
    if (is_array($id) && isset($id['id'], $id['name'])) extract($id);
    $this->setId(is_null($id) ? null : (int)$id)
         ->setName((string)$name)
         ->setChains();
  }
  
  public function getId() { return $this->id; }
  public function setId($id = null) {
    if (!is_null($id) && (!is_numeric($id) || $id < 1)) throw new HAUserException("Id must be a number greater than 0.");
    if (is_null($id)) $id = uniqid(self::UIDPX);
    $this->id = $id;
    $this->sully();
    return $this;
  }
  
  public function getName() { return $this->name; }
  public function setName($name = null) {
    if (!is_string($name) || trim($name) == "") throw new HAUserException("Name must be a valid string.");
    $this->name = $name;
    $this->sully();
    return $this;
  }
  
  public function getChains() { return $this->chains; }
  public function getChain($id) {
    if (!array_key_exists($id, $this->getChains())) return false; //throw new HAUserException("Chain [$id] does not exist.");
    return $this->chains[$id];
  }
  public function addChain($id = null, $name = null) {
    if ($id instanceof HAChain) {
      $chain = $id;
      $chain->setUser($this->getId());
    } else {
      $chain = new HAChain($id, $this->getId(), $name);
    }
    if (array_key_exists($chain->getId(), $this->getChains())) throw new HAUserException("Duplicate Chain [" . $chain->getId() . "] for user.");
    $this->chains[$chain->getId()] = $chain;
    return $this;
  }
  public function removeChain($id) {
    if (!array_key_exists($id, $this->getChains())) return false; //throw new HAUserException("Chain [$id] does not exist.");
    $chains = $this->getChains();
    unset($chains[$id]);
    return $this->setChains($chains);
  }
  public function setChains($chains = null) {
    if (!is_array($this->chains)) {
      if (is_null($chains)) $chains = array(); else throw new ChainException("Chains must be an array of Chains.");
    }
    $this->chains = $chains;
    return $this;
  }
  
  public function __toString() {
    $qry = "";
    if ($this->delete) {
      foreach ($this->getChains() as $chain) $qry .= (string)$chain->delete();
      $qry .= "DELETE IGNORE FROM `" . self::DBTBL . "` WHERE `id` = " . $this->getId() . "\n";
    } else {
      if ($this->dirty || $this->isNew()) {
        $kvs = "`name` = '" . $this->getName() . "'";
        $qry .= "INSERT INTO `" . self::DBTBL . "` SET `id` = " . ($this->isNew() ? "NULL" : $this->getId()) . ", $kvs ON DUPLICATE KEY UPDATE $kvs;\n";
      }
      foreach ($this->getChains() as $chain) $qry .= (string)$chain;
    }
    return $qry;
  }
}

class HAUserException extends Exception {}

?>