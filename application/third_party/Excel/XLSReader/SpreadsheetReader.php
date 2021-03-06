<?php

class Spreadsheet_Excel_Reader {
	// ParseXL definitions
	const XLS_BIFF8						= 0x0600;
	const XLS_BIFF7						= 0x0500;
	const XLS_WorkbookGlobals			= 0x0005;
	const XLS_Worksheet					= 0x0010;
	
	// record identifiers
	const XLS_Type_FORMULA				= 0x0006;
	const XLS_Type_FORMULA2				= 0x0406;
	const XLS_Type_EOF					= 0x000a;
	const XLS_Type_PROTECT				= 0x0012;
	const XLS_Type_OBJECTPROTECT		= 0x0063;
	const XLS_Type_SCENPROTECT			= 0x00dd;
	const XLS_Type_PASSWORD				= 0x0013;
	const XLS_Type_HEADER				= 0x0014;
	const XLS_Type_FOOTER				= 0x0015;
	const XLS_Type_EXTERNSHEET			= 0x0017;
	const XLS_Type_DEFINEDNAME			= 0x0018;
	const XLS_Type_VERTICALPAGEBREAKS	= 0x001a;
	const XLS_Type_HORIZONTALPAGEBREAKS	= 0x001b;
	const XLS_Type_NOTE					= 0x001c;
	const XLS_Type_SELECTION			= 0x001d;
	const XLS_Type_DATEMODE				= 0x0022;
	const XLS_Type_EXTERNNAME			= 0x0023;
	const XLS_Type_LEFTMARGIN			= 0x0026;
	const XLS_Type_RIGHTMARGIN			= 0x0027;
	const XLS_Type_TOPMARGIN			= 0x0028;
	const XLS_Type_BOTTOMMARGIN			= 0x0029;
	const XLS_Type_PRINTGRIDLINES		= 0x002b;
	const XLS_Type_FILEPASS				= 0x002f;
	const XLS_Type_FONT					= 0x0031;
	const XLS_Type_CONTINUE				= 0x003c;
	const XLS_Type_PANE					= 0x0041;
	const XLS_Type_CODEPAGE				= 0x0042;
	const XLS_Type_DEFCOLWIDTH 			= 0x0055;
	const XLS_Type_OBJ					= 0x005d;
	const XLS_Type_COLINFO				= 0x007d;
	const XLS_Type_IMDATA				= 0x007f;
	const XLS_Type_SHEETPR				= 0x0081;
	const XLS_Type_HCENTER				= 0x0083;
	const XLS_Type_VCENTER				= 0x0084;
	const XLS_Type_SHEET				= 0x0085;
	const XLS_Type_PALETTE				= 0x0092;
	const XLS_Type_SCL					= 0x00a0;
	const XLS_Type_PAGESETUP			= 0x00a1;
	const XLS_Type_MULRK				= 0x00bd;
	const XLS_Type_MULBLANK				= 0x00be;
	const XLS_Type_DBCELL				= 0x00d7;
	const XLS_Type_XF					= 0x00e0;
	const XLS_Type_MERGEDCELLS			= 0x00e5;
	const XLS_Type_MSODRAWINGGROUP		= 0x00eb;
	const XLS_Type_MSODRAWING			= 0x00ec;
	const XLS_Type_SST					= 0x00fc;
	const XLS_Type_LABELSST				= 0x00fd;
	const XLS_Type_EXTSST				= 0x00ff;
	const XLS_Type_EXTERNALBOOK			= 0x01ae;
	const XLS_Type_DATAVALIDATIONS		= 0x01b2;
	const XLS_Type_TXO					= 0x01b6;
	const XLS_Type_HYPERLINK			= 0x01b8;
	const XLS_Type_DATAVALIDATION		= 0x01be;
	const XLS_Type_DIMENSION			= 0x0200;
	const XLS_Type_BLANK				= 0x0201;
	const XLS_Type_NUMBER				= 0x0203;
	const XLS_Type_LABEL				= 0x0204;
	const XLS_Type_BOOLERR				= 0x0205;
	const XLS_Type_STRING				= 0x0207;
	const XLS_Type_ROW					= 0x0208;
	const XLS_Type_INDEX				= 0x020b;
	const XLS_Type_ARRAY				= 0x0221;
	const XLS_Type_DEFAULTROWHEIGHT 	= 0x0225;
	const XLS_Type_WINDOW2				= 0x023e;
	const XLS_Type_RK					= 0x007e;
	const XLS_Type_RK2					= 0x027e;
	const XLS_Type_STYLE				= 0x0293;
	const XLS_Type_FORMAT				= 0x041e;
	const XLS_Type_SHAREDFMLA			= 0x04bc;
	const XLS_Type_BOF					= 0x0809;
	const XLS_Type_SHEETPROTECTION		= 0x0867;
	const XLS_Type_RANGEPROTECTION		= 0x0868;
	const XLS_Type_SHEETLAYOUT			= 0x0862;
	const XLS_Type_XFEXT				= 0x087d;
	const XLS_Type_PAGELAYOUTVIEW		= 0x088b;
	const XLS_Type_UNKNOWN				= 0xffff;
	
	// Encryption type
	const MS_BIFF_CRYPTO_NONE = 0;
	const MS_BIFF_CRYPTO_XOR  = 1;
	const MS_BIFF_CRYPTO_RC4  = 2;
	
	// Size of stream blocks when using RC4 encryption
	const REKEY_BLOCK = 0x400;
	
	private $_pos;
	private $_data;
	private $_cell;
	private $_sst;
	private $_sheets;
	private $_dataSize;
	private $_codepage = 'CP1252';
	
