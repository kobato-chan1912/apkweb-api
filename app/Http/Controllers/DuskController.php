<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;

class DuskController extends Controller
{
    //
    public function index()
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
        $browser->type('#pkg-name-field', "com.longhorn.countmasterevo");
        $browser->click('#app > div > div > div:nth-child(5) > div > button');
        try {
            $browser->waitFor("#complete-text", 10);
            $html = $browser->element('#complete-text')->getDomProperty('innerText');
        } catch (\Exception $exception){
            $browser->waitFor("#error-text", 10);
            $html = $browser->element('#error-text')->getDomProperty('innerText');
        }
        echo $html;
        $browser->quit();
        $process->stop();
    }
}
