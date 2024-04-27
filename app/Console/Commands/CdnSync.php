<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CdnSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cdn:sync';

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
        $apks = App::where('apkFile', 'LIKE', '%cdn.apkgosu.com%')->get();
        foreach ($apks as $apk)
        {
            echo "Syncing $apk->title\n";
            $id = $apk->package_name;
            $location = $apk->apkFile;
            $fileName = time(). "-". basename($location);
            $endPath = "jotta:apks/$id";
            shell_exec("rclone delete '$endPath'");
            shell_exec("rclone copy '$location' '$endPath'");
            $location = exec("rclone link $endPath/$fileName");
            $apk->update(["apkFile" => $location]);

        }

    }
}
