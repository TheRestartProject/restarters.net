<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class LanguageSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'language:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise language preference from Restarters to Mediawiki.';

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
    public function handle(): void
    {
        // We want to sync the language preference from restarters to mediawiki.
        //
        // We cannot do this via the API because it doesn't permit it (see
        // https://www.mediawiki.org/wiki/Topic:U5t6jcbpkqa6yw8y).
        //
        // We cannot do it when the user changes their language, because at that point we do not have the password
        // in hand to log in to mediawiki.
        //
        // So we go directly in via the database.  This will also handle any pre-existing users.
        if (! env('WIKI_DB')) {
            $this->error('Wiki integration not enabled');
        } else {
            $dsn = 'mysql:host='.env('WIKI_HOST', 'localhost') . ';port=3306;dbname='.env('WIKI_DB').';charset=utf8';
            $mwdb = new \PDO($dsn, env('WIKI_DB_USER'), env('WIKI_DB_PASSWORD'), [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $users = User::all();

            foreach ($users as $user) {
                if ($user->mediawiki) {
                    $mwusers = $mwdb->query('SELECT user_id FROM mw_user WHERE user_name LIKE '.$mwdb->quote($user->mediawiki));

                    foreach ($mwusers as $mwuser) {
                        // Mediawiki doesn't have fr-BE so just going with main locale.
                        $locale = explode('-', $user->language)[0];

                        // The mw_user_properties table doesn't have a unique index so we need to check if an
                        // entry already exists.  This also allows us to reduce the update ops for performance.
                        $props = $mwdb->query("SELECT * FROM mw_user_properties WHERE up_user = {$mwuser['user_id']} AND up_property = 'language';");

                        $existing = false;

                        foreach ($props as $prop) {
                            $existing = true;

                            if (strcmp($prop['up_value'], $locale)) {
                                $this->info("Update preference $locale for {$user->mediawiki} ");
                                $mwdb->exec('UPDATE mw_user_properties SET up_value = '.$mwdb->quote($locale)." WHERE up_user = {$mwuser['user_id']} AND up_property = 'language';");
                            }
                        }

                        if (! $existing) {
                            $this->info("Add language preference $locale for {$user->mediawiki} ");
                            $mwdb->exec("INSERT INTO mw_user_properties (up_user, up_property, up_value) VALUES ({$mwuser['user_id']}, 'language', ".$mwdb->quote($locale).');');
                        }
                    }
                }
            }
        }
    }
}
