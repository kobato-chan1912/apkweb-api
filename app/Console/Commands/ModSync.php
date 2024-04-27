<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\Mod;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ModSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mod:sync';

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
        $apks = Mod::where('modUrl', 'LIKE', '%cdn.apkgosu.com%')->get();
        foreach ($apks as $apk) {
            echo "syncing ". $apk->modUrl;
            $location = Str::replace("https://cdn.apkgosu.com", "/home/flashvps/cdn.apkgosu.com/apkweb-backend/public", $apk->modUrl);

            $fileName = basename($apk->modUrl);
            $endPath = "jotta:mods";

            shell_exec("rclone copy '$location' '$endPath'");
            $location = exec("rclone link $endPath/$fileName");

            if (Str::contains($location, "jotta")) {
                $apk->update(["modUrl" => $location]);
            }
        }
    }
}
