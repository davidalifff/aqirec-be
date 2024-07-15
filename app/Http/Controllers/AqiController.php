<?php

namespace App\Http\Controllers;

use App\Models\Aqi;
use App\Models\AqiStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AqiController extends Controller
{
	public function exportAvg() {
		$aqi = new Aqi();
		$list = $aqi->getAvg();

		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
			'Content-type'        => 'text/csv',
			'Content-Disposition' => 'attachment; filename=export-avg-all.csv',
			'Expires'             => '0',
			'Pragma'              => 'public'
		];

		# add headers for each column in the CSV download
		array_unshift($list, array_keys($list[0]));

		$callback = function() use ($list) 
		{
			$FH = fopen('php://output', 'w');
			foreach ($list as $row) { 
				fputcsv($FH, $row, ";");
			}
			fclose($FH);
		};

		return response()->stream($callback, 200, $headers);
	}

	public function exportDataAqi($id) {
		$aqi = new Aqi();
		$list = $aqi->getDataAqi($id);


        $aqiStation = AqiStation::where('id', '=', $id)->first();

		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
			'Content-type'        => 'text/csv',
			'Content-Disposition' => 'attachment; filename='.$aqiStation->nama.'-air-quality.csv',
			'Expires'             => '0',
			'Pragma'              => 'public'
		];

		# add headers for each column in the CSV download
		array_unshift($list, array_keys($list[0]));

		$callback = function() use ($list) 
		{
			$FH = fopen('php://output', 'w');
			foreach ($list as $row) { 
				fputcsv($FH, $row, ";");
			}
			fclose($FH);
		};

		return response()->stream($callback, 200, $headers);
	}

	private function _getData()
	{

        $aqi_stations = AqiStation::get()->toArray();
        $aqi = DB::select(
        	'SELECT *, IFNULL(`index_1`, 0) AS index_1, IFNULL(`index_2`, 0) AS index_2
			FROM aqi WHERE (`id_aqi_stations`, `id`) IN 
			( SELECT id_aqi_stations, MAX(id)
			  FROM aqi
			  WHERE (`index_1` IS NOT NULL) OR (`index_2` IS NOT NULL)
			  GROUP BY id_aqi_stations
			)'
		);

        $arrIndex = [];
        foreach ($aqi as $key => $value) {
            $arrIndex[$value->id_aqi_stations] = [
                'index_1' => $value->index_1,
                'index_2' => is_null($value->index_2) || $value->index_2 == '-'? 0 : $value->index_2,
                'index' => 0,
                'ts' => $value->ts
            ];
        }

        foreach ($aqi_stations as $key => $value) {
            if (isset($arrIndex[$value['id']])) {
                $aqi_stations[$key]['index_1'] = $arrIndex[$value['id']]['index_1'];
                $aqi_stations[$key]['index_2'] = $arrIndex[$value['id']]['index_2'];

                if ($aqi_stations[$key]['index_1'] > 0) {
                	$aqi_stations[$key]['aqi'] = round(0.516 * $aqi_stations[$key]['index_1'] + 151, 0);
                	$aqi_stations[$key]['dominentpol'] = 'pm25';
                } else {
                	$aqi_stations[$key]['aqi'] = round(0.9259 * $aqi_stations[$key]['index_2'], 0);
                	$aqi_stations[$key]['dominentpol'] = 'pm10';
                }
            	if ($aqi_stations[$key]['aqi'] < 51) {
            		$aqi_stations[$key]['group'] = 'healty';
            	} elseif ($aqi_stations[$key]['aqi'] > 50 && $aqi_stations[$key]['aqi'] < 151) {
            		$aqi_stations[$key]['group'] = 'moderate';
            	} else {
            		$aqi_stations[$key]['group'] = 'unhealthy';
            	}
                $aqi_stations[$key]['ts'] = $arrIndex[$value['id']]['ts'];
            } else {
                unset($aqi_stations[$key]);
            }
        }

        usort($aqi_stations, function($a, $b) {
            return $a['aqi'] < $b['aqi'];
        });

        return $aqi_stations;
	}

	public function getOverall()
	{
		$data = $this->_getData();

		$return = [
			'healty' => 0,
			'moderate' => 0,
			'unhealthy' => 0,
		];

		foreach ($data as $key => $value) {
			if ($value['group'] == 'healty') {
				$return['healty']++;
			}
			if ($value['group'] == 'moderate') {
				$return['moderate']++;
			}
			if ($value['group'] == 'unhealthy') {
				$return['unhealthy']++;
			}
		}

        return response()->json(['message' => 'succes', 'data' => $return], 200);
	}
}
