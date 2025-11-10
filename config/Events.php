<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;
use FHCAPI_Controller as FHCAPI_Controller;

// adds information to projektabgaben, wether in visual library or not
Events::on('in_visual_library', function (&$projektAbgaben) {

	if (!is_array($projektAbgaben)) return;

	$ci =& get_instance();
	$ci->load->model('extensions/FHC-Core-Alma/AlmaProjektarbeit_model', 'AlmaProjektarbeitModel');

	foreach ($projektAbgaben as $abgabe)
	{
		if (!isset($abgabe->projektarbeit_id) || !is_numeric($abgabe->projektarbeit_id)) continue;

		$ci->AlmaProjektarbeitModel->addSelect('1');
		$result = $ci->AlmaProjektarbeitModel->loadWhere(['projektarbeit_id' => $abgabe->projektarbeit_id]);

		$abgabe->in_visual_library = hasData($result);
	}

});
