<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Symfony\Component\VarDumper\Dumper\esc;

class ImportMRES extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:mres {input} {output} {commands} {--networks=CSV list of ids}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One-off script to import MRES groups.  This takes a CSV of the groups in MRES format and outputs a CSV in our standard import format.  You can convert from the ODS file to CSV; make sure you select "Western Europe (ISO08859-15/EURO)" as the Character set.  It also outputs a command script with user creation commands.';

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
        $commands = $this->argument('commands');
        $networks = $this->option('networks');

        $inputFile = fopen($input, 'r');
        $outputFile = fopen($output, 'w');
        $commandsFile = fopen($commands, 'w');

        // First three lines are headers.
        fgetcsv($inputFile);
        fgetcsv($inputFile);
        fgetcsv($inputFile);

        // Write headers to output.
        fputcsv($outputFile, ['Name', 'Location', 'Postcode', 'Area', 'Country', 'Latitude', 'Longitude', 'Website', 'Phone', 'Networks', 'Description']);

        $creating = [];

        while (!feof($inputFile))
        {
            $fields = fgetcsv($inputFile);

            if ($fields) {
                $groupname = trim($fields[0]);
                $email = trim($fields[1]);
                $phone = trim($fields[2]);
                $inscription = trim($fields[3]);
                $hostname = trim($fields[4]);
                $organisateur = trim($fields[5]);
                $dates = trim($fields[6]);
                $regroupements = trim($fields[7]);
                $website = trim($fields[8]);
                $lat = trim($fields[9]);
                $lng = trim($fields[10]);
                $address = trim($fields[11]);
                $ville = trim($fields[12]);
                $cp = trim($fields[13]);
                $website2 = trim($fields[14]);

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
                    $description .= "<p>Dates et horaires: " . htmlspecialchars($dates, ENT_COMPAT,'ISO-8859-15', true) . "</p>";
                }

                if ($organisateur) {
                    $description .= "<p>Organisateur: " . htmlspecialchars($organisateur, ENT_COMPAT,'ISO-8859-15', true) . "</p>";
                }

                if ($inscription) {
                    $description .= "<p>Inscription: " . htmlspecialchars($inscription, ENT_COMPAT,'ISO-8859-15', true) . "</p>";
                }

                if ($regroupements) {
                    $description .= "<p>Regroupement: " . htmlspecialchars($regroupements, ENT_COMPAT,'ISO-8859-15', true) . "</p>";
                }

                $website = str_replace('http://', 'https://', $website);

                fputcsv($outputFile,
                        [
                            $groupname,
                            $location,
                            $cp,
                            $ville,
                            'France',
                            $lat,
                            $lng,
                            $website,
                            $phone,
                            $networks,
                            $description,
                        ]);

                // Now the host, if any.
                if (!$email) {
                    // We default to Enzo.
                    $email = "e.mandrin@mres-asso.fr";
                    $hostname = "Enzo Mandrin";
                }

                if (!$hostname) {
                    // We use the LHS of the email.
                    $hostname = explode("@", $email)[0];
                }

                // Random password.
                $password = Str::random(32);

                if (User::where('email', '=', $email)->count() == 0 && !array_key_exists($email, $creating)) {
                    // User doesn't exist, create it.
                    $creating[$email] = true;
                    fwrite($commandsFile, "php artisan user:create " . escapeshellarg($hostname) . " " . escapeshellarg($email) . " " . escapeshellarg($password) . "\n");
                    fwrite($commandsFile, "php artisan user:makehost " . escapeshellarg($email) . " " . escapeshellarg(utf8_encode($groupname)) . "\n");
                }
            }
        }
    }
}
