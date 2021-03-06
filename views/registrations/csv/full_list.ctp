<?php
$fp = fopen('php://output','w+');
$header = array(__('User ID', true));
$fields = array(
	'first_name' => 'First Name',
	'last_name' => 'Last Name',
	'email' => 'Email Address',
	'alternate_email' => 'Alternate Email Address',
	'addr_street' => 'Address',
	'addr_city' => 'City',
	'addr_prov' => 'Province',
	'addr_postalcode' => 'Postal Code',
	'home_phone' => 'Home Phone',
	'work_phone' => 'Work Phone',
	'work_ext' => 'Work Ext',
	'mobile_phone' => 'Mobile Phone',
	'gender' => 'Gender',
	'birthdate' => 'Birthdate',
	'height' => 'Height',
	'skill_level' => array('name' => 'Skill Level', 'model' => 'Skill'),
	'shirt_size' => 'Shirt Size',
	'alternate_first_name' => 'Alternate First Name',
	'alternate_last_name' => 'Alternate Last Name',
	'alternate_work_phone' => 'Alternate Work Phone',
	'alternate_work_ext' => 'Alternate Work Ext',
	'alternate_mobile_phone' => 'Alternate Mobile Phone',
);
// Skip fields that are all blank or disabled
$player_fields = $fields;
foreach ($player_fields as $field => $name) {
	$short_field = str_replace('alternate_', '', $field);
	if ($short_field == 'email') {
		$include = true;
	} else if ($short_field == 'work_ext') {
		$include = Configure::read('profile.work_phone');
	} else {
		$include = Configure::read("profile.$short_field");
	}
	if ($include) {
		if (is_array($name)) {
			$values = array_unique(Set::extract("/Person/{$name['model']}/$field", $registrations));
		} else {
			$values = array_unique(Set::extract("/Person/$field", $registrations));
		}
		if (count($values) > 1 || !empty($values[0])) {
			if (is_array($name)) {
				$name = $name['name'];
			}
			$header[] = __($name, true);
		} else {
			unset($player_fields[$field]);
		}
	} else {
		// Disabled fields are disabled for players and relatives
		unset($fields[$field]);
		unset($player_fields[$field]);
	}
}
$header[] = __('Order ID', true);
$header[] = __('Created Date', true);
$header[] = __('Modified Date', true);
$header[] = __('Payment Status', true);
$header[] = __('Total Amount', true);
$header[] = __('Amount Paid', true);
if (count($event['Price']) > 1) {
	$header[] = __('Price Point', true);
}
if (Configure::read('registration.online_payments')) {
	$header[] = __('Transaction ID', true);
}
$header[] = __('Notes', true);
foreach ($event['Questionnaire']['Question'] as $question) {
	if (!array_key_exists('anonymous', $question) || !$question['anonymous']) {
		if (in_array ($question['type'], array('text', 'textbox', 'radio', 'select'))) {
			if (array_key_exists('name', $question)) {
				$header[] = $question['name'];
			} else {
				$header[] = $question['question'];
			}
		} else if ($question['type'] == 'checkbox') {
			if (!empty($question['Answer'])) {
				foreach ($question['Answer'] as $answer) {
					$header[] = $answer['answer'];
				}
			} else {
				$header[] = $question['question'];
			}
		}
	}
}

