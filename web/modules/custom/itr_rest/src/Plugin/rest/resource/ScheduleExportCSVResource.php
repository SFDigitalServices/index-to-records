<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\itr\Utility\Utility;

/**
* Provides the export schedule csv resource
*
* @RestResource(
*   id = "schedule_export_csv",
*   label = @Translation("Schedule Export CSV"),
*   uri_paths = {
*     "canonical" = "/itr_rest/schedule/export/csv",
*     "create" = "/itr_rest/schedule/export/csv"
*   }
* )
*/

class ScheduleExportCsvResource extends ResourceBase {

  public function post($data) {
    $response = Utility::createCSV($data['data'], $data['dept']);
    return new ResourceResponse($response);
  }
}