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
        $games = $games = [
            'Minecraft',
            'Minibus Simulator Vietnam',
            'GTA 5 Mobile',
            'Getting Over It',
            'College Brawl',
            'Back Alley Tales',
            'FIFA Mobile 22',
            'APK Editor Pro',
            'Haileys Treasure Adventure',
            'Project GLUTT',
            'Rise of Eros',
            'Tag After School',
            'Mobyblog',
            'Poppy Playtime Chapter 1',
            'Lulubox',
            'Z Legends 2',
            'Super Mario Bros',
            'Lovecraft Locker',
            'MMLive',
            'Touch Himawari',
            'Pocket Girl',
            'Echidna Wars DX',
            'Terraria',
            'Brotato',
            'Kisaki Blue Archive',
            'IziGames Online',
            'Survive!',
            'GearUp Booster',
            'Hole House',
            'Douyin',
            'Lucky Patcher',
            'ePSXe for Android',
            'Kaguya Player',
            '7554',
            'Storyteller',
            'Lạng Sơn 1978',
            'Kaiber AI',
            'Guzheng Master',
            'Isekai Brother',
            'Geometry Dash',
            'Human: Fall Flat',
            'Doraemon X',
            'Nicoo',
            'Shinobi Girl Mini',
            'KingRoot',
            'Last Train JK',
            'Utouto Suyasuya',
            'Luma AI',
            'Dogas Info',
            'Vector Full',
            'Farming Simulator 23',
            'Riders Republic',
            'Game Guardian',
            'Rage of Demon King',
            'Girl Life',
            'Forbidden Playground',
            'Learn The Heart',
            'Untitled Goose Game',
            'MapleStory M',
            'YouTube Revanced',
            'FF Support Data v2',
            'Syahata A Bad Day',
            'Seal',
            'Antutu Benchmark',
            'Squid Honey',
            'CocoNut Shake',
            'Jump Harem',
            'Apex Legends',
            'Teaching Feelings',
            'Another Girl In The Wall',
            'Happy Girl On Mirror',
            'BoobRun',
            'Lonely Girl',
            'SIGMAX',
            'Haunted House',
            'Castle of Temptation',
            'EA Sports FC Mobile Beta',
            'Need for Speed Most Wanted',
            'Azur Lane',
            'Railbound',
            'Dinkum',
            'Loopsie',
            'Sloven Classmate',
            'METAL SLUG',
            'Oh My Waifu',
            'Zip APKGosu',
            'House Chores',
            'Summer Memories',
            'Bulma 3H',
            'Once In A Lifetime',
            'Metal Slug 2',
            'Camp With Mom',
            'NSaFN',
            'Super Mamono Sisters',
            'Manilla Nobi Tamako',
            'Lost Life 2',
            'Night Adventure',
            'Droidify',
            'Deep sleep 2',
            'apkgstore',
            'Minecraft Java Edition',
            'RTS TV',
            'Adrenalina Gol',
            'Konoha Nights',
            'Mobdro Plus',
            'Optifine',
            'ApkZub',
            'APKPure',
            'Elderand',
            'Joy Pony',
            'TikTok 18',
            'GTA Miami',
            'Pebble Dash',
            'Mangakakalot',
            '94fbr GTA 5 Mobile',
            'Naughty Pirates'
        ];

        $apks = App::whereIn("title", $games)->get();
        foreach ($apks as $apk) {

            if ($apk->apkFile == null) {
                $titleSlug = Str::slug($apk->title);
                $location = "/home/flashvps/cdn.apkgosu.com/apkweb-backend/public/userfiles/apks/$titleSlug/$titleSlug.apk";
            } else {
                $location = Str::replace("https://cdn.apkgosu.com", "/home/flashvps/cdn.apkgosu.com/apkweb-backend/public", $apk->apkFile);
            }

            $id = $apk->package_name;
            echo "Syncing $apk->title". "\n";

            $fileName = basename($location);
            $path = $location;

            $endPath = "jotta:apks/$id";
            shell_exec("rclone delete '$endPath'");
            shell_exec("rclone copy '$path' '$endPath'");
            $location = exec("rclone link $endPath/$fileName");

            if (Str::contains($location, "jotta"))
            {
                $apk->update(["apkFile" => $location]);
            }
        }


    }
}
