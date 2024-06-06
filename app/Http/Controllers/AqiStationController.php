<?php

namespace App\Http\Controllers;

use App\Http\Resources\AqiStationResource;
use App\Models\Aqi;
use App\Models\AqiStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AqiStationController extends Controller
{
    public function getAll() {
        $aqi_stations = AqiStation::get();
        $aqi = Aqi::orderBy('created_at', 'DESC')
            ->groupBy('id_aqi_stations')
            ->get();

        $arrIndex = [];
        foreach ($aqi as $key => $value) {
            $arrIndex[$value->id] = [
                'index_1' => $value->index_1,
                'index_2' => $value->index_2
            ];
        }

        foreach ($aqi_stations as $key => $value) {
            $aqi_stations[$key]->index_1 = $arrIndex[$value->id]['index_1'];
            $aqi_stations[$key]->index_2 = $arrIndex[$value->id]['index_2'];
        }

        return response()->json(['message' => 'succes', 'data' => new AqiStationResource($aqi_stations)], 200);
    }

    public function getById(Request $request, $id) {
        $aqi = Aqi::where('id_aqi_stations', '=', $id)
            ->get();

        return response()->json(['message' => 'success', 'data' => $aqi], 200);
    }

    public function getMostPolluted(Request $request) {
        $aqi_stations = AqiStation::get()->toArray();
        $aqi = Aqi::where('index_1', '!=', '-')
            ->orderBy('created_at', 'DESC')
            ->groupBy('id_aqi_stations')
            ->get();

        $arrIndex = [];
        foreach ($aqi as $key => $value) {
            $arrIndex[$value->id] = [
                'index_1' => $value->index_1,
                'index_2' => $value->index_2
            ];
        }

        foreach ($aqi_stations as $key => $value) {
            if (isset($arrIndex[$value['id']])) {
                $aqi_stations[$key]['index_1'] = $arrIndex[$value['id']]['index_1'];
                $aqi_stations[$key]['index_2'] = $arrIndex[$value['id']]['index_2'];
            } else {
                unset($aqi_stations[$key]);
            }
        }

        usort($aqi_stations, function($a, $b) {
            return $a['index_1'] < $b['index_1'];
        });

        $aqi_stations = array_slice($aqi_stations, 0, 10);

        return response()->json(['message' => 'succes', 'data' => new AqiStationResource($aqi_stations)], 200);
    }

    public function update() {
        $aqi_stations = AqiStation::get();

        $arrUpdate = [];
        foreach ($aqi_stations as $key => $value) {
            if ($value->id > 40) {
                // Get Data from API
                if (isset($value->url_1) && !empty($value->url_1)) {
                    $data_1 = Http::get($value->url_1)->json()['data'];
                }
    
                if (isset($value->url_2) && !empty($value->url_2)) {
                    $data_2 = Http::get($value->url_2)->json()['data'];
                }
    
                // Set Data
                $dt_update = [
                    'id_aqi_stations' => $value->id,
                    'index_1' => null,
                    'index_2' => null
                ];
    
                if (isset($data_1) && !empty($data_1)) {
                    $dt_update['index_1'] = $data_1['aqi'];
                }
    
                if (isset($data_2) && !empty($data_2)) {
                    $dt_update['index_2'] = $data_2['aqi'];
                }
    
                // Insert to table aqi
                $arrUpdate[$key] = Aqi::create($dt_update);
            }
        }

        return response()->json(['message' => 'success', 'data' => $arrUpdate]);
    }

    public function getCobaSatu() {
        $aqi_stations = AqiStation::get();
        
        $arrUpdate = [];
        foreach ($aqi_stations as $key => $value) {
            if (empty($value->nama)) {
                $data = Http::get($value->url_1)->json()['data'];
    
                $dt_update = [
                    'lat' => $data['city']['geo'][0],
                    'long' => $data['city']['geo'][1],
                    'nama' => $data['city']['name'],
                ];

                $arrUpdate[$key] = $value->update($dt_update);
            }
        }

        return response()->json(['message' => 'success', 'data' => $arrUpdate]);
    }
}
