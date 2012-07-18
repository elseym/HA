<?
/**
 * This Class represents a / an HAInterface
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAJob.class.php");

class HAInterface
{
  const QUEUEID = 2378462;
  
  const MSG_ADD = 4;
  const MSG_GET = 5;
  const MSG_DEL = 6;
  const MSG_CLR = 7;
  
  protected static $blocktime = 1500;
  
  /**
   * appends a new job to the queue.
   */
  public static function addJob(HAJob $job) {
    return self::sendMessage(self::MSG_ADD, $job);
  }
  
  /**
   * returns an array of remaining jobs.
   */
  public static function getJobs() {
    $type = self::MSG_GET;
    $responseType = rand(1e4, 6e4);
    if (self::sendMessage($type, $responseType)) {
      if (self::receiveMessage($responseType, $payload)) {
        return $payload;
      }
    }
    return false;
  }
  
  /**
   * deletes a single job, by id.
   */
  public static function deleteJob($jobId) {
    return self::sendMessage(self::MSG_DEL, $jobId);
  }
  
  /**
   * deletes all jobs in the queue
   */
  public static function clearJobs() {
    return self::sendMessage(self::MSG_CLR);
  }
  
  protected static function getMessageQueue($create = false) {
    if ($create) {
      if (msg_queue_exists(self::QUEUEID)) throw new HAInterfaceException("Could not create messagequeue because it already exists.");
    } else {
      if (!msg_queue_exists(self::QUEUEID)) throw new HAInterfaceException("Messagequeue not found. Is the server running?");
    }
    return msg_get_queue(self::QUEUEID);
  }
  
  protected static function destroyMessageQueue() {
    // try {
      // get existing queue and destroy it
      $queue = self::getMessageQueue(false);
      return msg_remove_queue($queue);
    // } catch (HAInterfaceException $e) {
    //   // no queue existant
    //   return true;
    // }
  }
  
  protected static function sendMessage($type, $payload = null) {
    $q = self::getMessageQueue();
    $ret = msg_send($q, $type, $payload, true, true, $err);
    if ($err != 0 || !$ret) throw new HAInterfaceException("Message [$type] could not be sent. Error: $err.");
    return $ret;
  }
  
  protected static function receiveMessage(&$type, &$payload, $blocking = true) {
    $q = self::getMessageQueue();
    $desiredType = (int)$type;
    $now = microtime(true);
    do {
      $ret = msg_receive($q, $desiredType, $type, 1e4, $payload, true, MSG_IPC_NOWAIT, $err);
      if ($ret) break; else usleep(1e5);
    } while ($blocking && (microtime(true) - $now < (self::$blocktime / 1000)));
    if ($err != 0 && (!$blocking && $err != MSG_ENOMSG)) throw new HAInterfaceException("Message [$desiredType] could not be received. Error: $err.");
    return $ret;
  }
}

class HAInterfaceException extends Exception {}

?>