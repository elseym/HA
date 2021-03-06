<?
/**
 * This Class represents a / an HAServer
 *
 * @package default
 * @author Simon Waibl
 **/

require_once("HAInterface.class.php");

class HAServer extends HAInterface
{
  const PIDFILE = "./HAServer.pid";
  const ARDUINO = "/dev/ttyACM0";
  
  private static $shutdown = false;
  private static $restart = false;
  
  private static $jobqueue;
  
  public static function serve($logLevel = HALog::LEVEL_DEBUG, $simulation = false) {
		HALog::setVerbosity($logLevel);
    HALog::info("HAServer. welcome.");
    do { // restart loop
      self::$restart = false;
      self::$shutdown = false;
      
      // write pidfile
      $pid = posix_getpid();
      HALog::debug("my pid is $pid");
      if (file_exists(self::PIDFILE)) {
        HALog::error("pidfile already exists: " . self::PIDFILE);
        break; // break out of restart loop
      } else {
        HALog::debug("writing pid to " . self::PIDFILE);
        file_put_contents(self::PIDFILE, $pid);
      }
      
      // init queue
      do {
        $repeat = false;
        HALog::debug("initializing message queue.");
        try {
          self::getMessageQueue(true);
        } catch (HAInterfaceException $iX) {
          HALog::info("removing stale message queue.");
          self::destroyMessageQueue();
          $repeat = true;
        }
      } while ($repeat);
      
			if (!$simulation) {
	      // init arduino
	      HALog::debug("opening arduino serial comm on " . self::ARDUINO . ".");
	      $arduino = dio_open(self::ARDUINO, O_WRONLY | O_NOCTTY | O_NONBLOCK);
	      usleep(15e5);
			} else {
				HALog::warn("HAServer runs in simulation mode.");
			}
    
      // signal handling
      HALog::debug("registering signal handlers.");
      declare(ticks = 1);
      $signalHandler = array("HAServer", "signalHandler");
      foreach (array(SIGTERM, SIGINT, SIGHUP, SIGUSR1) as $signal) pcntl_signal($signal, $signalHandler);
      
      // initializing
      self::$jobqueue = array();
      $txmsec = array(0 => 200, 1 => 400, 2 => 800, 3 => 1200);
      
      // main loop
      $i = 0;
      HALog::debug("entering main loop.");
      while (!self::$shutdown) {
        
        // process message queue
        self::chkADD();
        self::chkGET();
        self::chkDEL();
        self::chkCLR();
        
        // process jobqueue
        foreach (self::$jobqueue as $switchts => $jobs) {
          if ($switchts <= time()) { // gogogo!
            unset(self::$jobqueue[$switchts]);
            foreach ($jobs as $kjob => $job) {
              HALog::debug(($simulation ? "simulating" : "switching") . " #" . $job['switchnum'] . " o" . ($job['state'] ? "n" : "ff") . "\t[" . base64_encode($job['opcode']) . "]@" . $job['switchtime']);
              if (!$simulation) dio_write($arduino, $job['opcode']);
              usleep($txmsec[HASwitch::TXMSEC] * 1e3 + 6e4);
              unset(self::$jobqueue[$switchts][$kjob]);
            }
          }
        }
        
        // don't be greedy
        usleep(1e4); // 10ms
      }
      
			if (!$simulation) {
	      // close arduino communications
	      HALog::debug("closing arduino serial comm.");
	      dio_close($arduino);
      } else {
				HALog::warn("simulation complete.");
			}

      // remove queue
      HALog::debug("removing the message queue.");
      self::destroyMessageQueue();
      
      // remove pidfile
      HALog::debug("removing pidfile.");
      @unlink(self::PIDFILE);
      
    } while (self::$restart);
    HALog::info("exiting. bye.");
  }
  
  private static function chkADD() {
    // check for new jobs
    $type = self::MSG_ADD; $job = null;
    if (self::receiveMessage($type, $job, false)) {
      //usleep(1e5);
      HALog::debug("new job arrived:\tid:" . $job->getId() . "\t" . count($job->getSwitches()) . " switch" . (count($job->getSwitches()) == 1 ? "" : "es") . ".");
      foreach ($job->getSwitches() as $switch) {
        // calculate absolute switching time for each switch,
        //  then add to jobqueue.
        $switchtime = time() + $switch->getDelay();
        $switchjob = array(
          "jobid" => $job->getId(),
          "opcode" => $switch->getOp(),
          "switchnum" => $switch->getNumber(),
          "state" => $switch->getState(),
          "switchtime" => $switchtime
        );
        if (array_key_exists($switchtime, self::$jobqueue)) {
          array_push(self::$jobqueue[$switchtime], $switchjob);
        } else {
          self::$jobqueue[$switchtime] = array($switchjob);
          ksort(self::$jobqueue, SORT_NUMERIC);
        }
      }
    }
  }
  
  private static function chkGET() {
    // check for queue request
    $type = self::MSG_GET; $responseType = null;
    if (self::receiveMessage($type, $responseType, false)) {
      HALog::debug("client requested jobqueue on [$responseType].");
      self::sendMessage($responseType, self::$jobqueue);
    }
  }
  
  private static function chkDEL() {
    // check for deletion request
    $type = self::MSG_DEL; $jobIdToDelete = null;
    if (self::receiveMessage($type, $jobIdToDelete, false)) {
      HALog::debug("client requested deletion of job [$jobIdToDelete].");
      foreach (self::$jobqueue as $kjobs => $jobs) {
        foreach ($jobs as $kjob => $job) {
          if ($job['jobid'] == $jobIdToDelete) unset(self::$jobqueue[$kjobs][$kjob]);
        }
        if (count(self::$jobqueue[$kjobs]) == 0) unset(self::$jobqueue[$kjobs]);
      }
    }
  }
  
  private static function chkCLR() {
    // check for clearance request
    $type = self::MSG_CLR; $payload = null;
    if (self::receiveMessage($type, $payload, false)) {
      HALog::debug("client requested to clear the job queue.");
      
    }
  }
  
  public static function restart() {
    HALog::info("restarting...");
    self::$restart = true;
    self::$shutdown = true;
  }
  
  public static function shutdown() {
    HALog::info("shutting down...");
    self::$restart = false;
    self::$shutdown = true;
  }
  
  protected static function signalHandler($signal) {
    switch ($signal) {
      case SIGINT:
        echo "\n";
        HALog::debug("caught SIGINT.");
        self::shutdown();
        break;
      case SIGTERM:
        HALog::debug("caught SIGTERM.");
        self::shutdown();
        break;
      case SIGHUP:
        HALog::debug("caught SIGHUP.");
        self::restart();
        break;
      case SIGUSR1:
        HALog::debug("caught SIGUSR1.");
        echo "\n === HAServer jobqueue ===\n";
        var_dump(self::$jobqueue);
        echo "\n";
        break;
    }
  }
}

class HAServerException extends Exception {}
