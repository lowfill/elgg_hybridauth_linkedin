<?php

$user = elgg_get_logged_in_user_entity();

$tags = get_input('tags');

if ($tags) {
	foreach ($tags as $tag => $details) {

		$tag_import = elgg_extract('import', $details, false);

		if ($tag_import != 'yes') {
			continue;
		}

		$action = 'update_tags';

		$tag_name = elgg_extract('name', $details, $tag);
		$tag_values = elgg_extract('value', $details, '');
		$tag_access = elgg_extract('access_id', $details, ACCESS_PRIVATE);

		if (!$tag_values) {
			$error = true;
			continue;
		}

		if (is_array($tag_values)) {
			if (!is_array($tag_name)) {
				elgg_delete_metadata(array(
					'guids' => $user->guid,
					'metadata_names' => $tag_name,
					'limit' => 0
				));
				foreach ($tag_values as $tag_value) {
					$id = create_metadata($user->guid, $tag_name, $tag_value, '', $user->guid, $tag_access, true);
					if (!$id) {
						$error = true;
					}
				}
			} else {
				for ($i = 0; $i < count($tag_values); $i++) {
					$tag_name_part = elgg_extract($i, $tag_name, $tag_name[0]);
					$id = create_metadata($user->guid, $tag_name_part, $tag_values[$i], '', $user->guid, $tag_access);
					if (!$id) {
						$error = true;
					}
				}
			}
		} else {
			$id = create_metadata($user->guid, $tag_name, $tag_values, '', $user->guid, $tag_access);
			if (!$id) {
				$error = true;
			}
		}
	}

	if ($action == 'update_tags') {
		if ($error) {
			register_error(elgg_echo('hybridauth:linkedin:general:error', array($tag_name)));
		} else {
			system_message(elgg_echo('hybridauth:linkedin:general:success'));
		}
	}
}


$ha = new ElggHybridAuth();
$adapter = $ha->getAdapter('LinkedIn');
$adapter->adapter->api->setResponseFormat('JSON');

$access_input = get_input('accesslevel');


$positions_input = get_input('positions');

if (is_array($positions_input)) {

	$user_positions = elgg_get_entities_from_metadata(array(
		'types' => 'object',
		'subtypes' => LINKEDIN_POSITION_SUBTYPE,
		'owner_guid' => $user->guid,
		'metadata_names' => 'linkedin_id',
		'limit' => false
	));

	$linkedin = array();
	if ($user_positions) {
		foreach ($user_positions as $user_position) {
			$linkedin[$user_position->linkedin_id] = $user_position;
		}
	}

	$positions_api_result = $adapter->adapter->api->profile("~:(positions)");
	$positions_json_result = $positions_api_result['linkedin'];
	$positions = json_decode($positions_json_result);

	foreach ($positions->positions->values as $position) {
		if (!in_array($position->id, $positions_input)) {
			continue;
		}

		if ($linkedin[$position->id]) {
			$action = 'update';
			$object = $linkedin[$position->id];
		} else {
			$action = 'import';
			$object = new ElggObject();
			$object->subtype = LINKEDIN_POSITION_SUBTYPE;
			$object->owner_guid = $user->guid;
			$object->access_id = elgg_extract('positions', $access_input, get_default_access($user));

			$object->linkedin_id = $position->id;
		}

		$object->title = $position->title;
		$object->description = $position->summary;

		$object->calendar_start_month = $position->startDate->month;
		$object->calendar_start_year = $position->startDate->year;

		if ($position->isCurrent) {
			$object->is_current = true;
			$object->calendar_end_month = false;
			$object->calendar_end_year = false;
		} else {
			$object->is_current = false;
			$object->calendar_end_month = $position->endDate->month;
			$object->calendar_end_year = $position->endDate->year;

			if ($action == 'import') {
				create_metadata($user->guid, 'past_title', $position->title, '', $user->guid, elgg_extract('positions', $access_input, get_default_access($user)), true);
				create_metadata($user->guid, 'past_company', $position->company->name, '', $user->guid, elgg_extract('positions', $access_input, get_default_access($user)), true);
			}
		}

		$object->company = $position->company->name;
		$object->company_industry = $position->company->industry;
		$object->company_type = $position->company->type;
		$object->company_size = $position->company->size;
		$object->company_linkedin_id = $position->company->id;

		if ($object->save()) {
			system_message(elgg_echo('hybridauth:linkedin:position:success:' . $action, array($object->title)));
		} else {
			system_message(elgg_echo('hybridauth:linkedin:position:error', array($object->title)));
		}
	}
}


