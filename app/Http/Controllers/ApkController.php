<?php

namespace App\Http\Controllers;
ini_set('memory_limit','4096M');

use App\Models\App;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Weidner\Goutte\GoutteFacade;
use function MongoDB\BSON\toJSON;
use function PHPUnit\Framework\isEmpty;
use ZipArchive;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
class ApkController extends Controller
{
    //
    function modtodoAPI($id): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://modtodo.com/getapk');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "pid=10686&appid=$id");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Authority: modtodo.com';
        $headers[] = 'Accept: */*';
        $headers[] = 'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7,fr-FR;q=0.6,fr;q=0.5,ja;q=0.4';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
        $headers[] = 'Cookie: connect.sid=s%3AuSVX1Kn55oDrCOwYTADSqfFCnp0M5x5Z.7B48VXtbWkR8D2cshvpPQvX%2FYMmqjD0lCOBPr%2Brb9GE; lang=vi';
        $headers[] = 'Origin: https://modtodo.com';
        $headers[] = 'Referer: https://modtodo.com/';
        $headers[] = 'Sec-Ch-Ua: \"Chromium\";v=\"104\", \" Not A;Brand\";v=\"99\", \"Google Chrome\";v=\"104\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $arr_result = (json_decode($result, true));
        if($arr_result["code"] == 0){
            $arr["code"] = $arr_result["code"];
        } else {
            $arr["code"] = $arr_result["code"];
            $arr["dlink"] = $arr_result["data"]["link"];
            $arr["obb"] = $arr_result["data"]["obb"];
            $arr["version"] = $arr_result["data"]["version"];
        }

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $arr;


    }
    function getApksos($id){
//        $crawler = GoutteFacade::request('GET', 'https://apksos.com/download-app/'. $id);
//        $filterData = $crawler->filter('div.section.row > div.col-sm-12.col-md-8.text-center > p > a');
//        $arrExtracted = $filterData->extract(array('href'));
//        if (count($arrExtracted) > 0){
//            $dLink = $arrExtracted[0];
//        } else {
//            $dLink = '';
//        }
//        return $dLink;
        $req = Http::get(env("BOT_URL")."/apk/$id");
        $jsonGet = $req->body();
        return json_decode($jsonGet);
    }

    public function loadDusk($id)
    {
        $process = (new ChromeProcess)->toProcess();
        $process->start();
        $options = (new ChromeOptions)->addArguments(['--disable-gpu', '--headless']);
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = retry(5, function () use($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities, 60000, 60000);
        }, 50);
        $browser = new Browser($driver);
        $browser->visit('https://www.unboxapk.com/apk-downloader');
