<?php

class Commatose {
	
	public $wrap_quotes = true;
	public $separator = ',';

	protected $data = null;
	protected $header_row = null;
	protected $row_length = null;

	public function __construct($text = null, $has_header_row = false) {
		if(!empty($text)) {
			$this->fromText($text, $has_header_row);
		}
	}

	public function fromPath($path) {
		$text = file_get_contents($path);
		if($text === false) {
			throw new Exception("fromPath: file_get_contents returned false.");
		}
		$this->fromText($text);
	}

	public function fromText($text, $has_header_row = false) {
		$csv_lines = explode("\n", str_replace("\r\n", "\n", $text));
		$csv_lines = array_map('trim', $csv_lines);
		$csv_lines = array_filter($csv_lines);
		$csv_lines = array_map('str_getcsv', $csv_lines);

		if(count($csv_lines) === 0) {
			throw new Exception("fromText: no rows found.");
		}

		if($has_header_row) {
			$this->header_row = array_shift($csv_lines);
		}

		$this->data = $csv_lines;

		$this->updateRowLength();

		$this->validateRowLengths();
	}

	public function updateRowLength() {
		if($this->header_row !== null) {
			$this->row_length = count($this->header_row);
		} elseif (count($this->data)) {
			$this->row_length = count($this->data[0]);
		} else {
			throw new Exception("updateRowLength: no data to count.");
		}
	}

	public function validateRowLengths(array $data = null) {
		if($data === null) {
			$data = $this->data;
		}

		if($this->row_length === null) {
			throw new Exception("validateRowLengths: no row length set.");
		}

		array_map(function($line) {
			if(count($line) !== $this->row_length) {
				throw new Exception("validateRowLengths: row length inconsistent. line: " . var_export($line, true));
			}
		}, $data);
	}

	public function toText() {
		$output = array();
		if($this->header_row !== null) {
			$output[] = $this->lineToText($this->header_row);
		}
		foreach($this->data as $line) {
			$output[] = $this->lineToText($line);
		}
		return implode("\n", $output);
	}

	public function lineToText(array $line) {
		if($this->wrap_quotes) {
			return '"' . implode("\"{$this->separator}\"", $line) . '"';
		} else {
			return implode($this->separator, $line);
		}
	}

	public function toPath($path) {
		$bytes = file_put_contents($path, $this->toText());

		if($bytes === false) {
			throw new Exception("toPath: file_put_contents returned false.");
		}
	}

	public function toHtml() {
		$html = '<table>';
		
		if($this->header_row !== null) {
			$html .= '<thead><tr><th>#</th>';
			foreach($this->header_row as $col_name) {
				$html .= '<th>' . htmlspecialchars($col_name) . '</th>';
			}
			$html .= '</tr></thead><tbody>';
		}

		foreach($this->data as $row_index => $row) {
			$html .= '<tr><th>' . $row_index . '</th>';
			foreach($row as $col) {
				$html .= '<td>' . htmlspecialchars($col) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}

	public function columnExists($index_or_header) {
		return in_array($index_or_header, $this->header_row);
	}

	public function getColIndex($index_or_header) {
		if(empty($index_or_header)) {
			throw new Exception("getColIndex: index_or_header cannot be empty.");
		}

		$col_index = false;
		if(is_numeric($index_or_header)) {
			$col_index = intval($index_or_header);
		} else {
			$col_index = array_search($index_or_header, $this->header_row);
		}
		if($col_index === false) {
			throw new Exception("getColIndex: unknown index_or_header '{$index_or_header}'.");
		}
		return $col_index;
	}

	public function transformColumn($index_or_header, $callable) {
		$col_index = $this->getColIndex($index_or_header);

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$this->data[$data_index][$col_index] = call_user_func($callable, $this->data[$data_index][$col_index]);
		}
	}

	public function copyColumn($index_or_header, $new_header = null, $overwrite = true) {
		if($new_header === null && $this->header_row !== null) {
			throw new Exception("copyColumn: new_header is required with header row present.");
		}
		
		$col_index = $this->getColIndex($index_or_header);
		$new_col_index = null;
		if($new_header !== null && $this->columnExists($new_header)) {
			$new_col_index = $this->getColIndex($new_header);
		}
		if($new_col_index === null) {
			$this->header_row[] = $new_header;
		}

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			if($new_col_index === null) {
				$this->data[$data_index][] = $this->data[$data_index][$col_index];

			} elseif($overwrite || empty($this->data[$data_index][$new_col_index])) {
				$this->data[$data_index][$new_col_index] = $this->data[$data_index][$col_index];					
			}
		}

		$this->updateRowLength();
	}

	public function addColumn($new_header = null, array $new_values = array(), $default_value = '') {
		if($new_header === null && $this->header_row !== null) {
			throw new Exception("addColumn: new_header is required with header row present.");
		}
		
		if($this->header_row !== null) {
			$this->header_row[] = $new_header;
		}

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$this->data[$data_index][] = isset($new_values[$data_index]) ? $new_values[$data_index] : $default_value;
		}

		$this->updateRowLength();
	}

	public function renameColumn($index_or_header, $new_header) {
		if($this->header_row === null) {
			throw new Exception("renameColumn: no header row present.");
		}

		$col_index = $this->getColIndex($index_or_header);
		$this->header_row[$col_index] = $new_header;
	}

	public function deleteColumn($index_or_header) {
		$col_index = $this->getColIndex($index_or_header);
		
		array_splice($this->header_row, $col_index, 1);

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			array_splice($this->data[$data_index], $col_index, 1);
		}

		$this->updateRowLength();
	}

	public function explodeColumn($index_or_header, $first_level_separator = ';', $second_level_separator  = ':', $deleteColumn = true) {
		$col_index = $this->getColIndex($index_or_header);

		$new_columns = array();

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$first_level_pairs = explode($first_level_separator, $this->data[$data_index][$col_index]);
			foreach ($first_level_pairs as $first_level_pair) {
				$second_level_pair = explode($second_level_separator, $first_level_pair);
				if(count($second_level_pair) !== 2) {
					throw new Exception("explodeColumn: second level pair not split.");
				}
				$new_columns[ $second_level_pair[0] ][$data_index] = $second_level_pair[1];
			}
		}

		$new_column_names = array_keys($new_columns);

		foreach ($new_column_names as $col_name) {
			$this->header_row[] = $col_name;
		}

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			foreach ($new_column_names as $col_name) {
				$this->data[$data_index][] = isset($new_columns[$col_name][$data_index]) ? $new_columns[$col_name][$data_index] : '';
			}
		}

		if($deleteColumn) {
			$this->deleteColumn($index_or_header);
		} else {
			$this->updateRowLength();
		}

	}
}