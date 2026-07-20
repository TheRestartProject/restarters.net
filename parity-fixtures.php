<?php

// Parity-capture fixtures.
// ========================
// phpunit runs `migrate:fresh --seed` against restarters_db_test, which the
// dev site AND the legacy parity instance also use - so every backend test run
// wipes the data the screenshot capture depends on. This restores the minimum
// set both systems need, idempotently.
//
// Run AFTER destructive test runs finish, BEFORE `task parity:capture`:
//   script -qec "task docker:run:bash -- 'php artisan tinker parity-fixtures.php'" /dev/null
//
// NB Network and Group are mass-assignment guarded, so these use `new` plus
// property assignment rather than create()/firstOrCreate().

use App\Group;
use App\Network;
use App\Party;
use App\Role;
use App\User;
use App\UserGroups;

$out = [];

// --- users -------------------------------------------------------------
// jane is the account the capture logs in as (see client/parity/capture.spec.js).
$jane = User::where('email', 'jane@bloggs.net')->first();
if (!$jane) {
    $jane = new User;
    $jane->name = 'Jane Bloggs';
    $jane->email = 'jane@bloggs.net';
    $jane->password = bcrypt('passw0rd');
}
$jane->role = Role::ADMINISTRATOR;
$jane->consent_gdpr = true;
$jane->consent_past_data = true;
$jane->consent_future_data = true;
// number_of_logins >= 2 keeps the onboarding modal out of the dashboard shot.
$jane->number_of_logins = 5;
$jane->save();
$out[] = 'jane='.$jane->id;

$host = User::where('email', 'host@restarters.test')->first();
if (!$host) {
    $host = new User;
    $host->name = 'Host User';
    $host->email = 'host@restarters.test';
    $host->password = bcrypt('passw0rd');
}
$host->role = Role::HOST;
$host->consent_gdpr = true;
$host->number_of_logins = 5;
$host->save();
$out[] = 'host='.$host->id;

// --- networks ----------------------------------------------------------
// BOTH systems run CheckForRepairNetwork on every request. It maps the request
// host to a Network by shortname, and its default branch is
// Network::where('shortname','like','restarters') - with no match it THROWS
// ("Could not determine repair network from domain"), 500ing every page. The
// testing-environment fallback that invents a network does not apply to the
// dev/legacy instances. So a 'restarters' network is not optional here: without
// it the legacy parity instance is completely down and no capture can run.
$default = Network::where('shortname', 'restarters')->first();
if (!$default) {
    $default = new Network;
    $default->name = 'Restarters';
    $default->shortname = 'restarters';
    $default->default_language = 'en';
    $default->save();
}
$out[] = 'defaultNetwork='.$default->id;

$network = Network::where('name', 'Test London network')->first();
if (!$network) {
    $network = new Network;
    $network->name = 'Test London network';
    $network->shortname = 'test-london';
    $network->default_language = 'en';
    $network->save();
}
$out[] = 'network='.$network->id;

// --- group -------------------------------------------------------------
// The capture hits /group/view/1 on BOTH systems, so this group wants id 1.
$group = Group::where('name', 'Tag Test Group')->first();
if (!$group) {
    $group = new Group;
    $group->name = 'Tag Test Group';
    $group->location = 'London';
    $group->latitude = 51.5074;
    $group->longitude = -0.1278;
    $group->country = 'GB';
    $group->free_text = 'A test group for parity.';
    $group->website = 'https://example.org';
    $group->save();
}
$out[] = 'group='.$group->idgroups;

if (!$network->groups()->where('idgroups', $group->idgroups)->exists()) {
    $network->addGroup($group);
}

foreach ([[$jane, Role::HOST], [$host, Role::HOST]] as [$u, $r]) {
    if (!UserGroups::where('user', $u->id)->where('group', $group->idgroups)->exists()) {
        $ug = new UserGroups;
        $ug->user = $u->id;
        $ug->group = $group->idgroups;
        $ug->status = 1;
        $ug->role = $r;
        $ug->save();
    }
}

$out[] = 'members='.UserGroups::where('group', $group->idgroups)->count();
$out[] = 'events='.Party::where('group', $group->idgroups)->count();

echo 'FIXTURES '.implode(' ', $out)." END\n";
