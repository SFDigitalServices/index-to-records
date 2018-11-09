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

  // require_once('modules/devel/kint/kint/Kint.class.php');
  // \Kint::$maxLevels = 4;  

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

      $str = '<p>The following requirements should be met before importing a schedule:</p>';
      $str .= '<ul>';
      $str .= '  <li>The file to import must follow <a class="link" href="/sites/default/files/itr-schedule-import-template.csv" download>this template</a></li>';
      $str .= '  <li>The file to import must be in the CSV (comma separated values) file format</li>';
      $str .= '</ul>';
      $str .= '<p><strong>Please note that importing a schedule will overwrite the existing schedule</strong>.  If you are making changes to an existing schedule, ';
      $str .= 'please either <a class="link" href="'.$base_url.'/node/add/record">add to the existing schedule</a> or <a class="link" href="'.$base_url.'/schedule/export">export the existing schedule</a>, ';
      $str .= 'make changes locally on your computer, then re-import the updated schedule.</p>';
      
      $form['file_upload_details'] = array(
        '#markup' => t($str),
      );

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
        'file_validate_extensions' => array('csv'),
      );

      $form['import-schedule-fields']['schedule_file'] = array(
        '#type' => 'managed_file',
        '#name' => 'schedule_file',
        '#title' => t('Schedule File'),
        '#size' => 20,
        // '#description' => t('CSV format only'),
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
        error_log('ImportScheduleForm.php:btn_ajax_callback:'.$fileUri);
        $handle = fopen($fileUri, 'r');
        $header = NULL;
        $d = array();
        $count = 0;
        while(($row = fgetcsv($handle, 0, ',')) !== FALSE) {
          if(!$header) {
            $header = $row;
          } else {
            $d[] = array_combine($header, $row);
            $count++;
          }
        }
        fclose($handle);
        // error_log(print_r($d, 1));



        // csv data is now in associate array
        // each item in the associative array contains a key that corresponds to a field in the record content type
        // but three keys are entity references to taxonomy terms representing department categories, divisions, and retention types
        // those keys need to string match existing categories and their corresponding values need to be replaced with the appropriate term id
        // for example, adult probation category value from csv is Personnel/HR Records
        // that value needs to be found in the department vocabulary and replaced with the corresponding term id
        // if the term id does not exist, it should be created programmatically

        $deptId = $form_state->getValue('schedule_department');
        $finalData = $this->modifyData($d, $deptId);

        // $form['#attached']['drupalSettings']['itr']['importSchedule']['data'] = json_encode($d);
        $response->addCommand(new ImportScheduleCommand($finalData)); // execute custom ajax command which renders json array with data $d
      } else {
        $response->addCommand(new ImportScheduleCommand(['message' => 'no file uploaded']));
      }

      return $response;
    }

    // modify data specifically for record content type with a given associative array or records
    // essentially replaces category, division, and retention with the appropriate term id's
    // in order to prepare it for import via drupal's in-built content rest api
    function modifyData(array $d, $deptId) {
      $count = count($d);
      $entityRefKeys = ['category','division','retention']; // these are the keys that use entity references for record content type
      $recNum = 0;
      for($i = 0; $i < $count; $i++) {
        error_log('-----ImportScheduleForm: recNum: ' . $recNum . '-----');
        $rec = $d[$i];
        // error_log(print_r($rec, 1));

        // replace the category
        // if(isset($rec['category']) && $rec['category'] !== 'null' && strlen($rec['category']) > 0) {
        //   $category = $rec['category'];
        //   $categoryTermId = Utility::getTermId('category', $deptId); // the term id of the actual string 'category' as defined in vocab department
        //   $categoryId = Utility::getTermId($category, $categoryTermId);
        //   if($categoryId > 0) {
        //     error_log('term id found for category ' . $category . ': ' . $categoryId);
        //     $d[$i]['category'] = $categoryId; // replace category string with id in dataset
        //   } else {
        //     error_log('term id not found for category ' . $category . ', create');
        //   }
        // } else {
        //   error_log('category is null or empty');
        // }

        // // replace the division
        // if(isset($rec['division']) && $rec['division'] !== 'null' && strlen($rec['division']) > 0) {
        //   $division = $rec['division'];
        //   $divisionTermId = Utility::getTermId('division', $deptId);
        //   $divisionId = Utility::getTermId($division, $divisionTermId);
        //   if($divisionId > 0) {
        //     error_log('term id found for division ' . $division . ': ' . $divisionId);
        //     $d[$i]['division'] = $divisionId;
        //   } else {
        //     error_log('term id not found for division ' . $division . ', create');
        //   }
        // } else {
        //   error_log('division is null or empty');
        //   $d[$i]['division'] = '';
        // }

        // // replace the retention
        // if(isset())
        foreach($rec as $key => $value) {
          // error_log($value);
          
          if(in_array($key, $entityRefKeys)) {
            $vocab = $key == 'retention' ? 'retention' : 'department';
            error_log('ImportScheduleForm: modifyData: find term id for ' . $key . ': ' . $value);
            if(isset($value) && $value !== 'null' && strlen($value) > 0) {
              $keyTermId = Utility::getTermId(strtolower($key), $vocab, $deptId);
              $values = array();
              if($vocab == 'retention') {
                $keyTermId = 0;
                $retentionArray = preg_split("/,(\s+)/", $value);
                // error_log('ImportScheduleForm: modifyData: retention array: ' . print_r($retentionArray, 1));
                $values = $retentionArray;
              } else {
                $values = [$value];
              }
              error_log('ImportScheduleForm: modifyData: $values: ' . print_r($values, 1));
              $valuesCount = count($values);
              for($j=0; $j<$valuesCount; $j++) {
                $someValue = $values[$j];
                $valueTermId = Utility::getTermId(strtolower($someValue), $vocab, $keyTermId);
                if($valueTermId > 0) {
                  error_log('ImportScheduleForm: modifyData: ' . $key . ' term id found for [' . $someValue . '] in [' . $vocab . ']: [' . $valueTermId . ']');
                  if(is_array($d[$i][$key])) {
                    array_push($d[$i][$key], $valueTermId);
                  } else {
                    $d[$i][$key] = [$valueTermId];
                  }
                } else {
                  error_log('ImportScheduleForm: modifyData: ' . $key . ' term id not found for [' . $someValue . '] in [' . $vocab . '], create');
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
                    error_log('ImportScheduleForm: modifyData: ' . $key . ' created: ' . print_r($someValue, 1) . ' with newTermId: ' . $newTermId);
                    error_log('pre: ' . print_r($d[$i][$key], 1));
                    is_array($d[$i][$key]) ? array_push($d[$i][$key], $newTermId) : $d[$i][$key] = $newTermId;
                    error_log('post: ' . print_r($d[$i][$key], 1));
                  }
                }
              }
            } else {
              $d[$i][$key] = '';
            }
          }
        }
        error_log('-----ImportScheduleForm: recNum: ' . $recNum . '-----');
        $recNum++;
      }
      return ['department' => $deptId, 'schedule' => $d];
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      // $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());


      // error_log('what');
      // error_log(print_r($user, 1));

