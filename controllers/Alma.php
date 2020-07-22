<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Alma extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(array(
			'index'=>'admin:rw',
			'export'=>'admin:r'
			)
		);
	}

	/**
	 * Index Controller
	 * @return void
	 */
	public function index()
	{
		$this->load->library('WidgetLib');
		$this->load->view('extensions/FHC-Core-Alma/Alma');
	}

	public function export()
	{
		$params = array('alma_match_id'=>1);

		$this->output->set_content_type('application/xml');
		$this->load->view('extensions/FHC-Core-Alma/export', $params);
	}
}
