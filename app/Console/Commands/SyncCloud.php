<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncCloud extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:cloud';

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
        $apks = App::where("type_upload", "auto")->where("package_name", "!=", "no_chplay")->where("apkFile", "!=", null)->where("off_update", 0)->get();
        foreach ($apks as $apk)
        {
            if (Str::contains($apk->apkFile, env("APP_URL")))
            {
                echo "Syncing $apk->title\n";
                $id = $apk->package_name;
                $location = Str::replace(env("APP_URL"), "",  $apk->apkFile);

                $fileName = basename($location);
                $path = public_path($location);

                $endPath = "jotta:apks/$id";
                shell_exec("rclone delete '$endPath'");
                shell_exec("rclone copy '$path' '$endPath'");
                \File::deleteDirectory(public_path("uploads/apks/$id"));
                $location = exec("rclone link $endPath/$fileName");
                $apk->update(["apkFile" => $location]);
            }

        }
    }
}
