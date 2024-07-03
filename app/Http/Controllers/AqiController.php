<?php

namespace App\Http\Controllers;

use App\Models\Aqi;
use App\Models\AqiStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
}
