# Restarters.net Features

This document provides a comprehensive overview of all features in the Restarters.net platform - a community repair event management and impact tracking system.

## Overview

Restarters.net (The Fixometer) is a data collection and visualization tool for community repair organizations. It helps repair groups organize events, track repairs, measure environmental impact, and engage volunteers.

**Core Entities:**
- **Users** - Volunteers, hosts, and administrators
- **Groups** - Community repair organizations
- **Events (Parties)** - Community repair events
- **Devices** - Items brought for repair at events
- **Networks** - Regional networks of repair groups
- **Categories** - Device classification for impact calculation

---

## User Roles

- **Restarter** - Volunteer who attends repair events
- **Host** - Organizes and manages groups and events
- **Network Coordinator** - Regional oversight of multiple groups
- **Administrator** - Full platform access and configuration
- **Root** - Super administrator with all permissions

---

## 1. Authentication & User Management

<details>
<summary><strong>User Registration & Onboarding</strong></summary>

### Registration Flow
- Multi-step registration process
- Email and password with validation (min 6 chars, matching confirmation)
- Password requirements: minimum length, case-sensitive
- Age verification (must be 18+)
- Profile information: name, age, gender, location, bio
- Optional skill selection during registration
- Email notification preferences opt-in
- GDPR/privacy consent tracking (past data, future data, cookies)
- Automatic Wiki and Discourse account creation on registration

### Profile Management
- View and edit user profiles
- Upload profile images
- Update biographical information
- Manage location and contact details
- Language preference selection (EN, FR, FR-BE)
- Edit email notification preferences
- Skill management (add/remove repair skills)

### Authentication
- Email/password login (case-insensitive emails)
- Password reset via email
- Remember me functionality
- Single Sign-On (SSO) with Discourse (Talk)
- Single Sign-On with MediaWiki
- Soft-delete user accounts with anonymization

</details>

<details>
<summary><strong>User Dashboard</strong></summary>

### Personalized Dashboard Views
- **Restarter Dashboard** - View followed groups, nearby events, impact stats
- **Host Dashboard** - Manage groups and events, view host-specific stats
- **Admin Dashboard** - Platform-wide statistics and moderation queue

### Dashboard Components
- Getting started guide for new users
- Latest Discourse discussion topics
- Followed groups with upcoming events
- Quick "Add Data" section for recording repairs
- New groups nearby notifications
- Upcoming events in user's area
- Personal impact statistics

</details>

---

## 2. Groups (Community Repair Organizations)

<details>
<summary><strong>Group Management</strong></summary>

### Creating Groups
- Create new community repair groups
- Group details: name, website, description, location
- Group images/logos
- Geocoded location with lat/long
- Network affiliation (for admins/network coordinators)
- Group area/region assignment
- Text cleaning and sanitization
- Approval workflow for new groups

### Editing Groups
- Modify all group details
- Update images and descriptions
- Change location and contact information
- Manage network associations
- Set group tags for categorization
- Configure auto-approval settings for events

### Group Membership
- Join and leave groups
- Invite users to groups via email
- Shareable invitation links (hash-based)
- View all group members
- Track hosts vs restarters
- Member activity tracking
- Remove members from groups

### Group Roles & Permissions
- Promote restarters to hosts
- Multiple hosts per group
- Host permissions for event management
- Network coordinator oversight

</details>

<details>
<summary><strong>Browsing & Discovery</strong></summary>

### Finding Groups
- View all groups
- Search groups by name, location, tags
- Filter by network
- Find groups nearby using location
- View group statistics and impact
- Follow/unfollow groups

### Group Information
- View group profile and description
- See upcoming and past events
- Group statistics dashboard:
  - Total events held
  - Total volunteers
  - Participants count
  - Devices repaired
  - Waste prevented
  - CO2 emissions diverted
- Embeddable statistics widget

</details>

---

## 3. Events (Community Repair Events)

<details>
<summary><strong>Event Creation & Management</strong></summary>

### Creating Events
- Event name and description
- Associated group selection
- Date and time with timezone support
- Venue/location details (physical or online)
- Event images/photos
- Text cleaning and sanitization
- Geocoding for physical venues
- Calendar date picker
- Auto-calculated 3-hour duration
- Attendance capacity limits
- Online event support (no physical location required)

### Editing Events
- Modify all event details
- Update date, time, and timezone
- Change venue information
- Add/remove event images
- Update description and notes
- Cancel events

### Event Duplication
- Clone existing events to quickly create similar events
- Copy all event details with option to modify

