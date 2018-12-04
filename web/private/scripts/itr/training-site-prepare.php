<?php

#!/usr/bin/env drush

use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

$sitename = \Drupal::config('system.site')->get('name');

echo "This is the site $sitename\n";
echo "PANTHEON_ENV:".$_ENV['PANTHEON_ENVIRONMENT']."\n";

if($_ENV['PANTHEON_ENVIRONMENT'] == 'training' || $_ENV['PANTHEON_ENVIRONMENT'] == 'lando') {
  // delete record content types
  echo "Deleting record content types...\n";
  $record_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(array('type'=>'record'));
  foreach($record_nodes as $record_node) {
    $record_node->delete();
  }

  // delete department information content types
  echo "Deleting dept info content types...\n";
  $dept_info_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(array('type'=>'department_information'));
  foreach($dept_info_nodes as $dept_info_node) {
    $dept_info_node->delete();
  }

  // delete department taxonomy terms
  echo "Deleting department taxonomy terms...\n";
  $dept_tx_term_query = \Drupal::entityQuery('taxonomy_term')->condition('vid', 'department');
  $dept_tx_term_tids = $dept_tx_term_query->execute();
  $controller = \Drupal::entityManager()->getStorage('taxonomy_term');
  $dept_tx_entities = $controller->loadMultiple($dept_tx_term_tids);
  $controller->delete($dept_tx_entities);

  echo "Deleting non-admin users...\n";
  $users_query = \Drupal::entityQuery('user')->condition('status', 1);
  $user_ids = $users_query->execute();
  $users = User::loadMultiple($user_ids);
  foreach($users as $user) {
    $user_roles = $user->getRoles();
    if(!in_array('administrator', $user_roles) && !in_array('schedule_administrator', $user_roles)) {
      $user->delete();
    }
  }

  // create test depts and users

  // create test depts
  echo "Creating test department taxonomy terms on vocab department...\n";
  for($i = 1; $i <= 10; $i++) {
    $deptIndex = $i < 10 ? "0".$i : $i;
    $term = Term::create([
      'name' => 'dept' . $deptIndex,
      'vid' => 'department',
    ])->save();
  }

  // create users
  echo "Creating test users...\n";
  for($i = 1; $i <= 10; $i++) {
    $userIndex = $i < 10 ? "0".$i : $i;
    $user = User::create(['name' => 'user'.$userIndex, 'status' => TRUE]);
    $user->setEmail('test'.$userIndex.'@test.com');
    $user->setPassword('password');
    $deptTermAssocArray = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name'=>'dept'.$userIndex]);
    foreach($deptTermAssocArray as $theDeptTermObj) {
      $deptTerm = $theDeptTermObj;
      break;
    }
    echo "Assigning " . $deptTerm->getName() . " to user".$userIndex."\n";
    $user->set('field_department',$deptTerm->id());
    $user->save();
  }
} else {
  echo "Can only prepare against environments \"training\" or \"lando\"\n";
}