<?
  require_once("common/common.php");
  
  $result = array("success" => false);
  
  ob_start();
  
  $users = HAPersistor::load();
  
  function findChain($users, $id = null) {
    if (is_null($id)) $id = $_POST['id'];
    try {
      foreach ($users as $user) if ($chain = $user->getChain($id)) return $chain;
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
						$result['success'] = true;
						break;
						
					default:
            $result['error'] = "method not defined.";
				}
				break;
			
			case "switch":
			  switch ($_POST['method']) {
			    case "add":
			      // id is userId!
						if (!array_key_exists($_POST['id'], $users)) {
							$result['error'] = "user does not exist.";
							break;
						}
            if (!$chain = findChain($users, $_POST['chain'])) {
              if ($_POST['chain'] != -1) {
							  $result['error'] = "chain does not exist.";
							  break;
              }
              $chain = new HAChain(null, $_POST['id'], $_POST['name']);
					    $users[$_POST['id']]->addChain($chain);
              HAPersistor::save($users);
              $users = HAPersistor::load(true);
              // strict blah blah...
              $chain = @end($users[$_POST['id']]->getChains());
            }
            
            $chain->addSwitch(null, $_POST['number'], !($_POST['state'] == "0"), $_POST['delay']);
            HAPersistor::save($users);
            $result['success'] = true;
			      break;
			      
			    default:
			      $result['error'] = "method not defined.";
			  }
			  break;
			  
      case "chain":
        if (!$chain = findChain($users)) break;
        switch ($_POST['method']) {
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
            $s = array();
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
          
          case "removeswitch":
            $chain->getSwitch($_POST['number'])->delete();
            HAPersistor::save($users);
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
  } else {
    $result['error'] = "missing parameters!";
  }
  
  //usleep(56e4);
  $result['dbg'] = ob_get_clean();
  echo json_encode($result);
  // var_dump($result);
?>