$projects_input = get_input('projects');

if (is_array($projects_input)) {

	$user_projects = elgg_get_entities_from_metadata(array(
		'types' => 'object',
		'subtypes' => LINKEDIN_PROJECT_SUBTYPE,
		'owner_guid' => $user->guid,
		'metadata_names' => 'linkedin_id',
		'limit' => false
	));

	$linkedin = array();
	if ($user_projects) {
		foreach ($user_projects as $user_project) {
			$linkedin[$user_project->linkedin_id] = $user_project;
		}
	}

	$projects_api_result = $adapter->adapter->api->profile("~:(projects)");
	$projects_json_result = $projects_api_result['linkedin'];
	$projects = json_decode($projects_json_result);

	foreach ($projects->projects->values as $project) {
		if (!in_array($project->id, $projects_input)) {
			continue;
		}

		if ($linkedin[$project->id]) {
			$action = 'update';
			$object = $linkedin[$project->id];
		} else {
			$action = 'import';
			$object = new ElggObject();
			$object->subtype = LINKEDIN_PROJECT_SUBTYPE;
			$object->owner_guid = $user->guid;
			$object->access_id = elgg_extract('projects', $access_input, get_default_access($user));

			$object->linkedin_id = $project->id;
		}

		$object->title = $project->name;
		$object->description = $project->description;
		$object->address = $project->url;

		if ($object->save()) {
			system_message(elgg_echo('hybridauth:linkedin:project:success:' . $action, array($object->title)));
		} else {
			system_message(elgg_echo('hybridauth:linkedin:project:error', array($object->title)));
		}
	}
}


$educations_input = get_input('educations');

if (is_array($educations_input)) {

	$user_educations = elgg_get_entities_from_metadata(array(
		'types' => 'object',
		'subtypes' => LINKEDIN_EDUCATION_SUBTYPE,
		'owner_guid' => $user->guid,
		'metadata_names' => 'linkedin_id',
		'limit' => false
	));

	$linkedin = array();
	if ($user_educations) {
		foreach ($user_educations as $user_education) {
			$linkedin[$user_education->linkedin_id] = $user_education;
		}
	}

	$educations_api_result = $adapter->adapter->api->profile("~:(educations)");
	$educations_json_result = $educations_api_result['linkedin'];
	$educations = json_decode($educations_json_result);

	foreach ($educations->educations->values as $education) {
		if (!in_array($education->id, $educations_input)) {
			continue;
		}

		if ($linkedin[$education->id]) {
			$action = 'update';
			$object = $linkedin[$education->id];
		} else {
			$action = 'import';
			$object = new ElggObject();
			$object->subtype = LINKEDIN_EDUCATION_SUBTYPE;
			$object->owner_guid = $user->guid;
			$object->access_id = elgg_extract('educations', $access_input, get_default_access($user));

			$object->linkedin_id = $education->id;
		}

		$object->title = $education->schoolName;
		$object->description = $education->fieldOfStudy;
		$object->degree = $education->degree;
		$object->activities = $education->activities;
		$object->notes = $education->notes;

		$object->calendar_start_year = $education->startDate->year;
		$object->calendar_end_year = $education->endDate->year;

		if ($education->degree) {
			$label = "$education->degree, ";
		}
		$label .= "$education->fieldOfStudy, $education->schoolName";

		if ($action == 'import') {
			create_metadata($user->guid, 'school', $label, 'text', $user->guid, elgg_extract('educations', $access_input, get_default_access($user)), true);
		}

		if ($object->save()) {
			system_message(elgg_echo('hybridauth:linkedin:education:success:' . $action, array($object->title)));
		} else {
			system_message(elgg_echo('hybridauth:linkedin:education:error', array($object->title)));
		}
	}
}

forward('profile');
