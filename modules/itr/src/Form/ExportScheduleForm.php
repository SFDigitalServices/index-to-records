<?php

  namespace Drupal\itr\Form;
  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\itr\Ajax\ExportScheduleCSVCommand;

  use Drupal\file\Entity\File;

  use Drupal\itr\Utility\Utility;

  class ExportScheduleForm extends FormBase {

    /**
    * {@inheritdoc}
    */

    public function getFormId() {
      return 'export_schedule_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
      // get departments from taxonomy
      $depts = Utility::getDepartmentsForUser();
      $deptOptions = array();
      $deptOptions['_none'] = '- None -';
      foreach($depts as $dept) {
        $deptOptions[$dept['id']] = $dept['name'];
      }

      $form['#attached']['library'][] = 'itr/export-schedule-form';

      $form['#prefix'] = '<div id="export-form-wrapper">';
      $form['#suffix'] = '</div>';

      $str = '<p>Export to CSV or PDF (with signature page)</p>';
      
      $form['schedule_export_details'] = array(
        '#markup' => t($str),
      );

      // provides a container to group fields together to style with css
      $form['export-schedule-fields'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('export-schedule-fields'),
        ),
      );

      $form['export-schedule-fields']['schedule_department'] = array(
        '#type' => 'select',
        '#name' => 'schedule_department',
        '#title' => t('Department'),
        '#options' => $deptOptions,
      );

      $form['export-schedule-fields']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit1'),
      );

      $form['export-schedule-fields']['submit-export-csv'] = array(
        '#type' => 'submit',
        '#value' => t('Export CSV'),
        // '#submit' => array('::exportSubmitCSV'),
        '#attributes' => array(
          'class' => array('btn-export-submit-csv', 'btn-link'),
        ),
        '#ajax' => array(
          'callback' => array($this, 'btn_ajax_csv_export_callback'),
        ),
      );

      $form['export-schedule-fields']['submit-export-pdf'] = array(
        '#type' => 'submit',
        '#value' => t('Export PDF Signature Page'),
        '#submit' => array('::exportSubmitPDF'),
      );

      return $form;

    }

    public function btn_ajax_csv_export_callback(array &$form, FormStateInterface $form_state) {
      global $base_url;
      $response = new AjaxResponse();
      $data = [];
      $deptId = $form_state->getValue('schedule_department');
      error_log('ExportScheduleForm:btn_ajax_csv_export_callback:$deptId:' . $deptId);
      if(!isset($deptId) || $deptId == '_none') { // error
        $data = [
          'errors' => array(
            'field' => array(
              'name' => 'schedule_department',
              'message' => 'Please select a department',
            ),
          ),
        ];
      } else { // get the schedule for the department via itr_rest_view
        // http://localhost:8888/index-to-records/itr_rest_view/schedules/10?_format=json
        $request = \Drupal::httpClient()->get($base_url . '/itr_rest_view/schedules/' . $deptId . '/?_format=json'); // this path is a rest route defined in view Department Information
        $deptScheduleRecords = json_decode($request->getBody(), true);
        $data = $deptScheduleRecords;
        $this->createCSV($deptScheduleRecords, $deptId);
      }
      $response->addCommand(new ExportScheduleCSVCommand($data));
      return $response;
    }

    function createCSV(array $data, $deptId = null) {
      error_log(count($data));
      $fileName = isset($deptId) ? str_replace(' ', '-', strtolower(Utility::getTermNameByTid($deptId))) : 'no-filename';
      // error_log(print_r($data[0], 1));
      // title link  division  division_contact  on_site off_site  total category  retention remarks
      $csvRow = 'title,link,division,division_contact,on_site,off_site,total,category,retention,remarks';
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
        $retention = count($item['field_retention']) ? 'get retention string' : '';
        $remarks = $item['field_remarks'][0]['value'];
        $csvRow .= "\n" . $title . ',' . $link . ',' . $division . ',' . $division_contact . ',' . $on_site . ',' . $off_site . ',' . $total . ',' . $category . ',' . $retention . ',' . $remarks;
      }
      $file = File::create([
        'filename' => 'test.txt',
        'uri' => 'public://schedule/export/csv/' . $fileName . '.csv',
        'status' => 1,
      ]);
      $file->save();
      $dir = dirname($file->getFileUri());
      if(!file_exists($dir)) {
        mkdir($dir, 0770, TRUE);
      }
      file_put_contents($file->getFileUri(), $csvRow);
      $file->save();
      error_log(drupal_realpath($file->getFileUri()));
      error_log($file->url());
      error_log($csvRow);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
      // $deptId = $form_state->getValue('schedule_department');
      // error_log('ExportScheduleForm:validateForm:$deptId:' . $deptId);
      // if(!isset($deptId)) {
      //   error_log('dept id empty');
      //   $form->setValue('schedule_department', $this->t('Please select a department'));
      // }
      // parent::validateForm($form, $form_state);
      // // error_log('ExportScheduleForm:validateForm:$form_state->$deptId:empty');
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      error_log('export standard submit');
    }

    public function exportSubmitPDF(array &$form, FormStateInterface $form_state) {
      error_log('export submit pdf signature');
    }


  }
?>