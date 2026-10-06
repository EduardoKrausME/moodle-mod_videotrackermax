@mod @mod_videotrackermax
Feature: Video Tracker Max dashboard access
  In order to protect collective video analytics
  As a teacher
  I need analytics dashboards to require the appropriate capability

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  Scenario: A learner cannot access a teacher analytics URL
    Given I log in as "student1"
    When I visit "/mod/videotrackermax/dashboard.php?id=1"
    Then I should see "Invalid course module ID"

  Scenario: Dashboard sections use stable names
    Given I log in as "teacher1"
    Then the following strings should exist in language pack "videotrackermax":
      | string                  |
      | tab:overview            |
      | tab:retention           |
      | tab:heatmap             |
      | tab:comparison          |
      | tab:students            |
