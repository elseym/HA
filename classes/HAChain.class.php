<?
/**
 * This Class represents a / an HAChain
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAElement.class.php");
require_once("HASwitch.class.php");
require_once("HAInterface.class.php");

class HAChain extends HAElement
{
  const DBTBL = "Chains";
  const UIDPX = "tmpch_";
  
  private $id;
  private $user;
  private $name;
  
  private $switches;
  
  function __construct($id = null, $user = null, $name = null) {
    if (is_array($id) && isset($id['id'], $id['user'], $id['name'])) extract($id);
    if (is_null($id) && empty($this->id)) throw new HAChainException("No id given.");
    if (is_null($user) && empty($this->user)) throw new HAChainException("No user given.");
    if (is_null($name) && empty($this->name)) throw new HAChainException("No name given.");
    if (!empty($id) && !empty($user) && !empty($name)) {
      $this->setId(is_null($id) ? null : (int)$id)
           ->setUser((int)$user)
           ->setName((string)$name);
    }
    $this->setSwitches();
  }
  
  public function activate() {
    $job = new HAJob($this);
    HAInterface::addJob($job);
    return $this;
  }
  
  public function getId() { return $this->id; }
  public function setId($id = null) {
    if (!is_null($id) && (!is_numeric($id) || $id < 1)) throw new HAChainException("Id must be a number greater than 0.");
    if (is_null($id)) $id = uniqid(self::UIDPX);
    $this->id = $id;
    $this->sully();
    return $this;
  }
  
  public function getUser() { return $this->user; }
  public function setUser($user = null) {
    if (!is_numeric($user) || $user < 1) throw new HAChainException("User must be a number greater than 0.");
    $this->user = $user;
    $this->sully();
    return $this;
  }
  
  public function getName() { return $this->name; }
  public function setName($name = null) {
    if (!is_string($name) || trim($name) == "") throw new HAChainException("Name must be a valid string.");
    $this->name = $name;
    $this->sully();
    return $this;
  }
  
  public function getSwitches() { return $this->switches; }
  public function getSwitch($id) {
    if (!array_key_exists($id, $this->getSwitches())) throw new HAChainException("Switch [$id] does not exists.");
    return $this->switches[$id];
  }
  public function addSwitch($id = null, $number = null, $state = false, $delay = 0) {
    if ($id instanceOf HASwitch) {
      $switch = $id;
      $switch->setChain($this->getId());
    } else {
      $switch = new HASwitch($id, $this->getId(), $number, $state, $delay);
    }
    if (array_key_exists($switch->getId(), $this->getSwitches())) throw new HAChainException("Duplicate switch [" . $switch->getId() . "] in chain.");
    $this->switches[$switch->getId()] = $switch;
    return $this;
  }
  public function removeSwitch($id) {
    if (!array_key_exists($id, $this->getSwitches())) throw new HAChainException("Switch [$id] does not exists.");
    $switches = $this->getSwitches();
    unset($switches[$id]);
    return $this->setSwitches($switches);
  }
  public function setSwitches($switches = null) {
    if (!is_array($this->switches)) {
      if (is_null($switches)) $switches = array(); else throw new HAChainException("Switches must be an array of Switches.");
    }
    $this->switches = $switches;
    return $this;
  }
  
  public function generateQueries() {
    $queries = array();
    if ($this->delete) {
      foreach ($this->getSwitches() as $switch) $queries = array_merge($queries, $switch->delete()->generateQueries());
      array_push($queries, "DELETE FROM `" . self::DBTBL . "` WHERE `id` = " . $this->getId() . " LIMIT 1;");
    } else {
      if ($this->dirty || $this->isNew()) {
        array_push($queries, "REPLACE INTO `" . self::DBTBL . "` (`id`, `user`, `name`) VALUES (" . ($this->isNew() ? "NULL" : $this->getId()) . ", " . $this->getUser() . ", '" . $this->getName() . "');");
      }
      foreach ($this->getSwitches() as $switch) $queries = array_merge($queries, $switch->generateQueries());
    }
    return $queries;
  }
  
  public function __toString() {
    $qry = "";
    if ($this->delete) {
      foreach ($this->getSwitches() as $switch) $qry .= (string)$switch->delete();
      $qry .= "DELETE IGNORE FROM `" . self::DBTBL . "` WHERE `id` = " . $this->getId() . "\n";
    } else {
      if ($this->dirty || $this->isNew()) {
        $kvs = "`user` = " . $this->getUser() . ", `name` = '" . $this->getName() . "'";
        $qry .= "INSERT INTO `" . self::DBTBL . "` SET `id` = " . ($this->isNew() ? "NULL" : $this->getId()) . ", $kvs ON DUPLICATE KEY UPDATE $kvs;\n";
      }
      foreach ($this->getSwitches() as $switch) $qry .= (string)$switch;
    }
    return $qry;
  }
}

class HAChainException extends Exception {}

?>