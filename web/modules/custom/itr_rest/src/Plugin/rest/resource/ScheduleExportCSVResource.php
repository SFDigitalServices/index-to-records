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
*   id = "schedule_export_csv",
*   label = @Translation("Schedule Export CSV"),
*   uri_paths = {
*     "https://www.drupal.org/link-relations/create" = "/itr_rest/schedule/export/csv"
*   }
* )
*/

class ScheduleExportCsvResource extends ResourceBase {

  public function post($data) {
    $response = ['exists' => false, 'delete_ids' => []];
    // error_log('ScheduleExportCSVResource: ' . print_r($data, 1));
    // if(isset($data) && count($data) > 0) {
    //   $controller = \Drupal::entityTypeManager()->getStorage('node');
    //   $entities = $controller->loadMultiple($data);
    //   $controller->delete($entities);
    //   $response = ['exists' => true, 'delete_ids' => $data];
    // }
    $response = $this->createCSV($data['data'], $data['dept']);
    return new ResourceResponse($response);
  }

  function createCSV(array $data, $deptId = null) {
    // error_log(count($data));
    $fileName = isset($deptId) ? str_replace(' ', '-', strtolower(Utility::getTermNameByTid($deptId))) : 'no-filename';
    // error_log(print_r($data[0], 1));

    $file = File::create([
      'filename' => $fileName . '.csv',
      'uri' => 'public://schedule/export/csv/' . $fileName . '.csv',
      'status' => 1,
    ]);
    $file->save();
    $dir = dirname($file->getFileUri());
    if(!file_exists($dir)) {
      mkdir($dir, 0770, TRUE);
    }
    $fp = fopen($file->getFileUri(), 'w');

    $csvHeaderRow = 'title,link,division,division_contact,on_site,off_site,total,category,retention,remarks'."\n";
    fwrite($fp, $csvHeaderRow);
    // $csvRow = 'title,link,division,division_contact,on_site,off_site,total,category,retention,remarks';
    foreach($data as $item) {
      // error_log(print_r($item,1));
      $title = $item['field_record_title'][0]['value'];
      $link = $item['field_link'][0]['value'];
      $division = count($item['field_division']) > 0 ? Utility::getTermNameByTid($item['field_division'][0]['target_id']) : '';
      $division_contact = $item['field_division_contact'][0]['value'];
      $on_site = $item['field_on_site'][0]['value'];
      $off_site = $item['field_off_site'][0]['value'];
      $total = $item['field_total'][0]['value'];
      $category = count($item['field_category']) > 0 ? Utility::getTermNameByTid($item['field_category'][0]['target_id']) : '';

      $retention = '';
      if(count($item['field_retention']) > 0) {
        foreach($item['field_retention'] as $retentionItem) {
          $retentionId = $retentionItem['target_id'];
          $retention .= Utility::getRetentionName($retentionId) ? Utility::getRetentionName($retentionId) . "\n" : '';
        }
      }

      $remarks = $item['field_remarks'][0]['value'];
      // $csvRow .= "\n" . '"' . $title . '","' . $link . '","' . $division . '","' . $division_contact . '","' . $on_site . '","' . $off_site . '","' . $total . '","' . $category . '","' . addslashes($retention) . '","' . addslashes($remarks) . '"';
      $csvArray = array($title, $link, $division, $division_contact, $on_site, $off_site, $total, $category, $retention, $remarks);
      // error_log(print_r($csvArray, 1));
      fputcsv($fp, $csvArray);
    }

    // file_put_contents($file->getFileUri(), $csvRow);
    $file->save();
    // error_log(drupal_realpath($file->getFileUri()));
    // error_log($file->url());
    // error_log($csvRow);
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