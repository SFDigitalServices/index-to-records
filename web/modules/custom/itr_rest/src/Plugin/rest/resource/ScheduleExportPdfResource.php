<?php

namespace Drupal\itr_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\file\Entity\File;
use Drupal\itr\Utility\Utility;

use Dompdf\Dompdf;

require_once 'vendor/dompdf/autoload.inc.php';

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
    $response = ['exists' => false, 'delete_ids' => []];
    // error_log('ScheduleExportCSVResource: ' . print_r($data, 1));
    // if(isset($data) && count($data) > 0) {
    //   $controller = \Drupal::entityTypeManager()->getStorage('node');
    //   $entities = $controller->loadMultiple($data);
    //   $controller->delete($entities);
    //   $response = ['exists' => true, 'delete_ids' => $data];
    // }
    $response = $this->createPdf($data['data'], $data['dept']);
    return new ResourceResponse($response);
  }

  function deptInfo($deptId) {
    global $base_url;
    $request = \Drupal::httpClient()->get($base_url . '/itr_rest_view/dept/info/' . $deptId . '?_format=json'); // this path is a rest route defined in view Department Info
    $deptInfoData = json_decode($request->getBody(), true);
    $deptData = null;
    if(count($deptInfoData) > 0) {
      $deptData = [Array(
        'field_department_name' => count($deptInfoData[0]['field_department_name']) > 0 ? $deptInfoData[0]['field_department_name'][0]['target_id'] : 'no dept name',
        'field_department_contact_name' => count($deptInfoData[0]['field_department_contact_name']) > 0 ? $deptInfoData[0]['field_department_contact_name'][0]['value'] : 'no dept contact name',
        'field_department_contact_email' => count($deptInfoData[0]['field_department_contact_email']) > 0 ? $deptInfoData[0]['field_department_contact_email'][0]['value'] : 'no dept contact email',
        'field_department_contact_phone_n' => count($deptInfoData[0]['field_department_contact_phone_n']) > 0 ? $deptInfoData[0]['field_department_contact_phone_n'][0]['value'] : 'no dept contact phone',
        'field_department_website' => count($deptInfoData[0]['field_department_website']) > 0 ? $deptInfoData[0]['field_department_website'][0]['value'] : 'no dept website',
        'field_schedule_ratified_date' => count($deptInfoData[0]['field_schedule_ratified_date']) > 0 ? $deptInfoData[0]['field_schedule_ratified_date'][0]['value'] : 'no ratified date',
      )];
    }
    // error_log(print_r($deptInfoData, 1));
    // error_log($deptName);
    // error_log($deptContactName);
    return $deptData;
  }

  function createPdf(array $data, $deptId) {
    if(!isset($deptId)) {
      return null;
    }
    $deptName = Utility::getTermNameByTid($deptId);
    $fileName = str_replace(' ', '-', strtolower($deptName)) . '.pdf';

    $deptInfo = $this->deptInfo($deptId);
    error_log(print_r($deptInfo, 1));
    if(count($deptInfo) > 0) {
      $deptContact = $deptInfo[0]['field_department_contact_name'];
      $deptContactPhone = $deptInfo[0]['field_department_contact_phone_n'];
      $deptWebsite = $deptInfo[0]['field_department_website'];
      $deptContactEmail = $deptInfo[0]['field_department_contact_email'];
    }

    $headerHtml = '<h1>Records Retention and Destruction Schedule</h1>' . "\n" .
                  '<div id="header-table-container">' . "\n" . 
                  ' <table id="header-dept-info">' . "\n" .
                  '   <tr>' . "\n" . 
                  '     <td><strong>Department Name: </strong>' . $deptName . '</td>' . "\n" .
                  '     <td>&nbsp;</td>' . "\n" .
                  '     <td><strong>Department Website: </strong>' . $deptWebsite . '</td>' . "\n" .
                  '   </tr>' . "\n" .
                  '  <tr>' . "\n" . 
                  '     <td><strong>Department Contact: </strong>' . $deptContact . '</td>' . "\n" .
                  '    <td><strong>Contact Phone Number: </strong>' . $deptContactPhone . '</td>' . "\n" .
                  '     <td><strong>Contact Email: </strong>' . $deptContactEmail . '</td>' . "\n" .
                  '   </tr>' . "\n" .
                  ' </table>' . "\n" .
                  '</div>';
    
    $scheduleHtml = '<table width="100%" id="schedule-table">' . "\n" .
                    '  <tr>' . "\n" .
                    '    <th>Division</th>' . "\n" .
                    '    <th>Division Contact</th>' . "\n" .
                    '    <th>Record Category</th>' . "\n" .
                    '    <th>Record Title/Description</th>' . "\n" .
                    '    <th>Document Link</th>' . "\n" .
                    '    <th>Retention Category</th>' . "\n" .
                    '    <th>Total</th>' . "\n" .
                    '    <th>On-site</th>' . "\n" .
                    '    <th>Off-site</th>' . "\n" .
                    '    <th>Remarks</th>' . "\n" .
                    '  </tr>' . "\n";

    $i = 0;

    foreach($data as $item) {
      $cssRowClass = $i % 2 == 0 ? 'even' : 'odd';
      // error_log(print_r($item,1));
      $title = $item['field_record_title'][0]['value'];
      $link = $item['field_link'][0]['value'];
      $division = count($item['field_division']) > 0 ? Utility::getTermNameByTid($item['field_division'][0]['target_id']) : '';
      $division_contact = $item['field_division_contact'][0]['value'];
      $on_site = $item['field_on_site'][0]['value'];
      $off_site = $item['field_off_site'][0]['value'];
      $total = $item['field_total'][0]['value'];
      $category = count($item['field_category']) > 0 ? Utility::getTermNameByTid($item['field_category'][0]['target_id']) : '';
      $retention = count($item['field_retention']) ? 'get retention string' : '';
      $remarks = $item['field_remarks'][0]['value'];
      $scheduleHtml .=  '<tr class="' . $cssRowClass . '">' . "\n" .
                        '  <td>' . $division . '</td>' . "\n" .
                        '  <td>' . $division_contact . '</td>' . "\n" .
                        '  <td>' . $category . '</td>' . "\n" .
                        '  <td>' . $title . '</td>' . "\n" .
                        '  <td>' . $link . '</td>' . "\n" .
                        '  <td>' . $retention . '</td>' . "\n" .
                        '  <td>' . $total . '</td>' . "\n" .
                        '  <td>' . $on_site . '</td>' . "\n" .
                        '  <td>' . $off_site . '</td>' . "\n" .
                        '  <td>' . $remarks . '</td>' . "\n" .
                        '</tr>' . "\n";
      $i++;
    }

    $scheduleHtml .= '</table>' . "\n";

    $sigHtml .= '<div id="signature-page">' . "\n" .
                '  <h2>Records and Retention Destruction Policy and Schedule Signature Page</h2>' . "\n" .
                '  <p>Submit your Policy with the Schedule and signature page attached at the end.  Secure the signatures below, as appropriate, and deliver to:<br/>Office of the City Administrator, City Hall Room 362, Attention: Index to Records.</p>' . "\n" .
                '  <div id="dept-name">' . "\n" .
                '    <div class="label">Name of Department:</div><div class="value">' . $deptName . '</div>' . "\n" .
                '  </div>' . "\n" .
                '  <div id="dept-board-commission">' . "\n" .
                '    <div class="dept-board-commission-section">' . "\n" .
                '      <div class="dept-board-commission-text">For departments that do not have a board or commission:</div>' . "\n" .
                '      <div class="signature-name-title text-below">Steve Kawa, Mayor\'s Chief of Staff</div>' . "\n" .
                '      <div class="signature-date-approved text-below">Date Approved</div>' . "\n" .
                '    </div>' .
                '    <div class="dept-board-commission-section">' . "\n" .
                '      <div class="dept-board-commission-text">For departments that have a board or commission:</div>' . "\n" .
                '      <div class="signature-name-title text-below">Commission Secretary name and signature</div>' . "\n" .
                '      <div class="signature-date-approved text-below">Date Approved</div>' . "\n" .
                '    </div>' . "\n" .
                '  </div>' . "\n" .
                '  <h3>All Departments</h3>' . "\n" .
                '  <div id="all-departments">' . "\n" .
                '    <div class="section dept-head-sig">' . "\n" .
                '      <div class="label">Department Head Name and Signature:</div><div class="line"></div>' . "\n" .
                '    </div>' . "\n" .
                '    <div class="section">' . "\n" .
                '      <div class="label">Date Approved:</div><div class="line"></div>' . "\n" .
                '    </div>' . "\n" .
                '  </div>' . "\n" .
                '  <div id="other-approvals">' . "\n" .
                '    <div class="other-approval-section">' . "\n" .
                '      <h3>Approval as to Records relating to financial matters:</h3>' . "\n" .
                '      <div class="approver-section">' . "\n" .
                '        <div class="approver text-below">Ben Rosenfield, Controller</div>' . "\n" .
                '        <div class="approve-date text-below">Date Approved</div>' . "\n" .
                '      </div>' . "\n" .
                '      <div class="approver-section">' . "\n" .
                '        <div class="approver text-below">Controller Staff (print and sign)</div>' . "\n" .
                '        <div class="approve-date text-below">Date Approved</div>' . "\n" .
                '      </div>' . "\n" .
                '    </div>' . "\n" .
                '    <div class="other-approval-section">' . "\n" .
                '      <h3>Approval as to Records of legal significance:</h3>' . "\n" .
                '      <div class="approver-section">' . "\n" .
                '        <div class="approver text-below">Dennis J. Herrera, City Attorney</div>' . "\n" .
                '        <div class="approve-date text-below">Date Approved</div>' . "\n" .
                '      </div>' . "\n" .
                '      <div class="approver-section">' . "\n" .
                '        <div class="approver text-below">City Deputy Attorney (print and sign)</div>' . "\n" .
                '        <div class="approve-date text-below">Date Approved</div>' . "\n" .
                '      </div>' . "\n" .
                '    </div>' . "\n" .
                '    <div class="other-approval-section">' . "\n" .
                '      <h3>Approval as to Records relating to payroll matters:</h3>' . "\n" .
                '      <div class="approver-section">' . "\n" .
                '        <div class="approver text-below">Jay Huish, Executive Director - Retirement Board</div>' . "\n" .
                '        <div class="approve-date text-below">Date Approved</div>' . "\n" .
                '      </div>' . "\n" .
                '    </div>' . "\n" .
                '  </div>' . "\n" .
                '</div>' . "\n";

    $style =  "\n" . 
              '<style type="text/css">' . "\n" .
              '  @page { margin: 8px; }' . "\n" .
              '  body { margin: 8px; font-size: 12px; font-family: sans-serif; }' . "\n" .
              '  h1 { font-size: 1.25em; text-align: center; text-decoration: underline; margin: 15px 0; font-weight: normal; }' . "\n" .
              '  h2 { font-size: 1.2em; text-align: center; }' . "\n" .
              '  table#header-dept-info { border: 0; width: 100%; border-collapse: collapse; }' . "\n" .
              '  table#header-dept-info td { width: 33%; border: 0; padding: 2px 0; }' . "\n" .
              '  #header-table-container { margin-bottom: 15px }' . "\n" .
              '  table#schedule-table { border: 1px solid #000; border-collapse: collapse; }' . "\n" .
              '  table#schedule-table td, table#schedule-table th { border: 1px solid #000; padding: 10px; }' . "\n" .
              '  .odd { background-color: #eee; }' . "\n" .
              '  .text-below { border-top: 1px solid #000; font-size: .8em; }' . "\n" .
              '  #signature-page { page-break-before: always; }' . "\n" .
              '  #signature-page p { text-align: center; width: 75%; margin: 15px auto; }' . "\n" .
              '  #signature-page #dept-name { padding: 20px 0 28px 0; border: 0; }' . "\n" .
              '  #signature-page #dept-name > div { display: inline-block; }' . "\n" .
              '  #signature-page #dept-name .value { border-bottom: 1px solid #000; width: 25%; margin-left: 5px; padding-left: 20px; }' . "\n" .
              '  #signature-page #dept-board-commission .dept-board-commission-section { display: inline-block; width: 49%; }' . "\n" .
              '  #signature-page #dept-board-commission .dept-board-commission-section .dept-board-commission-text { display: block; margin: 20px 0 40px 0; }' . "\n" .
              '  #signature-page #dept-board-commission .dept-board-commission-section .signature-name-title { display: inline-block; width: 60%; margin-right: 10px; }' . "\n" .
              '  #signature-page #dept-board-commission .dept-board-commission-section .signature-date-approved { display: inline-block; width: 35%; }' . "\n" .
              '  #signature-page h3 { display: block; text-transform: uppercase; text-align: center; }' . "\n" .
              '  #signature-page #all-departments { width: 80%; margin: 0 auto; padding: 25px 0 0 0; }' . "\n" .
              '  #signature-page #all-departments .section { display: inline-block; width: 38%; }' . "\n" .
              '  #signature-page #all-departments .section.dept-head-sig { width: 60%; }' . "\n" .
              '  #signature-page #all-departments .section > div { display: inline-block; }' . "\n" .
              '  #signature-page #all-departments .section .line { border-bottom: 1px solid #000; width: 70%; }' . "\n" .
              '  #signature-page #all-departments .section.dept-head-sig .line { width: 55%; }' . "\n" .
              '  #signature-page #other-approvals { display: block; width: 70%; margin: 0 auto; }' . "\n" .
              '  #signature-page #other-approvals h3 { display: block; text-align: center; text-transform: capitalize; text-align: left; font-weight: normal; margin-top: 0; }' . "\n" .
              '  #signature-page #other-approvals .other-approval-section { margin-top: 30px; }' . "\n" .
              '  #signature-page #other-approvals .other-approval-section .approver-section { margin-top: 40px; }' . "\n" .
              '  #signature-page #other-approvals .other-approval-section .text-below { display: inline-block; width: 48%; margin-right: 5px; }' . "\n" .
              '</style>' . "\n";

    $html = $headerHtml . $scheduleHtml . $sigHtml . $style;
    // error_log($html);
    $dompdf = new Dompdf();
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->render();
    $output = $dompdf->output();

    $file = File::create([
      'filename' => $fileName,
      'uri' => 'public://schedule/export/pdf/' . $fileName,
      'status' => 1,
    ]);
    $file->save();
    $dir = dirname($file->getFileUri());
    if(!file_exists($dir)) {
      mkdir($dir, 0770, TRUE);
    }
    file_put_contents($file->getFileUri(), $output);
    $file->save();
    $returnArray = array(
      array(
        'filename' => $fileName,
        'url' => $file->url() . '?t=' . time(),
      )
    );
    return $returnArray;
  }
}

?>