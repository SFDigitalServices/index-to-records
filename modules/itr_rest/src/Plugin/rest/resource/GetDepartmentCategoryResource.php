<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
* Provides the categories for a specific department
*
* @RestResource(
*   id = "department_category",
*   label = @Translation("Department Category"),
*   uri_paths = {
*     "canonical" = "/itr_rest/department_category/{deptId}"
*   }
* )
*/

class GetDepartmentCategoryResource extends ResourceBase {
  /**
  * Responds to entity GET requests.
  * @return \Drupal\rest\ResourceResponse
  */
  public function get($deptId = NULL) {
    $vid = 'department';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $deptId);
    foreach($terms as $term) {
      if(strtolower($term->name) != 'category') {
        $categories[] = array(
          'id' => $term->tid,
          'name' =>$term->name
        );
      }
    }

    $response = ['message' => 'Hello, this is a rest service'];
    error_log($deptId);
    error_log(print_r($categories, 1));
    return new ResourceResponse($categories);
  }
}

?>