</details>

<details>
<summary><strong>Event Invitations & Participation</strong></summary>

### Invitations
- Invite group volunteers to events
- Send invitations to specific email addresses
- Custom invitation messages
- Hash-based invitation links
- Email notifications for invitations
- Track invitation status (pending, accepted, declined)

### RSVP & Attendance
- Accept/decline event invitations
- View list of invited volunteers
- View list of volunteers who attended
- Track volunteer attendance
- Record volunteer hours
- Add/remove volunteers from events

</details>

<details>
<summary><strong>Event Browsing & Filtering</strong></summary>

### Finding Events
- View upcoming events
- View past events
- Filter by group
- Filter by network
- Filter by date range
- Filter by tags
- Filter online vs in-person events
- Search events by name
- View events by geographic area

### Event Views
- Event details page with full information
- Event statistics and impact
- List of attendees
- Devices repaired at event
- Event photos

</details>

---

## 4. Devices & Repairs (The Fixometer)

<details>
<summary><strong>Device Recording</strong></summary>

### Adding Devices
- Quick data entry for devices repaired at events
- Device details:
  - Category (electronics, textiles, furniture, etc.)
  - Brand and model
  - Age of device
  - Problem description
  - Repair status (Fixed, Repairable, End of Life)
  - Spare parts required
  - Assessment details
- Upload device images
- Link devices to specific events

### Device Categories
- Powered devices (electronics with CO2 calculations)
- Unpowered devices (textiles, furniture, etc.)
- Category-specific properties:
  - Average weight
  - CO2 footprint per kg
  - Reliability scores
  - Cluster grouping

### Repair Status Tracking
- **Fixed** - Successfully repaired
- **Repairable** - Could be fixed with more time/parts
- **End of Life** - Not economically repairable

### Repair Barriers
- Track why repairs couldn't be completed:
  - Spare parts unavailable
  - Spare parts too expensive
  - No repair information available
  - Device cannot be opened
  - Professional help required

</details>

<details>
<summary><strong>Device Management</strong></summary>

### Editing Devices
- Modify device details post-event
- Update repair status
- Add/remove device images
- Correct categorization
- Update problem descriptions
- Delete devices if needed

### Device Search & Browse
- View all devices platform-wide
- Filter by category
- Filter by brand
- Filter by repair status
- Filter by event or group
- Search by model or description
- Sort by various criteria
- Pagination support

### Device Images
- Upload multiple images per device
- Image resizing and optimization
- Delete unwanted images
- Link images to repair records

</details>

---

## 5. Impact Tracking & Statistics

<details>
<summary><strong>Environmental Impact Calculations</strong></summary>

### Metrics Tracked
- **Waste Prevented** - Weight of devices kept out of landfill
- **CO2 Emissions Prevented** - Equivalent to manufacturing new devices
- **Devices Repaired** - Count by status (fixed, repairable, end of life)
- **Volunteer Hours** - Time contributed by volunteers
- **Participants** - Number of people attending events

### Impact Visualization
- Global platform statistics
- Group-level statistics
- Event-level statistics
- Network-level statistics
- Comparison visualizations:
  - CO2 as car driving distance
  - CO2 as manufacturing equivalents
  - Waste in meaningful units

</details>

<details>
<summary><strong>Reporting & Analytics</strong></summary>

### Available Reports
- Breakdown by country
- Breakdown by category
- Most commonly repaired devices
- Volunteer hour tracking
- Network performance reports
- Group performance reports
- Time-based trend analysis

### Data Export
- CSV export capabilities:
  - Devices by event
  - Devices by group
  - Devices globally
  - Event statistics by group
  - Event statistics by network
- Custom column selection
- Date range filtering

### Embeddable Statistics
- JSON API for external embedding
- Mobile-optimized formats
- Share statistics on social media
- Downloadable statistics images

</details>

---

## 6. Networks & Regional Coordination

<details>
<summary><strong>Network Management</strong></summary>

### Network Structure
- Regional networks organize groups
- Network-level coordination
- Network coordinators with oversight permissions
- Groups can belong to multiple networks

### Network Features
- Associate groups to networks (admin only)
- View all groups in network
- View all events across network
- Network-wide statistics
- Network calendar feeds
- Network coordinators role with regional permissions

### Network Coordination
- NetworkCoordinator role for regional oversight
- Permission to:
  - View all groups in network
  - View all events in network
  - Edit groups in network (if affiliated)
  - Moderate events in network
  - View network statistics

