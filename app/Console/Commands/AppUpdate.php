<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApkController;
use App\Models\App;
use App\Models\LogUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Weidner\Goutte\GoutteFacade;

class AppUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

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
        $apks = App::where("type_upload", "auto")->get();
        $apkController = new ApkController();
        // Foreach Apps in --role argument
        foreach($apks as $apk)
        {
            // Step-1: Call Google Play API Service.
            $google = new \GooglePlay();
            $app = $google->parseApplication($apk->package_name, "vi");
            // Step-2: Check APP Version.
            // If version matches "variable" word
            if($apk->price == 0) {
                $req = $apkController->modtodoAPI($apk->package_name);
                if ($app["versionName"] == "Varies with device") {
                    // Step-3: Check App version from 3rd Service. Go step 4.
                    $app["versionName"] = $req["version"]; // remake app version name
                }

                // Step-4: If App Version does not change, skips this app. Else go to step 5.
                if ($app["versionName"] == $apk->version) {
                    echo $apk->title . " ---- nothing to update.". "\n";
                } else {
                    try {
                        // Step-5: Call Route Update File.

                        echo $apk->title . " ---- update " . $app["versionName"]. "\n";
                        $apkPath = public_path("uploads/apks/$apk->package_name");
                        if (\File::exists( $apkPath)){
                            \File::cleanDirectory($apkPath); // clear old version
                        }
                        $update = Http::get(route("getApk", $apk->package_name));
                        App::where("id", $apk->id)->update(["icon" => $update["icon"],
                            "version" => $update["versionName"],
                            "size" => $update["size"],
                            "apkFile" => $update["location"]]);
                        LogUpdate::create(["icon" => $apk->icon, "name" => $apk->title, "version" => $apk->version. " >>> ". $app["versionName"]]);

                    } catch (Throwable $exception){
                        echo $apk->title . " ---- update Failed" . "\n";
                        LogUpdate::create(["icon" => $apk->icon, "name" => $apk->title, "version" => "Failed"]);
                    }
                }
            }

        }

    }
}
