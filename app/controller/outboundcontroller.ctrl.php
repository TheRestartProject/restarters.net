<?php
	class OutboundController extends Controller {



		public function __construct($model, $controller, $action){

		  parent::__construct($model, $controller, $action);

		  $this->groups = new Group;
			$this->parties = new Party;
			$this->devices = new Device;

			$weights = $this->devices->getWeights();

			$this->TotalWeight = $weights[0]->total_weights;
			$this->TotalEmission = $weights[0]->total_footprints;
			$this->EmissionRatio = $this->TotalEmission / $this->TotalWeight;
    }

		public function index(){

			$counters = array();
			$counters['groups'] = $this->groups->howMany();
			$counters['parties'] = $this->parties->howMany();
			$counters['pax'] = $this->parties->attendees();

			$allParties = $this->parties->ofThisGroup('admin', true, true);
			$hours_volunteered = 0;
			foreach($allParties as $party){
				$hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
			}

			$counters['hours'] = $hours_volunteered;
			$counters['devices'] = $this->devices->howMany();
			$counters['statuses'] = $this->devices->statusCount();
			$counters['most_seen'] = $this->devices->findMostSeen();

			$rates = array();
			$mostseen = array();
			$states = array();
			for($i = 1; $i < 5; $i++){
				$mostseen[$i] = $this->devices->findMostSeen(null, $i);
				$rates['most'][$i] = $this->devices->successRates($i);
				$rates['least'][$i] = $this->devices->successRates($i, 'ASC');
				$states[$i] = $this->devices->clusterCount($i);
			}

			$this->set('mostseen', $mostseen);
			$this->set('counters', $counters);
			$this->set('rates', $rates);
			$this->set('states', $states);

		}




		/** type can be either party or group
		 * id is id of group or party to display
		 * */
		public function info($type, $id){
			if(!is_numeric($id) && !filter_var($id, FILTER_VALIDATE_INT)){
				die('Data not properaly formatted. Exiting.');
			}
			else {
				$info = array();
				$co2 = 0;

				$id = (int)$id;
				$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
				
				if(strtolower($type) == 'party'){
					$weights = $this->devices->getPartyWeights($id);
					$party = $this->parties->findThis($id, true);
					//dbga($party->devices);
					foreach($party->devices as $device){
						if($device->repair_status == DEVICE_FIXED){
							$co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
						}
					}
				}
				elseif(strtolower($type) == 'group'){
					$weights = $this->devices->getWeights($id);
					$allparties = $this->parties->ofThisGroup($id, true, true);
					foreach($allparties as $party){
						foreach($party->devices as $device){
							if($device->repair_status == DEVICE_FIXED){
								$co2 += (!empty($device->estimate) && $device->category == 46 ? ($device->estimate * $this->EmissionRatio) : $device->footprint);
							}
						}
					}

					//$weights[0]->total_footprints = $co2;
				}
				$co2 = $co2 * $this->devices->displacement;

				if($co2 > 6000) {
					$info['consume_class'] 	= 'car';
					$info['consume_image'] 	= 'Counters_C2_Driving.svg';
					$info['consume_label'] 	= 'Equal to driving';
					$info['consume_eql_to'] = (1 / 0.12) * $co2;
					$info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',') . '<small>km</small>';

					$info['manufacture_eql_to'] = round($co2 / 6000);
					$info['manufacture_img'] 	= 'Icons_04_Assembly_Line.svg';
					$info['manufacture_label'] 	= 'or like the manufacture of <span class="dark">' . $info['manufacture_eql_to'] . '</span> cars';
					$info['manufacture_legend'] = ' 6000kg of CO<sub>2</sub>';
				}
				else {
					$info['consume_class'] 	= 'tv';
					$info['consume_image'] 	= 'Counters_C1_TV.svg';
					$info['consume_label'] 	= 'Like watching TV for';
					$info['consume_eql_to'] = ((1 / 0.024) * $co2 ) / 24;
					$info['consume_eql_to'] = number_format(round($info['consume_eql_to']), 0, '.', ',') . ' <small>days</small>';

					$info['manufacture_eql_to'] = round($co2 / 100);
					$info['manufacture_img'] = 'Icons_03_Sofa.svg';
					$info['manufacture_label'] = 'or like the manufacture of <span class="dark">' . $info['manufacture_eql_to'] . '</span> sofas';
					$info['manufacture_legend'] = ' 100kg of CO<sub>2</sub>';
				}
				$this->set('info', $info);
				$this->set('co2', $co2);
			}

		}
	}
