<?php

namespace App\Http\Controllers;

use App\Http\Resources\AqiStationResource;
use App\Models\AqiStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AqiStationController extends Controller
{
    public function getAll() {
        $aqi_stations = AqiStation::get();

        return response()->json(['message' => 'succes', 'data' => new AqiStationResource($aqi_stations)], 200);
    }

    public function getCobaSatu() {
        $aqi_stations = AqiStation::get();
        
        $arrUpdate = [];
        foreach ($aqi_stations as $key => $value) {
            $data = Http::get($value->url_1)->json()['data'];

            $dt_update = [
                'lat' => $data['city']['geo'][0],
                'long' => $data['city']['geo'][1],
                'nama' => $data['city']['name'],
            ];

            if (isset($value->nama) && !empty($value->nama)) {
                $arrUpdate[$key] = $value->update($dt_update);
            }
        }

        return response()->json(['message' => 'success', 'data' => $arrUpdate]);
    }
}
