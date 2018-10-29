@itr
Feature: Content
  In order to test some basic Behat functionality
  As a website user
  I need to be able to see that the Drupal and Drush drivers are working

  @api
  Scenario: Create department term
    Given "department" terms:
    | name    |
    | Department 1 |
    | Department 2 |
    And I am logged in as a user with the "administrator" role
    When I go to "admin/structure/taxonomy/manage/department/overview"
    Then I should see "Department 1"
    And I should see "Department 2"
