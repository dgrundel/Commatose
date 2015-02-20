<?php

class Commatose {
	
	public $wrap_quotes = true;
	public $separator = ',';
	public $line_ending = "\n";
	public $valid_file_extensions = array('csv');

	protected $data = null;
	protected $header_row = null;
	protected $row_length = null;

	/*
	 * Constructor
	 *
	 * You may pass the constructor CSV data as text or a file path to read from.
	 *
	 * The second argument is a boolean that indicates whether or not the data contains a header row.
	 *
	 * WARNING: You should not directly pass user input to the constructor.
	 * No attempt is made to escape the value received.
	 *
	 * The safest way to parse user provided CSV data is to use fromText:
	 * $csv = new Commatose();
	 * $csv->fromText($the_user_input);
	 *
	 * This avoids the possibility that a user could pass you a valid local file path 
	 * rather than the CSV data you expect.
	 */
	public function __construct($text_or_path = null, $has_header_row = false) {
		if(!empty($text_or_path)) {
			if(is_file($text_or_path) !== false) {
				$this->fromPath($text_or_path, $has_header_row);
			} else {
				$this->fromText($text_or_path, $has_header_row);
			}
		}
	}

	/*
	 * Validates file extension of a give path.
	 */
	public function validateFileExtension($path) {
		if($this->valid_file_extensions === false) {
			return true;
		}

		if(!is_string($path)) {
			throw new Exception("validateFileExtension: path is not a string.");
		}
		if(!is_array($this->valid_file_extensions)) {
			throw new Exception("validateFileExtension: valid_file_extensions is not an array.");
		}
		$extension = array_pop(explode('.', $path));
		return in_array($extension, $this->valid_file_extensions);
	}

	/*
	 * Parses a data set from a CSV file.
	 */
	public function fromPath($path) {
		if(!validateFileExtension($path)) {
			throw new Exception("fromPath: file extension not allowed.");
		}
		if(!is_file($path)) {
			throw new Exception("fromPath: path is not a file.");
		}

		$text = file_get_contents($path);
		if($text === false) {
			throw new Exception("fromPath: file_get_contents returned false.");
		}
		$this->fromText($text);
	}

	/*
	 * Parses a data set from a CSV string.
	 */
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

	/*
	 * Sets the row length for the data set based on the header row (if present) or the first row of data.
	 */
	public function updateRowLength() {
		if($this->header_row !== null) {
			$this->row_length = count($this->header_row);
		} elseif (count($this->data)) {
			$this->row_length = count($this->data[0]);
		} else {
			throw new Exception("updateRowLength: no data to count.");
		}
	}

	/*
	 * Validates consistency of row lengths in data set.
	 */
	public function validateRowLengths(array $data = null) {
		if($data === null) {
			$data = $this->data;
		}

		if($this->row_length === null) {
			throw new Exception("validateRowLengths: no row length set.");
		}

		array_map(function($line) {
			if(count($line) !== $this->row_length) {
				throw new Exception("validateRowLengths: row length inconsistent. line: " . $this->lineToText($line));
			}
		}, $data);
	}

	/*
	 * Escapes a single column value for CSV output.
	 */
	public function escapeColValue($col_value) {
		return str_replace('"', '""', $col_value);
	}

	/*
	 * Returns a single row of data formatted for CSV output.
	 */
	public function lineToText(array $line) {
		$line = array_map(array($this, 'escapeColValue'), $line);

		if($this->wrap_quotes) {
			return '"' . implode("\"{$this->separator}\"", $line) . '"';
		} else {
			return implode($this->separator, $line);
		}
	}

	/*
	 * Returns the data set as a CSV string.
	 */
	public function toText() {
		$output = array();
		if($this->header_row !== null) {
			$output[] = $this->lineToText($this->header_row);
		}
		foreach($this->data as $line) {
			$output[] = $this->lineToText($line);
		}
		return implode($this->line_ending, $output);
	}

	/*
	 * Writes the data set as a CSV to a file path.
	 */
	public function toPath($path) {
		$bytes = file_put_contents($path, $this->toText());

		if($bytes === false) {
			throw new Exception("toPath: file_put_contents returned false.");
		}
	}

	/*
	 * Send the CSV to the browser as a downloadable file.
	 */
	public function toDownload($filename = null) {
		if(empty($filename)) {
			$filename = 'download.csv';
		}

		$filename = str_replace('"', '\'', $filename);

		$text = $this->toText();

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . strlen($text));

		echo $text;
		
