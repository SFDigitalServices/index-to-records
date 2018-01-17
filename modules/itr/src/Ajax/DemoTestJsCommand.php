<?php
  namespace Drupal\itr\Ajax;
  use Drupal\Core\Ajax\CommandInterface;
  class DemoTestJsCommand implements CommandInterface
  {
      public function render(){
          return [
              'command' => 'demoTestJsCommand',
          ];
      }

  }
?>