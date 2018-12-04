<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use Drupal\itr\Utility\Utility;

/**
* Provides a rest resource to get or add department categories
*
* @RestResource(
*   id = "department_category_by_id",
*   label = @Translation("Department Category"),
*   uri_paths = {
*     "canonical" = "/itr_rest/department/{deptId}/category",
*     "https://www.drupal.org/link-relations/create" = "/itr_rest/department/category/add"
*   }
* )
*/

class DepartmentCategoryResource extends ResourceBase {
  /**
  * Responds to entity GET requests.
  * @return \Drupal\rest\ResourceResponse
  */
  public function get($deptId) {
    $response = ['message' => 'Hello, this is a rest service'];
    $build = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );
    if(!isset($deptId)) {
      $response = ['message' => 'dept id is required'];
    } else {
      $vid = 'department';
      $categoryId = $this->getCategoryTermId($deptId);
      $categoryTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $categoryId);
      foreach($categoryTerms as $term) {
        $categories[] = array(
          'id' => $term->tid,
          'name' => $term->name,
        );
      }
      $response = $categories;
    }
    return (new ResourceResponse($response))->addCacheableDependency($build);
  }

  /**
  * Responds to entity POST requests.
  * expected data format:
  * {
  *   deptId: deptId,
  *   categories: array containing category strings
  * }
  * @return \Drupal\rest\ResourceResponse
  */

  public function post($data) {
    // error_log('itr_rest:DepartmentCategoryResource:post:data:' . print_r($data, 1));
    $response = ['message' => 'category create failed'];
    $deptId = $data['deptId'];
    $categories = $data['categories'];
    $createdIds = Utility::addTermToDeptChildTerm($deptId, 'category', $categories);
    if(count($createdIds) > 0) {
      $response = ['message' => 'save success', 'categories' => $createdIds];
    }
    return new ResourceResponse($response);
  }

  // public function post($data) {
  //   try {
  //     $categoryId = $this->getCategoryTermId($data['deptId']);
  //     $categories = $data['categories'];
  //     if(isset($categories) && isset($categoryId)) {
  //       $catCount = count($categories);
  //       for($i = 0; $i < $catCount; $i++) {
  //         $term = Term::create([
  //           'vid' => 'department',
  //           'name' => $categories[$i],
  //           'parent' => $categoryId
  //         ]);
  //         $term->save();
  //       }
  //     }
  //     $response = ['message' => 'save success'];
  //   } catch (Exception $e) {
  //     error_log('Exception in DepartmentCategoryResource POST: ');
  //     error_log($e->getMessage());
  //     $response = ['message' => 'save fail.  check logs'];
  //   }

  //   return new ResourceResponse($response);
  // }

  private function getCategoryTermId($deptId) {
    $deptTermChildren = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department', $deptId);
    foreach($deptTermChildren as $deptTermChild) {
      if(strtolower($deptTermChild->name) == 'category') {
        return $deptTermChild->tid;
      }
    }
    return null;
  }
}

?>