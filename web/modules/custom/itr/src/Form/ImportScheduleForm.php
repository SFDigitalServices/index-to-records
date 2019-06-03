<?php
  
  namespace Drupal\itr\Form;
  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  use GuzzleHttp\Client;
  use GuzzleHttp\Cookie\CookieJar;
  use GuzzleHttp\Exception\RequestException;

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\itr\Ajax\ImportScheduleCommand;

  use Drupal\itr\Utility\Utility;

  class ImportScheduleForm extends FormBase {

    /**
    * {@inheritdoc}
    */

    public function getFormId() {
      return 'import_schedule_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

      global $base_url;

      // get departments from taxonomy
      $depts = Utility::getDepartmentsForUser();
      $deptOptions = array();
      $deptOptions['_none'] = '- None -';
      foreach($depts as $dept) {
        $deptOptions[$dept['id']] = $dept['name'];
      }

      $form = array(
        '#attributes' => array('enctype' => 'multipart/form-data'),
      );

      $form['#prefix'] = '<div id="import-form-wrapper">';
      $form['#suffix'] = '</div>';

      // provides a container to group fields together to style with css
      $form['import-schedule-fields'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('import-schedule-fields'),
        ),
      );

      $form['import-schedule-fields']['schedule_department'] = array(
        '#type' => 'select',
        '#name' => 'schedule_department',
        '#title' => t('Department'),
        '#options' => $deptOptions,
      );

      $validators = array(
        'file_validate_extensions' => array('csv', 'txt'),
      );

      $form['import-schedule-fields']['schedule_file'] = array(
        '#type' => 'managed_file',
        '#name' => 'schedule_file',
        '#title' => t('Schedule File'),
        '#size' => 20,
        '#upload_validators' => $validators,
      );

      $form['#attached']['library'][] = 'itr/import-schedule';
      $form['#attached']['drupalSettings']['itr']['importSchedule']['data'] = array();

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Import Schedule'),
        '#button_type' => 'primary',
        '#attributes' => array(
          'class' => array('blue-btn', 'btn-md'),
        ),
        '#ajax' => array(
          'callback' => array($this, 'btn_ajax_callback'),
        ),
      );
      return $form;
    }



    public function btn_ajax_callback(array &$form, FormStateInterface $form_state) {
      $response = new AjaxResponse();

      // get the file
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($form_state->getValue('schedule_file')[0]);
      if($file) {
        $fileUri = $file->getFileUri();
        $handle = fopen($fileUri, 'r', 'utf-8');
        $header = NULL;
        $d = array();
        $count = 0;
        while(($row = fgetcsv($handle, 0, ',')) !== FALSE) {
          $elemCount = count($row);
          $bomRemoved = [];
          for($i=0; $i<$elemCount; $i++) {
            $bomRemoved[] = preg_replace('/[^A-Za-z0-9\.\r\n -_]/', '', $this->remove_utf8_bom($row[$i]));
          }
          if(!$header) {
            $header = $bomRemoved;
          } else {
            $item = array_combine($header, $bomRemoved);
            $d[] = $item;
            $count++;
          }
        }
        fclose($handle);

        // csv data is now in associate array
        // each item in the associative array contains a key that corresponds to a field in the record content type
        // but three keys are entity references to taxonomy terms representing department categories, divisions, and retention types
        // those keys need to string match existing categories and their corresponding values need to be replaced with the appropriate term id
        // for example, adult probation category value from csv is Personnel/HR Records
        // that value needs to be found in the department vocabulary and replaced with the corresponding term id
        // if the term id does not exist, it should be created programmatically

        $deptId = $form_state->getValue('schedule_department');
        $finalData = $this->modifyData($d, $deptId);
        $response->addCommand(new ImportScheduleCommand($finalData)); // execute custom ajax command which renders json array with data $d
      } else {
        $response->addCommand(new ImportScheduleCommand(['message' => 'no file uploaded']));
      }

      return $response;
    }

    // from here: https://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences
    // when saving as csv from MS Excel, a byte order mark is automatically inserted.
    // this function will remove it.  if left alone, the importer will throw up
    function remove_utf8_bom($text) {
      $bom = pack('H*', 'EFBBBF');
      $text = preg_replace("/^$bom/", '', $text);
      return $text;
    }

    // modify data specifically for record content type with a given associative array or records
    // essentially replaces category, division, and retention with the appropriate term id's
    // in order to prepare it for import via drupal's in-built content rest api
    function modifyData(array $d, $deptId) {
      $count = count($d);
      $entityRefKeys = ['category','division','retention']; // these are the keys that use entity references for record content type
      $recNum = 0;
      for($i = 0; $i < $count; $i++) {
        $rec = $d[$i];
        foreach($rec as $key => $value) {
          if(in_array($key, $entityRefKeys)) {
            $vocab = $key == 'retention' ? 'retention' : 'department';
            if(isset($value) && $value !== 'null' && strlen($value) > 0) {
              $keyTermId = Utility::getTermId(strtolower($key), $vocab, $deptId);
              $values = array();
              if($vocab == 'retention') {
                $keyTermId = 0;
                $retentionArray = preg_split("/,(\s+)?/", $value);
                $values = $retentionArray;
              } else {
                $values = [$value];
              }
              $valuesCount = count($values);
              for($j=0; $j<$valuesCount; $j++) {
                $someValue = $values[$j];
                $valueTermId = Utility::getTermId(strtolower($someValue), $vocab, $keyTermId);
                if($valueTermId > 0) {
                  if(is_array($d[$i][$key])) {
                    array_push($d[$i][$key], $valueTermId);
                  } else {
                    $d[$i][$key] = [$valueTermId];
                  }
                } else {
                  $newTermId = null;
                  if($key == 'category') {
                    $newTermId = Utility::addTermToDeptChildTerm($deptId, 'category', [$someValue]);
                  }
                  if($key == 'division') {
                    $newTermId = Utility::addTermToDeptChildTerm($deptId, 'division', [$someValue]);
                  }
                  if($key == 'retention') {
                    $newTermId = Utility::addRetention($someValue);
                  }
                  if(isset($newTermId)) {
                    is_array($d[$i][$key]) ? array_push($d[$i][$key], $newTermId) : $d[$i][$key] = $newTermId;
                  }
                }
              }
            } else {
              $d[$i][$key] = '';
            }
          }
        }
        $recNum++;
      }
      return ['department' => $deptId, 'schedule' => $d];
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

    }
  }
  