	private $index = 0;
	private $curretSheet = 0;
	private $dateFormats = array (
		0xe => "m/d/Y",
		0xf => "M-d-Y",
		0x10 => "d-M",
		0x11 => "M-Y",
		0x12 => "h:i a",
		0x13 => "h:i:s a",
		0x14 => "H:i",
		0x15 => "H:i:s",
		0x16 => "d/m/Y H:i",
		0x2d => "i:s",
		0x2e => "H:i:s",
		0x2f => "i:s.S"
	);
	private $numberFormats = array(
		0x1 => "0",
		0x2 => "0.00",
		0x3 => "#,##0",
		0x4 => "#,##0.00",
		0x5 => "\$#,##0;(\$#,##0)",
		0x6 => "\$#,##0;[Red](\$#,##0)",
		0x7 => "\$#,##0.00;(\$#,##0.00)",
		0x8 => "\$#,##0.00;[Red](\$#,##0.00)",
		0x9 => "0%",
		0xa => "0.00%",
		0xb => "0.00E+00",
		0x25 => "#,##0;(#,##0)",
		0x26 => "#,##0;[Red](#,##0)",
		0x27 => "#,##0.00;(#,##0.00)",
		0x28 => "#,##0.00;[Red](#,##0.00)",
		0x29 => "#,##0;(#,##0)",  // Not exactly
		0x2a => "\$#,##0;(\$#,##0)",  // Not exactly
		0x2b => "#,##0.00;(#,##0.00)",  // Not exactly
		0x2c => "\$#,##0.00;(\$#,##0.00)",  // Not exactly
		0x30 => "##0.0E+0"
	);
	
	public $error = false;
	
	/**
	 * Create a new Spreadsheet_Excel_Reader instance
	 */
	public function __construct($file) {
		$this->_loadOLE($file);		// Read the OLE file
	}
	
	/**
	 * Changes sheet to another.
	 * @param bool
	 */
	public function ChangeSheet($index){
		$this->curretSheet = $index;
		return true;
	}
	
	/**
	 * ??????Cell??????
	 */
	public function getCell(){
		$this->_cell = array();
		$this->_endRow = false;
		$this->_key = null;
		
		if( ! $this->_parse){
			$this->_parse = true;
			$this->_pos = 0;
			
			// Parse Workbook Global Substream
			while ($this->_pos < $this->_dataSize) {
				$code = self::_GetInt2d($this->_data, $this->_pos);
				
				switch ($code) {
					case self::XLS_Type_SST:			$this->_readSst();				break;
					case self::XLS_Type_CODEPAGE:		$this->_readCodepage();			break;
					case self::XLS_Type_DATEMODE:		$this->_readDateMode();			break;
					case self::XLS_Type_FORMAT:			$this->_readFormat();			break;
					case self::XLS_Type_XF:				$this->_readXf();				break;
					case self::XLS_Type_EOF:			$this->_readDefault();			break 2;
					default:							$this->_readDefault();			break;
				}
			}
		}

		// Parse the individual sheet
		$this->_pos = $this->_lastPos ? $this->_lastPos : $this->_sheets[$this->curretSheet]['offset'];
		while ($this->_pos <= $this->_dataSize - 4) {
			if($this->_endRow) break;
			$code = self::_GetInt2d($this->_data, $this->_pos);

			switch ($code) {
				case self::XLS_Type_RK:
				case self::XLS_Type_RK2:				$this->_readRk();						break;
				case self::XLS_Type_LABELSST:			$this->_readLabelSst();					break;
				case self::XLS_Type_MULRK:				$this->_readMulRk();					break;
				case self::XLS_Type_NUMBER:				$this->_readNumber();					break;
				case self::XLS_Type_FORMULA:
				case self::XLS_Type_FORMULA2:			$this->_readFormula();					break;
				case self::XLS_Type_BOOLERR:			$this->_readBoolErr();					break;
				case self::XLS_Type_STRING:				$this->_readString();					break;
				case self::XLS_Type_MULBLANK:			$this->_readBlank();					break;
				case self::XLS_Type_LABEL:				$this->_readLabel();					break;
				case self::XLS_Type_EOF:				$this->_readDefault();					break 2;
				default:								$this->_readDefault();					break;
			}
		}
		return $this->_cell;
	}
	
	/**
	 * Return worksheet info (Name, Last Column Letter, Last Column Index, Total Rows, Total Columns)
	 */
	public  function getWorksheetInfo() {	
		if( ! $this->_sheets){
			$this->_dataSize = strlen($this->_data);					// total byte size of Excel data (workbook global substream + sheet substreams)
			$this->_pos      = 0;
			$this->_sheets   = array();
		
			// Parse Workbook Global Substream
			while ($this->_pos < $this->_dataSize) {
				$code = self::_GetInt2d($this->_data, $this->_pos);
		
				switch ($code) {
					case self::XLS_Type_BOF:	$this->_readBof();			break;
					case self::XLS_Type_SHEET:	$this->_readSheet();		break;
					case self::XLS_Type_EOF:	$this->_readDefault();		break 2;
					default:					$this->_readDefault();		break;
				}
			}
		}
		
		if( ! isset($this->_sheets[$this->curretSheet])){
			return array();
		}
		
		$sheetInfo = array(
			'worksheetName'		=> $this->_sheets[$this->curretSheet]['name'],
			'lastColumnLetter'	=> 'A',
			'lastColumnIndex'	=> 0,
			'totalRows'			=> 0,
			'totalColumns'		=> 0
		);
		
		// Parse the individual sheet
		$this->_pos = $this->_sheets[$this->curretSheet]['offset'];
		while ($this->_pos <= $this->_dataSize - 4) {
			$code = self::_GetInt2d($this->_data, $this->_pos);
		
			switch ($code) {
				case self::XLS_Type_DIMENSION:
					$length = self::_GetInt2d($this->_data, $this->_pos + 2);
					$this->_pos += 4;
					if ($length == 10 && $this->_version == self::XLS_BIFF7) {
						$sheetInfo['totalRows'] = self::_GetInt2d($this->_data, $this->_pos + 2);
						$sheetInfo['totalColumns'] = self::_GetInt2d($this->_data, $this->_pos + 6);
					}
					else{
						$sheetInfo['totalRows'] = self::_GetInt2d($this->_data, $this->_pos + 4);
						$sheetInfo['totalColumns'] = self::_GetInt2d($this->_data, $this->_pos + 10);
					}
					break 2;
				default:
					$this->_readDefault();
					break;
			}
		}
		
		if ($sheetInfo['totalColumns']) {
			$sheetInfo['lastColumnIndex'] = $sheetInfo['totalColumns'] - 1;
		}
		$sheetInfo['lastColumnLetter'] = self::_stringFromColumnIndex($sheetInfo['lastColumnIndex']);
	
		return $sheetInfo;
	}
	