/*
      foreach ($form_state->getValues() as $key => $value) {
        drupal_set_message($key . ': ' . $value);
      }
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($form_state->getValue('schedule_file')[0]);
      $data = file_get_contents($file->getFileUri());
      drupal_set_message('ok');


      // error_log(print_r($data, 1));

      $handle = fopen($file->getFileUri(), 'r');
      $header = NULL;
      $d = array();
      while(($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
        if(!$header) {
          $header = $row;
        } else {
          $d[] = array_combine($header, $row);
        }
      }
      fclose($handle);

      error_log(print_r($d, 1));

      $form['#attached']['drupalSettings']['itr']['importSchedule']['data'] = json_encode($d);
*?

      /*
        type: [{ target_id: 'record' }],
        title: [{ value: 'Example record title' }],
        field_department: [{
          target_id: 25
        }],
        field_category: [{ 
          target_id: 90 
        }],
        field_retention: [{ 
          target_id: 21
        }]
      */

      // $host = \Drupal::request()->getSchemeAndHttpHost();
      // $baseUrl = \Drupal::request()->getBaseUrl();
      // $contentRestPath = '/entity/node?_format=json';

      // $contentPostUrl = $host . $baseUrl . $contentRestPath;
      // $sessionTokenUrl = $host . $baseUrl . '/session/token';

      // $jar = new CookieJar();

      // $client = new Client([
      //   'base_url' => $host . $baseUrl,
      //   'cookies' => true,
      //   'allow_redirects' => true,
      //   'debug' => true,
      // ]);

      // $token = $client->get($sessionTokenUrl, [
      //   'cookies' => $jar,
      // ])->getBody();

      // error_log($token);

      // $serialized_entity = json_encode([
      //   'title' => [['value' => 'Example record title']],
      //   'type' => [['target_id' => 'record']],
      //   'field_department' => [['target_id' => 25]],
      //   'field_category' => [['target_id' => 90]],
      //   'field_retention' => [['target_id' => 21 ]],
      // ]);

      // // $response = \Drupal::httpClient()->get($host.$baseUrl.'/session/token');
      // // $token = $response->getBody();
      
      // \Drupal::httpClient()->post($contentPostUrl, [
      //   'body' => $serialized_entity,
      //   'headers' => [
      //     'Content-Type' => 'application/json',
      //     'X-CSRF-Token' => $token,
      //     'cookies' => $jar,
      //   ],
      // ]);


    }


  }

?>