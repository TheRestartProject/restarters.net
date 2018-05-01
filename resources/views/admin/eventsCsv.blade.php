
                    <?php
echo 'Date|Venue|Group|Participants|Volunteers|CO2 (kg)|Weight (kg)|Fixed|Repairable|Dead<br/>';
      if(isset($allparties)) {
                    foreach($allparties as $party){
                    if($party->device_count < 1){
			echo '';
}
		    else {  ?>
                                <?php echo date('Y', $party->event_timestamp); ?><?php echo date('m', $party->event_timestamp); ?><?php echo date('d', $party->event_timestamp); ?>|
                                <?php echo $party->venue; ?>|
                                <?php echo $party->group_name; ?>|
                                <?php echo $party->pax; ?>|
                                <?php echo $party->volunteers; ?>|
                                <?php echo $party->co2; ?>|
                                <?php echo $party->ewaste; ?>|
                                <?php echo $party->fixed_devices; ?>|
                                <?php echo $party->repairable_devices; ?> |
                                <?php echo $party->dead_devices; ?>
<?php echo '<br/>' ?>
                    <?php } ?>
                <?php } ?>
<?php } ?>