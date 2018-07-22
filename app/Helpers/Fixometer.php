<?php

namespace App\Helpers;

use App\Party;
use App\Role;
use App\Skills;
use App\UserGroups;
use App\Permissions;

use App;
use Auth;
use DB;

class FixometerHelper {

  public static function allAges() {

    // return [
    //   'N/A'   => 'N/A',
    //   '16-20' => '16-20',
    //   '20-30' => '20-30',
    //   '30-40' => '30-40',
    //   '40-50' => '40-50',
    //   '50-60' => '50-60',
    //   '60-70' => '60-70',
    //   '70+'   => '70+',
    // ];

    $ages = ['' => ''];

    for ($i=intval(date('Y')) - 18; $i > intval(date('Y', strtotime("- 100 years"))); $i--) {
      $ages[$i] = $i;
    }

    return $ages;

  }

  public static function allRoles() {

    return Role::all();

  }

  public static function allPermissions() {

    return Permissions::all();

  }

  /** checks if user has a role **/
  public static function hasRole($user, $role){

        if ( Auth::guest() ) {
          return false;
        }

        if (is_null($user)) {
          $user = Auth::user();
        }

        $usersRole = $user->role()->first()->role;
        if($usersRole == 'Root'){
            return true;
        }
        else {
            if($usersRole == ucwords($role)){
                return true;
            }
            else {
                return false;
            }
        }
    }

  public static function barChartValue($portion, $total){
      if((int)$portion > 0){
          return round((($portion / $total) * 100) , 2) - 15;
      }else {
          return -15 ;
      }
  }

  public static function featureIsEnabled($feature) {
        return $feature === true;
  }

  public static function dateFormat($timestamp){
      return date('D, j M Y, H:i', $timestamp);
  }

  public static function userHasEditPartyPermission($partyId, $userId)
  {
      if (FixometerHelper::hasRole(Auth::user(), 'Administrator')) {
        return true;
      } else {
        if (FixometerHelper::hasRole(Auth::user(), 'Host')) {
          $group_id_of_event = Party::where('idevents', $partyId)->value('group');
          if (FixometerHelper::userIsHostOfGroup($group_id_of_event, $userId)) {
              return true;
          } else if (empty(DB::table('events_users')->where('event', $partyId)->where('user', $userId)->first())) {
            return false;
          } else {
            return true;
          }
        } else {
          return false;
        }
      }
  }

    public static function userCanCreateEvents($user)
    {
        if ( Auth::guest() ) {
            return false;
        }

        if (is_null($user)) {
            $user = Auth::user();
        }

        $usersRole = $user->role()->first()->role;
        $superusers = ['Root', 'Administrator'];

        if (in_array($usersRole, $superusers)) {
            return true;
        }

        $userIsHostOfAGroup = UserGroups::where('user', Auth::user()->id)
                            ->where('role', 3)
                            ->count() > 0;

        if ($userIsHostOfAGroup) {
            return true;
        }

        return false;
    }

    public static function userIsHostOfGroup($groupId, $userId)
    {
        $user_group_association = DB::table('users_groups')
                                ->where('group', $groupId)
                                ->where('user', $userId)
                                ->where('role', 3)
                                ->first();

        if (!empty($user_group_association)) {
            return true;
        }

        return false;
    }

  /** Prints out Bootstrap alerts
   * finds key of response and
   * uses it to format the alert
   * as wished
   * */
  public static function printResponse($response){
      foreach($response as $type => $text){
          switch($type) {
              case 'success':
                  $icon = 'check';
                  break;
              case 'danger':
                  $icon = 'exclamation-triangle';
                  break;
              case 'warning':
                  $icon = 'exclamation-circle';
                  break;
              case 'info':
                  $icon = 'info';
                  break;
          }
          echo '<div class="alert alert-' . $type . '  alert-dismissible" role="alert">
                  <!--<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                  <i class="fa fa-' . $icon . '"></i> ' . $text . '

              </div>';
      }
  }