// Check if we need to include relative contact info
$relatives = 0;
$contact_fields = $fields;
foreach (array('gender', 'birthdate', 'height', 'skill_level', 'shirt_size') as $field) {
	unset($contact_fields[$field]);
}
$contact_fields_required = array();
foreach ($registrations as $registration) {
	if (empty($registration['Person']['user_id']) || AppController::_isChild($registration['Person']['birthdate'])) {
		$relatives = max($relatives, count($registration['Person']['Related']));
		foreach ($registration['Person']['Related'] as $i => $relative) {
			foreach (array_keys($contact_fields) as $field) {
				if (!empty($relative[$field])) {
					$contact_fields_required[$i][$field] = true;
				}
			}
		}
	}
}
if ($relatives > 0) {
	$header1 = array_fill(0, count($header), '');
	for ($i = 0; $i < $relatives; ++ $i) {
		foreach ($contact_fields as $field => $name) {
			if (!empty($contact_fields_required[$i][$field])) {
				if (is_array($name)) {
					$name = $name['name'];
				}
				$header[] = __($name, true);
			}
		}

		$header1[] = sprintf(__('Contact %s', true), $i + 1);
		$header1 = array_merge($header1, array_fill(0, array_sum($contact_fields_required[$i]) - 1, ''));
	}

	fputcsv($fp, $header1);
}

fputcsv($fp, $header);

$order_id_format = Configure::read('registration.order_id_format');

foreach($registrations as $registration) {
	$row = array($registration['Person']['id']);
	foreach ($player_fields as $field => $name) {
		if (is_array($name)) {
			if (array_key_exists($field, $registration['Person'][$name['model']])) {
				$row[] = $registration['Person'][$name['model']][$field];
			} else if (array_key_exists($field, $registration['Person'][$name['model']][0])) {
				$row[] = $registration['Person'][$name['model']][0][$field];
			} else {
				$row[] = '';
			}
		} else {
			if (array_key_exists($field, $registration['Person'])) {
				$row[] = $registration['Person'][$field];
			} else {
				$row[] = '';
			}
		}
	}
	$row[] = sprintf ($order_id_format, $registration['Registration']['id']);
	$row[] = $registration['Registration']['created'];
	$row[] = $registration['Registration']['modified'];
	$row[] = $registration['Registration']['payment'];
	$row[] = $registration['Registration']['total_amount'];
	$row[] = array_sum(Set::extract('/Payment/payment_amount', $registration));
	if (count($event['Price']) > 1) {
		$row[] = $event['Price'][$registration['Registration']['price_id']]['name'];
	}
	if (Configure::read('registration.online_payments')) {
		$row[] = implode(';', array_unique(Set::extract('/Payment/RegistrationAudit/transaction_id', $registration)));
	}
	$row[] = $registration['Registration']['notes'];
	foreach ($event['Questionnaire']['Question'] as $question) {
		if (!array_key_exists('anonymous', $question) || !$question['anonymous']) {
			if (in_array ($question['type'], array('text', 'textbox', 'radio', 'select'))) {
				$answer = reset(Set::extract ("/Response[question_id={$question['id']}]/.", $registration));
				if (!empty ($answer['answer_id'])) {
					$answer = reset(Set::extract ("/Answer[id={$answer['answer_id']}]/.", $question));
				}
				$row[] = $answer['answer'];
			} else if ($question['type'] == 'checkbox') {
				if (!empty($question['Answer'])) {
					foreach ($question['Answer'] as $answer) {
						$answers = Set::extract ("/Response[question_id={$question['id']}][answer_id={$answer['id']}]/.", $registration);
						$row[] = empty ($answers) ? __('No', true) : __('Yes', true);
					}
				} else {
					// Auto questions may fall into this category
					$answers = Set::extract ("/Response[question_id={$question['id']}][answer_id=1]/.", $registration);
					$row[] = empty ($answers) ? __('No', true) : __('Yes', true);
				}
			}
		}
	}

	if ($relatives > 0 && (empty($registration['Person']['user_id']) || AppController::_isChild($registration['Person']['birthdate']))) {
		foreach ($registration['Person']['Related'] as $i => $relative) {
			foreach (array_keys($contact_fields) as $field) {
				if (!empty($contact_fields_required[$i][$field])) {
					if (array_key_exists($field, $relative)) {
						$row[] = $relative[$field];
					} else {
						$row[] = '';
					}
				}
			}
		}
	}

	fputcsv($fp, $row);
}

fclose($fp);

?>
