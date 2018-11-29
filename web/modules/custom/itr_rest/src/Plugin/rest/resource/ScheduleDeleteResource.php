<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
* Provides the a delete schedule resource
*
* @RestResource(
*   id = "schedule_delete",
*   label = @Translation("Schedule Delete"),
*   uri_paths = {
*     "canonical" = "/itr_rest/schedule/{deptId}",
*     "https://www.drupal.org/link-relations/create" = "/itr_rest/schedule/delete"
*   }
* )
*/

class ScheduleDeleteResource extends ResourceBase {

  public function post($data) {
    $response = ['exists' => false, 'delete_ids' => []];
    // error_log('ScheduleDeleteResource: ' . print_r($data, 1));
    if(isset($data) && count($data) > 0) {
      $controller = \Drupal::entityTypeManager()->getStorage('node');
      $entities = $controller->loadMultiple($data);
      $controller->delete($entities);
      $response = ['exists' => true, 'delete_ids' => $data];
    }
    return new ResourceResponse($response);
  }

}

?>