  /**
   * verify that an array index exists and is not empty or null.
   * can also do some type control.
   * */
  public static function verify($var, $strict = false, $type = 'string'){
      if(!isset($var) || empty($var) || is_null($var)){
          return false;
      }
      else {
          if($strict){
              switch($type){
                  case 'number':
                      if(is_numeric($var)){
                          return true;
                      }
                      break;
                  case 'string':
                      return true;
                      break;
                  case 'array':
                      if(is_array($var)){
                          return true;
                      }
                      break;
                  default:
                      return false;
                      break;
              }
          }
          else {
              return true;
          }

      }
  }

  public static function dbDateNoTime($string){
      $d = explode('/', $string);
      return implode('-', array_reverse($d));
  }

  public static function translate($key) {
      $translation = __(App::getLocale().'.'.$key);
      if ( strpos($translation, App::getLocale().'.') !== false ) {
        return $key;
      } else {
        return $translation;
      }
  }

  /**
   * Rearranges an array
   * used to "reflow" the $_FILES array
   * with multiple entries
   * */
  public static function rearrange($arr){
      foreach($arr as $key => $all){
          foreach($all as $i => $val){
              $new[$i][$key] = $val;
          }
      }
      return $new;
  }

  /**
   * check for existence of images associated with
   * this particular object of $type and $id
   * return boolean, or full results if requested
   * */
  public static function hasImage($id, $table, $return_rows = false){
      switch($table){
          case 'users':
              $object = env('TBL_USERS');
              break;
          case 'groups':
              $object = env('TBL_GROUPS');
              break;
          case 'events':
              $object = env('TBL_EVENTS');
              break;
          case 'devices':
              $object = env('TBL_DEVICES');
              break;
          default:
              $object = false;
              break;
      }
      if($object){
          $sql = '
                      SELECT * FROM `images`
                          INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                          WHERE `xref`.`object_type` = 5
                          AND `xref`.`reference_type` = :object
                          AND `xref`.`reference` = :id
                          GROUP BY `images`.`path`'
                  ;

          try {
            if($return_rows){
                return DB::select(DB::raw($sql), array('id' => $id, 'object' => $object));
            }
            else {
                return (count(DB::select(DB::raw($sql), array('id' => $id, 'object' => $object))) > 0 ? true : false);
            }
          } catch (\Illuminate\Database\QueryException $e) {
            return db($e);
          }
      }
  }

  /**
   * check for existence of images associated with
   * this particular object of $type and $id
   * return boolean, or full results if requested
   * */
  public static function removeImage($id, $table, $image){

      switch($table){
          case 'users':
              $object = env('TBL_USERS');
              break;
          case 'groups':
              $object = env('TBL_GROUPS');
              break;
          case 'events':
              $object = env('TBL_EVENTS');
              break;
          case 'devices':
              $object = env('TBL_DEVICES');
              break;
          default:
              $object = false;
              break;
      }

      if($object){
          /** delete cross references **/

          $sql = "DELETE FROM `xref`
                  WHERE
                  `xref`.`object_type` = 5 AND
                  `xref`.`reference_type` = :object AND
                  `xref`.`reference` = :id ";

          DB::delete(DB::raw($sql), array('id' => $id, 'object' => $object));

          /** delete image from db **/
          $sql = "DELETE FROM `images` WHERE `images`.`idimages` = :image";

          DB::delete(DB::raw($sql), array('image' => $image->idimages));

          /** delete image from disk **/
          unlink($_SERVER['DOCUMENT_ROOT'].'/uploads/'. $image->path);


      }
  }

  public static function getRoleName($id) {

    return Role::where('idroles', $id)->first()->role;

  }

