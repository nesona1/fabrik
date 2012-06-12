<?php
/**
* @version		$Id: pdf.php 14401 2010-01-26 14:10:00Z louis $
* @package		Joomla.Framework
* @subpackage	Document
* @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.php for copyright notices and details.
*/

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

require_once(JPATH_LIBRARIES .'/joomla/document/html/html.php');

/**
 * DocumentPDF class, provides an easy interface to parse and display a pdf document
 *
 * @package		Joomla.Framework
 * @subpackage	Document
 * @since		1.5
 */
class JDocumentpdf extends JDocumentHTML
{
	private $engine	= null;

	var $_name = 'joomla';

	var $_header = null;

	var $_margin_header	= 5;
	var $_margin_footer	= 10;
	var $_margin_top	= 27;
	var $_margin_bottom	= 25;
	var $_margin_left	= 15;
	var $_margin_right	= 15;

	// Scale ratio for images [number of points in user unit]
	var $_image_scale	= 4;
	
	private $renderer = null;
	
	/**
	 * Class constructore
	 *
	 * @access protected
	 * @param	array	$options Associative array of options
	 */
	function __construct($options = array())
	{
		//set mime type
		$this->_mime = 'application/pdf';
		
		//set document type
		$this->_type = 'pdf';
		
		parent::__construct($options);

		if (!$this->iniDomPdf())
		{
			if (!$this->iniTcpdf())
			{
				JError::raiseError(JText::_('COM_FABRIK_ERR_NO_PDF_LIB_FOUND'));
			}
		}
	}
	
	protected function iniDomPdf()
	{
		$file = JPATH_LIBRARIES .'/dompdf/dompdf_config.inc.php';
		if (!JFile::exists($file))
		{
			return false;
		}
		require_once($file);
		$this->renderer = 'dompdf';
		// Default settings are a portrait layout with an A4 configuration using millimeters as units
		$this->engine =new DOMPDF();
		return true;
	}
	
	protected function iniTcpdf()
	{
		if (!jimport('tcpdf.tcpdf'))
		{
			return false;
		}
		$this->renderer = 'tcpdf';
		if (isset($options['margin-header'])) {
			$this->_margin_header = $options['margin-header'];
		}
		
		if (isset($options['margin-footer'])) {
			$this->_margin_footer = $options['margin-footer'];
		}
		
		if (isset($options['margin-top'])) {
			$this->_margin_top = $options['margin-top'];
		}
		
		if (isset($options['margin-bottom'])) {
			$this->_margin_bottom = $options['margin-bottom'];
		}
		
		if (isset($options['margin-left'])) {
			$this->_margin_left = $options['margin-left'];
		}
		
		if (isset($options['margin-right'])) {
			$this->_margin_right = $options['margin-right'];
		}
		
		if (isset($options['image-scale'])) {
			$this->_image_scale = $options['image-scale'];
		}
		
		
		/*
		 * Setup external configuration options
		*/
		define('K_TCPDF_EXTERNAL_CONFIG', true);
		
		// Path options
		
		// Installation path
		 define("K_PATH_MAIN", JPATH_LIBRARIES . 'tcpdf');
		 /*
		// URL path
		define("K_PATH_URL", JPATH_BASE);
		
		// Fonts path
		define("K_PATH_FONTS", JPATH_SITE . '/libraries/tcpdf/fonts/');
		
		// Cache directory path
		$config = JFactory::getConfig();
		define("K_PATH_CACHE", $config->get('tmp_path') . '/');
		
		// Cache URL path
		define("K_PATH_URL_CACHE", K_PATH_URL . '/libraries/tcpdf/fonts/');
		
		// Images path
		define("K_PATH_IMAGES", K_PATH_MAIN. '/libraries/tcpdf/images/');
		
		// Blank image path
		define("K_BLANK_IMAGE", K_PATH_IMAGES . '/_blank.png');
		
		// Format options
		
		// Cell height ratio
		define("K_CELL_HEIGHT_RATIO", 1.25);
		
		// Magnification scale for titles
		define("K_TITLE_MAGNIFICATION", 1.3);
		
		// Reduction scale for small font
		define("K_SMALL_RATIO", 2/3);
		
		// Magnication scale for head
		define("HEAD_MAGNIFICATION", 1.1); */
		
		/*
		 * Create the pdf document
		*/
		
		// Default settings are a portrait layout with an A4 configuration using millimeters as units
		$this->engine = new TCPDF();
		
		//set margins
		$this->engine->SetMargins($this->_margin_left, $this->_margin_top, $this->_margin_right);
		//set auto page breaks
		$this->engine->SetAutoPageBreak(TRUE, $this->_margin_bottom);
		$this->engine->SetHeaderMargin($this->_margin_header);
		$this->engine->SetFooterMargin($this->_margin_footer);
		$this->engine->setImageScale($this->_image_scale);
		return true;
	}

