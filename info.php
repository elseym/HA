<?
  require_once("common/common.php");
  $users = HAPersistor::load();
  
?><!DOCTYPE html>
<html>
  <head>
    <? Doc::head("R9E Control - Info"); ?>
  </head>

  <body>
    <? Doc::nav(); ?>

    <section class="container">
      <?=basename(__FILE__)?>
    </section>
  </body>
</html>
