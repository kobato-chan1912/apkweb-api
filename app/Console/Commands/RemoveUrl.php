<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\Mod;
use Illuminate\Console\Command;

class RemoveUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $apps = App::where(function($query) {
            $query->where("package_name", "no_chplay")
                ->orWhere("price", "!=", "Free");
        })->get();

        foreach ($apps as $app)
        {
            $app->update([
                "apkFile" => null,
                "mediafire" => null,
                "is_mediafire" => 0
            ]);

            Mod::where("app_id", $app->id)->update([
                "modUrl" => null,
                "is_mediafire" => 0,
                "mediafire" => null
            ]);

        }

    }
}
