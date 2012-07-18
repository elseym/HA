<?
/**
 * This Class represents a / an HALog
 *
 * @package default
 * @author Simon Waibl
 **/
class HALog
{
  const LEVEL_DEBUG = 4;
  const LEVEL_INFO  = 3;
  const LEVEL_WARN  = 2;
  const LEVEL_ERROR = 1;
  
  private static $messages;
  private static $printLevel = self::LEVEL_DEBUG;
  
  protected static function message($message, $level) {
    if (!is_array(self::$messages)) self::$messages = array();
    $bt = debug_backtrace(0);
    if (isset($bt[2])) $source = $bt[2]['class'] . $bt[2]['type'] . $bt[2]['function'] . ":" . $bt[2]['line'];
    array_push(self::$messages, array("level" => $level, "message" => $message, "source" => $source));
    if (self::$printLevel >= $level) print "HALog\t[" . self::errorLevelString($level) . ", $source]:\t$message\n";
  }
  
  public static function debug($message) {
    self::message($message, self::LEVEL_DEBUG);
  }
  
  public static function info($message) {
    self::message($message, self::LEVEL_INFO);
  }
  
  public static function warn($message) {
    self::message($message, self::LEVEL_WARN);
  }
  
  public static function error($message) {
    self::message($message, self::LEVEL_ERROR);
  }
  
  public static function errorLevelString($level) {
    switch ($level) {
      case self::LEVEL_DEBUG: return "Debug";
      case self::LEVEL_INFO:  return "Info";
      case self::LEVEL_WARN:  return "Warn";
      case self::LEVEL_ERROR: return "Error";
    }
  }
}

class HALogException extends Exception {}

?>