Feature: Remove volunteer from group
  
Hosts need to be able to remove volunteers from groups.  They might be a long-standing
member who is no longer active with the group, or sometimes it is just that someone has
been added to the group by mistake.

Only Hosts of a group and Admins should be able to remove members from a group.

TODO: should there be any associated notifications with this?

Scenario: Admin removes member from a group they're a member of
  Given I am an Admin
  When I remove a member from a group I'm a member of
  Then the member is no longer part of the group
  And I see a message confirming that the member has been removed successfully
# Fail
# No message shown upon successful removal

Scenario: Admin removes member from a group they're not a member of
  Given I am an Admin
  When I remove a member from a group I'm not a member of
  Then the member is no longer part of the group
  And I see a message confirming that the member has been removed successfully
# Fail
# No message shown upon successful removal

Scenario: Host removes member from a group
  Given I am an Host
  When I remove a member from a group
  Then the member is no longer part of the group
# Fail
# See General Fail

# General Fail
# Can't view groups
# Undefined variable: formdata (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php) (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php) (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php)