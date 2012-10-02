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
  const LEVEL_QUIET = 0;
  
  private static $messages;
  private static $printLevel = self::LEVEL_DEBUG;
  
  protected static function message($message, $level) {
    if (!is_array(self::$messages)) self::$messages = array();
    $bt = debug_backtrace(0);
    if (isset($bt[2])) $source = $bt[2]['class'] . $bt[2]['type'] . $bt[2]['function'] . ":" . $bt[2]['line'];
    array_push(self::$messages, array("level" => $level, "message" => $message, "source" => $source));
    if (self::$printLevel >= $level) {
      $levelcolours = array("default", "red", "brown", "green", "blue");
      echo 
        self::colourise("HALog", "grey", true),
        self::colourise("\t[", "grey", true),
          self::colourise(str_pad("$source", 29), "cyan"),
          self::colourise(str_pad(self::errorLevelString($level), 6, " ", 0), $levelcolours[$level]),
        self::colourise("]\t", "grey", true),
        "$message\n";
    }
  }
  
  private static function colourise($string, $colour = "default", $bold = false, $underline = false, $inverse = false, $blink = false) {
    $colours = array(
      "black"   => "30", "red"     => "31", "green"   => "32", "brown"   => "33",
      "blue"    => "34", "purple"  => "35", "cyan"    => "36", "grey"    => "37", "default" => ""
    );
    $ret  = "\033[" . (array_key_exists($colour, $colours) ? $colours[$colour] : "") . ($bold ? ";2" : "");
    return $ret . ($underline ? ";4" : "") . ($inverse ? ";7" : "") . ($blink ? ";5" : "") . "m$string\033[0m";
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
  
  public static function getVerbosity() { return self::$printLevel; }
  public static function setVerbosity($printLevel) {
    self::$printLevel = max(min($printLevel, 4), 0);
  }
  
}

class HALogException extends Exception {}

?>