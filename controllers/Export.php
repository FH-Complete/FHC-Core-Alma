<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Export extends FHC_Controller
{

	private $_ci; // Code igniter instance

	public function __construct()
	{
		parent::__construct();

		$this->_ci =& get_instance();

		$this->_ci->load->model('extensions/FHC-Core-Alma/AlmaProjektarbeit_model', 'AlmaProjektarbeitModel');
		$this->_ci->load->model('education/Paabgabe_model', 'PaabgabeModel');
		$this->_ci->load->config('extensions/FHC-Core-Alma/AlmaProjekt');
	}

	public function getpdf()
	{
		if (!isset($_GET['id']))
			show_error('Fehlerhafte Parameterübergabe');

		$pseudo_id = $_GET['id'];

		if (isEmptyString($pseudo_id))
			show_error('Fehlerhafte Parameterübergabe');

		$result = $this->_ci->AlmaProjektarbeitModel->loadWhere(
			array(
				'pseudo_id' => $pseudo_id,
				'freigeschaltet_datum >=' => date('Y-m-d')
			)
		);

		if (isError($result))
			show_error('Fehlerhafte Parameterübergabe');

		if (hasData($result))
		{
			$data = getData($result);

			$projektArbeitResult = $this->_ci->PaabgabeModel->getEndabgabe($data[0]->projektarbeit_id);
			
			if (isError($projektArbeitResult))
				show_error('Fehlerhafte Parameterübergabe');

			$projektArbeit = getData($projektArbeitResult)[0];

			$this->_exportPDF($projektArbeit->filename);
		}
		else
			show_error('Fehlerhafte Parameterübergabe');
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
			show_error('File does not exist.');
		}
	}

}