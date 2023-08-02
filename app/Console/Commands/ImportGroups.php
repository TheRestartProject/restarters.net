<?php

namespace App\Console\Commands;

use App\Helpers\Fixometer;
use App\User;
use Illuminate\Console\Command;

class ImportGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:groups {input}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk import of groups.';

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
        $inputFile = fopen($input, 'r');

        if (!$inputFile)
        {
            $this->error('Could not open input file.');
            return;
        }

        // First line is headers.
        fgetcsv($inputFile);

        while (!feof($inputFile))
        {
            $fields = fgetcsv($inputFile);
            if ($fields) {
                // Convert charset so that we handle accented characters.
                $fields = array_map( function($str) {
                    return iconv( "iso-8859-15", "UTF-8", $str );
                }, $fields);

                // Format is  'Name', 'Location', 'Postcode', 'Area', 'CountryCode', 'Latitude', 'Longitude', 'Website', 'Phone', 'Networks', 'Description'.

                $groupname = $fields[0];
                $location = $fields[1];
                $postcode = $fields[2];
                $area = $fields[3];
                $country = $fields[4];
                $lat = $fields[5];
                $lng = $fields[6];
                $website = $fields[7];
                $phone = $fields[8];
                $networks = $fields[9];
                $description = $fields[10];

                // Validate.
                if (!$groupname) {
                    error_log("Invalid empty group name");
                    exit(1);
                }

                if (!$location) {
                    error_log("Invalid empty location");
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

                if ($description) {
                    // Basic validation for terrible HTML.
                    $xml = simplexml_load_string("<html><body>$description</body></html>");

                    if (count(libxml_get_errors())) {
                        error_log("Invalid HTML description $description");
                        exit(1);
                    }
                }

                // Create the group.
                try {
                    $group = new \App\Group();
                    $group->name = $groupname;
                    $group->location = $location;
                    $group->postcode = $postcode;
                    $group->area = $area;
                    $group->latitude = $lat;
                    $group->longitude = $lng;
                    $group->country_code = $country;
                    $group->website = $website;
                    $group->phone = $phone;
                    $group->free_text = $description;
                    $group->shareable_code = Fixometer::generateUniqueShareableCode(\App\Group::class, 'shareable_code');

                    // All these groups are approved.
                    $group->approved = true;

                    $group->save();

                    if ($networks) {
                        $networks = explode(',', $networks);
                        foreach ($networks as $network) {
                            $n = \App\Network::findOrFail($network);
                            $n->addGroup($group);
                        }
                    }
                } catch (\Exception $e) {
                    error_log("ERROR: failed to create group $groupname: " . $e->getMessage());
                }
            }
        }
    }
}
