<?php

// object subtype names for imported data
define('LINKEDIN_POSITION_SUBTYPE', 'employment');
define('LINKEDIN_PROJECT_SUBTYPE', 'project');
define('LINKEDIN_EDUCATION_SUBTYPE', 'education');
define('LINKEDIN_PUBLICATION_SUBTYPE', 'publication');
define('LINKEDIN_PATENT_SUBTYPE', 'patent');
define('LINKEDIN_CERTIFICATION_SUBTYPE', 'certification');
define('LINKEDIN_COURSE_SUBTYPE', 'course');
define('LINKEDIN_VOLUNTEER_SUBTYPE', 'volunteer');
define('LINKEDIN_RECOMMENDATION_SUBTYPE', 'recommendation');

elgg_register_event_handler('init', 'system', 'elgg_hybridauth_linkedin_init');

/**
 * Initialize the plugin
 */
function elgg_hybridauth_linkedin_init() {

	$path = dirname(__FILE__);

	// Page handler to display import data before acting on it
	elgg_register_page_handler('linkedin', 'elgg_hybridauth_linked_page_handler');

	// An action that performs the import
	elgg_register_action('hybridauth/linkedin/import', $path . '/actions/hybridauth/linkedin/import.php');
	elgg_register_action('hybridauth/linkedin/settings', $path . '/actions/hybridauth/linkedin/settings.php');

	// Add some settings to the connected accounts page
	elgg_extend_view('hybridauth/accounts/LinkedIn', 'hybridauth/linkedin/settings');

	// Add linkedin metatags to the list of profile fields
	elgg_register_plugin_hook_handler('profile:fields', 'profile', 'elgg_hybridauth_linkedin_fields');
	elgg_register_plugin_hook_handler('linkedin:fields', 'profile', 'elgg_hybridauth_linkedin_fields');

	// Register widgets for LinkedIn data
	elgg_register_widget_type('employment', elgg_echo('hybridauth:linkedin:widget:employment'), elgg_echo('hybridauth:linkedin:widget:employment:desc'), 'profile', false);
	elgg_register_widget_type('projects', elgg_echo('hybridauth:linkedin:widget:projects'), elgg_echo('hybridauth:linkedin:widget:projects:desc'), 'profile', false);
	elgg_register_widget_type('education', elgg_echo('hybridauth:linkedin:widget:education'), elgg_echo('hybridauth:linkedin:widget:education:desc'), 'profile', false);
	elgg_register_widget_type('publications', elgg_echo('hybridauth:linkedin:widget:publications'), elgg_echo('hybridauth:linkedin:widget:publications:desc'), 'profile', false);
	elgg_register_widget_type('patents', elgg_echo('hybridauth:linkedin:widget:patents'), elgg_echo('hybridauth:linkedin:widget:patents:desc'), 'profile', false);
	elgg_register_widget_type('certification', elgg_echo('hybridauth:linkedin:widget:certification'), elgg_echo('hybridauth:linkedin:widget:certification:desc'), 'profile', false);
	elgg_register_widget_type('courses', elgg_echo('hybridauth:linkedin:widget:courses'), elgg_echo('hybridauth:linkedin:widget:courses:desc'), 'profile', false);
	elgg_register_widget_type('volunteer_experiences', elgg_echo('hybridauth:linkedin:widget:volunteer_experiences'), elgg_echo('hybridauth:linkedin:widget:volunteer_experiences:desc'), 'profile', false);
	elgg_register_widget_type('recommendations', elgg_echo('hybridauth:linkedin:widget:recommendations'), elgg_echo('hybridauth:linkedin:widget:recommendations:desc'), 'profile', false);
}

/**
 * LinkedIn page handler
 *
 * @return boolean
 */
function elgg_hybridauth_linked_page_handler() {

	gatekeeper();

	$ha = new ElggHybridAuth();
	$adapter = $ha->getAdapter('LinkedIn');

	$forward_url = urlencode(current_page_url());
	$auth_url = elgg_http_add_url_query_elements('hybridauth/authenticate', array(
		'provider' => 'LinkedIn',
		'elgg_forward_url' => $forward_url
	));
	if (!$adapter->isUserConnected()) {
		forward($auth_url);
	}

	elgg_push_context('profile_edit');

	$title = elgg_echo('hybridauth:linkedin:import');
	$content = elgg_view_form('hybridauth/linkedin/import');

	$layout = elgg_view_layout('content', array(
		'title' => $title,
		'content' => $content,
		'filter' => false
	));

	echo elgg_view_page($title, $layout);
	return true;
}

/**
 * Setup LinkedIn import and profile fields
 *
 * @param string $hook Equals 'profile:fields' or 'linkedin:fields'
 * @param string $type Equals 'profile'
 * @param array $return An array of current fields
 * @return array An updated array of fields
 */
function elgg_hybridauth_linkedin_fields($hook, $type, $return) {

	// Configure what metadata names will be assigned to imported tags
	$linkedin_metatags = array(
		'summary' => 'description',
		'headline' => 'briefdescription',
		'location' => 'location',
		'industry' => 'industry',
		'associations' => 'associations',
		'interests' => 'interests',
		'languages' => 'languages',
		'skills' => 'skills',
		'honorsAwards' => 'awards',
		'dateOfBirth' => 'birthday',
		'mainAddress' => 'address',
		'phoneNumbers' => 'phone',
		'twitterAccounts' => 'twitter',
	);

	// Map the above fields to their value types
	$linkedin_profile_fields = array(
		'description' => 'longtext',
		'briefdescription' => 'longtext',
		'location' => 'tags',
		'birthday' => 'date',
		'phone' => 'text',
		'address' => 'text',
		'interests' => 'tags',
		'languages' => 'tags',
		'skills' => 'tags',
		'industry' => 'tags',
		'awards' => 'tags',
		'associations' => 'tags',
		'twitter' => 'text',
		'linkedin_url' => 'url', // URL imported by hybridauth
	);

	// A namespace for instant messaging accounts
	// These will result in a metadata such as 'im_accounts:skype'
	// LinkedIn might provide any of the following: aim, gtalk, icq, msn, skype, and/or yahoo
	$im_accounts_ns = 'im_accounts';
	$im_accounts_providers = array('aim', 'gtalk', 'icq', 'msn', 'skype', 'yahoo');
	$linkedin_metatags['imAccounts'] = $im_accounts_ns;

	foreach ($im_accounts_providers as $provider) {
		$linkedin_profile_fields["$im_accounts_ns:$provider"] = 'text';
	}

	if (!is_array($return)) {
		$return = array();
	}

	if ($hook == 'profile:fields') {
		return array_merge($linkedin_profile_fields, $return);
	} else if ($hook == 'linkedin:fields') {
		return array_merge($linkedin_metatags, $return);
	}

	return $return;
}