	private function _addCell($row, $column, $value, $format){
		if(is_null($this->_key)){
			$this->_key = $row;
		}
		
		if($row > $this->_key){
			$this->_endRow = true;
			return false;
		}
		
		switch ($format) {
			case 'NULL':
				$_value = $value;
				break;
			case 'STRING2':				
			case 'STRING':
			case 'INLINE':
				$value = substr($value, 0, 32767);
				$_value = str_replace(array("\r\n", "\r"), "\n", $value);
				break;
			case 'NUMERIC':
				$_value = (float)$value;
				break;
			case 'FORMULA':
				$_value = (string)$value;
				break;
			case 'BOOL':
				$_value = (bool)$value;
				break;
			case 'ERROR':
				$_errorCodes = array(
					'#NULL!'  => 0,
					'#DIV/0!' => 1,
					'#VALUE!' => 2,
					'#REF!'   => 3,
					'#NAME?'  => 4,
					'#NUM!'   => 5,
					'#N/A'    => 6
				);
				$_value = (string)$value;
				
				if ( ! array_key_exists($_value, $_errorCodes)) {
					$_value = '#NULL!';
				}				
				break;
			default:
				$_value = '#NULL!';
				break;
		}
		
		$this->_lastPos = $this->_pos;
		$this->_cell[$column] = $_value;
	}
	
	/**
	 * Use OLE reader to extract the relevant data streams from the OLE file
	 */
	private function _loadOLE($file)	{
		self::_loadClass();
		$ole = new PHPExcel_Shared_OLERead();		// OLE reader
		$res = $ole->read($file);					// get excel data
		if ($ole->error) {
			$this->error = true;
			return false;
		}
		
		$this->_data = $ole->getStream($ole->workbook);			// Get workbook data: workbook stream + sheet streams
	}
	
	private function _getFormatDetail($data, $pos, $value, $column){
		$xfIndex = self::_GetInt2d($data, $pos + 4);
		$xfRecord = $this->xfRecords[$xfIndex];
		$type = $xfRecord['type'];
		$format = $xfRecord['format'];
		$formatIndex = $xfRecord['formatIndex'];

		if ($type == 'date') {
			$_type = 'STRING';
			// Convert numeric value into a date
			$utcDays = floor($value - ($this->_excelBaseDate == 1904 ? 24107 : 25569));
			$utcValue = $utcDays * 86400;
			$keys = array('seconds','minutes','hours','mday','wday','mon','year','yday','weekday','month',0);
			$datas = explode(":", gmdate('s:i:G:j:w:n:Y:z:l:F:U', $utcValue));
			foreach ($keys as $key => $value) {
				$dateInfo[$value] = $datas[$key];
			}
		
			$fractionalDay = $value - floor($value) + .0000001; // The .0000001 is to fix for php/excel fractional diffs
			$totalSeconds = floor(86400 * $fractionalDay);
			$secs = $totalSeconds % 60;
			$totalSeconds -= $secs;
			$hours = floor($totalSeconds / (60 * 60));
			$mins = floor($totalSeconds / 60) % 60;
			$_value = date ($format, mktime($hours, $mins, $secs, $dateInfo["mon"], $dateInfo["mday"], $dateInfo["year"]));
		} 
		else if ($type == 'number') {
			$_type = 'NUMERIC';
			$_value = $this->_format_value($format, $value, $formatIndex);
		}
		else {
			$_type = 'STRING';
			$_value = $this->_format_value("%s", $value, $formatIndex);
		}
		
		return array(
			'value' => $_value,
			'type' 	=> $_type
		);
	}
	
	private function _format_value($format, $value, $formatIndex){
		// 49==TEXT format
		if ( ( ! $formatIndex && $format == "%s") || ($formatIndex == 49) || ($format == "GENERAL") ) {
			return $value;
		}
		
		// Custom pattern can be POSITIVE;NEGATIVE;ZERO
		// The "text" option as 4th parameter is not handled
		$parts = explode(";", $format);
		$pattern = $parts[0];
		
		if (count($parts) > 2 && $value == 0) {	// Negative pattern
			$pattern = $parts[2];
		}
		else if (count($parts) > 1 && $value < 0) {	// Zero pattern
			$pattern = $parts[1];
			$value = abs($value);
		}
		
		// In Excel formats, "_" is used to add spacing, which we can't do in HTML
		$pattern = preg_replace("/_./", "", $pattern);
		
		// Some non-number characters are escaped with \, which we don't need
		$pattern = preg_replace("/\\\/", "", $pattern);
		
		// Some non-number strings are quoted, so we'll get rid of the quotes
		$pattern = preg_replace("/\"/", "", $pattern);

		// TEMPORARY - Convert # to 0
		$pattern = preg_replace("/\#/", "0", $pattern);

		// Find out if we need comma formatting
		$has_commas = preg_match("/,/", $pattern);
		if ($has_commas) {
			$pattern = preg_replace("/,/", "", $pattern);
		}
		
		// Handle Percentages
		if (preg_match("/\d(\%)([^\%]|$)/", $pattern, $matches)) {
			$value = $value * 100;
			$pattern = preg_replace("/(\d)(\%)([^\%]|$)/", "$1%$3", $pattern);
		}
		
		// Handle the number itself
		$number_regex = "/(\d+)(\.?)(\d*)/";
		if (preg_match($number_regex,$pattern,$matches)) {
			$left = $matches[1];
			$dec = $matches[2];
			$right = $matches[3];
			if ($has_commas) {
				$formatted = number_format($value,strlen($right));
			}
			else {
				$sprintf_pattern = "%1.".strlen($right)."f";
				$formatted = sprintf($sprintf_pattern, $value);
			}
			$pattern = preg_replace($number_regex, $formatted, $pattern);
		}
		
		return $pattern;
	}
	
	/**
	 * Read BOF
	 */
	private function _readBof()	{
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
		
		$this->_pos += 4 + $length;				// move stream pointer to next record
		$substreamType = self::_GetInt2d($recordData, 2);	// offset: 2; size: 2; type of the following data
		switch ($substreamType) {
			case self::XLS_WorkbookGlobals:
				$version = self::_GetInt2d($recordData, 0);
				if (($version != self::XLS_BIFF8) && ($version != self::XLS_BIFF7)) {
					die('Cannot read this Excel file. Version is too old.');
				}
				$this->_version = $version;
				break;
	
			case self::XLS_Worksheet:
				// do not use this version information for anything
				// it is unreliable (OpenOffice doc, 5.8), use only version information from the global stream
				break;
	
			default:
				// substream, e.g. chart. just skip the entire substream
				do {
					$code = self::_GetInt2d($this->_data, $this->_pos);
					$this->_readDefault();
				} while ($code != self::XLS_Type_EOF && $this->_pos < $this->_dataSize);
				break;
		}
	}
	
