<?php

namespace App\Http\Controllers;
ini_set('memory_limit', '4096M');

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

    function getRemoteFilesize($file_url, $formatSize = true)
    {
        $head = array_change_key_case(get_headers($file_url, 1));
// content-length of download (in bytes), read from Content-Length: field
        $clen = $head['content-length'] ?? 0;
// cannot retrieve file size, return “-1”
        if (!$clen) {
            return 0;
        }
        if (!$formatSize) {
            return $clen;
// return size in bytes
        }
        $size = $clen;
        switch ($clen) {
            case $clen < 1024:
                $size = $clen . ' B';
                break;
            case $clen < 1048576:
                $size = round($clen / 1024, 2) . ' KB';
                break;
            case $clen < 1073741824:
                $size = round($clen / 1048576, 2) . ' MB';
                break;
            case $clen < 1099511627776:
                $size = round($clen / 1073741824, 2) . ' GB';
                break;
        }

        if (is_array($size)) {
            $sizeInMB = round($size[1] / 1048576, 2);
            return $sizeInMB . ' MB';
        }

        return $size;
    }


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
        if ($arr_result["code"] == 0) {
            $arr["code"] = $arr_result["code"];
        } else {
            $obb = $arr_result["data"]["obb"];
            if ($obb == "") {
                $arr["dlink"] = $arr_result["data"]["link"];
            } else {
                $arr["dlink"] = "https://d.cdnpure.com/b/XAPK/" . $id . "?version=latest";
            }
            $arr["code"] = $arr_result["code"];

            $arr["obb"] = $arr_result["data"]["obb"];
            $arr["version"] = $arr_result["data"]["version"];
            $arr["size"] = $this->getRemoteFilesize($arr["dlink"]);
        }

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return $arr;


    }


    public function unzip($filePath, $filePathSave)
    {
        $zip = new ZipArchive();
        $status = $zip->open($filePath);
        if ($status !== true) {
            throw new \Exception($status);
        } else {
            $storageDestinationPath = $filePathSave;
            if (!\File::exists($storageDestinationPath)) {
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
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $this->getRedirect($link));
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Dark Secret Ninja/1.0');
        $getApk = curl_exec($curl_handle);
        curl_close($curl_handle);
        $savePath = public_path("uploads/apks/$id");
        if (!\File::exists($savePath)) {
            \File::makeDirectory($savePath, 0755, true);
        }
        else {
            \File::cleanDirectory($savePath);
        }
        $fileName = "$id" .".$extension";
        $originalPath = public_path("uploads/apks/$id/$fileName");
        file_put_contents($originalPath, $getApk); // save to public folder
        if ($extension == "zip")
        {
            if (env("MOD") == "zip")
            {
                $this->unzip($originalPath, public_path("uploads/apks/$id/$id"));
                \File::delete($originalPath);

                $fileName = "$id". "_". $version ."_". ".$extension";
                new \GoodZipArchive(public_path("uploads/apks/$id/$id"), public_path("uploads/apks/$id/$fileName"));
                \File::deleteDirectory(public_path("uploads/apks/$id/$id"));

            }

            if (env("MOD") == "obb") {
                $this->unzip($originalPath, public_path("uploads/apks/$id"));
                \File::delete($originalPath);

                $obbPath = public_path("uploads/apks/$id/Android/obb/$id/*.obb");
                foreach (glob($obbPath) as $fileInFolder) {
                    if (str_contains($fileInFolder, $id)){
                        $obbFile = basename($fileInFolder);;
                        return [env("APP_URL"). "/uploads/apks/$id/$id.apk", env("APP_URL"). "/uploads/apks/$id/Android/obb/$id/$obbFile"]; // Can add Current Point URL

                    }
                }
            }
        }

        return env("APP_URL"). "/uploads/apks/$id/$fileName"; // Can add Current Point URL
    }


    function saveObbFile($link, $id, $version, $extension, $obbPath)
    {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $this->getRedirect($link));
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Dark Secret Ninja/1.0');
        $getApk = curl_exec($curl_handle);
        curl_close($curl_handle);
        if (!\File::exists($obbPath)) {
            \File::makeDirectory($obbPath, 0755, true);
        } else {
            \File::cleanDirectory($obbPath);
        }
        $fileName = "$id" . "_" . $version . "_.$extension";
        file_put_contents(("$obbPath. " / " .$fileName"), $getApk); // save to obb folder
    }

    public function checkDuplicate($id): \Illuminate\Http\JsonResponse
    {
        if (App::where("package_name", $id)->exists()) {
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
        $ver = $request->get("ver");

        if (array_key_exists("packageName", $app)) { // app found and free
            try {
                $cover = $gplay->getAppInfo($id)->getCover()->getOriginalSizeUrl();
            } catch (\Exception $e) {
                $cover = null;
            }


            // update version info
            $appRequest = $this->modtodoAPI($id);
            if ($appRequest["code"] == 0) {
                return $app;
            }
            $app["thumbnail"] = $cover;
            $app["versionName"] = $appRequest["version"];
            if ($request->has("ver")){
                if($ver == $app["versionName"]){
                    return response()->json(["status" => 400]);
                }
            }

            $app["size"] = $appRequest["size"];


            if (!$request->has("mode")) { // if not has mode = only

                $directLink = $appRequest["dlink"];
                if ($appRequest["obb"] == "" || $appRequest["obb"] == null) {
                    // Không có OBB
                        $location = $this->saveApkFile($directLink, $id, $app["versionName"], "apk");
                } else {
                        $location = $this->saveApkFile($directLink, $id, $app["versionName"], "zip");

                }

            }
        }
//
        $app["dlink"] = $directLink;
        if (is_array($location))
        {
            $app["location"] = $location[0];
            $app["obb"] = $location[1];
        } else {
            $app["location"] = $location;
            $app["obb"] = null;
        }

        $app["status"] = 200;



        return response()->json($app);
    }
}
