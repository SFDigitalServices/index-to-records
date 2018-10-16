<?php
  namespace Drupal\itr\Ajax;
  use Drupal\Core\Ajax\CommandInterface;

  class ExportScheduleCSVCommand implements CommandInterface
  {
    protected $data;

    public function __construct($data) {
      $this->data = $data;
    }  

    public function render(){
      return [
        'command' => 'exportScheduleCSVCommand',
        'data' => $this->data,
      ];
    }
  }
?>