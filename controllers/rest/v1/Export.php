<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Export extends API_Controller
{

	private $_ci; // Code igniter instance

	private $_uid;

	public function __construct()
	{
		parent::__construct(array(
			'pdf' => 'extension/alma:rw'
		));

		$this->_ci =& get_instance();

		$this->_ci->load->model('extensions/FHC-Core-Alma/AlmaProjektarbeit_model', 'AlmaProjektarbeitModel');
		$this->_ci->load->model('education/Paabgabe_model', 'PaabgabeModel');
		$this->_ci->load->config('extensions/FHC-Core-Alma/AlmaProjekt');

		$this->_ci->load->helper('hlp_authentication');

		$this->_setAuthUID();
	}

	/**
	 * @return void
	 */
	public function getpdf()
	{
		$pseudo_id = $this->_ci->get('id');

		if (is_null($pseudo_id))
			$this->_ci->response(error('Fehlerhafte Parameterübergabe'), REST_Controller::HTTP_FORBIDDEN);

		$result = $this->_ci->AlmaProjektarbeitModel->loadWhere(
			array(
				'pseudo_id' => $pseudo_id,
				'freigeschaltet_datum >=' => date('Y-m-d')
			)
		);

		if (isError($result))
			$this->_ci->response(error('Fehlerhafte Parameterübergabe'), REST_Controller::HTTP_FORBIDDEN);

		if (hasData($result))
		{
			$data = getData($result);

			$projektArbeitResult = $this->_ci->PaabgabeModel->getEndabgabe($data[0]->projektarbeit_id);
			
			if (isError($projektArbeitResult))
				$this->_ci->response(error('Fehlerhafte Parameterübergabe'), REST_Controller::HTTP_FORBIDDEN);

			$projektArbeit = getData($projektArbeitResult)[0];

			$this->_exportPDF($projektArbeit->filename);
		}
	}

	private function _exportPDF($filename)
	{
		$pdfPath = $this->_ci->config->item('pdf_path');
		$filePath = "$pdfPath/$filename";
		
		if (file_exists($filePath))
		{
			header('Content-type: application/pdf');
			header('Content-Disposition: inline; filename="Document.pdf"');
			header('Content-Length: '. filesize($filePath));
			
			echo file_get_contents($filePath);
		}
		else
		{
			$this->_ci->response(error('File does not exist.'), REST_Controller::HTTP_FORBIDDEN);
		}
	}

	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid)
			$this->_ci->response(error('User authentification failed.'), REST_Controller::HTTP_FORBIDDEN);
	}

}