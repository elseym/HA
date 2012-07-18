<?
/**
 * This Class represents a / an HAPersistor
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HALog.class.php");
require_once("HAUser.class.php");

class HAPersistor
{
  const DBHOST = "localhost";
  const DBDABA = "ha";
  const DBUSER = "ha";
  const DBPASS = "MfVzSHTpGBQd9vfe";
  
  const SCACHE = 768;
  
  private static $loading = false;
  
  public static function load($force = true) {
    self::$loading = true;
    if (!isset($_SESSION)) session_start();
    $users = array();
    if (   !$force
        && array_key_exists("hadata", $_SESSION)
        && is_array($_SESSION['hadata'])
        && array_key_exists("users", $_SESSION['hadata'])
        && array_key_exists("date", $_SESSION['hadata'])
        && ($_SESSION['hadata']['date'] + self::SCACHE) >= time()
        && ($data = unserialize($_SESSION['hadata']['users']))
        && is_array($data)
        && (count($data) > 0)
    ) {
      $users = $data;
    } else {
      $db = new mysqli(self::DBHOST, self::DBUSER, self::DBPASS, self::DBDABA);
      $db->query("SET character_set_results = 'utf8', " .
                 "character_set_client = 'utf8', " .
                 "character_set_connection = 'utf8', " .
                 "character_set_database = 'utf8', " .
                 "character_set_server = 'utf8'");
      if ($db->connect_error) throw new HAPersistorException("Could not establish database connection. Error: " . $db->connect_error);
      if (!$retUser = $db->query("SELECT `id`, `name` From `Users`")) throw new HAPersistorException("Query error: " . $db->error);
      while ($rowUser = $retUser->fetch_assoc()) { // Users loop
        $user = new HAUser($rowUser);
        if (!$retChain = $db->query("SELECT `id`, `user`, `name` FROM `Chains` WHERE `user` = " . $user->getId())) throw new HAPersistorException("Query error: " . $db->error);
        while ($rowChain = $retChain->fetch_assoc()) { // Chains loop
          $chain = new HAChain($rowChain);
          if (!$retSwitch = $db->query("SELECT `id`, `chain`, `number`, `state`, `delay` FROM `Switches` WHERE `chain` = " . $chain->getId())) throw new HAPersistorException("Query error: " . $db->error);
          while ($rowSwitch = $retSwitch->fetch_assoc()) { // Switches loop
            $switch = new HASwitch($rowSwitch);
            $chain->addSwitch($switch);
          }
          $retSwitch->free();
          $user->addChain($chain);
        }
        $retChain->free();
        $users[$user->getId()] = $user;
      }
      $retUser->free();
      $db->close();
    }
    self::$loading = false;
    return $users;
  }
  
  public static function save($users, $noPersist = false) {
    if (!isset($_SESSION)) session_start();
    $qry = "";
    if (!is_array($users)) throw new HAPersistorException("Data is not an array.");
    foreach ($users as $user) {
      if (!$user instanceof HAUser) throw new HAPersistorException("Data contains entities which are not instances of HAUser.");
      $qry .= (string)$user;
    }
    $_SESSION['hadata'] = array(
      "date" => time(),
      "users" => serialize($users)
    );
    echo $qry;
    if (!$noPersist && trim($qry) != "") {
      $db = new mysqli(self::DBHOST, self::DBUSER, self::DBPASS, self::DBDABA);
      $db->query("SET character_set_results = 'utf8', " .
                 "character_set_client = 'utf8', " .
                 "character_set_connection = 'utf8', " .
                 "character_set_database = 'utf8', " .
                 "character_set_server = 'utf8'");
      if ($db->connect_error) throw new HAPersistorException("Could not establish database connection. Error: " . $db->connect_error);
      if (!$db->multi_query($qry)) throw new HAPersistorException("Query error: " . $db->error);
      $i = 0; do { $i++; } while ($db->more_results() && $db->next_result());
      if ($db->errno) {
        $aqry = explode("\n", $qry);
        throw new HAPersistorException("Query error executing \"" . $aqry[$i] . "\"");
      }
      $db->close();
    }
  }
  
  public static function isLoading() {
    return self::$loading;
  }
}

class HAPersistorException extends Exception {}

?>