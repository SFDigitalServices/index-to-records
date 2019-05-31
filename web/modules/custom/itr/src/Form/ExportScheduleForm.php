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

      $str = '<p>You may export a schedule to CSV (Excel) format for editing or to a PDF signature page for approval.</p>';
      $str .= "<p>Please select a department to begin.</p>";
      
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
        '#prefix' => '<div id="errors"></div>',
      );
      $exportCsvHtml =  '<p>The export csv option will output a department schedule as a downloadable csv file.  A csv file may be opened for editing with a spreadsheet application, such as Microsoft Excel.  Click the "Export CSV" button to begin csv export.</p>';
      $exportCsvHtml .= '<a id="export-csv" class="blue-btn btn-md" href="javascript:void(0)">Export CSV</a>';
      $exportCsvHtml .= '<div id="csv-export-status" class="export-status"><div class="loader"></div><div class="message"></div></div>';

      $form['export-schedule-fields']['export-csv-link'] = array(
        '#attributes' => array(
          'class' => array('itr-export-option'),
        ),
        '#type' => 'item',
        '#markup' => t($exportCsvHtml),
      );

      $exportPdfHtml = '<p>The export pdf with signature page option will output a department schedule as a downloadable pdf with a signature page for department head approval.  Click the "Export PDF with signature page" button to begin pdf export.</p>';
      $exportPdfHtml .= '<a id="export-pdf" class="blue-btn btn-md" href="javascript:void(0)">Export PDF with signature page</a>';
      $exportPdfHtml .= '<div id="pdf-export-status" class="export-status"><div class="loader"></div><div class="message"></div></div>';

      $form['export-schedule-fields']['export-pdf-link'] = array(
        '#attributes' => array(
          'class' => array('itr-export-option'),
        ),
        '#type' => 'item',
        '#markup' => t($exportPdfHtml),
      );
      return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      
    }

    public function exportSubmitPDF(array &$form, FormStateInterface $form_state) {
      
    }


  }
?>