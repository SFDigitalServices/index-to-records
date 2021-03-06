<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
* Provides the department names
*
* @RestResource(
*   id = "departments",
*   label = @Translation("Department"),
*   uri_paths = {
*     "canonical" = "/itr_rest/departments"
*   }
* )
*/

class DepartmentResource extends ResourceBase {
  /**
  * Responds to entity GET requests.
  * @return \Drupal\rest\ResourceResponse
  */
  public function get() {
    $vid = 'department';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
    foreach($terms as $term) {
      if(in_array(0, $term->parents)) {
        $departments[] = array(
          'id' => $term->tid,
          'name' => $term->name,
        );
      }
    }
    return new ResourceResponse($departments);
  }
}