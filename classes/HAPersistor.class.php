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
  const DBFILE = "ha.sqlite3";
  
  const METHOD = "sqlite";
  
  const SCACHE = 768;
  
  private static $loading = false;
  
  public static function load($force = true) {
    switch (self::METHOD) {
      case "mysql":   return self::loadMySql($force);
      case "sqlite":  return self::loadSqlite($force);
    }
    return false;
  }
  
  public static function save($users, $noPersist = false) {
    switch (self::METHOD) {
      case "mysql":   return self::saveMySql($users, $noPersist);
      case "sqlite":  return self::saveSqlite($users, $noPersist);
    }
    return false;
  }
  
  private static function checkCache($force) {
    if (!isset($_SESSION)) session_start();
    if (!$force
        && array_key_exists("hadata", $_SESSION)
        && is_array($_SESSION['hadata'])
        && array_key_exists("users", $_SESSION['hadata'])
        && array_key_exists("date", $_SESSION['hadata'])
        && ($_SESSION['hadata']['date'] + self::SCACHE) >= time()
        && ($data = unserialize($_SESSION['hadata']['users']))
        && is_array($data)
        && (count($data) > 0)
    ) return $data; else return false;
  }

  private static function loadSqlite($force) {
    self::$loading = true;
    if ($data = self::checkCache($force)) {
      $users = $data;
    } else {
      $db = new PDO("sqlite:" . self::DBFILE);
      $users = array();
      foreach($db->query("SELECT `id`, `name` From `Users`", PDO::FETCH_CLASS, "HAUser") as $user) {
        foreach ($db->query("SELECT `id`, `user`, `name` FROM `Chains` WHERE `user` = " . $user->getId(), PDO::FETCH_CLASS, "HAChain") as $chain) {
          foreach ($db->query("SELECT `id`, `chain`, `number`, `state`, `delay` FROM `Switches` WHERE `chain` = " . $chain->getId(), PDO::FETCH_CLASS, "HASwitch") as $switch) $chain->addSwitch($switch);
          $user->addChain($chain);
        }
        $users[$user->getId()] = $user;
      }
    }
    self::$loading = false;
    return $users;
  }

  private static function loadMySql($force) {
    self::$loading = true;
    if ($data = self::checkCache($force)) {
      $users = $data;
    } else {
      $db = new mysqli(self::DBHOST, self::DBUSER, self::DBPASS, self::DBDABA);
      $users = array();
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
  
  private static function saveSqlite($users, $noPersist) {
    if (!isset($_SESSION)) session_start();
    $queries = array();
    if (!is_array($users)) throw new HAPersistorException("Data is not an array.");
    foreach ($users as $user) {
      if (!$user instanceof HAUser) throw new HAPersistorException("Data contains entities which are not instances of HAUser.");
      $queries = array_merge($queries, $user->generateQueries());
    }
    $_SESSION['hadata'] = array(
      "date" => time(),
      "users" => serialize($users)
    );
    if (!$noPersist && !empty($queries)) {
      $db = new PDO("sqlite:" . self::DBFILE);
      $ret = true;
      foreach ($queries as $query) $ret = $ret && (false !== $db->exec($query));
      if (!$ret) throw new HAPersistorException("Could not save data.");
    }
  }
  
  private static function saveMySql($users, $noPersist) {
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