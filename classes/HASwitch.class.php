<?
/**
 * This Class represents a / an HASwitch
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAElement.class.php");

class HASwitch extends HAElement
{
  const DBTBL = "Switches";
  const UIDPX = "tmpsw_";
  const TXMSEC = 1;
  
  private $id;
  private $chain;
  private $number;
  private $state;
  private $delay;
  
  function __construct($id = null, $chain = null, $number = null, $state = false, $delay = 0) {
    if (is_array($id) && isset($id['id'], $id['chain'], $id['number'])) extract($id);
    //if (is_null($id) && empty($this->id)) throw new HASwitchException("No id given.");
    if (is_null($chain) && empty($this->chain)) throw new HASwitchException("No chain given.");
    if (is_null($number) && empty($this->number)) throw new HASwitchException("No number given.");
    if (!empty($chain) && !empty($number)) {
      $this->setId(is_null($id) ? null : (int)$id)
           ->setChain((int)$chain)
           ->setNumber((int)$number)
           ->setState((bool)$state)
           ->setDelay((int)$delay);
    }
  }
  
  public function getOp($debug = false, $simulate = false) {
    $d = "";
    foreach (str_split(""
      /*     data */ . str_pad(decbin(max(0, min(1023, $this->getNumber()))), 10, "0", STR_PAD_LEFT)
      /* reserved */ . "0"
      /*      on  */ . ($this->getState() ? 1 : 0)
      /*   txmsec */ . strrev(str_pad(decbin(max(0, min(3, self::TXMSEC))), 2, "0", STR_PAD_LEFT))
      /*    debug */ . ($debug ? 1 : 0)
      /* simulate */ . ($simulate ? 1 : 0)
      ,8) as $v) $d .= chr(bindec($v));
    return $d;
  }
  
  public function getId() { return $this->id; }
  public function setId($id = null) {
    if (!is_null($id) && (!is_numeric($id) || $id < 1)) throw new HASwitchException("ID must be a number greater than 0 or null.");
    if (is_null($id)) $id = uniqid(self::UIDPX);
    $this->id = $id;
    $this->sully();
    return $this;
  }
  
  public function getChain() { return $this->chain; }
  public function setChain($chain = null) {
    // if (!is_numeric($chain) || $chain < 1) throw new HASwitchException("Chain must be a number greater than 0.");
    $this->chain = $chain;
    $this->sully();
    return $this;
  }
  
  public function getNumber() { return $this->number; }
  public function setNumber($number = null) {
    if (!is_numeric($number) || $number < 1 || $number > 1023) throw new HASwitchException("Switchnumber must be a number between 1 and 1023");
    $this->number = $number;
    $this->sully();
    return $this;
  }
  
  public function getState($asInt = false) { if ($asInt) return (int)($this->state ? 1 : 0); else return $this->state; }
  public function setState($state = null) {
    if (!(is_bool($state) || $state == 0 || $state == 1)) throw new HASwitchException("State must be boolean.");
    $this->state = (bool)$state;
    $this->sully();
    return $this;
  }
  
  public function getDelay() { return $this->delay; }
  public function setDelay($delay = null) {
    if (!is_numeric($delay) || $delay < 0) throw new HASwitchException("Delay must be a number greater than 0.");
    $this->delay = $delay;
    $this->sully();
    return $this;
  }
  
  public function generateQueries() {
    $queries = array();
    if ($this->delete) {
      array_push($queries, "DELETE FROM `" . self::DBTBL . "` WHERE `id` = " . $this->getId());
    } else {
      if ($this->dirty || $this->isNew()) {
        array_push($queries, "REPLACE INTO `" . self::DBTBL . "` (`id`, `chain`, `number`, `state`, `delay`) VALUES (" . ($this->isNew() ? "NULL" : $this->getId()) . ", " . $this->getChain() . ", " . $this->getNumber() . ", " . $this->getState(true) . ", " . $this->getDelay() . ");");
      }
    }
    return $queries;
  }
  
  public function __toString() {
    $qry = "";
    if ($this->delete) {
      $qry .=  "DELETE IGNORE FROM `" . self::DBTBL . "` WHERE `id` = " . $this->getId() . ";\n";
    } else {
      if ($this->dirty || $this->isNew()) {
        $kvs = "`chain` = " . $this->getChain() . ", `number` = " . $this->getNumber() . ", `state` = " . $this->getState(true) . ", `delay` = " . $this->getDelay();
        $qry .=  "INSERT INTO `" . self::DBTBL . "` SET `id` = " . ($this->isNew() ? "NULL" : $this->getId()) . ", $kvs ON DUPLICATE KEY UPDATE $kvs;\n"; 
      }
    }
    return $qry;
  }
}

class HASwitchException extends Exception {}

?>