//        $html = $browser->element('#conversation-title')->getDomProperty('innerText');
        $browser->waitFor('#pkg-name-field', 10);
        $browser->type('#pkg-name-field', $id);
        $browser->click('#app > div > div > div:nth-child(5) > div > button');
        try {
            $browser->waitFor("#complete-text", 10);
            $html = $browser->element('#complete-text')->getDomProperty('innerText');
        } catch (\Exception $exception){
            $browser->waitFor("#error-text", 10);
            $html = $browser->element('#error-text')->getDomProperty('innerText');
        }
        $browser->quit();
        $process->stop();
    }

    function getApiStore($id)
    {
        // Request an package ID //

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.unboxapk.com/api/v1/download/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  '{"pkgName":"'.$id.'","platform":"armeabi-v7a","apiLevel":28,"locale":"en_US"}');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Authority: www.unboxapk.com';
        $headers[] = 'Accept: */*';
        $headers[] = 'Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7,fr-FR;q=0.6,fr;q=0.5';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Origin: https://www.unboxapk.com';
        $headers[] = 'Referer: https://www.unboxapk.com/apk-downloader';
        $headers[] = 'Sec-Ch-Ua: \".Not/A)Brand\";v=\"99\", \"Google Chrome\";v=\"103\", \"Chromium\";v=\"103\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Sec-Ch-Ua-Platform: \"macOS\"';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Site: same-origin';
        $headers[] = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return 'Error:' . curl_error($ch);
        }
        curl_close($ch);


        // load dusk
        try {
            $this->loadDusk($id);
        } catch (\Exception $e){

        }

        // solve the result //
        $req = Http::get("https://www.unboxapk.com/api/v1/download/$id?apiLevel=28&platform=armeabi-v7a&locale=en_US");
        $jsonGet = $req->body();
        $jsonStr = json_decode($jsonGet);
        if (isset($jsonStr->downloadLink)){
            return $jsonStr->downloadLink;
        }

    }

    function urlExtension($url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);

        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        if(str_contains($header, "application/vnd.android.package-archive")){
            return "apk";
        }
        elseif (str_contains($header, "application/zip")){
            return "zip";

        } else {
            return null;
        }
    }

    public function unzip($filePath, $filePathSave)
    {
        $zip = new ZipArchive();
        $status = $zip->open($filePath);
        if ($status !== true)
        {
            throw new \Exception($status);
        }
        else {
            $storageDestinationPath = $filePathSave;
            if (!\File::exists( $storageDestinationPath)) {
            \File::makeDirectory($storageDestinationPath, 0755, true);
            }
            $zip->extractTo($storageDestinationPath);
            $zip->close();

        }
    }

    function getRedirect($link){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $a = curl_exec($ch); // $a will contain all headers

        // This is what you need, it will return you the last effective URL
        return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    }

    function saveApkFile($link, $id, $version, $extension)
    {
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $this->getRedirect($link));
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Dark Secret Ninja/1.0');
        $getApk = curl_exec($curl_handle);
        curl_close($curl_handle);
        $savePath = public_path("uploads/apks/$id");
        if (!\File::exists( $savePath)) {
            \File::makeDirectory($savePath, 0755, true);
        } else {
            \File::cleanDirectory($savePath);
        }
        $fileName = "$id"."_".$version."_.$extension";
        file_put_contents(public_path("uploads/apks/$id/$fileName"), $getApk); // save to public folder
        return env("APP_URL"). "/uploads/apks/$id/$fileName"; // Can add Current Point URL
    }


    function saveObbFile($link, $id, $version, $extension, $obbPath)
    {
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $this->getRedirect($link));
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Dark Secret Ninja/1.0');
        $getApk = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (!\File::exists( $obbPath)) {
            \File::makeDirectory($obbPath, 0755, true);
        } else {
            \File::cleanDirectory($obbPath);
        }
        $fileName = "$id"."_".$version."_.$extension";
        file_put_contents(("$obbPath. "/" .$fileName"), $getApk); // save to obb folder
    }

    public function checkDuplicate($id): \Illuminate\Http\JsonResponse
    {
        if (App::where("package_name", $id)->exists()){
            $status = 1;
        } else {
            $status = 0;
        }

        return response()->json(["duplicate" => $status]);
    }


    public function index($id, Request $request)
    {

        $google = new \GooglePlay();
        $app = $google->parseApplication($id, "vi");
        $gplay = new \Nelexa\GPlay\GPlayApps($defaultLocale = 'vi_VN', $defaultCountry = 'vn');
        $directLink = "";
        $location = "";

        if (array_key_exists("packageName", $app)){ // app found and free
            // update version info
            $appRequest = $this->modtodoAPI($id);
            $cover = $gplay->getAppInfo($id)->getCover()->getOriginalSizeUrl();
            $app["thumbnail"] = $cover;
            if($app["versionName"] == "Varies with device"){
//                $crawler = GoutteFacade::request('GET', 'https://apksos.com/app/'. $id);
//                $filterData = $crawler->filter('div.section.row > div.col-sm-12.col-md-8 > ul > li:nth-child(1)')->text();
//                $versionText = str_replace("Version: ", "", $filterData);
                $app["versionName"] = $appRequest["version"];
            }
//            $directLink = $this->getApiStore($id); // If you want to revert to apistore api, uncomment this
            $directLink = null; // Use APKSOS Server Only // If you want to revert to apistore api, comment this

            if (!$request->has("mode")) { // if not has mode = only
                if ($directLink !== null) { // Found Direct Link in API Store
                    $location = $this->saveFile($directLink, $id, $app["versionName"], "apk");
                } else {
                    // change to apksos module
                    //
                    if ($app["price"] == "0" || $app["price"] == null) {
                        $directLink = $appRequest["dlink"];
                        if ($directLink !== '') {
//                            $fileExt = $this->urlExtension($directLink);
                            if ($appRequest["obb"] == "" || $appRequest["obb"] == null){
                                $location = $this->saveApkFile($directLink, $id, $app["versionName"], "apk");
                            }
                            else {
                                $directLink = $this->getApksos($id)->dlink;
                                $fileVer = $app["versionName"];
                                $location = $this->saveApkFile($directLink, $id, $fileVer, "zip");
                                $fileName = "$id" . "_" . $app["versionName"] . "_.zip";
                                $zipFile = "$id" . "_" . $app["versionName"];
                                $filePath = public_path("uploads/apks/$id/$fileName");
                                $fileSave = public_path("uploads/apks/$id");
                                $this->unzip($filePath, $fileSave);
                                // remove file after, rename apk file //
                                foreach (glob(public_path("uploads/apks/$id/$id/*.apk")) as $fileInFolder) {
                                    if (str_contains($fileInFolder, $id)) {
                                        $file = realpath($fileInFolder);
                                        rename($file, public_path("uploads/apks/$id/$id/$zipFile.apk"));
                                        \File::delete($file); // delete old apk file
                                        break;
                                    }
                                }
                                \File::delete(public_path("uploads/apks/$id/$id/How-to-install.txt"));
                                \File::delete($filePath); // remove downloaded zip file
                                // add extra zip //
                                new \GoodZipArchive(public_path("uploads/apks/$id/$id"), public_path("uploads/apks/$id/$fileName"));
                                \File::deleteDirectory(public_path("uploads/apks/$id/$id"));

                            }

//                            if ($fileExt == "apk") {
//                                $location = $this->saveFile($directLink, $id, $app["versionName"], "apk");
//                            }
//                            if ($fileExt == "zip") {
//                                $fileVer = $app["versionName"];
//                                $location = $this->saveFile($directLink, $id, $fileVer, "zip");
//                                $fileName = "$id" . "_" . $app["versionName"] . "_.zip";
//                                $zipFile = "$id" . "_" . $app["versionName"];
//                                $filePath = public_path("uploads/apks/$id/$fileName");
//                                $fileSave = public_path("uploads/apks/$id");
//                                $this->unzip($filePath, $fileSave);
//                                // remove file after, rename apk file //
//                                foreach (glob(public_path("uploads/apks/$id/$id/*.apk")) as $fileInFolder) {
//                                    if (str_contains($fileInFolder, $id)) {
//                                        $file = realpath($fileInFolder);
//                                        rename($file, public_path("uploads/apks/$id/$id/$zipFile.apk"));
//                                        \File::delete($file); // delete old apk file
//                                        break;
//                                    }
//                                }
//                                \File::delete(public_path("uploads/apks/$id/$id/How-to-install.txt"));
//                                \File::delete($filePath); // remove downloaded zip file
//                                // add extra zip //
//                                new \GoodZipArchive(public_path("uploads/apks/$id/$id"), public_path("uploads/apks/$id/$fileName"));
//                                \File::deleteDirectory(public_path("uploads/apks/$id/$id"));
//
//                            }
                        }
                    }
                }
            }
        }
//
        $app["dlink"] = $directLink;
        $app["location"] = $location;
        // save to DB //


        return response()->json($app);
    }
}