  public static function getAllCountries() {

    return [
      "AF" => "Afghanistan",
      "AX" => "Åland Islands",
      "AL" => "Albania",
      "DZ" => "Algeria",
      "AS" => "American Samoa",
      "AD" => "Andorra",
      "AO" => "Angola",
      "AI" => "Anguilla",
      "AQ" => "Antarctica",
      "AG" => "Antigua and Barbuda",
      "AR" => "Argentina",
      "AM" => "Armenia",
      "AW" => "Aruba",
      "AU" => "Australia",
      "AT" => "Austria",
      "AZ" => "Azerbaijan",
      "BS" => "Bahamas",
      "BH" => "Bahrain",
      "BD" => "Bangladesh",
      "BB" => "Barbados",
      "BY" => "Belarus",
      "BE" => "Belgium",
      "BZ" => "Belize",
      "BJ" => "Benin",
      "BM" => "Bermuda",
      "BT" => "Bhutan",
      "BO" => "Bolivia, Plurinational State of",
      "BQ" => "Bonaire, Sint Eustatius and Saba",
      "BA" => "Bosnia and Herzegovina",
      "BW" => "Botswana",
      "BV" => "Bouvet Island",
      "BR" => "Brazil",
      "IO" => "British Indian Ocean Territory",
      "BN" => "Brunei Darussalam",
      "BG" => "Bulgaria",
      "BF" => "Burkina Faso",
      "BI" => "Burundi",
      "KH" => "Cambodia",
      "CM" => "Cameroon",
      "CA" => "Canada",
      "CV" => "Cape Verde",
      "KY" => "Cayman Islands",
      "CF" => "Central African Republic",
      "TD" => "Chad",
      "CL" => "Chile",
      "CN" => "China",
      "CX" => "Christmas Island",
      "CC" => "Cocos (Keeling) Islands",
      "CO" => "Colombia",
      "KM" => "Comoros",
      "CG" => "Congo",
      "CD" => "Congo, the Democratic Republic of the",
      "CK" => "Cook Islands",
      "CR" => "Costa Rica",
      "CI" => "Côte d'Ivoire",
      "HR" => "Croatia",
      "CU" => "Cuba",
      "CW" => "Curaçao",
      "CY" => "Cyprus",
      "CZ" => "Czech Republic",
      "DK" => "Denmark",
      "DJ" => "Djibouti",
      "DM" => "Dominica",
      "DO" => "Dominican Republic",
      "EC" => "Ecuador",
      "EG" => "Egypt",
      "SV" => "El Salvador",
      "GQ" => "Equatorial Guinea",
      "ER" => "Eritrea",
      "EE" => "Estonia",
      "ET" => "Ethiopia",
      "FK" => "Falkland Islands (Malvinas)",
      "FO" => "Faroe Islands",
      "FJ" => "Fiji",
      "FI" => "Finland",
      "FR" => "France",
      "GF" => "French Guiana",
      "PF" => "French Polynesia",
      "TF" => "French Southern Territories",
      "GA" => "Gabon",
      "GM" => "Gambia",
      "GE" => "Georgia",
      "DE" => "Germany",
      "GH" => "Ghana",
      "GI" => "Gibraltar",
      "GR" => "Greece",
      "GL" => "Greenland",
      "GD" => "Grenada",
      "GP" => "Guadeloupe",
      "GU" => "Guam",
      "GT" => "Guatemala",
      "GG" => "Guernsey",
      "GN" => "Guinea",
      "GW" => "Guinea-Bissau",
      "GY" => "Guyana",
      "HT" => "Haiti",
      "HM" => "Heard Island and McDonald Islands",
      "VA" => "Holy See (Vatican City State)",
      "HN" => "Honduras",
      "HK" => "Hong Kong",
      "HU" => "Hungary",
      "IS" => "Iceland",
      "IN" => "India",
      "ID" => "Indonesia",
      "IR" => "Iran, Islamic Republic of",
      "IQ" => "Iraq",
      "IE" => "Ireland",
      "IM" => "Isle of Man",
      "IL" => "Israel",
      "IT" => "Italy",
      "JM" => "Jamaica",
      "JP" => "Japan",
      "JE" => "Jersey",
      "JO" => "Jordan",
      "KZ" => "Kazakhstan",
      "KE" => "Kenya",
      "KI" => "Kiribati",
      "KP" => "Korea, Democratic People's Republic of",
      "KR" => "Korea, Republic of",
      "KW" => "Kuwait",
      "KG" => "Kyrgyzstan",
      "LA" => "Lao People's Democratic Republic",
      "LV" => "Latvia",
      "LB" => "Lebanon",
      "LS" => "Lesotho",
      "LR" => "Liberia",
      "LY" => "Libya",
      "LI" => "Liechtenstein",
      "LT" => "Lithuania",
      "LU" => "Luxembourg",
      "MO" => "Macao",
      "MK" => "Macedonia, the former Yugoslav Republic of",
      "MG" => "Madagascar",
      "MW" => "Malawi",
      "MY" => "Malaysia",
      "MV" => "Maldives",
      "ML" => "Mali",
      "MT" => "Malta",
      "MH" => "Marshall Islands",
      "MQ" => "Martinique",
      "MR" => "Mauritania",
      "MU" => "Mauritius",
      "YT" => "Mayotte",
      "MX" => "Mexico",
      "FM" => "Micronesia, Federated States of",
      "MD" => "Moldova, Republic of",
      "MC" => "Monaco",
      "MN" => "Mongolia",
      "ME" => "Montenegro",
      "MS" => "Montserrat",
      "MA" => "Morocco",
      "MZ" => "Mozambique",
      "MM" => "Myanmar",
      "NA" => "Namibia",
      "NR" => "Nauru",
      "NP" => "Nepal",
      "NL" => "Netherlands",
      "NC" => "New Caledonia",
      "NZ" => "New Zealand",
      "NI" => "Nicaragua",
      "NE" => "Niger",
      "NG" => "Nigeria",
      "NU" => "Niue",
      "NF" => "Norfolk Island",
      "MP" => "Northern Mariana Islands",
      "NO" => "Norway",
      "OM" => "Oman",
      "PK" => "Pakistan",
      "PW" => "Palau",
      "PS" => "Palestinian Territory, Occupied",
      "PA" => "Panama",
      "PG" => "Papua New Guinea",
      "PY" => "Paraguay",
      "PE" => "Peru",
      "PH" => "Philippines",
      "PN" => "Pitcairn",
      "PL" => "Poland",
      "PT" => "Portugal",
      "PR" => "Puerto Rico",
      "QA" => "Qatar",
      "RE" => "Réunion",
      "RO" => "Romania",
      "RU" => "Russian Federation",
      "RW" => "Rwanda",
      "BL" => "Saint Barthélemy",
      "SH" => "Saint Helena, Ascension and Tristan da Cunha",
      "KN" => "Saint Kitts and Nevis",
      "LC" => "Saint Lucia",
      "MF" => "Saint Martin (French part)",
      "PM" => "Saint Pierre and Miquelon",
      "VC" => "Saint Vincent and the Grenadines",
      "WS" => "Samoa",
      "SM" => "San Marino",
      "ST" => "Sao Tome and Principe",
      "SA" => "Saudi Arabia",
      "SN" => "Senegal",
      "RS" => "Serbia",
      "SC" => "Seychelles",
      "SL" => "Sierra Leone",
      "SG" => "Singapore",
      "SX" => "Sint Maarten (Dutch part)",
      "SK" => "Slovakia",
      "SI" => "Slovenia",
      "SB" => "Solomon Islands",
      "SO" => "Somalia",
      "ZA" => "South Africa",
      "GS" => "South Georgia and the South Sandwich Islands",
      "SS" => "South Sudan",
      "ES" => "Spain",
      "LK" => "Sri Lanka",
      "SD" => "Sudan",
      "SR" => "Suriname",
      "SJ" => "Svalbard and Jan Mayen",
      "SZ" => "Swaziland",
      "SE" => "Sweden",
      "CH" => "Switzerland",
      "SY" => "Syrian Arab Republic",
      "TW" => "Taiwan, Province of China",
      "TJ" => "Tajikistan",
      "TZ" => "Tanzania, United Republic of",
      "TH" => "Thailand",
      "TL" => "Timor-Leste",
      "TG" => "Togo",
      "TK" => "Tokelau",
      "TO" => "Tonga",
      "TT" => "Trinidad and Tobago",
      "TN" => "Tunisia",
      "TR" => "Turkey",
      "TM" => "Turkmenistan",
      "TC" => "Turks and Caicos Islands",
      "TV" => "Tuvalu",
      "UG" => "Uganda",
      "UA" => "Ukraine",
      "AE" => "United Arab Emirates",
      "GB" => "United Kingdom",
      "US" => "United States",
      "UM" => "United States Minor Outlying Islands",
      "UY" => "Uruguay",
      "UZ" => "Uzbekistan",
      "VU" => "Vanuatu",
      "VE" => "Venezuela, Bolivarian Republic of",
      "VN" => "Viet Nam",
      "VG" => "Virgin Islands, British",
      "VI" => "Virgin Islands, U.S.",
      "WF" => "Wallis and Futuna",
      "EH" => "Western Sahara",
      "YE" => "Yemen",
      "ZM" => "Zambia",
      "ZW" => "Zimbabwe",
    ];

  }

