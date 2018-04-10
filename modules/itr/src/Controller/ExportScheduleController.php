<?php
  namespace Drupal\itr\Controller;

  use Drupal\Core\Controller\ControllerBase;

  class ExportScheduleController extends ControllerBase {
    public function content($deptId) {
      if($deptId == 'all') {
        $str = 'no dept provided';
      } else {
        $str = 'export schedule controller:' . $deptId;
      }
      return array(
        '#type' => 'markup',
        '#markup' => $this->t($str),
      );
    }
  }
?>