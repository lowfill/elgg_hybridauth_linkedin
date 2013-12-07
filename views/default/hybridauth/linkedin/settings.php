<?php

echo elgg_view('output/url', array(
	'text' => elgg_view_icon('linkedin') . '<span>' . elgg_echo('hybridauth:linkedin:import') . '</span>',
	'href' => 'linkedin',
	'class' => 'elgg-button elgg-button-action'
));

echo elgg_view_form('hybridauth/linkedin/settings');