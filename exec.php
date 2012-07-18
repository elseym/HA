<?
  require_once("common/common.php");
  
  $result = array("success" => false);
  
  ob_start();
  
  $users = HAPersistor::load();
  
  function findChain($users) {
    try {
      foreach ($users as $user) if ($chain = $user->getChain($_POST['id'])) return $chain;
    } catch (Exception $e) {
      $result['error'] = $e->getMessage();
    }
    return false;
  }
  
  if (isset($_POST['type'], $_POST['method'], $_POST['id'])) {
    switch ($_POST['type']) {
      case "misc":
        switch ($_POST['method']) {
          case "getlog":
            
            break;
          default:
            $result['error'] = "method not defined.";
        }
        break;
      
      case "chain":
        if (!$chain = findChain($users)) break;
        switch ($_POST['method']) {
          case "activate":
            $chain->activate();
            break;
            
          case "rename":
            if (isset($_POST['name'])) {
              $n = trim($_POST['name']);
              if ($n != "" && $n != $chain->getName()) $chain->setName($n);
            } else $result['error'] = "missing parameter.";
            break;
            
          case "details":
            var_dump($_POST);
            foreach($chain->getSwitches() as $sw)
              $s[] = array(
                "id"      => $sw->getId(),
                "chain"   => $sw->getChain(),
                "number"  => $sw->getNumber(),
                "state"   => $sw->getState(),
                "delay"   => $sw->getDelay()
              );
            $result['data'] = array(
              "id"       => $chain->getId(),
              "user"     => $chain->getUser(),
              "name"     => $chain->getName(),
              "switches" => $s
            );
            break;
            
          case "delete":
            $chain->delete();
            $result['callback'] = 'reload';
            break;
            
          default:
            $result['error'] = "method not defined.";
        }
        HAPersistor::save($users);
        break;
        
      case "manual":
        try {
          $state = $_POST['method'] == "true";
          $swnum = (int) $_POST['id'];
          $switch = new HASwitch(null, null, $swnum, $state, 0);
          $job = new HAJob($switch);
          HAInterface::addJob($job);
          $result['success'] = true;
          $result['info'] = $_POST;
        } catch (Exception $e) {
          $result['error'] = $e->getMessage();
        }
        break;
        
      default:
        $result['error'] = "type not defined.";
    }
  }
  
  usleep(56e4);
  $result['dbg'] = ob_get_clean();
  echo json_encode($result);
?>