		exit;
	}

	/*
	 * Returns the data set as an HTML table with row indexes and headers when present.
	 */
	public function toHtml() {
		$html = '<table>';
		
		$html .= '<thead><tr><th>#</th>';
		if($this->header_row !== null) {
			foreach($this->header_row as $col_name) {
				$html .= '<th>' . htmlspecialchars($col_name) . '</th>';
			}
		} else {
			for($header_index = 0; $header_index < $this->row_length; $header_index++) {
				$html .= '<th>' . $header_index . '</th>';
			}
		}
		$html .= '</tr></thead><tbody>';

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

	/*
	 * Returns true if the column exists in the header row.
	 */
	public function columnExists($index_or_header) {
		if($this->header_row === null) {
			throw new Exception("columnExists: no header row present.");
		}
		return in_array($index_or_header, $this->header_row);
	}

	/*
	 * Gets the 0-based index of the column in the header and/or data set.
	 *
	 * Throws an exception if $index_or_header is not found, so it is best to check 
	 * if the column exists (via columnExists) prior to using this function.
	 */
	public function getColIndex($index_or_header) {
		if(empty($index_or_header)) {
			throw new Exception("getColIndex: index_or_header cannot be empty.");
		}

		$col_index = false;
		if(is_numeric($index_or_header)) {
			$col_index = intval($index_or_header);

		} elseif($this->header_row !== null) {
			$col_index = array_search($index_or_header, $this->header_row);
		}
		if($col_index === false) {
			throw new Exception("getColIndex: unknown index_or_header '{$index_or_header}'.");
		}
		return $col_index;
	}

	/*
	 * Applies a callback function to each row of a particular column in the data set.
	 */
	public function transformColumn($index_or_header, $callable) {
		$col_index = $this->getColIndex($index_or_header);

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$this->data[$data_index][$col_index] = call_user_func($callable, $this->data[$data_index][$col_index]);
		}
	}

	/*
	 * Copies a column to a new or existing column.
	 *
	 * When overwrite is false and the destination column exists, only empty values are overwritten in the destination.
	 */
	public function copyColumn($index_or_header, $new_header = null, $overwrite = true) {
		if($new_header === null && $this->header_row !== null) {
			throw new Exception("copyColumn: new_header is required with header row present.");
		}
		if($new_header !== null && $this->header_row === null) {
			throw new Exception("copyColumn: new_header must be null when no header row present.");
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

	/*
	 * Adds a new column to the data set.
	 *
	 * $new_header is optional when no header row is present.
	 *
	 * When an array of $new_values is passed, values are put into the new column 
	 * matching the 0-based array indexes with the 0-based row indexes of the data set.
	 *
	 * The $default_value is used when no value is found in $new_values for the corresponding new row.
	 */
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

	/*
	 * Rename a column header. 
	 *
	 * Any references to this column after the rename operation should reference the new name.
	 */
	public function renameColumn($index_or_header, $new_header) {
		if($this->header_row === null) {
			throw new Exception("renameColumn: no header row present.");
		}

		$col_index = $this->getColIndex($index_or_header);
		$this->header_row[$col_index] = $new_header;
	}

	/*
	 * Delete a column from the data set. 
	 *
	 * Both the column header and the data in the column are removed and the columns are reindexed.
	 */
	public function deleteColumn($index_or_header) {
		$col_index = $this->getColIndex($index_or_header);
		
		array_splice($this->header_row, $col_index, 1);

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			array_splice($this->data[$data_index], $col_index, 1);
		}

		$this->updateRowLength();
	}

	/*
	 * Explode a column into many columns.
	 *
	 * A simple, single level explode takes a column header like 'Colors' with a value like 'red,green,blue'
	 * and splits it into three columns with column headers 'Colors0', 'Colors1', and 'Colors2'
	 *
	 * A two-level explode takes values like 'color:red;size:large;shape:round'
	 * and splits it into three columns with column headers 'color', 'size', and 'shape' 
	 * with the corresponding values in their respective rows. 
	 * The source column header does not affect the resulting rows.
	 *
	 * When $deleteColumn is true, the original column is removed from the data set, leaving only the new columns.
	 */
	public function explodeColumn($index_or_header, $first_level_separator, $second_level_separator = null, $deleteColumn = false) {
		$col_index = $this->getColIndex($index_or_header);

		$new_columns = array();

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$first_level_items = explode($first_level_separator, $this->data[$data_index][$col_index]);
			foreach ($first_level_items as $first_level_item_index => $first_level_item) {
				
				if($second_level_separator !== null) {
					$second_level_pair = explode($second_level_separator, $first_level_item);
					if(count($second_level_pair) !== 2) {
						throw new Exception("explodeColumn: second level pair not split.");
					}
					$new_columns[ $second_level_pair[0] ][$data_index] = $second_level_pair[1];
				} else {
					$new_columns[ $index_or_header . $first_level_item_index ][$data_index] = $first_level_item;
				}
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

	/*
	 * Explode rows with multiple values per column into many rows with one value per column.
	 */
	public function explodeRows($index_or_header, $separator) {
		$col_index = $this->getColIndex($index_or_header);
		$new_rows = array();
		$delete_rows = array();

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$col_values = explode($separator, $this->data[$data_index][$col_index]);
			if(count($col_values) > 1) {
				$new_row = $this->data[$data_index];
				foreach ($col_values as $col_value) {
					$new_row[$col_index] = $col_value;
					$new_rows[] = $new_row;
				}
				$delete_rows[] = $data_index;
			}
		}

		foreach ($delete_rows as $data_index) {
			unset($this->data[$data_index]);
		}

		//reindex and add new rows
		if(count($new_rows)) {
			$this->data = array_merge(array_values($this->data), $new_rows);
		}
	}

	/*
	 * Returns true when all values in a column are unique
	 */
	public function columnValuesUnique($index_or_header) {
		$col_index = $this->getColIndex($index_or_header);

		$col_values = array_column($this->data, $col_index);
		$unique_values = array_unique($col_values);
		return count($col_values) === count($unique_values);
	}

	/*
	 * Combine the values of two or more columns using a callback function to modify the values
	 */
	public function combineColumns(array $indexes_or_headers, $new_header, $callable, $deleteColumns = false) {
		$col_indexes = array();
		foreach ($indexes_or_headers as $index_or_header) {
			$col_indexes[] = $this->getColIndex($index_or_header);
		}

		if($this->header_row !== null) {
			$this->header_row[] = $new_header;
		}

		for($data_index = 0; $data_index < count($this->data); $data_index++) {
			$col_values = array();
			foreach ($col_indexes as $col_index) {
				$col_values[] = $this->data[$data_index][$col_index];
			}

			$this->data[$data_index][] = call_user_func_array($callable, $col_values);
		}

		if($deleteColumns) {
			$this->deleteColumn($index_or_header_a);
			$this->deleteColumn($index_or_header_b);
		} else {
			$this->updateRowLength();
		}
	}
}