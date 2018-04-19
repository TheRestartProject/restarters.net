<?php

/**
 * Script to read in CSV files of translations.
 * Outputs them to a PHP file for each language.
 * The output language files are composed of one array,
 * keyed by the English version of a piece of text, and
 * the values are the translations for the given language.
 *
 * Example usage from root Fixo folder:
 * php scripts/create_translations.php data/translations-source lang/
 */

require 'vendor/league/csv/autoload.php';

use League\Csv\Reader;

$input_folder = $argv[1];
$output_folder = $argv[2];
$translations = array();

// Pull the translations into an array of arrays. 1 array for each language,
// keyed by the short code for that language. Each language array is keyed by
// the English version of the particular piece of text, and the value is the
// translated version for that language.

echo "\nReading files from $input_folder...";

$dir = new DirectoryIterator($input_folder);

echo "\n";
foreach ($dir as $fileInfo)
{
    if (!$fileInfo->isFile())
        continue;

    $source_file = $fileInfo->getPathname(); 

    echo "\nProcessing $source_file...";

    $reader = Reader::createFromPath($source_file);
    $reader->setHeaderOffset(0);

    foreach($reader->getRecords() as $row)
    {
        $key = htmlentities($row['en'], ENT_QUOTES);
        $translations['en'][$key] = htmlentities($row['en'], ENT_QUOTES);
        $translations['it'][$key] = htmlentities($row['it'], ENT_QUOTES);
        $translations['no'][$key] = htmlentities($row['no'], ENT_QUOTES);
        $translations['de'][$key] = htmlentities($row['de'], ENT_QUOTES);
    }
}


// Write out a PHP file for each language.

echo "\n\nOutputting translation files to $output_folder...";


foreach ($translations as $language => $language_translations)
{
    $fh = fopen("$output_folder$language.php", "w");
    if (!is_resource($fh)) {
        return false;
    }

    fwrite($fh, "<?php\n");
    fwrite($fh, "\$translations = array(");

    foreach ($language_translations as $key => $value)
    {
        fwrite($fh, sprintf("\n    '%s' => '%s',", $key, $value));
    }

    fwrite($fh, "\n);");
    fclose($fh);
}

echo "\n\nComplete.\n";