	 /**
	 * Sets the document name
	 *
	 * @param   string   $name	Document name
	 * @access  public
	 * @return  void
	 */
	function setName($name = 'joomla') {
		$this->_name = $name;
	}

	/**
	 * Returns the document name
	 *
	 * @access public
	 * @return string
	 */
	 function getName() {
		return $this->_name;
	} 

	protected function renderDomPdf()
	{
		$dompdf = $this->engine;
		$data = parent::render();
		//echo $data;exit;
		$dompdf->load_html($data);
		$dompdf->render();
		$dompdf->stream($this->getName() . '.pdf');
		return '';
	}
	
	protected function renderTcpdf()
	{
		$current_level = error_reporting();
		error_reporting(0);
		$pdf = $this->engine;
		// Set PDF Metadata
		$pdf->SetCreator($this->getGenerator());
		$pdf->SetTitle($this->getTitle());
		$pdf->SetSubject($this->getDescription());
		$pdf->SetKeywords($this->getMetaData('keywords'));
		
		// Set PDF Header data
		//$pdf->setHeaderData('',0,$this->getTitle(), $this->getHeader());
		
		// Set PDF Header and Footer fonts
		$lang = JFactory::getLanguage();
		$font = 'freesans';
		
		$pdf->setRTL($lang->isRTL());
		
		$pdf->setHeaderFont(array($font, '', 10));
		$pdf->setFooterFont(array($font, '', 8));
		//$pdf->SetFont('times');
		// Initialize PDF Document
		$pdf->AliasNbPages();
		$pdf->AddPage();
		
		// Build the PDF Document string from the document buffer
		//$pdf->WriteHTML($this->getBuffer(), true);
		
		$data = parent::render();
		$pdf->WriteHTML($data, true);
		$data = $pdf->Output('test.pdf', 'I');
		return $data;
	}

	/**
	 * Render the document.
	 * @access public
	 * @param boolean 	$cache		If true, cache the output
	 * @param array		$params		Associative array of attributes
	 * @return 	The rendered data
	 */
	
	function render($cache = false, $params = array())
	{
		$this->setCss();
		
		//echo "<pre>";print_r($this->_styleSheets);
		//JResponse::setHeader('Content-disposition', 'inline; filename="' . $this->getName() . '.pdf"', true);
		
		// Set document type headers
		//parent::render();

		$data = $this->renderer == 'tcpdf' ? $this->renderTcpdf() : $this->renderDomPdf();
		//Close and output PDF document
		return $data;
	}
	
	private function setCss()
	{
		$uri = JUri::getInstance();
		$host =  $uri->getScheme() . '://' . $uri->getHost();
		foreach ($this->_styleSheets as $path => $sheet)
		{
			if (!strstr($path, $host))
			{
				$newPath = $host . $path;
				$this->_styleSheets[$newPath] = $sheet;
				unset($this->_styleSheets[$path]);
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see JDocumentHTML::getBuffer()
	 */
	
  	public function getBuffer($type = null, $name = null, $attribs = array())
	{
		if ($type == 'head' || $type == 'component')
		{
			return parent::getBuffer($type, $name, $attribs);
		}
		else
		{
			return '';
		}
	} 

}