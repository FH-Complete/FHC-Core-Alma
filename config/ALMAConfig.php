<?php

// Config XML user identifier
// ---------------------------------------------------------------------------------------------------------------------
$config['user_identifier_match_id'] = '05';
$config['user_identifier_campuscard_id'] = '07';
$config['user_identifier_uid'] = '01';

// Config XML students
// ---------------------------------------------------------------------------------------------------------------------
$config['student_user_group'] = '01';
$config['student_email_type_desc'] = 'campus';

// Config XML employees
// ---------------------------------------------------------------------------------------------------------------------
$config['mitarbeiter_user_group'] = '03';
$config['mitarbeiter_email_type_desc'] = 'work';
$config['mitarbeiter_expiry_date'] = '2099-12-31';  // default expiry date for employees

// Config others
// ---------------------------------------------------------------------------------------------------------------------
$config['filename_prefix'] = '03_ftw';

/**
 * Students of excluded study programs are not inserted into table ALMA and not exported to ALMA.
 */
$config['excluded_study_programs'] = array(
	10001,    // VOCTECH
	10003,    // CISCO Acadamy
	10004,    // Hertha Firnberg Schulen
	10009,    // Alumni Club
	11000     // Reihungstests
);


/**
 * Double Person IDs that should not be merged.
 * E.g. One Employee with two person IDs, one for campus, other for Acadamy.
 * Add both person IDs.
 */
$config['allowed_double_personIDs'] = array(91163, 738, 107, 91170, 91109, 73322, 91108, 39488);


