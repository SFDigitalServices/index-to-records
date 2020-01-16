<?php
  namespace Drupal\itr\Utility;

  use Drupal\taxonomy\Entity\Term;
  use Drupal\user\Entity\User;
  use Drupal\file\Entity\File;
  use Drupal\views\ViewExecutable;
  use Drupal\views\Views;

  class Utility {
    
    public static function getDepartmentsForUser() {
      $user = User::load(\Drupal::currentUser()->id());
      if(!in_array('anonymous', $user->getRoles())) {
        $assignedDepts = $user->get('field_department')->getValue();
        $count = count($assignedDepts);
        if($count > 0 && !empty(Term::load($assignedDepts[0]['target_id']))) {
          $vid = Term::load($assignedDepts[0]['target_id'])->getVocabularyId();
          $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
          $depts = array();
          for($i = 0; $i < $count; $i++) {
            $tid = $assignedDepts[$i]['target_id'];
            $deptTerm = Term::load($tid);
            if($deptTerm) {
              $deptName = $deptTerm->getName();
              $depts[] = array(
                'id' => $tid,
                'name' => $deptName
              );
            }
          }
          foreach($depts as $key => $row) {
            $id[$key] = $row['id'];
            $name[$key] = $row['name'];
          }
          array_multisort($name, SORT_ASC, $depts);
          return $depts;
        }
      }
      return [];
    }

    // get top level department terms from department vocab
    public static function getDepartments() {
      $vid = 'department';
      $depts = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, 1);
      foreach($depts as $key => $row) {
        $name[$key] = $row->name;
      }
      array_multisort($name, SORT_ASC, $depts);
      return $depts;
    }

    public static function getTermId(string $idStr, $vocab = 'department', $parentId = 0) {
      $vid = $vocab;
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $parentId);
      $tid = -1;
      foreach($terms as $term) {
        if(strtolower($term->name) == strtolower($idStr)) {
          $tid = $term->tid;
          break;
        }
      }
      return $tid;
    }

    // get categories for a specific department
    public static function getDepartmentCategories($deptId) {
      if(!isset($deptId)) return array();
      $vid = 'department';
      $categories = array();
      $categoryTermId = self::getTermId('category', 'department', $deptId);
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
      if(!isset($deptId)) return array();
      $vid = 'department';
      $divisions = array();
      $divisionTermId = self::getTermId('division', 'department', $deptId);
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
      $userDepts = self::getDepartmentsForUser();
      $c = count($userDepts);
      for($i=0; $i<$c; $i++) {
        if($userDepts[$i]['id'] == $deptId)
          return true;
      }
      return false;
    }

    // check if the current user has the admin role
    public static function userIsAdmin() {
      $user = User::load(\Drupal::currentUser()->id());
      return in_array('administrator', $user->getRoles()) || in_array('schedule_administrator', $user->getRoles());
    }

    public static function getCategoryTermId($deptId) {
      $deptTermChildren = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department', $deptId);
      foreach($deptTermChildren as $deptTermChild) {
        if(strtolower($deptTermChild->name) == 'category') {
          return $deptTermChild->tid;
        }
      }
      return null;
    }

    public static function addRetention($retentionTermName) {
      $retentionTermId = null;
      if(isset($retentionTermName)) {
        $retentionTerm = Term::create([
          'vid' => 'retention',
          'name' => $retentionTermName,
          'parent' => 0
        ]);
        $retentionTerm->save();
        $retentionTermId = $retentionTerm->id();
      }
      return $retentionTermId;
    }

    /*
    * method addTermToDeptChildTerm will add a child term to a specified term in department taxonomy
    * @param $deptId - the id of the department to add child terms to
    * @param $deptChildTerm - the child term of the department to add child terms to (first level child)
    * @param $termToAdd - an array of term(s) to add to the department
    * Basically, dept taxonomy looks like this: 
    * - department
    * -- a department name (with id 10, for example)
    * ---- category
    * ------ category 1
    * ------ category 2
    *
    * use this method to add a term to category like this addTermToDeptChildTerm(10, 'category', ['category 3'])
    * will add 'category 3' to dept category so that output is this:
    *
    * - department
    * -- a department name
    * ---- category
    * ------ category 1
    * ------ category 2
    * ------ category 3
    */
    public static function addTermToDeptChildTerm($deptId, $deptChildTermName, array $termsToAdd) {
      $deptChildTermId = self::getDeptChildTerm($deptId, $deptChildTermName);
      $createdIds = [];
      if(!isset($deptChildTermId)) { // this term does not exist, create it
        $deptChildTerm = Term::create([
          'vid' => 'department',
          'name' => $deptChildTermName,
          'parent' => $deptId
        ]);
        $deptChildTerm->save();
        $deptChildTermId = $deptChildTerm->id();
      }
      if(isset($deptChildTermId) && isset($termsToAdd)) {
        $termCount = count($termsToAdd);
        for($i=0; $i<$termCount; $i++) {
          $term = Term::create([
            'vid' => 'department',
            'name' => $termsToAdd[$i],
            'parent' => $deptChildTermId
          ]);
          $term->save();
          array_push($createdIds, $term->id());
        }
      }
      return $createdIds;
    }

    /*
    * method getTermIdByDepartment will return a term id for a given string (if it exists)
    * @param $deptId - the id of the department to search
    * @param $termName - the string name of the term to find
    */
    public static function getDeptChildTerm($deptId, $termName) {
      if(isset($deptId) && isset($termName)) {
        $deptTermChildren = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('department', $deptId); // get the terms for this department (all levels)
        foreach($deptTermChildren as $deptTermChild) {
          if(strtolower($deptTermChild->name) == strtolower($termName)) {
            return $deptTermChild->tid;
          }
        }
      }
      return null;
    }

    public static function getTermNameByTid($tid) {
      $term = Term::load($tid);
      return $term->getName();
    }

    public static function getRetentionName($retentionTermId) {
      $retentionTerms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('retention');
      foreach($retentionTerms as $retentionTerm) {
        if($retentionTerm->tid == $retentionTermId) {
          return $retentionTerm->name;
        }
      }
      return null;
    }

    public static function getView($viewId, $displayId, $arguments) {
      $result = false;
      $view = Views::getView($viewId);
  
      if (is_object($view)) {
        $view->setDisplay($displayId);
        $view->setArguments($arguments);
        $view->preExecute();
        $view->execute();

        // Render the view
        $result = \Drupal::service('renderer')->render($view->render());
      }
      return $result;
    }

    public static function createCSV(array $data, $deptId = null, $filepath = null) {
      $fileName = isset($deptId) ? str_replace(' ', '-', str_replace("/", '-', strtolower(self::getTermNameByTid($deptId)))) : 'no-filename';
  
      $file = File::create([
        'filename' => $fileName . '.csv',
        'uri' => $filepath ? $filepath . $fileName . '.csv' : 'public://schedule/export/csv/' . $fileName . '.csv',
        'status' => 1,
      ]);
      $file->save();
      $dir = dirname($file->getFileUri());
      chmod($dir, 0777);
      if(!file_exists($dir)) {
        mkdir($dir, 0770, TRUE);
      }
      $fp = fopen($file->getFileUri(), 'w');
  
      $csvHeaderRow = 'title,link,division,division_contact,on_site,off_site,total,category,retention,remarks'."\n";
      fwrite($fp, $csvHeaderRow);
      foreach($data as $item) {
        $title = $item['field_record_title'][0]['value'];
        $link = $item['field_link'][0]['value'];
        $division = count($item['field_division']) > 0 ? self::getTermNameByTid($item['field_division'][0]['target_id']) : '';
        $division_contact = $item['field_division_contact'][0]['value'];
        $on_site = $item['field_on_site'][0]['value'];
        $off_site = $item['field_off_site'][0]['value'];
        $total = $item['field_total'][0]['value'];
        $category = count($item['field_category']) > 0 ? self::getTermNameByTid($item['field_category'][0]['target_id']) : '';
  
        $retention = '';
        if(count($item['field_retention']) > 0) {
          foreach($item['field_retention'] as $retentionItem) {
            $retentionId = $retentionItem['target_id'];
            $retention .= self::getRetentionName($retentionId) ? self::getRetentionName($retentionId) . ', ' : '';
          }
          $retention = rtrim($retention);
          $retention = rtrim($retention, ',');
        }
  
        $remarks = $item['field_remarks'][0]['value'];
        $csvArray = array($title, $link, $division, $division_contact, $on_site, $off_site, $total, $category, $retention, $remarks);
        fputcsv($fp, $csvArray);
      }
      $file->save();
      fclose($fp);
      $returnArray = array(
        array(
          'filename' => $fileName . '.csv',
          'url' => $file->url(),
        )
      );
      return $returnArray;
    }

  }

?>