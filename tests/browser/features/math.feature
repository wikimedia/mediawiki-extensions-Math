@chrome @en.wikipedia.beta.wmflabs.org @firefox
Feature: Math

  Scenario: Display simple math
    Given I am editing a random page with source editor
    When I type <math>3 + 2</math>
      And I dismiss Welcome to Wikipedia popup
      And I click Preview
    Then the page should contain 3 + 2 image
