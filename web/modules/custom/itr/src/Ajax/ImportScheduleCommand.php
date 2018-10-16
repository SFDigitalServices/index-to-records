<?php
  namespace Drupal\itr\Ajax;
  use Drupal\Core\Ajax\CommandInterface;

  class ImportScheduleCommand implements CommandInterface
  {
    protected $data;

    public function __construct($data) {
      $this->data = $data;
    }  

    public function render(){
      return [
        'command' => 'importScheduleCommand',
        'data' => $this->data,
      ];
    }
  }
?>