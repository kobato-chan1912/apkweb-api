<?php

namespace App\Console\Commands;

use App\Http\Controllers\ApkController;
use App\Models\App;
use App\Models\AppTranslation;
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
        $apks = App::where("type_upload", "auto")->where("package_name", "!=", "no_chplay")->where("off_update", 0)->get();
        // Foreach Apps in --role argument
        foreach($apks as $apk)
        {


            try {
                $updateUrl = env("APP_URL"). "/api/apk/$apk->package_name";
                $update = Http::get($updateUrl, ["ver" => $apk->version])->json();
                if ($update["status"] == 200){
                    $pattern = '/(\d+(\.\d+)+)/';
                    $replacement = $update["versionName"];
                    $translations = AppTranslation::where("app_id", $apk->id)->get();
                    foreach ($translations as $translation){
                        $meta_title = $translation->meta_title;
                        $meta_description = $translation->meta_description;
                        $newMetaTitle = preg_replace($pattern, $replacement, $meta_title);
                        $newMetaDescription = preg_replace($pattern, $replacement, $meta_description);
                        \App\Models\AppTranslation::where("id", $translation->id)
                            ->update(["meta_title" => $newMetaTitle, "meta_description" => $newMetaDescription]);
                    }


                    App::find($apk->id)->update([
                        "obb" => $update["obb"],
                        "icon" => $update["icon"],
                        "version" => $update["versionName"],
                        "size" => $update["size"],
                        "apkFile" => $update["location"],
                    ]);



                    LogUpdate::create(["icon" => $apk->icon, "name" => $apk->title, "version" => $apk->version. " >>> ". $update["versionName"]]);

                }
            } catch (\Exception $e){
                LogUpdate::create(["icon" => $apk->icon, "name" => $apk->title, "version" => "Failed"]);

            }



        }

    }
}
