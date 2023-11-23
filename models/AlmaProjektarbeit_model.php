<?php

class AlmaProjektarbeit_model extends DB_Model
{
	/** Constructor */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'sync.tbl_alma_projektarbeit';
	}
}