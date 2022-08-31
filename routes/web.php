<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/iclock/cdata', function () {
    $OpStamp = request()->get("OpStamp", "");
    $Stamp = request()->get("Stamp", "");
    $SN = request()->get("SN", "");
    if ($Stamp != "") {
        $content = request()->getContent();
        $presensiListContent = explode("\n", $content);
        $presensiList = [];
        foreach ($presensiListContent as $presensiContent) {
            $presensi = explode("\t", $presensiContent);
            Log::debug($presensi);
            if (count($presensi) >= 2)
                $presensiList[] = [
                    "user_device_id" => $presensi[0],
                    "time" => $presensi[1],
                    "flag" => ($presensi[2] == "0" || $presensi[2] == "4") ? "I" : "O"
                ];
        }
        $bodyRequest = [
            "serial_number" => $SN,
            "presence" => $presensiList
        ];
        $client = new Client();

        try {
            Log::debug("Request ke taptap push presensi: " . json_encode($bodyRequest));
            $response = $client->post(
                env("PUSH_BASE_URL") . "/device/adms/presence",
                [
                    RequestOptions::JSON => $bodyRequest
                ]
            );
            Log::debug("Response dari taptap push presensi: " . $response);
            return "OK";
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::debug("Response dari taptap push presensi: " . $response);
            return "OK";
        } catch (ConnectException $e) {
            return "FAILED";
        }
        return $response;
    } else if ($OpStamp != "") {
        $fullContent = request()->getContent();
        $listLine = explode("\n", $fullContent);
        $userList = [];
        foreach ($listLine as $line) {
            if (substr($line, 0, 4) == "USER") {
                $attribute = explode("\t", $line);
                $id = substr($attribute[0], 9);
                $name = substr($attribute[1], 5);
                $userList[$id] = [
                    "name" => $name
                ];
                Log::debug("Pendaftaran User dg nama $name ID $id");
            } else if (substr($line, 0, 2) == "FP") {
                $posFP = strpos($line, " ");
                $tmp = substr($line, $posFP + 1);
                $attributes = explode("\t", $tmp);

                foreach ($attributes as $attr) {

                    if (substr($attr, 0, 3) == "TMP") {
                        // Log::debug($attr);
                        $fp = substr($attr, 4);
                    } else {
                        Log::debug(substr($attr, 0, 3));
                    }

                    if (substr($attr, 0, 3) == "PIN") {
                        $id = substr($attr, 4);
                    }
                }

                $userList[$id]["fp"] = $fp ?? "";
            }
        }
        $updateUserList = [];
        foreach ($userList as $id => $user) {
            $updateUserList[] = [
                "user_device_id" =>     $id,
                "user_name" => $user["name"],
                "spesification" => [
                    "finger" => $user["fp"]
                ]
            ];
        }

        $bodyRequest = [
            "serial_number" => $SN,
            "data" => $updateUserList
        ];

        try {
            $client = new Client();
            Log::debug("Request ke taptap registrasi user: " . json_encode($bodyRequest));
            $response = $client->post(
                env("PUSH_BASE_URL") . "/device/adms/register/user",
                [
                    RequestOptions::JSON => $bodyRequest
                ]
            );
            Log::debug("Response dari taptap push presensi: " . $response);
            return "OK";
        } catch (ClientException $e) {
            $response = $e->getResponse()->getBody();
            Log::debug("Response dari taptap push presensi: " . $response);
            return "OK";
        } catch (ConnectException $e) {
            return "FAILED";
        }


        Log::debug($updateUserList);
    }
    return "OK";
});


Route::get('/iclock/cdata', function () {
    $SN = request()->get("SN", "");
    $Stamp = request()->get("82983982", "");
    return "GET OPTION FROM: $SN
Stamp=$Stamp
OpStamp=9238883
ErrorDelay=60
Delay=30
TransTimes=00:00;14:05
TransInterval=1";
});

Route::get('/iclock/getrequest', function () {
    return "OK";
});

Route::post("/iclock/devicecmd", function () {
    return "OK";
});
