<?
  if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) exit();
  
  error_reporting (E_ALL);
  ini_set ("error_reporting", E_ALL);
  ini_set ("display_errors", "stdout");
  
  if (!isset($_SESSION)) session_start();
  require_once("classes/HAPersistor.class.php");
  // require_once("classes/Doc.class.php");