  public static function checkDistance($object, $user){

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$object->latitude.",".$object->longitude."&destinations=".$user->latitude.",".$user->longitude;

    $json = file_get_contents($url);
    $json = json_decode($json);

    if (is_object($json) && !empty($json->{'rows'})) {

        try {
          $distance = str_replace(' mi', '', $json->{'rows'}[0]->{'elements'}[0]->{'distance'}->{'text'});
          $distance = floatval(str_replace(',', '', $distance));

        } catch (\Exception $e) {
          return false;
        }

        return $distance;
    }

  }

  public static function skillCategories() {

    return [
      '1' => 'Organising Skills',
      '2' => 'Technical Skills',
    ];

  }

  public static function allSkills() {

    $return[1] = Skills::where('category', 1)->get();
    $return[2] = Skills::where('category', 2)->get();

    return $return;

  }

  public static function getLatLongFromCityCountry($town_city, $country) {

    $return = array();

    // Try and get country long name to improve geocoding.
    if (array_key_exists($country, FixometerHelper::getAllCountries())) {
        $country = FixometerHelper::getAllCountries()[$country];
    }
    $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=".urlencode($town_city.','.$country)."&sensor=false&key=".env('GOOGLE_API_CONSOLE_KEY'));
    $json = json_decode($json);

    if (is_object($json) && !empty($json->{'results'})) {
        $return[] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $return[] = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
    }

    return $return;

  }

  public static function footprintReliability() {

    return [
      '1' => 'Very poor',
      '2' => 'Poor',
      '3' => 'Fair',
      '4' => 'Good',
      '5' => 'Very good',
      '6' => 'N/A'
    ];

  }

  public static function categoryCluster() {

    return [
      '1' => 'Computers and Home Office',
      '2' => 'Computers',
      '3' => 'Home Office',
      '4' => 'Test'
    ];

  }

  public static function loginRegisterStats() {

      $Party = new \App\Party;
      $Device = new \App\Device;

      $stats = [];
      if (\Cache::has('all_stats')) {
          $stats = \Cache::get('all_stats');
      } else {
          $stats['allparties'] = $Party->ofThisGroup('admin', true, false);
          $stats['co2Total'] = $Device->getWeights();
          $stats['device_count_status'] = $Device->statusCount();
          \Cache::put('all_stats', $stats, 120);
      }

      return $stats;

  }

  public static function userHasEditGroupPermission($group_id, $user_id, $role = 3) {

      return !empty(\App\UserGroups::where('group', $group_id)
                                  ->where('user', $user_id)
                                    ->where('role', $role)
                                      ->first());

  }

}
