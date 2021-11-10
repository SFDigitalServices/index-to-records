<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use Drupal\itr\Utility\Utility;

/**
* Provides a rest resource to get or add department divisions
*
* @RestResource(
*   id = "department_division_by_id",
*   label = @Translation("Department Division"),
*   uri_paths = {
*     "canonical" = "/itr_rest/department/{deptId}/division",
*     "create" = "/itr_rest/department/division/add"
*   }
* )
*/

class DepartmentDivisionResource extends ResourceBase {
  /**
  * Responds to entity GET requests.
  * @return \Drupal\rest\ResourceResponse
  */
  public function get($deptId) {
    $response = ['message' => 'no dept divisions'];
    $build = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );
    if(!isset($deptId)) {
      $response = ['message' => 'dept id is required'];
    } else {
      $vid = 'department';
      $divisionId = $this->getDivisionTermId($deptId);
      $divisionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $divisionId);
      foreach($divisionTerms as $term) {
        $divisions[] = array(
          'id' => $term->tid,
          'name' => $term->name,
        );
      }
      $response = $divisions;
    }
    return (new ResourceResponse($response))->addCacheableDependency($build);
  }

  /**
  * Responds to entity POST requests.
  * expected data format:
  * {
  *   deptId: deptId,
  *   divisions: array containing division strings
  * }
  * @return \Drupal\rest\ResourceResponse
  */
  public function post($data) {
    $response = ['message' => 'division create failed'];
    $deptId = $data['deptId'];
    $divisions = $data['divisions'];
    $createdIds = Utility::addTermToDeptChildTerm($deptId, 'division', $divisions);
    if(count($createdIds) > 0) {
      $response = ['message' => 'save success', 'divisions' => $createdIds];
    }
    return new ResourceResponse($response);
  }

  private function getDivisionTermId($deptId) {
    $deptTermChildren = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department', $deptId);
    foreach($deptTermChildren as $deptTermChild) {
      if(strtolower($deptTermChild->name) == 'division') {
        return $deptTermChild->tid;
      }
    }
    return null;
  }
}