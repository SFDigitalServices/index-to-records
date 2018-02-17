<?php
  
  namespace Drupal\itr\Form;
  use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  use GuzzleHttp\Client;
  use GuzzleHttp\Cookie\CookieJar;
  use GuzzleHttp\Exception\RequestException;

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\itr\Ajax\ImportScheduleCommand;

  require_once('modules/devel/kint/kint/Kint.class.php');
  \Kint::$maxLevels = 4;  

  class ImportScheduleForm extends FormBase {

    /**
    * {@inheritdoc}
    */

    public function getFormId() {
      return 'import_schedule_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

      // get departments from taxonomy
      $depts = getDepartmentsForUser(); // this method is defined in .module file.  
                                        // yes, it should maybe be a static utility class or service.
                                        // no, i will not
      $deptOptions = array();
      $deptOptions['_none'] = '- None -';
      foreach($depts as $dept) {
        $deptOptions[$dept['id']] = $dept['name'];
      }

      $form = array(
        '#attributes' => array('enctype' => 'multipart/form-data'),
      );

      $form['#prefix'] = '<div id="my-form-prefix">';
      $form['#suffix'] = '</div>';

      $str = '<p>The following requirements should be met before importing a schedule:</p>';
      $str .= '<ul>';
      $str .= '  <li>The file to import must follow <a href="#">this template</a></li>';
      $str .= '  <li>The file to import must be in the CSV (comma separated values) file format</li>';
      $str .= '</ul>';
      $str .= '<p><strong>Please note that importing a schedule will overwrite the existing schedule</strong>.  If you are making changes to an existing schedule, ';
      $str .= 'please either <a href="/node/add/record">add to the existing schedule</a> or <a href="/schedule/export">export the existing schedule</a>, ';
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
        '#value' => $this->t('Save'),
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
        $handle = fopen($fileUri, 'r');
        $header = NULL;
        $d = array();
        $count = 0;
        while(($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
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

        for($i = 0; $i < $count; $i++) {
          $rec = $d[$i];
          
          $division = $rec['division'];

          // replace the category
          if(isset($rec['category']) && $rec['category'] !== 'null' && strlen($rec['category']) > 0) {
            $category = $rec['category'];
            $categoryTermId = getTermId('category', $deptId); // the term id of the actual string 'category' as defined in vocab department
            $categoryId = getTermId($category, $categoryTermId);
            if($categoryId > 0) {
              error_log('term id found for category ' . $category . ': ' . $categoryId);
              $d[$i]['category'] = $categoryId; // replace category string with id in dataset
            } else {
              error_log('term id not found for category ' . $category . ', create');
            }
          }
        }


        // $form['#attached']['drupalSettings']['itr']['importSchedule']['data'] = json_encode($d);
        $response->addCommand(new ImportScheduleCommand($d)); // execute custom ajax command which renders json array with data $d
      } else {
        $response->addCommand(new ImportScheduleCommand(['message' => 'no file uploaded']));
      }

      return $response;
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