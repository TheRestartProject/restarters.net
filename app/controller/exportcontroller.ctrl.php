<?php

    class ExportController extends Controller {

      public $TotalWeight;
      public $TotalEmission;
      public $EmissionRatio;
      public function __construct($model, $controller, $action){
          parent::__construct($model, $controller, $action);


                  $Device = new Device;
                  $weights = $Device->getWeights();

                  $this->TotalWeight = $weights[0]->total_weights;
                  $this->TotalEmission = $weights[0]->total_footprints;
                  $this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;


      }

        public function devices(){

            $Device = new Device;

            $data = $Device->export();
            foreach($data as $i => $d){

                /** Fix date **/
                $data[$i]['event_date'] = date('d/m/Y', $d['event_timestamp']);
                unset($data[$i]['event_timestamp']);
                /** Readable status **/
                switch($d['repair_status']) {
                    case 1:
                        $data[$i]['repair_status'] = 'Fixed';
                        break;
                    case 2:
                        $data[$i]['repair_status'] = 'Repairable';
                        break;
                    case 3:
                        $data[$i]['repair_status'] = 'End of life';
                        break;
                    default:
                        $data[$i]['repair_status'] = 'Unknown';
                        break;
                }

                /** Spare parts parser **/
                $data[$i]['spare_parts'] = ($d['spare_parts'] == 1 ? 'Yes' : 'No');

                /** clean up linebreaks and commas **/
                $data[$i]['brand'] = '"' . preg_replace( "/\r|\n/", "", str_replace('"', " ",  utf8_encode($d['brand']))) . '"' ;
                $data[$i]['model'] = '"' . preg_replace( "/\r|\n/", "", str_replace('"', " ",  utf8_encode($d['model']))) . '"' ;
                $data[$i]['problem'] = '"' . preg_replace( "/\r|\n/", "", str_replace('"', " ",  utf8_encode($d['problem']))) . '"' ;
                $data[$i]['location'] = '"' . preg_replace( "/\r|\n/", "", utf8_encode($d['location'])) . '"' ;
                $data[$i]['category'] = utf8_encode($d['category']);
                /** empty group ? **/
                $data[$i]['group_name'] = (empty($d['group_name']) ? 'Unknown' : $d['group_name']);

            }
            $header = array(
                        array(
                            'Category',
                            'Brand',
                            'Model',
                            'Comments',
                            'Repair Status',
                            'Spare parts (needed/used)',
                            'Restart Party Location',
                            'Restart Group',
                            'Restart Party Date'
                            )
                        );
            $data = array_merge($header, $data);

            $this->set('data', $data);
        }


        public function parties(){
          $Groups = new Group;
          $Parties = new Party;
          $Device = new Device;
          $Search = new Search;
          unset($_GET['url']);
          if(isset($_GET['fltr']) && !empty($_GET['fltr'])) {
            $searched_groups = null;
            $searched_parties = null;
            $toTimeStamp = null;
            $fromTimeStamp = null;

            /** collect params **/
            if(isset($_GET['groups'])){
              $searched_groups = filter_var_array($_GET['groups'], FILTER_SANITIZE_NUMBER_INT);
            }

            if(isset($_GET['parties'])){
              $searched_parties = filter_var_array($_GET['parties'], FILTER_SANITIZE_NUMBER_INT);
            }

            if(isset($_GET['from-date']) && !empty($_GET['from-date'])){
              if (!DateTime::createFromFormat('d/m/Y', $_GET['from-date'])) {
                $response['danger'] = 'Invalid "from date"';
                $fromTimeStamp = null;
              }
              else {
                $fromDate = DateTime::createFromFormat('d/m/Y', $_GET['from-date']);
                $fromTimeStamp = strtotime($fromDate->format('Y-m-d'));
              }
            }

            if(isset($_GET['to-date']) && !empty($_GET['to-date'])){
              if (!DateTime::createFromFormat('d/m/Y', $_GET['to-date'])) {
                $response['danger'] = 'Invalid "to date"';
              }
              else {
                $toDate = DateTime::createFromFormat('d/m/Y', $_GET['to-date']);
                $toTimeStamp = strtotime($toDate->format('Y-m-d'));
              }
            }

            $PartyList = $Search->parties($searched_parties, $searched_groups, $fromTimeStamp, $toTimeStamp);
            $PartyArray = array();
            foreach($PartyList as $i => $party){

                if($party->device_count == 0){
                    $need_attention++;
                }

                $partyIds[] = $party->idevents;


                $party->co2 = 0;
                $party->weight = 0;
                $party->fixed_devices = 0;
                $party->repairable_devices = 0;
                $party->dead_devices = 0;
                $party->guesstimates = false;

                $participants += $party->pax;
                $party->hours_volunteered = (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
                $hours_volunteered += $party->hours_volunteered;

                foreach($party->devices as $device){



                    switch($device->repair_status){
                        case 1:
                            $party->co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
                            $party->fixed_devices++;
                            //$totalWeight += (!empty($device->estimate) && $device->category==46 ? $device->estimate : $device->weight);
                            $party->weight += (!empty($device->estimate) && $device->category==46 ? $device->estimate : $device->weight);

                            break;
                        case 2:
                            $party->repairable_devices++;
                            break;
                        case 3:
                            $party->dead_devices++;
                            break;
                    }
                    if($device->category == 46){
                        $party->guesstimates = true;
                    }
                }
                $party->co2 = $party->co2 * $Device->displacement;
                $party->weight = $party->weight;

                $totalCO2 += $party->co2;

                $PartyArray[$i] = array(
                  strftime('%d/%m/%Y', $party->event_timestamp),
                  '"' . $party->venue . '"',
                  '"' . $party->name . '"',
                  '"' .($party->pax  > 0 ? $party->pax : "0"). '"',
                  '"' .($party->volunteers  > 0 ? $party->volunteers : "0"). '"',
                  '"' .($party->co2 > 0 ? round($party->co2,2) : "0"). '"',
                  '"' .($party->weight > 0 ? round($party->weight,2) : "0"). '"',
                  '"' .($party->fixed_devices > 0 ? $party->fixed_devices : "0"). '"',
                  '"' .($party->repairable_devices > 0 ? $party->repairable_devices : "0"). '"',
                  '"' .($party->dead_devices > 0 ? $party->dead_devices : "0"). '"',
                  '"' .($party->hours_volunteered > 0 ? $party->hours_volunteered : "0"). '"',
                );
            }

            /** lets format the array **/
            $headers = array(
              array(
                  "Date","Venue","Group","Participants","Volunteers","CO2 (kg)","Weight (kg)","Fixed","Repairable","Dead","Hours Volunteered"
              )
            );
            $data = array_merge($headers, $PartyArray);

            $this->set('data', $data);
        }
      }
    }