</details>

---

## 7. Administration

<details>
<summary><strong>User Administration</strong></summary>

### User Management
- View all users with search/pagination
- Create new users directly
- Edit any user profile
- Soft-delete users
- Manage user permissions granularly
- Assign repair directory roles
- Edit user email preferences
- Change user roles
- Track user activity via audit trail

### Role Management
- View all roles and permissions
- Edit role definitions
- Configure permissions matrix
- Assign custom permissions beyond roles

</details>

<details>
<summary><strong>Content Management</strong></summary>

### Categories
- Create/edit/delete device categories
- Manage category properties:
  - Name and description
  - Average weight
  - CO2 footprint per kg
  - Reliability percentage
  - Cluster assignment
- Life Cycle Assessment (LCA) data management

### Brands
- Add device brands
- Edit brand names
- Delete brands
- Brand search and autocomplete

### Skills
- Create volunteer skills
- Edit skill descriptions
- Delete skills
- Assign skills to categories

### Group Tags
- Create group classification tags
- Edit tag names and descriptions
- Delete tags
- Assign tags to groups for organization

</details>

<details>
<summary><strong>Moderation</strong></summary>

### Approval Workflows
- Review new groups before approval
- Review new events before approval
- Moderate event photos
- Admin notifications for moderation queue

### Platform Statistics
- View global impact metrics
- Monitor platform health
- Track user engagement
- View most active groups
- See trending device categories
- Abnormal device count alerts

</details>

---

## 8. External Integrations

<details>
<summary><strong>Discourse Integration (Restarters Talk)</strong></summary>

### Features
- User account synchronization
- Single Sign-On (SSO)
- Auto-create Discourse groups for repair groups
- Fetch discussion topics by tag
- Display recent topics on dashboard
- Pull unread notifications
- Language preference sync
- Profile links

</details>

<details>
<summary><strong>MediaWiki Integration (Restarters Wiki)</strong></summary>

### Features
- User account sync tracking
- Wiki login credential management
- SSO integration

</details>

<details>
<summary><strong>WordPress Integration</strong></summary>

### Features
- Publish approved events to WordPress
- Publish group information
- XML-RPC API integration
- Automated content syndication

</details>

<details>
<summary><strong>Calendar Integration</strong></summary>

### Features
- iCalendar (.ics) export
- Personal calendar feeds (hash-authenticated)
- Group calendar feeds
- Network calendar feeds
- Geographic area calendar feeds
- Master platform calendar (secured)
- Event status tracking (confirmed/tentative/cancelled)
- Timezone support in calendar feeds

</details>

<details>
<summary><strong>Other Integrations</strong></summary>

### Geocoding
- Convert addresses to coordinates
- Display group locations on maps
- Find nearby groups by radius

### Drip Email Marketing
- Subscriber management
- Campaign integration
- Track Drip subscriber IDs

### Zapier
- Push audit data to Zapier
- Track changes to users, groups, events
- Webhook support for automation

</details>

---

## 9. API

<details>
<summary><strong>Public API (No Authentication)</strong></summary>

### Endpoints
- `GET /api/homepage_data` - Global platform statistics
- `GET /api/party/{id}/stats` - Event statistics
- `GET /api/group/{id}/stats` - Group statistics
- `GET /api/outbound/info/{type}/{id}/{format}` - Impact visualizations
- `GET /api/devices/{page}/{size}` - Paginated device list
- `GET /api/talk/topics/{tag}` - Discourse topics
- `GET /api/timezones` - Available timezones

</details>

<details>
<summary><strong>Authenticated API (API Token Required)</strong></summary>

### User Endpoints
- `GET /api/users/me` - Current user info
- `GET /api/users` - List all users
- `GET /api/users/changes` - Track user changes (Zapier)
- `GET /api/users/{id}/notifications` - User notifications

### Group Endpoints
- `GET /api/groups` - List groups
- `GET /api/groups/changes` - Track group changes (Zapier)
- `GET /api/groups/network` - Groups by user's networks

### Event Endpoints
- `GET /api/events/network/{date_from}/{date_to}` - Events by network and date
- `GET /api/events/{id}/volunteers` - Event volunteers
- `PUT /api/events/{id}/volunteers` - Add volunteer to event

### Network Endpoints
- `GET /api/networks/{network}/stats` - Network statistics

### Membership Endpoints
- `GET /api/usersgroups/changes` - Track membership changes (Zapier)
- `DELETE /api/usersgroups/{id}` - Remove user from group

