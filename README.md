# Commatose
Manipulate CSV files on the fly with ease.

## Usage

**See source code comments for additional usage information.**

### Instantiate and Load CSV Data

```
// Instantiate Commatose
$csv = new Commatose();

// Grab a file (and, optionally, indicate that you have a header row)
$csv->fromPath('/your/file/path.csv', $has_header_row = false);

// or, load from a string
$csv->fromText($your_csv_text, $has_header_row = false);
```

#### Constructor Shortcuts

You may also pass your file path or CSV data to the constructor, as well as a boolean indicating whether or not a header row is present.

```
$csv = new Commatose('/your/file/path.csv', $has_header_row = false);
//or
$csv = new Commatose($your_csv_text, $has_header_row = false);
```

**WARNING:** You should not directly pass user input to the constructor.
No attempt is made to escape the value received.

The safest way to parse user provided CSV data is to use fromText:
```
$csv = new Commatose();
$csv->fromText($the_user_input);
```

This avoids the possibility that a user could pass you a valid local file path rather than the CSV data you expect.

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

### Check a Column for Uniqueness

Returns a boolean to indicate if the values in a particular column are unique in that column.

For example, in a CSV of products, you might want to verify that your SKU column contains only unique values.

```
$is_sku_unique = $csv->columnValuesUnique('sku');
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

If second_level_separator is omitted, column headers will be automatically generated based on the original column header.

```
$csv->explodeColumn($index_or_header, $first_level_separator, $second_level_separator = null, $deleteColumn = false)
```

### Combine Many Columns into One

Combine the values of two or more columns using a callback function to modify the values.

Column values are passed as individual arguments to your callback function (via call_user_func_array) in the order specified in your indexes_or_headers array.

```
$csv->combineColumns(array $indexes_or_headers, $new_header, $callable, $deleteColumns = false)
```

### Explode Rows with Multiple Values per Column

Similar to exploding columns, you can also explode rows that have multiple values per column into many rows with one value per column.

So, one row of data with a column of 'red;green;blue' could be exploded into three distinct rows. (Other columns in the row are duplicated.)

```
$csv->explodeRows($index_or_header, $separator);
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

### Using Files Without a .csv Extension

**By default, only files with a .csv extension can be loaded.**

You can set which file extensions Commatose will allow by setting the valid_file_extensions array:

```
$csv->valid_file_extensions = array('csv', 'txt');
```

You can also set valid_file_extensions to boolean false to completely disable this check (not advisable!)
```
$csv->valid_file_extensions = false; //do not check file extensions
```

### Commatose Throws Exceptions

Fair warning: Rather than fail silently (or just returing false here and there) and keep you guessing, Commatose likes to throw exceptions.

It is advisable when working with unfamiliar data to wrap some or all of your calls to Commatose in a try...catch block to avoid nasty errors from being displayed to your users.