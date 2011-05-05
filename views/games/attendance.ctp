<?php
$this->Html->addCrumb (__('Games', true));
$this->Html->addCrumb (__('Attendance', true));
$this->Html->addCrumb ($team['name']);
?>

<div class="games">
<h2><?php  __('Attendance'); ?></h2>
	<dl>
		<dt><?php __('Game Date'); ?></dt>
		<dd><?php echo $this->ZuluruTime->date($game['GameSlot']['game_date']); ?></dd>
		<dt><?php __('Game Time'); ?></dt>
		<dd><?php echo $this->ZuluruTime->time($game['GameSlot']['game_start']); ?> - <?php echo $this->ZuluruTime->time($game['GameSlot']['display_game_end']); ?></dd>
		<dt><?php __('Team'); ?></dt>
		<dd><?php echo $this->element('team/block', array('team' => $team)); ?></dd>
		<dt><?php __('Opponent'); ?></dt>
		<dd><?php echo $this->element('team/block', array('team' => $opponent)); ?></dd>
		<dt><?php __('Totals'); ?></dt>
		<dd><?php
		// Build the totals
		$statuses = Configure::read('attendance');
		$alt = Configure::read('attendance_alt');
		$count = array_fill_keys(array_keys($statuses), array('Male' => 0, 'Female' => 0));
		foreach ($attendance['Person'] as $person) {
			if (!array_key_exists (0, $person['Attendance']))
				continue;
			$record = $person['Attendance'][0];
			$status = $record['status'];
			++$count[$status][$person['gender']];
		}

		foreach ($statuses as $status => $description) {
			$counts = array();
			foreach (array('Male', 'Female') as $gender) {
				if ($count[$status][$gender]) {
					$counts[] = $count[$status][$gender] . substr (__($gender, true), 0, 1);
				}
			}
			if (!empty ($counts)) {
				$low = low($statuses[$status]);
				$short = $this->ZuluruHtml->icon("attendance_{$low}_24.png", array(
						'title' => sprintf (__('Attendance: %s', true), __($statuses[$status], true)),
						'alt' => $alt[$status],
				));
				echo $short . ': ' . implode(' / ', $counts) . '&nbsp;';
			}
		}
		?></dd>
	</dl>

<div class="related">
	<table cellpadding = "0" cellspacing = "0">
	<thead>
	<tr>
		<th><?php __('Name'); ?></th>
		<th><?php __('Position'); ?></th>
		<th><?php __('Gender'); ?></th>
		<th><?php __('Rating'); ?></th>
		<th><?php __('Attendance'); ?></th>
		<th><?php __('Updated'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$i = 1;
	foreach ($attendance['Person'] as $person):
		if (!array_key_exists (0, $person['Attendance']))
			continue;
		$record = $person['Attendance'][0];
		$status = $record['status'];
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $this->element('people/block', compact('person')); ?></td>
		<td><?php __(Configure::read("options.roster_position.{$person['TeamsPerson']['position']}")); ?></td>
		<td><?php __($person['gender']);?></td>
		<td><?php echo $person['skill_level'];?></td>
		<td class="<?php echo low($statuses[$status]);?>"><?php
			echo $this->element('game/attendance_change', array(
				'team' => $team,
				'game_id' => $game['Game']['id'],
				'game_date' => $game['GameSlot']['game_date'],
				'game_time' => $game['GameSlot']['game_start'],
				'person_id' => $person['id'],
				'position' => $person['TeamsPerson']['position'],
				'status' => $status,
			));
		?></td>
		<td><?php
		if ($record['created'] != $record['updated']) {
			echo $this->ZuluruTime->datetime($record['updated']);
		}
		?></td>
	</tr>
	<?php endforeach; ?>

	</tbody>
	</table>
</div>

<?php echo $this->element('game/attendance_div'); ?>