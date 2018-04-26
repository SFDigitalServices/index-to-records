<?php
  namespace Drupal\itr\Controller;

  use Drupal\Core\Controller\ControllerBase;
  use Symfony\Component\HttpFoundation\RedirectResponse;

  use Drupal\itr\Utility\Utility;

  class EditDepartmentController extends ControllerBase {

    private function retrieveDepartmentInfo() {
      global $base_url;
      $depts = Utility::getDepartmentsForUser();
      error_log('EditDepartmentController:count:' . count($depts) . ':contents:' . print_r($depts, 1));
      if(count($depts) > 0) {
        $deptId = $depts[0][id];
        error_log('EditDepartmentController:deptId:' . $deptId);
        error_log('EditDepartmentController:base_url:' . $base_url);
        $client = \Drupal::httpClient();
        $request = $client->get($base_url . '/itr_rest_view/dept/info/' . $deptId . '/?_format=json'); // this path is a rest route defined in view Department Information
        $response = json_decode($request->getBody(), true);
        if(count($response) > 0) {
          error_log('EditDepartmentController:has data, go to edit');
          // error_log(print_r($response, 1));
          $nid = $response[0]['nid'][0]['value'];
          error_log('EditDepartmentController:nid:' . $nid);
          return $nid;
        } else {
          error_log('EditDepartmentController:no data, redirect to create');
        }
        return null;
      }
    }

    public function content() {
      $nid = $this->retrieveDepartmentInfo();
      error_log('EditDepartmentController:content:redirect with nid=' . $nid);
      if($nid) { // department information exists
        // if admin, redirect to admin view
        if(Utility::userIsAdmin()) {
          $routeName = 'view.content.manage_department_info';
          $routeParameters = [];
        } else {
          $routeName = 'entity.node.edit_form';
          $routeParameters = ['node'=>$nid];
        }
      } else { // no department information found, redirect to create
        $routeName = 'node.add';
        $routeParameters = ['node_type'=>'department_information'];
      }
      $url = \Drupal::url($routeName, $routeParameters);

      if($routeName) {
        return new RedirectResponse($url);
      }

      return array(
        '#type' => 'markup',
        '#markup' => $this->t('No department information found.'),
      );
    }
  }
?>