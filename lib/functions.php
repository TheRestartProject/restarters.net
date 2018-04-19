<?php

    /** checks if user has a role
     * var object $user from Auth->getProfile() (Session->getSession())
     * returns boolean
     * */
    function hasRole($user, $role){

        if($user->role == 'Root'){
            return true;
        }
        else {
            if($user->role == ucwords($role)){
                return true;
            }
            else {
                return false;
            }
        }
    }



    /** Prints out Bootstrap alerts
     * finds key of response and
     * uses it to format the alert
     * as wished
     * */
    function printResponse($response){
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

    /** Parse responses from deletion
     * and passes off the info to printResponse
     * */
    function parseResponse($response){
        $res = array();
        $r = explode(':', $response);

        switch($r[0]){
            case 'd':
                if($r[1] == 'err') {
                    $res['danger'] = 'Could not delete the desired element.';
                }
                elseif($r[1] == 'ok') {
                    $res['success'] = 'Element permanently deleted.';
                }
                break;
            default:
                break;
        }
        return $res;
    }

    /**
     * verify that an array index exists and is not empty or null.
     * can also do some type control.
     * */
    function verify($var, $strict = false, $type = 'string'){
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

    /** prints friendly arrays
     * used mainly for debugging
     * */
    function dbga($array){
        echo '<div class="dbg"><pre>';
        print_r($array);
        echo '</pre></div>';
    }

    function dsql($sql){
        echo '<div class="dbg"><pre>';
        echo $sql;
        echo '</pre></div>';
    }

    /**
     * Rearranges an array
     * used to "reflow" the $_FILES array
     * with multiple entries
     * */
    function rearrange($arr){
        foreach($arr as $key => $all){
            foreach($all as $i => $val){
                $new[$i][$key] = $val;
            }
        }
        return $new;
    }


    function reflow($files){
      $reflowed = array();

      foreach($files as $label => $entries) {
        foreach($entries as $entry => $value){
          $reflowed[$entry][$label] = $value['image'];
        }
      }

      return $reflowed;

    }


    /**
     *DateTime printers
     **/
    function engDate($date){
        $date = explode('/', $date);
        $date = $date[1].'/'.$date[0].'/'.$date[2];
        return $date;
    }

    function dateFormat($timestamp){
        return date('D, j M Y, H:i', $timestamp);
    }

    function dateFormatNoTime($timestamp){
        return date('D, j M Y', $timestamp);
    }

    function dbDate($date){
        if(is_int($date)){
            $time = $date;
        }
        else {
            $time = strtotime($date);
        }
        return date('Y-m-d H:i:s', $time);
    }
    function dbDateNoTime($string){
        $d = explode('/', $string);
        return implode('-', array_reverse($d));
    }

    /**
     * make things look pretty.
     * 1 = Fixed
     * 2 = Repairable
     * 3 = End of Lifecycle
     * */
    function parseRepairStatus($status){
        $repClass = 'repair-status ';
        switch($status){
            case 1:
                $repClass .= 'fixed';
                $text = 'f';
                break;
            case 2:
                $repClass .= 'repairable';
                $text = 'r';
                break;
            case 3:
                $repClass .= 'end-of-life';
                $text = 'x';
                break;
            default:
                break;
        }
        echo '<div class="'.$repClass.'">' . $text . '</div>';
    }

    /**
     * Avoid warnings if bars are not set at all
     * */
    function barChartValue($portion, $total){
        if((int)$portion > 0){
            return round((($portion / $total) * 100) , 2) - 15;
        }else {
            return -15 ;
        }
    }


    function featureIsEnabled($feature) {
        return $feature === true;
    }


    /***** I18N *****/

    /** language Switcher **/
    function lng_switcher($print = true){
      global $fixometer_languages;
      $switcher = '<ul class="lng-switcher">';
      foreach($fixometer_languages as $k => $lang){
        $switcher .= '<li><a href="/user/lng/' . $k . '" title="' . $lang . '">' . $k . '</a></li>';
      }
      $switcher .= '</ul>';

      if($print == true){
        echo $switcher;
      }
      else {
        return $switcher;
      }
    }

    /** translator **/

    /****** IMPLEMENT CONTEXT VARIABLE *******/
    function _t($string, $tokens = null, $print = true){
      global $lang;
      global $translations;

      if($lang === DEFAULT_LANG){
        if($print){
          echo $string;
          return true;
        }
        else {
          return $string;
        }
      }

      // check for tokens
      if(!empty($tokens)){
        extract($tokens, EXTR_PREFIX_ALL, 'tfx');
      }

      // get the string
      if(in_array($string, array_keys($translations)) && !empty($translations[$string])){
        $translation = $translations[$string];
      }
      else {
        if(HIGHLIGHT_I18N){
          $translation = '<span class="to-i18n">' . $string . '</span>';
        }
        else {
          $translation = $string;
        }
      }

      if($print){
          echo html_entity_decode($translation, ENT_QUOTES);
        return true;
      }
      else {
          return html_entity_decode($translation, ENT_QUOTES);
      }
    }
