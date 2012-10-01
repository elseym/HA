<?
  require_once("common/common.php");
  
  $users = HAPersistor::load();
  $user = null;
  if (isset($_GET['user'])) foreach ($users as $u) if (strtolower($u->getName()) === strtolower($_GET['user'])) $user = $u;
?><!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>R9E Control - <?=(is_null($user) ? "Willkommen" : $user->getName())?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="description" content="Nifty Home Automation">
    <meta name="author" content="Simon Waibl">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="css/default.css" rel="stylesheet">

    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <script src="js/jquery-1.7.2.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/script.js"></script>
  </head>

  <body>
    <nav class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-list-alt icon-white"></span></a>
          <a class="brand" href="#">R9E Control - <?=(is_null($user) ? "Willkommen" : $user->getName())?></a>
          <div class="nav-collapse">
            <ul class="nav">
              <li><a href="#" id="userselectlink"><i class="icon-user icon-white"></i> Benutzerwahl</a></li>
              <li><a href="#" id="infolink"><i class="icon-info-sign icon-white"></i> Info</a></li>
              <li><a href="#" id="reloadlink"><i class="icon-refresh icon-white"></i> Reload</a></li>
            </ul>
          </div>
        </div>
      </div>
    </nav>
    
    <section id="main" class="container">
      <? if ($user): ?>
      <section id="<?=$user->getName()?>-<?=$user->getId()?>" class="chains well">
        <h2>Chains <small><button data-toggle="button" id="editmode" class="btn btn-mini"><i class="icon-edit"></i></button></small></h2>
        <? foreach ($user->getChains() as $chain): ?>
        <button data-loading-text="schalte&hellip;" class="switch btn btn-warning btn-large<?=(strlen($chain->getName()) > 14 ? " wide" : "")?>" id="chain-<?=$chain->getId()?>"><?=$chain->getName()?></button>
        <? endforeach; ?>
      </section>
      <? else: // no user ?>
      <section id="anonymous" class="chains well">
        <h2>Anonymous <small>anmelden?</small></h2>
        <button class="btn btn-success btn-large wide" id="userselectbutton">Benutzer wählen...</button>
      </section>
      <? endif; ?>
      <section id="manual" class="manual well">
        <h2>Manuell <small></small></h2>
        <div class="input-append control-group">
          <input type="tel" id="manual-num" value="" class="manual"><button class="manual btn btn-info"><i class="icon-white icon-info-sign"></i></button>
        </div>
        <aside id="dips">
          <div class="dip well" id="bre"><h4>Brennenstuhl (1kW) DIPs</h4><div class="well"></div></div>
          <div class="dip well" id="xan"><h4>Xanax (3,6kW) DIPs</h4><div class="well"></div></div>
        </aside>
        <div class="btn-group onoff">
          <button data-loading-text="schalte&hellip;" class="switch on btn btn-large btn-warning"><i class="icon-asterisk icon-white"></i> Ein</button>
          <button data-loading-text="schalte&hellip;" class="switch off btn btn-large btn-inverse"><i class="icon-off icon-white"></i> Aus</button>
          <button data-loading-text="&hellip;" class="add btn btn-large btn-success"<?=(!$user ? " disabled" : "")?>><i class="icon-plus icon-white"></i></button>
        </div>
      </section>
      
      <!-- Edit modal -->
      <aside id="chainedit" class="modal">
        <header class="modal-header"><button class="close" data-dismiss="modal">×</button><h3></h3><input type="text" id="chainedit-name" value=""></header>
        <section class="modal-body">
        </section>
        <footer class="modal-footer">
          <button data-loading-text="Lösche&hellip;" class="user btn btn-danger pull-left" id="chainedit-delete">Löschen</button>
          <button data-loading-text="Abbrechen&hellip;" class="user btn" id="chainedit-cancel">Schließen</button>
          <button data-loading-text="Speichere&hellip;" class="user btn btn-success" id="chainedit-save">Speichern</button>
        </footer>
      </aside>
      
      <!-- Add modal -->
      <aside id="chainadd" class="modal">
        <header class="modal-header"><button class="close" data-dismiss="modal">×</button><h3>Hinzufügen</h3></header>
        <section class="modal-body">
					<h4>Schaltereinstellungen</h4>
					<section id="chainadd-switch">
						<div id="chainadd-switch-state" class="btn-group" data-toggle="buttons-radio">
      		    <button id="chainadd-switch-state-on" class="btn btn-warning btn-large active"><i class="icon-asterisk icon-white"></i> Ein</button>
		          <button id="chainadd-switch-state-off" class="btn btn-inverse btn-large"><i class="icon-off icon-white"></i> Aus</button>
						</div>
            <div class="btn-group">
              <a id="chainadd-delay-choose" class="btn dropdown-toggle btn-large btn-info" data-toggle="dropdown" href="#">
                <span id="chainadd-delay-text">0 sek.</span>
                <span class="caret"></span>
              </a>
              <ul id="chainadd-delay-select" class="dropdown-menu">
                <li><a id="chainadd-delay-value-0" href="#">Keine Verzögerung</a></li>
                <li><a id="chainadd-delay-value-5" href="#">5 Sekunden</a></li>
                <li><a id="chainadd-delay-value-30" href="#">30 Sekunden</a></li>
                <li><a id="chainadd-delay-value-60" href="#">1 Minute</a></li>
                <li><a id="chainadd-delay-value-1800" href="#">30 Minuten</a></li>
                <li class="divider"></li>
                <li><a id="chainadd-delay-value-custom" href="#">Benutzerdefiniert...</a></li>
              </ul>
            </div>
  					<p class="muted">Soll der Schalter ein- oder ausgeschaltet werden und wie lange soll vor dem Schaltvorgang gewartet werden?</p>
					</section>
					<hr />
					<h4>Verfügbare Chains</h4>
					<section id="chainadd-list">
            <div class="btn-group">
              <a id="chainadd-list-choose" class="btn dropdown-toggle btn-large btn-info" data-toggle="dropdown" href="#">
                <span id="chainadd-list-text">Chain auswählen...</span>
                <span class="caret"></span>
              </a>
              <ul id="chainadd-list-select" class="dropdown-menu">
                <li><a id="chainadd-list-value-new" data-name="Neu" href="#">Neue Chain...</a></li>
                <li class="divider"></li>
                <li><div class="progress progress-info progress-striped active" style="width:86%;margin:auto"><div class="bar" style="width: 100%;">lade Chains...</div></div></li>
              </ul>
            </div>
					</section>
					<p class="muted">Der gewählte Schalter wird ans Ende der Chain angefügt.</p>
        </section>
        <footer class="modal-footer">
          <button data-loading-text="Abbrechen&hellip;" class="user btn" id="chainadd-cancel">Schließen</button>
          <button data-loading-text="Speichere&hellip;" class="user btn btn-success" id="chainadd-save">Speichern</button>
        </footer>
      </aside>
      
      <!-- Userselect modal -->
      <aside id="userselect" class="modal">
        <header class="modal-header"><button class="close" data-dismiss="modal">×</button><h3>Benutzer wählen&hellip;</h3></header>
        <section class="modal-body">
          <? foreach ($users as $user): ?>
          <button class="user btn btn-info btn-large" id="user-<?=$user->getName()?>"><?=$user->getName()?> <span class="badge"><?=count($user->getChains())?> Chain<?=(count($user->getChains()) == 1 ? "" : "s")?></span></button>
          <? endforeach; ?>
          <button class="user btn btn-danger btn-large" id="user-cancel">Abmelden</button>
        </section>
        <footer class="modal-footer"></footer>
      </aside>
      
      <!-- Info modal -->
      <aside id="infobox" class="modal">
        <header class="modal-header"><button class="close" data-dismiss="modal">×</button><h3>R9E Control - Info</h3></header>
        <section class="modal-body">
          <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>
        </section>
        <footer class="modal-footer">
          <span>&copy; 2012 &bull; elseym</span>
          <a href="#" class="btn btn-primary">Vielen Dank.</a>
        </footer>
      </aside>
    </section>
  </body>
</html>
