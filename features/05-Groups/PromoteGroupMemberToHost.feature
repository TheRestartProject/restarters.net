Feature: Set group member as group host
  
As a Host of a Group
In order to share the Host responsbilities
I need to be able to set other Group Members as a Host of the Group

We need the ability for hosts to upgrade other members of the group to a host
role.  Currently only the person who created the group is set as a host of the group.
Both hosts and admins should be able to set other members of the group as hosts
of the group.

Both users with the Host and Restarter roles can be set as Hosts.  If a Restarter is set
as a group host, their account should also be set to have the Host role.

TODO: explain why we don't simply let being a Host and being a member of a group make you
a host of that group.

TODO: worth noting that when a restarter becomes a host, it will change their dashboard.
Could potentially be confusing.  I think we need a way of choosing what's on the dashboard.

Scenario: Host of a group sets a group member with the host role as a group host
  Given I am host of a group
  When I set another group member who has the host role to be group host
  Then they are set as a host of the group
# Fail
# Error when viewing a group
# Undefined variable: formdata (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php) (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php) (View: /home/neil/Code/fixometer_laravel/resources/views/partials/volunteer-row.blade.php)

Scenario: Host of a group sets a group member with the restarter role as a group host
  Given I am host of a group
  When I set another group member who has the Restarter role to be group host
  Then they are set as a host of the group
  And they are given the host role
# Fail
# Error when viewing a group, as above

Scenario: Admin sets a group member with the host role as a group host
  Given I am an Admin
  When I set a group member of a group who has the host role to be group host
  Then they are set as a host of the group
# Pass

Scenario: Admin sets a group member with the restarter role as a group host
  Given I am host of a group
  When I set a group member of a group who has the host role to be group host
  Then they are set as a host of the group
  And they are given the host role
# Pass

  
# General Fail
# The action of marking as a host succeeded, but received an error
# 'Trying to get property 'name' of non-object' - think this is related to the user not having opted in to email notifications