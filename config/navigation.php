<?php
// Add Menu-Entry to Main Page
$config['navigation_header']['*']['Personen']['children']['alma'] = array(
			'link' => site_url('extensions/FHC-Core-Alma/Alma'),
			'description' => 'Alma',
			'expand' => true,
			'requiredPermissions' => 'admin:r'
);