</details>

<details>
<summary><strong>API v2 (RESTful)</strong></summary>

### Groups
- `GET /api/v2/groups/names` - Group names list
- `GET /api/v2/groups/tags` - All group tags
- `GET /api/v2/groups/{id}` - Group details
- `GET /api/v2/groups/{id}/events` - Group events
- `GET /api/v2/groups/{id}/volunteers` - Group volunteers
- `POST /api/v2/groups` - Create group
- `PATCH /api/v2/groups/{id}` - Update group
- `PATCH /api/v2/groups/{id}/volunteers/{iduser}` - Update volunteer
- `DELETE /api/v2/groups/{id}/volunteers/{iduser}` - Remove volunteer

### Events
- `GET /api/v2/events/{id}` - Event details
- `POST /api/v2/events` - Create event
- `PATCH /api/v2/events/{id}` - Update event

### Devices
- `GET /api/v2/devices/{id}` - Device details
- `POST /api/v2/devices` - Create device
- `PATCH /api/v2/devices/{id}` - Update device
- `DELETE /api/v2/devices/{id}` - Delete device

### Networks
- `GET /api/v2/networks` - List networks
- `GET /api/v2/networks/{id}` - Network details
- `GET /api/v2/networks/{id}/groups` - Network groups
- `GET /api/v2/networks/{id}/events` - Network events

### Moderation
- `GET /api/v2/moderate/groups` - Groups pending moderation
- `GET /api/v2/moderate/events` - Events pending moderation

### Items
- `GET /api/v2/items` - Device categories

### Alerts
- `GET /api/v2/alerts` - List alerts
- `PUT /api/v2/alerts` - Create alert
- `PATCH /api/v2/alerts/{id}` - Update alert

### Documentation
- Full OpenAPI/Swagger documentation available
- Consistent JSON response structure
- Pagination support
- Date range filtering
- Token-based and session-based authentication

</details>

---

## 10. Notifications & Communication

<details>
<summary><strong>Email Notifications</strong></summary>

### Automated Emails
- Post-event reminder to add data
- Password reset emails
- Group invitation emails
- Event invitation emails
- Event moderation notifications (admins)
- Volunteer attendance notifications
- Account setup confirmation
- Wiki submission alerts
- New group nearby notifications
- User deletion notifications

### Notification Preferences
- Opt-in/opt-out per notification type
- User-configurable preferences
- Email frequency settings

</details>

<details>
<summary><strong>In-App Notifications</strong></summary>

### Features
- Mark notifications as read
- Notification count badges
- Discourse notification integration
- Real-time notification updates

</details>

---

## 11. Additional Features

<details>
<summary><strong>Multi-Language Support</strong></summary>

### Supported Languages
- English (EN)
- French (FR)
- French (Belgium) (FR-BE)

### Translation Features
- User language preference
- On-the-fly language switching
- Translation management commands
- Automatic JavaScript translation generation
- Translation completeness checking
- Regional locale support (Discourse doesn't support regional variants)

</details>

<details>
<summary><strong>File & Image Management</strong></summary>

### Features
- Image upload for groups, events, devices
- Multiple images per entity
- Image resizing and optimization
- Image deletion
- File type validation
- Storage management

</details>

<details>
<summary><strong>Search Functionality</strong></summary>

### Search Capabilities
- User search (name, email, location)
- Group search (name, location, tags, network)
- Device search (category, brand, status)
- Event search (name, date, location)
- Brand autocomplete
- Category filtering

</details>

<details>
<summary><strong>Privacy & Compliance</strong></summary>

### Features
- Cookie consent tracking
- Cookie policy display
- GDPR data consent management
- Information alerts with dismissal
- Soft-delete for data preservation
- User data anonymization
- Audit trail for compliance

</details>

<details>
<summary><strong>Security Features</strong></summary>

### Security Measures
- CSRF protection on forms
- Authentication middleware
- Authorization policies for resources
- Secure password hashing (bcrypt)
- API token authentication
- Email verification
- Hash-based secure invitation links
- Role-based access control
- Granular permission system

</details>

---

## Related Documentation

- **[Tech Documentation](Tech.md)** - Technical architecture, testing, and development details
- **[Local Development Setup](docs/local-development.md)** - Getting started guide for developers
- **[CLAUDE.md](CLAUDE.md)** - AI assistant guidelines and project overview
- **[API Documentation](https://restarters.net/api/documentation)** - OpenAPI/Swagger interactive docs
