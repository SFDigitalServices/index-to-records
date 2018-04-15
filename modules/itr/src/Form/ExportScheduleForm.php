<?php

  namespace Drupal\itr\Form;
  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

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
        '#submit' => array('::exportSubmitCSV'),
      );

      $form['export-schedule-fields']['submit-export-pdf'] = array(
        '#type' => 'submit',
        '#value' => t('Export PDF Signature Page'),
        '#submit' => array('::exportSubmitPDF'),
      );

      return $form;

    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      error_log('export standard submit');
    }

    public function exportSubmitCSV(array &$form, FormStateInterface $form_state) {
      error_log('export submit csv');
    }

    public function exportSubmitPDF(array &$form, FormStateInterface $form_state) {
      error_log('export submit pdf signature');
    }


  }
?>