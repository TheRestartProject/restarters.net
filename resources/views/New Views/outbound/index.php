<div id="hangovers">
<section id="ho-activity" class="row">
	<div class="col-md-12 col-sm-12 text-center">
		<h2>Activity</h2>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box">
			<h4>Participants</h4>
			<div class="number"><?php echo $counters['pax']; ?></div>
			<div class="link">&nbsp;</div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box">
			<h4>Hours Volunteered</h4>
			<div class="number"><?php echo $counters['hours']; ?></div>
			<div class="link">&nbsp;</div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box">
			<h4>Restart Groups</h4>
			<div class="number"><?php echo $counters['groups']; ?></div>
			<div class="link"><a href="https://therestartproject/group" target="_parent">go to group list</a></div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box">
			<h4>Parties Thrown</h4>
			<div class="number"><?php echo $counters['parties']; ?></div>
			<div class="link"><a href="https://therestartproject/party" target="_parent">go to event calendar</a></div>
		</div>
	</div>
</section>

<section id="ho-devices" class="row">
	<div class="col-md-12 text-center">
		<h2>Devices &amp; Appliances</h2>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box box-blue">
			<h4>Total Attempts</h4>
			<div class="number"><?php echo $counters['devices']; ?></div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box box-fixed">
			<h4>Fixed</h4>
			<div class="number"><?php echo $counters['statuses'][0]->counter; ?></div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box box-repairable">
			<h4>Repairable</h4>
			<div class="number"><?php echo $counters['statuses'][1]->counter; ?></div>
		</div>
	</div>
	<div class="col-md-3 col-sm-3">
		<div class="text-center box box-dead">
			<h4>End-of-life</h4>
			<div class="number"><?php echo $counters['statuses'][2]->counter; ?></div>
		</div>
	</div>
	<div class="col-md-12 text-center">
		<h3>Highest number of attempts</h3>
	</div>
	<div class="col-md-10 col-md-offset-1">		
		<div class="row">
			<div class="col-md-4  col-sm-4 text-center">
				<div class="box box-blue">
					<h4><?php echo $counters['most_seen'][0]->name; ?></h4>
					<div class="number"><?php echo $counters['most_seen'][0]->counter; ?></div>
				</div>
			</div>
			<div class="col-md-4 col-sm-4 text-center">
				<div class="box box-blue">
					<h4><?php echo $counters['most_seen'][1]->name; ?></h4>
					<div class="number"><?php echo $counters['most_seen'][1]->counter; ?></div>
				</div>
			</div>
			<div class="col-md-4 col-sm-4 text-center">
				<div class="box box-blue">
					<h4><?php echo $counters['most_seen'][2]->name; ?></h4>
					<div class="number"><?php echo $counters['most_seen'][2]->counter; ?></div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="row" id="ho-rates">
	<div class="col-md-12">
	
			<table class="table table-striped">
				<thead>
					<tr>
						<th></th>
						<th>FIXED</th>
						<th>REPAIRABLE</th>
						<th>END-OF-LIFE</th>
						<th>MOST SEEN</th>
						<th>HIGHEST SUCCESS RATE</th>
						<th>LOWEST SUCCESS RATE</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Computers and Home Office</td>
						<td class="state state-fixed"><?php echo $states[1][0]->counter; ?></td>
						<td class="state state-repairable"><?php echo $states[1][1]->counter; ?></td>
						<td class="state state-dead"><?php echo $states[1][2]->counter; ?></td>
						<td>
							<h5><?php echo $mostseen[1][0]->name; ?></h5>
							<div class="number"><?php echo $mostseen[1][0]->counter; ?></div>
							<div class="aggregate">&nbsp;</div>
						</td>
						<td>
							<h5><?php echo $rates['most'][1][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['most'][1][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['most'][1][0]->fixed . '/' . $rates['most'][1][0]->total_devices; ?></div>
						</td>
						<td>
							<h5><?php echo $rates['least'][1][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['least'][1][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['least'][1][0]->fixed . '/' . $rates['least'][1][0]->total_devices; ?></div>
						</td>
					</tr>
					<tr>
						<td>Electronic Gadgets</td>
						<td class="state state-fixed"><?php echo $states[2][0]->counter; ?></td>
						<td class="state state-repairable"><?php echo $states[2][1]->counter; ?></td>
						<td class="state state-dead"><?php echo $states[2][2]->counter; ?></td>
						<td>
							<h5><?php echo $mostseen[2][0]->name; ?></h5>
							<div class="number"><?php echo $mostseen[2][0]->counter; ?></div>
							<div class="aggregate">&nbsp;</div>
						</td>
						<td>
							<h5><?php echo $rates['most'][2][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['most'][2][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['most'][2][0]->fixed . '/' . $rates['most'][2][0]->total_devices; ?></div>
						</td>
						<td>
							<h5><?php echo $rates['least'][2][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['least'][2][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['least'][2][0]->fixed . '/' . $rates['least'][2][0]->total_devices; ?></div>
						</td>
					</tr>
					<tr>
						<td>Home Entertainment</td>
						<td class="state state-fixed"><?php echo $states[3][0]->counter; ?></td>
						<td class="state state-repairable"><?php echo $states[3][1]->counter; ?></td>
						<td class="state state-dead"><?php echo $states[3][2]->counter; ?></td>
						<td>
							<h5><?php echo $mostseen[3][0]->name; ?></h5>
							<div class="number"><?php echo $mostseen[3][0]->counter; ?></div>
							<div class="aggregate">&nbsp;</div>
						</td>
						<td>
							<h5><?php echo $rates['most'][3][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['most'][3][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['most'][3][0]->fixed . '/' . $rates['most'][3][0]->total_devices; ?></div>
						</td>
						<td>
							<h5><?php echo $rates['least'][3][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['least'][3][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['least'][3][0]->fixed . '/' . $rates['least'][3][0]->total_devices; ?></div>
						</td>
					</tr>
					<tr>
						<td>Kitchen and Household Items</td>
						<td class="state state-fixed"><?php echo $states[4][0]->counter; ?></td>
						<td class="state state-repairable"><?php echo $states[4][1]->counter; ?></td>
						<td class="state state-dead"><?php echo $states[4][2]->counter; ?></td>
						<td>
							<h5><?php echo $mostseen[4][0]->name; ?></h5>
							<div class="number"><?php echo $mostseen[4][0]->counter; ?></div>
							<div class="aggregate">&nbsp;</div>
						</td>
						<td>
							<h5><?php echo $rates['most'][4][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['most'][4][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['most'][4][0]->fixed . '/' . $rates['most'][4][0]->total_devices; ?></div>
						</td>
						<td>
							<h5><?php echo $rates['least'][4][0]->category_name; ?></h5>
							<div class="number"><?php echo $rates['least'][4][0]->success_rate; ?>%</div>
							<div class="aggregate"><?php echo $rates['least'][4][0]->fixed . '/' . $rates['least'][4][0]->total_devices; ?></div>
						</td>
					</tr>
				</tbody>
			</table>
		
		
	</div>
</section>


</div>
