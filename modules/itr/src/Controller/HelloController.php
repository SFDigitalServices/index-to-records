<?php
  namespace Drupal\itr\Controller;

  use Drupal\Core\Controller\ControllerBase;

  class HelloController extends ControllerBase {
    public function content() {
      return array(
        '#type' => 'markup',
        '#markup' => $this->t('Blah!'),
      );
    }
  }
?>