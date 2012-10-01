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
            
            $result['success'] = true;
            break;
          default:
            $result['error'] = "method not defined.";
        }
        break;

			case "chains":
				switch ($_POST['method']) {
					case "list":
						if (!array_key_exists($_POST['id'], $users) || count($users[$_POST['id']]->getChains()) == 0) {
            	$result['error'] = "no chains for user " . $_POST['id'];
							break;
						}
						$chains = $users[$_POST['id']]->getChains();
						foreach ($chains as $chain) {
							$result['data'][] = array(
								"id" => $chain->getId(),
								"name" => $chain->getName(),
								"switch-count" => count($chain->getSwitches())
							);
						}
						sleep(2);
						$result['success'] = true;
						break;
						
					default:
            $result['error'] = "method not defined.";
				}
				break;
			
			case "switch":
			  break;
			  
      case "chain":
        if (!$chain = findChain($users)) break;
        switch ($_POST['method']) {
          case "add":
						var_dump($_REQUEST);
						break;
						if (!array_key_exists($_POST['id'], $users)) {
							$result['error'] = "user does not exist.";
							break;
						}
						$users[$_POST['id']]->addChain(null, $_POST['name']);
            $result['success'] = true;
						break;
						
          case "activate":
            try {
              $chain->activate();
              $result['success'] = true;
            } catch (HAInterfaceException $ie) {
              $result['error'] = $ie->getMessage();
            }
            break;
            
          case "rename":
            if (isset($_POST['name'])) {
              $n = trim($_POST['name']);
              if ($n != "" && $n != $chain->getName()) $chain->setName($n);
              HAPersistor::save($users);
              $result['success'] = true;
            } else $result['error'] = "missing parameter.";
            break;
            
          case "details":
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
            $result['success'] = true;
            break;
            
          case "delete":
            $chain->delete();
            HAPersistor::save($users);
            $result['callback'] = 'reload';
            $result['success'] = true;
            break;
            
          default:
            $result['error'] = "method not defined.";
        }
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