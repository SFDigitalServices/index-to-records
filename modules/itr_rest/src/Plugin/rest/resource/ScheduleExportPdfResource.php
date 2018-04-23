<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\file\Entity\File;
use Drupal\itr\Utility\Utility;

/**
* Provides the export schedule csv resource
*
* @RestResource(
*   id = "schedule_export_pdf",
*   label = @Translation("Schedule Export PDF"),
*   uri_paths = {
*     "https://www.drupal.org/link-relations/create" = "/itr_rest/schedule/export/pdf"
*   }
* )
*/

class ScheduleExportPdfResource extends ResourceBase {

  public function post($data) {
    $response = ['exists' => false, 'delete_ids' => [], 'data': []];
    // error_log('ScheduleExportCSVResource: ' . print_r($data, 1));
    // if(isset($data) && count($data) > 0) {
    //   $controller = \Drupal::entityTypeManager()->getStorage('node');
    //   $entities = $controller->loadMultiple($data);
    //   $controller->delete($entities);
    //   $response = ['exists' => true, 'delete_ids' => $data];
    // }
    // $response = $this->createCSV($data['data'], $data['dept']);
    return new ResourceResponse($response);
  }
}

?>