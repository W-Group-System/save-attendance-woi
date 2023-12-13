<?php

namespace App\Http\Controllers;
use App\AttPunch;
use Illuminate\Http\Request;
class AttendanceController extends Controller
{
    //
    public function index()
    {
        $name = config('app.name');
        
        $system = config('app.system');
        $client = new \GuzzleHttp\Client();
        $request = $client->get('https://hris.wsystem.online/api/get-last-id/'.$name);
        $response = json_decode($request->getBody());
        $attendances = AttPunch::with('employee','terminal_info')->where('id','>',$response->id)->orderBy('id','asc')->get()->take(300);
        $requestContent = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => [ 
                'data' => $attendances->toArray(),
                'location' => $name,
            ]
        ];
        $client = new \GuzzleHttp\Client();

        $apiRequest = $client->request('POST', $system."/save-attendance", $requestContent);

        $response = json_decode($apiRequest->getBody());
      
    }
}
