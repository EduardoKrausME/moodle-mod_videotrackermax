@mod @mod_videotrackermax
Feature: Video Tracker Max analytics dashboard
  In order to inspect collective video consumption safely
  As a teacher
  I need access to the analytics dashboard while learners remain limited to their own activity view

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity        | name          | intro          | course | idnumber |
      | videotrackermax | Analytics Max | Video analytics | C1   | vtm1     |

  Scenario: Teacher opens the basic dashboard sections
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I follow "Analytics Max"
    And I follow "Analytics"
    Then I should see "Overview"
    And I should see "Retention"
    And I should see "Heatmap"
    And I should see "Comparison"
    And I should see "Learners"

  Scenario: Learner does not receive a collective analytics link
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Analytics Max"
    Then I should not see "Analytics" in the "region-main" "region"
