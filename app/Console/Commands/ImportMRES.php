<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class ImportMRES extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:mres {input} {output} {--networks=}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-off script to import MRES groups.  This takes a CSV of the groups in MRES format and outputs a CSV in our standard import format.  You can convert from the ODS file to CSV; make sure you select "Western Europe (ISO08859-15/EURO)" as the Character set.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $input = $this->argument('input');
        $output = $this->argument('output');
        $networks = $this->option('networks');

        $inputFile = fopen($input, 'r');
        $outputFile = fopen($output, 'w');

        // First three lines are headers.
        fgetcsv($inputFile);
        fgetcsv($inputFile);
        fgetcsv($inputFile);

        // Write headers to output.
        fputcsv($outputFile, ['Name', 'Location', 'Country', 'Latitude', 'Longitude', 'Website', 'Phone', 'Networks', 'Description']);

        while (!feof($inputFile))
        {
            $fields = fgetcsv($inputFile);

            if ($fields) {
                $groupname = $fields[0];
                $email = $fields[1];
                $phone = $fields[2];
                $inscription = $fields[3];
                $hostname = $fields[4];
                $organisateur = $fields[5];
                $dates = $fields[6];
                $website = $fields[7];
                $catsupport = $fields[8];
                $geoloc1 = $fields[9];
                $lat = $fields[10];
                $lng = $fields[11];
                $address = $fields[12];
                $ville = $fields[13];
                $cp = $fields[14];
                $website2 = $fields[15];

                // Validate.
                if (!$groupname) {
                    error_log("Invalid empty group name");
                    exit(1);
                }

                if ($website) {
                    // We may have accented characters in the URLs.  Which is OK, but PHP doesn't know that.
                    $path = parse_url($website, PHP_URL_PATH);
                    $encoded_path = array_map('urlencode', explode('/', $path));
                    $url = str_replace($path, implode('/', $encoded_path), $website);

                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        error_log("Invalid URL $website");
                        exit(1);
                    }
                }

                if (!is_numeric($lat) || !is_numeric($lng)) {
                    error_log("Invalid lat/lng $lat,$lng");
                    exit(1);
                }

                $location = "";

                if ($address) {
                    $location .= $address;
                }

                if ($ville) {
                    $location .= ($location ? ", " : "") . $ville;
                }

                if ($cp) {
                    $location .= ($location ? ", " : "") . $cp;
                }

                if (!$location) {
                    // There are a couple where there is no address information at all.  Just use the name.
                    $location = trim(preg_replace("/Repair Caf./i", "", $groupname));
                    error_log("WARNING: $groupname empty address fields, using $location");
                }

                // Construct.  We don't have a generic description, so it will be empty for most groups.
                $description = "";

                if ($dates) {
                    $description .= "<p>Dates et horaires: " . htmlspecialchars($dates) . "</p>";
                }

                if ($organisateur) {
                    $description .= "<p>Organisateur: " . htmlspecialchars($organisateur) . "</p>";
                }

                $website = str_replace('http://', 'https://', $website);
                $location = "$address, $ville, $cp";

                fputcsv($outputFile,
                        [
                            $groupname,
                            $location,
                            'France',
                            $lat,
                            $lng,
                            $website,
                            $phone,
                            $networks,
                            $description,
                        ]);
            }
        }
    }
}
