# Commatose
Manipulate CSV files on the fly with ease.

## Usage

```
// Instantiate Commatose
$csv = new Commatose();

// Grab a file (and, optionally, indicate that you have a header row)
$csv->fromPath('/your/file/path', $has_header_row = false);

// or, load from a string
$csv->fromText($your_csv_text, $has_header_row = false);

// you can tell Commatose if you want the output wrapped in quotes (default is true, which is safest)
$csv->wrap_quotes = true;

// you can also specify a separator for output other than a comma
$csv->separator = ';';

// but I like commas
$csv->separator = ',';

// you can add a column to you CSV, including column data and a default for empty rows
$csv->addColumn($new_header = null, array $new_values = array(), $default_value = '');

// you can also copy an existing column to a new column
// if overwrite is false and the new column already exists, only empty columns will be overwritten in the copy
$csv->copyColumn($index_or_header, $new_header = null, $overwrite = true);

// maybe you just want to rename a column to something else
$csv->renameColumn($index_or_header, $new_header);

// you can, of course, remove columns too
$csv->deleteColumn($index_or_header);

// transformColumn lets you apply a function to every value in a column
// you can use a lambda or anything that call_user_func accepts
$csv->transformColumn($index_or_header, $callable)

// with explodeColumn, you split a single column into many columns
// for example, let's say you have a column with values like "color:red;size:large;shape:round"
// explodeColumn will break these into three distinct columns with correct headers
$csv->explodeColumn($index_or_header, $first_level_separator = ';', $second_level_separator  = ':', $deleteColumn = true)

// if you'd like to see what your CSV looks like, you can spit out a table:
echo $csv->toHtml();

// when you're through, you can output to a string or a file
$output = $csv->toText();
$csv->toPath('/your/output/path');

```