	/**
	 * Read Sheet
	 */
	private function _readSheet() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
		
		$rec_offset = self::_GetInt4d($this->_data, $this->_pos + 4);	// offset: 0; size: 4; absolute stream position of the BOF record of the sheet
		$this->_pos += 4 + $length;					// move stream pointer to next record
	
		// offset: 6; size: var; sheet name
		if ($this->_version == self::XLS_BIFF8) {
			$string = self::_readUnicodeStringShort(substr($recordData, 6));
			$rec_name = $string['value'];
		} elseif ($this->_version == self::XLS_BIFF7) {
			$string = self::_readByteStringShort(substr($recordData, 6));
			$rec_name = $string['value'];
		}
	
		$this->_sheets[] = array(
			'name' => $rec_name,
			'offset' => $rec_offset
		);
	}
	
	/**
	 * Reads a general type of BIFF record. Does nothing except for moving stream pointer forward to next record.
	 */
	private function _readDefault()	{
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		
		$this->_pos += 4 + $length;		// move stream pointer to next record
	}
	
	/**
	 * CODEPAGE
	 *
	 * This record stores the text encoding used to write byte
	 * strings, stored as MS Windows code page identifier.
	 */
	private function _readCodepage() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
		$this->_pos += 4 + $length;
		$codepage = self::_GetInt2d($recordData, 0);
	
		$this->_codepage = self::NumberToName($codepage);
	}
	
	/**
	 * DATEMODE
	 *
	 * This record specifies the base date for displaying date values. All dates are stored as count of days past this base date. 
	 * In BIFF2-BIFF4 this record is part of the Calculation Settings Block. In BIFF5-BIFF8 it is stored in the Workbook Globals Substream.
	 */
	private function _readDateMode() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
		$this->_pos += 4 + $length;
		if (ord($recordData{0}) == 1) {
			$this->_excelBaseDate = 1904;
		}
		else{
			$this->_excelBaseDate = 1900;
		}
	}
	
	/**
	 * data format
	 */
	private function _readFormat(){
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$indexCode = self::_GetInt2d($this->_data, $this->_pos + 4);
		if ($this->_version == self::XLS_BIFF8) {
			$numchars = self::_GetInt2d($this->_data, $this->_pos + 6);
			if (ord($this->_data[$this->_pos + 8]) == 0){
				$formatString = substr($this->_data, $this->_pos + 9, $numchars);
			} else {
				$formatString = substr($this->_data, $this->_pos + 9, $numchars*2);
			}
		} 
		else {
			$numchars = ord($this->_data[$this->_pos + 6]);
			$formatString = substr($this->_data, $this->_pos + 7, $numchars*2);
		}
		$this->formatRecords[$indexCode] = $formatString;
		$this->_pos += 4 + $length;
	}
	
	/**
	 * XF - Extended Format
	 *
	 * This record contains formatting information for cells, rows, columns or styles.
	 * According to http://support.microsoft.com/kb/147732 there are always at least 15 cell style XF and 1 cell XF.
	 * Inspection of Excel files generated by MS Office Excel shows that XF records 0-14 are cell style XF and XF record 15 is a cell XF
	 * We only read the first cell style XF and skip the remaining cell style XF records
	 */
	private function _readXf() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$indexCode = self::_GetInt2d($this->_data, $this->_pos + 6);
		$this->_pos += 4 + $length;
		$xf = array('formatIndex' => $indexCode);
		if (isset($this->dateFormats[$indexCode])) {
			$xf['type'] = 'date';
			$xf['format'] = $this->dateFormats[$indexCode];
		}
		elseif (isset($this->numberFormats[$indexCode])) {
			$xf['type'] = 'number';
			$xf['format'] = $this->numberFormats[$indexCode];
		}
		else {
			if ($indexCode > 0){
				if (isset($this->formatRecords[$indexCode])) {
					$formatStr = $this->formatRecords[$indexCode];
				}
				
				if ($formatStr) {
					$tmp = preg_replace("/\;.*/", "", $formatStr);
					$tmp = preg_replace("/^\[[^\]]*\]/", "", $tmp);
					if (preg_match("/[^hmsday\/\-:\s\\\,AMP]/i", $tmp) == 0) { // found day and time format
						$isDate = TRUE;
						$formatStr = $tmp;
						$formatStr = str_replace(array('AM/PM','mmmm','mmm'), array('a','F','M'), $formatStr);
						// m/mm are used for both minutes and months - oh SNAP!
						// This mess tries to fix for that.
						// 'm' == minutes only if following h/hh or preceding s/ss
						$formatstr = preg_replace("/(h:?)mm?/","$1i", $formatStr);
						$formatstr = preg_replace("/mm?(:?s)/","i$1", $formatStr);
						// A single 'm' = n in PHP
						$formatStr = preg_replace("/(^|[^m])m([^m]|$)/", '$1n$2', $formatStr);
						$formatStr = preg_replace("/(^|[^m])m([^m]|$)/", '$1n$2', $formatStr);
						// else it's months
						$formatStr = str_replace('mm', 'm', $formatStr);
						// Convert single 'd' to 'j'
						$formatStr = preg_replace("/(^|[^d])d([^d]|$)/", '$1j$2', $formatStr);
						$formatStr = str_replace(array('dddd','ddd','dd','yyyy','yy','hh','h'), array('l','D','d','Y','y','H','g'), $formatStr);
						$formatStr = preg_replace("/ss?/", 's', $formatStr);
					}
				}
			}
			
			if ($isDate){
				$xf['type'] = 'date';
				$xf['format'] = $formatStr;
			}else{
				if (preg_match("/[0#]/", $formatStr)) {
					$xf['type'] = 'number';
					$xf['format'] = $formatStr;
				}
				else {
					$xf['type'] = 'other';
					$xf['format'] = 0;
				}
			}
		}
		
		$this->xfRecords[] = $xf;
	}
	
	/**
	 * SST - Shared String Table
	 *
	 * This record contains a list of all strings used anywherein the workbook. Each string occurs only once. The
	 * workbook uses indexes into the list to reference the strings.
	 **/
	private function _readSst()	{
		$pos = 0;												// offset within (spliced) record data
		$splicedRecordData = $this->_getSplicedRecordData();	// get spliced record data
		$recordData = $splicedRecordData['recordData'];
		$spliceOffsets = $splicedRecordData['spliceOffsets'];
	
		$pos += 4;												// offset: 0; size: 4; total number of strings in the workbook
		$nm = self::_GetInt4d($recordData, 4);					// offset: 4; size: 4; number of following strings ($nm)
		$pos += 4;
		
		for ($i = 0; $i < $nm; ++$i) {							// loop through the Unicode strings (16-bit length)
			$numChars = self::_GetInt2d($recordData, $pos);		// number of characters in the Unicode string
			$pos += 2;
			
			$optionFlags = ord($recordData{$pos});				// option flags
			++$pos;
			
			$isCompressed = (($optionFlags & 0x01) == 0) ;		// bit: 0; mask: 0x01; 0 = compressed; 1 = uncompressed
			$hasAsian = (($optionFlags & 0x04) != 0);			// bit: 2; mask: 0x02; 0 = ordinary; 1 = Asian phonetic
			$hasRichText = (($optionFlags & 0x08) != 0);		// bit: 3; mask: 0x03; 0 = ordinary; 1 = Rich-Text
	
			if ($hasRichText) {
				$formattingRuns = self::_GetInt2d($recordData, $pos);		// number of Rich-Text formatting runs
				$pos += 2;
			}
	
			if ($hasAsian) {
				$extendedRunLength = self::_GetInt4d($recordData, $pos);	// size of Asian phonetic setting
				$pos += 4;
			}
	
			$len = ($isCompressed) ? $numChars : $numChars * 2;		// expected byte length of character array if not split
	
			foreach ($spliceOffsets as $spliceOffset) {				// look up limit position
				if ($pos <= $spliceOffset) {						// it can happen that the string is empty, therefore we need. <= and not just <
					$limitpos = $spliceOffset;
					break;
				}
			}
	
			if ($pos + $len <= $limitpos) {
				$retstr = substr($recordData, $pos, $len);			// character array is not split between records
				$pos += $len;
			} else {
				$retstr = substr($recordData, $pos, $limitpos - $pos);		// character array is split between records. first part of character array
				$bytesRead = $limitpos - $pos;
				$charsLeft = $numChars - (($isCompressed) ? $bytesRead : ($bytesRead / 2));	// remaining characters in Unicode string
				$pos = $limitpos;
	
				// keep reading the characters
				while ($charsLeft > 0) {
					// look up next limit position, in case the string span more than one continue record
					foreach ($spliceOffsets as $spliceOffset) {
						if ($pos < $spliceOffset) {
							$limitpos = $spliceOffset;
							break;
						}
					}
	
					// repeated option flags. OpenOffice.org documentation 5.21
					$option = ord($recordData{$pos});
					++$pos;
	
					if ($isCompressed && ($option == 0)) {
						// 1st fragment compressed. this fragment compressed
						$len = min($charsLeft, $limitpos - $pos);
						$retstr .= substr($recordData, $pos, $len);
						$charsLeft -= $len;
						$isCompressed = true;
	
					} elseif (!$isCompressed && ($option != 0)) {
						// 1st fragment uncompressed. this fragment uncompressed
						$len = min($charsLeft * 2, $limitpos - $pos);
						$retstr .= substr($recordData, $pos, $len);
						$charsLeft -= $len / 2;
						$isCompressed = false;
	
					} elseif (!$isCompressed && ($option == 0)) {
						// 1st fragment uncompressed. this fragment compressed
						$len = min($charsLeft, $limitpos - $pos);
						for ($j = 0; $j < $len; ++$j) {
							$retstr .= $recordData{$pos + $j} . chr(0);
						}
						$charsLeft -= $len;
						$isCompressed = false;
	
					} else {
						// 1st fragment compressed. this fragment uncompressed
						$newstr = '';
						for ($j = 0; $j < strlen($retstr); ++$j) {
							$newstr .= $retstr[$j] . chr(0);
						}
						$retstr = $newstr;
						$len = min($charsLeft * 2, $limitpos - $pos);
						$retstr .= substr($recordData, $pos, $len);
						$charsLeft -= $len / 2;
						$isCompressed = false;
					}
	
					$pos += $len;
				}
			}
			
			$retstr = self::_encodeUTF16($retstr, $isCompressed);	// convert to UTF-8
			$fmtRuns = array();										// read additional Rich-Text information, if any
			if ($hasRichText) {
				// list of formatting runs
				for ($j = 0; $j < $formattingRuns; ++$j) {
					$charPos = self::_GetInt2d($recordData, $pos + $j * 4);			// first formatted character; zero-based
					$fontIndex = self::_GetInt2d($recordData, $pos + 2 + $j * 4);	// index to font record
					$fmtRuns[] = array(
						'charPos' => $charPos,
						'fontIndex' => $fontIndex
					);
				}
				$pos += 4 * $formattingRuns;
			}
	
			// read additional Asian phonetics information, if any
			if ($hasAsian) {
				$pos += $extendedRunLength;		// For Asian phonetic settings, we skip the extended string data
			}
	
			// store the shared sting
			$this->_sst[] = array(
				'value' => $retstr,
				'fmtRuns' => $fmtRuns
			);
		}
	
	}
	
	/**
	 * Read RK record
	 * This record represents a cell that contains an RK value (encoded integer or floating-point value). If a floating-point value 
	 * cannot be encoded to an RK value, a NUMBER record will be written. This record replaces the record INTEGER written in BIFF2.
	 */
	private function _readRk() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;		
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
		$rknum = self::_GetInt4d($recordData, 6);
		$numValue = self::_GetIEEE754($rknum);
	
		// add cell
		$this->_addCell($row, $column, $numValue, 'NUMERIC');
	}
	
	/**
	 * Read LABELSST record This record represents a cell that contains a string. It
	 * replaces the LABEL record and RSTRING record used in BIFF2-BIFF5.
	 */
	private function _readLabelSst() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
	
		// offset: 6; size: 4; index to SST record
		$index = self::_GetInt4d($recordData, 6);
		$this->_addCell($row, $column, $this->_sst[$index]['value'], 'STRING');
	}
	
	
	/**
	 * Read MULRK record
	 * This record represents a cell range containing RK value cells. All cells are located in the same row.
	 */
	private function _readMulRk() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$colFirst = self::_GetInt2d($recordData, 2);
		$colLast = self::_GetInt2d($recordData, $length - 2);
		$columns = $colLast - $colFirst + 1;
	
		// offset within record data
		$offset = 4;
	
		for ($i = 0; $i < $columns; ++$i) {
			$numValue = self::_GetIEEE754(self::_GetInt4d($recordData, $offset + 2));
			$info = $this->_getFormatDetail($recordData, $offset - 4, $numValue, $colFirst + $i + 1);
			$this->_addCell($row, $colFirst + $i, $info['value'], $info['type']);

			$offset += 6;
		}
	}
	
	
	/**
	 * Read NUMBER record
	 * This record represents a cell that contains a floating-point value.
	 */
	private function _readNumber() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
	
		$numValue = self::_extractNumber(substr($recordData, 6, 8));
		$this->_addCell($row, $column, $numValue, 'NUMERIC');
	}
	
	/**
	 * Read FORMULA record + perhaps a following STRING record if formula result is a string
	 * This record contains the token array and the result of a formula cell.
	 */
	private function _readFormula()	{
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
		
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
		
		if ((ord($recordData{6}) == 0) && (ord($recordData{12}) == 255) && (ord($recordData{13}) == 255)) {
			$this->_preRow = $row;
			$this->_preColumn = $column;
			return false;
		}
		elseif ((ord($recordData{6}) == 1) && (ord($recordData{12}) == 255)	&& (ord($recordData{13}) == 255)) {
			// Boolean formula. Result is in +2; 0=false, 1=true
			$dataType = 'BOOL';
			$value = (bool) ord($recordData{8});
		}
		elseif ((ord($recordData{6}) == 2) && (ord($recordData{12}) == 255)	&& (ord($recordData{13}) == 255)) {
			// Error formula. Error code is in +2
			$dataType = 'ERROR';
			$value = self::_mapErrorCode(ord($recordData{8}));
		}
		elseif ((ord($recordData{6}) == 3) && (ord($recordData{12}) == 255)	&& (ord($recordData{13}) == 255)) {
			// Formula result is a null string
			$dataType = 'NULL';
			$value = '';
		}
		else {
			// forumla result is a number, first 14 bytes like _NUMBER record
			$dataType = 'NUMERIC';
			$value = self::_extractNumber(substr($recordData, 6, 8));
		}
		
		$this->_addCell($row, $column, $value, $dataType);
	}	
	
	/**
	 * Read a STRING record from current stream position and advance the stream pointer to next record
	 * This record is used for storing result from FORMULA record when it is a string, and it occurs directly after the FORMULA record
	 *
	 * @return string The string contents as UTF-8
	 */
	private function _readString() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
	
		if ($this->_version == self::XLS_BIFF8) {
			$string = self::_readUnicodeStringLong($recordData);
			$value = $string['value'];
		} else {
			$string = $this->_readByteStringLong($recordData);
			$value = $string['value'];
		}
	
		$this->_addCell($this->_preRow, $this->_preColumn, $value, 'STRING');
	}
	
	
	/**
	 * Read BOOLERR record
	 * This record represents a Boolean value or error value cell.
	 */
	private function _readBoolErr()	{
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
	
		// offset: 6; size: 1; the boolean value or error value
		$boolErr = ord($recordData{6});

		// offset: 7; size: 1; 0=boolean; 1=error
		$isError = ord($recordData{7});

		switch ($isError) {
			case 0: // boolean
				$value = (bool) $boolErr;

				// add cell value
				$this->_addCell($row, $column, $value, 'BOOL');
				break;

			case 1: // error type
				$value = self::_mapErrorCode($boolErr);

				// add cell value
				$this->_addCell($row, $column, $value, 'ERROR');
				break;
		}
	}	
	
	/**
	 * Read LABEL record
	 * This record represents a cell that contains a string. In BIFF8 it is usually replaced by the LABELSST record.
	 * Excel still uses this record, if it copies unformatted text cells to the clipboard.
	 */
	private function _readLabel() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
	
		if ($this->_version == self::XLS_BIFF8) {
			$string = self::_readUnicodeStringLong(substr($recordData, 6));
			$value = $string['value'];
		} else {
			$string = $this->_readByteStringLong(substr($recordData, 6));
			$value = $string['value'];
		}
		$this->_addCell($row, $column, $value, 'STRING');
	}
	
	
	/**
	 * Read BLANK record
	 */
	private function _readBlank() {
		$length = self::_GetInt2d($this->_data, $this->_pos + 2);
		$recordData = substr($this->_data, $this->_pos + 4, $length);
	
		$this->_pos += 4 + $length;
		$row = self::_GetInt2d($recordData, 0);
		$column = self::_GetInt2d($recordData, 2);
		$this->_addCell($row, $column, '', 'NULL');			
	}
	
	/**
	 * Reads a record from current position in data stream and continues reading data as long as CONTINUE
	 * records are found. Splices the record data pieces and returns the combined string as if record data is in one piece.
	 * Moves to next current position in data stream to start of next record different from a CONtINUE record
	 *
	 * @return array
	 */
	private function _getSplicedRecordData() {
		$data = '';
		$spliceOffsets = array();
	
		$i = 0;
		$spliceOffsets[0] = 0;
		do {
			++$i;
			$identifier = self::_GetInt2d($this->_data, $this->_pos);	// offset: 0; size: 2; identifier
			$length = self::_GetInt2d($this->_data, $this->_pos + 2);	// offset: 2; size: 2; length
			$data .= substr($this->_data, $this->_pos + 4, $length);
	
			$spliceOffsets[$i] = $spliceOffsets[$i - 1] + $length;
			$this->_pos += 4 + $length;
			$nextIdentifier = self::_GetInt2d($this->_data, $this->_pos);
		} while ($nextIdentifier == self::XLS_Type_CONTINUE);
	
		$splicedData = array(
			'recordData' => $data,
			'spliceOffsets' => $spliceOffsets,
		);
	
		return $splicedData;
	}
	
	/**
	 * Read byte string (16-bit string length)
	 * OpenOffice documentation: 2.5.2
	 *
	 * @param string $subData
	 * @return array
	 */
	private function _readByteStringLong($subData) {
		// offset: 0; size: 2; length of the string (character count)
		$ln = self::_GetInt2d($subData, 0);
	
		// offset: 2: size: var; character array (8-bit characters)
		$value = $this->_decodeCodepage(substr($subData, 2));
	
		//return $string;
		return array(
				'value' => $value,
				'size' => 2 + $ln, // size in bytes of data structure
		);
	}
	
	/**
	 * Map error code, e.g. '#N/A'
	 *
	 * @param int $subData
	 * @return string
	 */
	private static function _mapErrorCode($subData)	{
		switch ($subData) {
			case 0x00: return '#NULL!';		break;
			case 0x07: return '#DIV/0!';		break;
			case 0x0F: return '#VALUE!';		break;
			case 0x17: return '#REF!';		break;
			case 0x1D: return '#NAME?';		break;
			case 0x24: return '#NUM!';		break;
			case 0x2A: return '#N/A';		break;
			default: return false;
		}
	}
	
	/**
	 * Convert Microsoft Code Page Identifier to Code Page Name which iconv
	 * and mbstring understands
	 *
	 * @param integer $codePage Microsoft Code Page Indentifier
	 * @return string Code Page Name
	 */
	private static function NumberToName($codePage = 1252) {
		switch ($codePage) {
			case 367:	return 'ASCII';				break;	//	ASCII
			case 437:	return 'CP437';				break;	//	OEM US
			//case 720:	throw new PHPExcel_Exception('Code page 720 not supported.');	break;	//	OEM Arabic
			case 737:	return 'CP737';				break;	//	OEM Greek
			case 775:	return 'CP775';				break;	//	OEM Baltic
			case 850:	return 'CP850';				break;	//	OEM Latin I
			case 852:	return 'CP852';				break;	//	OEM Latin II (Central European)
			case 855:	return 'CP855';				break;	//	OEM Cyrillic
			case 857:	return 'CP857';				break;	//	OEM Turkish
			case 858:	return 'CP858';				break;	//	OEM Multilingual Latin I with Euro
			case 860:	return 'CP860';				break;	//	OEM Portugese
			case 861:	return 'CP861';				break;	//	OEM Icelandic
			case 862:	return 'CP862';				break;	//	OEM Hebrew
			case 863:	return 'CP863';				break;	//	OEM Canadian (French)
			case 864:	return 'CP864';				break;	//	OEM Arabic
			case 865:	return 'CP865';				break;	//	OEM Nordic
			case 866:	return 'CP866';				break;	//	OEM Cyrillic (Russian)
			case 869:	return 'CP869';				break;	//	OEM Greek (Modern)
			case 874:	return 'CP874';				break;	//	ANSI Thai
			case 932:	return 'CP932';				break;	//	ANSI Japanese Shift-JIS
			case 936:	return 'CP936';				break;	//	ANSI Chinese Simplified GBK
			case 949:	return 'CP949';				break;	//	ANSI Korean (Wansung)
			case 950:	return 'CP950';				break;	//	ANSI Chinese Traditional BIG5
			case 1200:	return 'UTF-16LE';			break;	//	UTF-16 (BIFF8)
			case 1250:	return 'CP1250';			break;	//	ANSI Latin II (Central European)
			case 1251:	return 'CP1251';			break;	//	ANSI Cyrillic
			case 0:											//	CodePage is not always correctly set when the xls file was saved by Apple's Numbers program
			case 1252:	return 'CP1252';			break;	//	ANSI Latin I (BIFF4-BIFF7)
			case 1253:	return 'CP1253';			break;	//	ANSI Greek
			case 1254:	return 'CP1254';			break;	//	ANSI Turkish
			case 1255:	return 'CP1255';			break;	//	ANSI Hebrew
			case 1256:	return 'CP1256';			break;	//	ANSI Arabic
			case 1257:	return 'CP1257';			break;	//	ANSI Baltic
			case 1258:	return 'CP1258';			break;	//	ANSI Vietnamese
			case 1361:	return 'CP1361';			break;	//	ANSI Korean (Johab)
			case 10000:	return 'MAC';				break;	//	Apple Roman
			case 10006:	return 'MACGREEK';			break;	//	Macintosh Greek
			case 10007:	return 'MACCYRILLIC';		break;	//	Macintosh Cyrillic
			case 10008: return 'CP936';             break;  //  Macintosh - Simplified Chinese (GB 2312)
			case 10029:	return 'MACCENTRALEUROPE';	break;	//	Macintosh Central Europe
			case 10079: return 'MACICELAND';		break;	//	Macintosh Icelandic
			case 10081: return 'MACTURKISH';		break;	//	Macintosh Turkish
			case 32768:	return 'MAC';				break;	//	Apple Roman
			//case 32769:	throw new PHPExcel_Exception('Code page 32769 not supported.');		break;	//	ANSI Latin I (BIFF2-BIFF3)
			case 65000:	return 'UTF-7';				break;	//	Unicode (UTF-7)
			case 65001:	return 'UTF-8';				break;	//	Unicode (UTF-8)
			default:	return 'UTF-8';				break;
		}
	}
	
	/**
	 *	String from columnindex
	 *
	 *	@param	int $pColumnIndex
	 *	@return	string
	 */
	private static function _stringFromColumnIndex($pColumnIndex = 0)	{
		static $_indexCache = array();
	
		if ( ! isset($_indexCache[$pColumnIndex])) {
			if ($pColumnIndex < 26) {
				$_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
			} elseif ($pColumnIndex < 702) {
				$_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
			} else {
				$_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
			}
		}
		return $_indexCache[$pColumnIndex];
	}
	
	/**
	 * Extracts an Excel Unicode short string (8-bit string length)
	 * OpenOffice documentation: 2.5.3
	 * function will automatically find out where the Unicode string ends.
	 *
	 * @param string $subData
	 * @return array
	 */
	private static function _readUnicodeStringShort($subData) {
		$characterCount = ord($subData[0]);		// offset: 0: size: 1; length of the string (character count)
		$string = self::_readUnicodeString(substr($subData, 1), $characterCount);
		$string['size'] += 1;				// add 1 for the string length
	
		return $string;
	}
	
	/**
	 * Read byte string (8-bit string length)
	 * OpenOffice documentation: 2.5.2
	 *
	 * @param string $subData
	 * @return array
	 */
	private static function _readByteStringShort($subData)	{
		$ln = ord($subData[0]);		// offset: 0; size: 1; length of the string (character count)
		$value = self::_decodeCodepage(substr($subData, 1, $ln));		// offset: 1: size: var; character array (8-bit characters)
	
		return array(
			'value' => $value,
			'size' => 1 + $ln, // size in bytes of data structure
		);
	}
	
	/**
	 * Extracts an Excel Unicode long string (16-bit string length)
	 * OpenOffice documentation: 2.5.3. this function is under construction, needs to support rich text, and Asian phonetic settings
	 *
	 * @param string $subData
	 * @return array
	 */
	private static function _readUnicodeStringLong($subData) {
		$value = '';
	
		// offset: 0: size: 2; length of the string (character count)
		$characterCount = self::_GetInt2d($subData, 0);
	
		$string = self::_readUnicodeString(substr($subData, 2), $characterCount);
	
		// add 2 for the string length
		$string['size'] += 2;
	
		return $string;
	}
	
	/**
	 * Read Unicode string with no string length field, but with known character count
	 * this function is under construction, needs to support rich text, and Asian phonetic settings
	 * OpenOffice.org's Documentation of the Microsoft Excel File Format, section 2.5.3
	 *
	 * @param string $subData
	 * @param int $characterCount
	 * @return array
	 */
	private static function _readUnicodeString($subData, $characterCount) {
		$isCompressed = !((0x01 & ord($subData[0])) >> 0);		// bit: 0; mask: 0x01; character compression (0 = compressed 8-bit, 1 = uncompressed 16-bit)
		$hasAsian = (0x04) & ord($subData[0]) >> 2;				// bit: 2; mask: 0x04; Asian phonetic settings
		$hasRichText = (0x08) & ord($subData[0]) >> 3;			// bit: 3; mask: 0x08; Rich-Text settings
	
		// offset: 1: size: var; character array
		// this offset assumes richtext and Asian phonetic settings are off which is generally wrong
		// needs to be fixed
		$value = self::_encodeUTF16(substr($subData, 1, $isCompressed ? $characterCount : 2 * $characterCount), $isCompressed);
	
		return array(
			'value' => $value,
			'size' => $isCompressed ? 1 + $characterCount : 1 + 2 * $characterCount, // the size in bytes including the option flags
		);
	}
	
	/**
	 * Get UTF-8 string from (compressed or uncompressed) UTF-16 string
	 *
	 * @param string $string
	 * @param bool $compressed
	 * @return string
	 */
	private static function _encodeUTF16($string, $compressed = '')	{
		if ($compressed) {
			$string = self::_uncompressByteString($string);
		}
	
		return mb_convert_encoding($string, 'UTF-8', 'UTF-16LE');
	}
	
	/**
	 * Convert string to UTF-8. Only used for BIFF5.
	 *
	 * @param string $string
	 * @return string
	 */
	private static function _decodeCodepage($string) {
		return mb_convert_encoding($string, 'UTF-8', $this->_codepage);
	}
	
	/**
	 * Convert UTF-16 string in compressed notation to uncompressed form. Only used for BIFF8.
	 *
	 * @param string $string
	 * @return string
	 */
	private static function _uncompressByteString($string) {
		$uncompressedString = '';
		$strLen = strlen($string);
		for ($i = 0; $i < $strLen; ++$i) {
			$uncompressedString .= $string[$i] . "\0";
		}
	
		return $uncompressedString;
	}
	
	/**
	 * Read 16-bit unsigned integer
	 *
	 * @param  string $data
	 * @param  int $pos
	 * @return int
	 */
	private static function _GetInt2d($data, $pos) {
		return ord($data[$pos]) | (ord($data[$pos+1]) << 8);
	}
	
	/**
	 * Read 32-bit signed integer
	 * FIX: represent numbers correctly on 64-bit system. Hacked by Andreas Rehm 2006 to ensure correct result of the <<24 block on 32 and 64bit systems
	 * http://sourceforge.net/tracker/index.php?func=detail&aid=1487372&group_id=99160&atid=623334
	 * 
	 * @param  string $data
	 * @param  int $pos
	 * @return int
	 */
	private static function _GetInt4d($data, $pos) {
		$_or_24 = ord($data[$pos + 3]);
		if ($_or_24 >= 128) {
			$_ord_24 = -abs((256 - $_or_24) << 24);		// negative number
		} else {
			$_ord_24 = ($_or_24 & 127) << 24;
		}
		return ord($data[$pos]) | (ord($data[$pos+1]) << 8) | (ord($data[$pos+2]) << 16) | $_ord_24;
	}
	
	/**
	 * Reads first 8 bytes of a string and return IEEE 754 float
	 *
	 * @param string $data Binary string that is at least 8 bytes long
	 * @return float
	 */
	private static function _extractNumber($data) {
		$rknumhigh = self::_GetInt4d($data, 4);
		$rknumlow = self::_GetInt4d($data, 0);
		$sign = ($rknumhigh & 0x80000000) >> 31;
		$exp = (($rknumhigh & 0x7ff00000) >> 20) - 1023;
		$mantissa = (0x100000 | ($rknumhigh & 0x000fffff));
		$mantissalow1 = ($rknumlow & 0x80000000) >> 31;
		$mantissalow2 = ($rknumlow & 0x7fffffff);
		$value = $mantissa / pow( 2 , (20 - $exp));
	
		if ($mantissalow1 != 0) {
			$value += 1 / pow (2 , (21 - $exp));
		}
	
		$value += $mantissalow2 / pow (2 , (52 - $exp));
		if ($sign) {
			$value *= -1;
		}
	
		return $value;
	}
	
	private static function _GetIEEE754($rknum)	{
		if (($rknum & 0x02) != 0) {
			$value = $rknum >> 2;
		} else {
			// changes by mmp, info on IEEE754 encoding from
			// research.microsoft.com/~hollasch/cgindex/coding/ieeefloat.html
			// The RK format calls for using only the most significant 30 bits
			// of the 64 bit floating point value. The other 34 bits are assumed
			// to be 0 so we use the upper 30 bits of $rknum as follows...
			$sign = ($rknum & 0x80000000) >> 31;
			$exp = ($rknum & 0x7ff00000) >> 20;
			$mantissa = (0x100000 | ($rknum & 0x000ffffc));
			$value = $mantissa / pow( 2 , (20- ($exp - 1023)));
			if ($sign) {
				$value = -1 * $value;
			}
			//end of changes by mmp
		}
		if (($rknum & 0x01) != 0) {
			$value /= 100;
		}
		return $value;
	}
	
	/**
	 * load OLERead class
	 */
	private static function _loadClass() {
		if ( ! class_exists('PHPExcel_Shared_OLERead', false)) {
			require 'OLERead.php';
		}
	}
}
