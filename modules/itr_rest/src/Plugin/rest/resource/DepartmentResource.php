<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
* Provides the categories for a specific department
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
      // if(strtolower($term->name) != 'category') {
      //   $categories[] = array(
      //     'id' => $term->tid,
      //     'name' =>$term->name
      //   );
      // }
      // error_log(print_r($term, 1));
      if(in_array(0, $term->parents)) {
        $departments[] = array(
          'id' => $term->tid,
          'name' => $term->name,
        );
      }
    }

    $response = ['message' => 'Hello, this is a rest service'];
    return new ResourceResponse($departments);
  }
}

?>