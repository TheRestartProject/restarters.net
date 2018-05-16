<?php

namespace App\Helpers;

use App;
use Auth;

class FixometerHelper {

  public static function allAges() {

    return [
      'N/A'   => 'N/A',
      '16-20' => '16-20',
      '20-30' => '20-30',
      '30-40' => '30-40',
      '40-50' => '40-50',
      '50-60' => '50-60',
      '60-70' => '60-70',
    ];

  }

  /** checks if user has a role **/
  public static function hasRole($user, $role){

        if($user->role()->first()->role == 'Root'){
            return true;
        }
        else {
            if($user->role()->first()->role == ucwords($role)){
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
          if (empty(DB::table('event_users')->where('event', $partyId)->where('user', $userId)->first())) {
            return false;
          } else {
            return true;
          }
        } else {
          return false;
        }
      }
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
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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


}

?>
