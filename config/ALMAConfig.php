<?php

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


