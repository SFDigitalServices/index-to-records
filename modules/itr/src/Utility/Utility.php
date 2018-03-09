<?php
  namespace Drupal\itr\Utility;

  use Drupal\taxonomy\Entity\Term;
  use Drupal\user\Entity\User;

  class Utility {
    public static function test($str) {
      return $str;
    }

    public static function getDepartmentsForUser() {
      $user = User::load(\Drupal::currentUser()->id());
      $assignedDepts = $user->get('field_department')->getValue();
      $count = count($assignedDepts);
      $vid = Term::load($assignedDepts[0]['target_id'])->getVocabularyId();
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
      $depts = array();
      for($i = 0; $i < $count; $i++) {
        $tid = $assignedDepts[$i]['target_id'];
        $deptName = Term::load($tid)->getName();
        $depts[] = array(
          'id' => $tid,
          'name' => $deptName
        );
      }
      foreach($depts as $key => $row) {
        $id[$key] = $row['id'];
        $name[$key] = $row['name'];
      }
      array_multisort($name, SORT_ASC, $depts);
      return $depts;
    }

    // get top level department terms from department vocab
    public static function getDepartments() {
      $vid = 'department';
      $depts = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1);
      foreach($depts as $key => $row) {
        $id[$key] = $row->id;
        $name[$key] = $row->name;
      }
      array_multisort($name, SORT_ASC, $depts);
      return $depts;
    }

    public static function getTermId(string $idStr, $vocab = 'department', $parentId = 0) {
      error_log('get term id for [' . $idStr . '] with parentId: [' . $parentId . '] in vocab [' . $vocab . ']');
      $vid = $vocab;
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $parentId);
      $tid = -1;
      // error_log($parentId);
      foreach($terms as $term) {
        // error_log(strtolower($term->name) . '==?' . $idStr);
        if(strtolower($term->name) == strtolower($idStr)) {
          $tid = $term->tid;
          break;
        }
      }
      return $tid;
    }

    // get categories for a specific department
    public static function getDepartmentCategories($deptId) {
      error_log('Utility: getDepartmentCategories: whut getDepartmentCategories: ' . $categoryTermId);
      if(!isset($deptId)) return array();
      $vid = 'department';
      $categories = array();
      $categoryTermId = getTermId('category', $deptId);
      if($categoryTermId >= 0) {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $categoryTermId);
        foreach($terms as $term) {
          $categories[] = array(
            'id' => $term->tid,
            'name' => $term->name
          );
        }
      }
      return $categories;
    }

    public static function getDepartmentDivisions($deptId) {
      error_log('Utility: getDepartmentDivisions: whut getDepartmentDivisions: ' . $divisionTermId);
      if(!isset($deptId)) return array();
      $vid = 'department';
      $divisions = array();
      $divisionTermId = getTermId('division', $deptId);
      if($divisionTermId >= 0) {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $divisionTermId);
        foreach($terms as $term) {
          $divisions[] = array(
            'id' => $term->tid,
            'name' => $term->name
          );
        }
      }
      return $divisions;
    }

    // check if the current user has a specific dept id
    public static function userHasDept($deptId) {
      $user = User::load(\Drupal::currentUser()->id());
      $userDepts = getDepartmentsForUser();
      $c = count($userDepts);
      // error_log(print_r($userDepts, 1));
      for($i=0; $i<$c; $i++) {
        if($userDepts[$i]['id'] == $deptId)
          return true;
      }
      return false;
    }

    // check if the current user has the admin role
    public static function userIsAdmin() {
      $user = User::load(\Drupal::currentUser()->id());
      return in_array('administrator', $user->getRoles());
    }


  }

?>