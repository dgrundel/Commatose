# Commatose
Manipulate CSV files on the fly with ease.

## Usage

### Instantiate and Load CSV Data

```
// Instantiate Commatose
$csv = new Commatose();

// Grab a file (and, optionally, indicate that you have a header row)
$csv->fromPath('/your/file/path', $has_header_row = false);

// or, load from a string
$csv->fromText($your_csv_text, $has_header_row = false);

```

### Adding Columns

You can add a column to your CSV, including column data and a default for empty rows.

```
$csv->addColumn($new_header_name = null, array $new_values = array(), $default_value = '');

```

### A Quick Note About $index_or_header

The methods below accept a parameter named $index_or_header.

The easiest route is to use a CSV with a header row and reference columns using those header names.

However, if you don't have a header row, you can supply a 0-based integer column index.

### Copying Columns

You can also copy an existing column to a new column (or over another existing column.)

If overwrite is false and the new column already exists, only empty columns will be overwritten in the copy.

```
$csv->copyColumn($index_or_header, $new_header = null, $overwrite = true);

```

### Rename a Column

Maybe you just want to change the column header. No sweat.

```
$csv->renameColumn($index_or_header, $new_header);

```

### Remove a Column

You can, of course, remove columns too. (Note: this will reindex the columns.)

```
$csv->deleteColumn($index_or_header);

```

### Transform Values in a Column

transformColumn lets you apply a function to every value in a column. It's like array_map for a single column.

You can use a lambda or anything that call_user_func accepts.

```
$csv->transformColumn($index_or_header, $callable)

```

### Explode a Column into Many Columns

With explodeColumn, you split a single column into many columns.

For example, let's say you have a column with values like "color:red;size:large;shape:round"

explodeColumn will break these into three distinct columns with correct headers and values.

```
$csv->explodeColumn($index_or_header, $first_level_separator = ';', $second_level_separator  = ':', $deleteColumn = true)

```

### Output Options

```
// you can tell Commatose if you want the output wrapped in quotes (default is true, which is safest)
$csv->wrap_quotes = true;

// you can also specify a separator for output other than a comma
$csv->separator = ';';

// you specify the line endings your operating system prefers
$csv->line_ending = "\r\n"; // "\n" is default

// if you'd like to see what your CSV looks like, you can spit out a table:
echo $csv->toHtml();

// when you're through, you can output to a string or a file
$output = $csv->toText();
$csv->toPath('/your/output/path');

```
