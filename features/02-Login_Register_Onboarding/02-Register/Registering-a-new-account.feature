Feature: Registering a new account

Scenario: valid registration
  Given I am on the registration page
  # Navigate to https://restarters.dev/user/register
  When I complete all of the registration details
  # Fill in values for step 1: skills
  # Fill in values for step 2: profile and account info
  # Fill in values for step 3: newsletter opt-in
  # Fill in values for step 4: data consent
  And I complete my registration
  # Click the register button
  Then an account is created for me
  # Check you have a new account and are on the dashboard
