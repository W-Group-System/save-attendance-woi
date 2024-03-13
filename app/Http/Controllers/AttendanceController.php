<?php

namespace App\Http\Controllers;
use App\AttPunch;
use Illuminate\Http\Request;

use Rats\Zkteco\Lib\ZKTeco;
class AttendanceController extends Controller
{
    //
    public function index()
    {
        ini_set('memory_limit', '-1');
        //
        info("START Get Attendance");
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            
            $zk = new ZKTeco($add);
            dd($zk->connect());
            if ($zk->connect()){
            // $zk->enableDevice();
            // $zk->getAttendance();
            $system = config('app.system');
            // dd($zk->getUser()); 
            // dd($zk->getAttendance());
            $attendances = collect($zk->getAttendance())->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->take(200);
            
            dd($attendances);
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id/".$add);
            
            $response = json_decode($request->getBody());
            if($response->id)
            {

                $attendances = $attendances->where('timestamp','>=',$response->id)->take(200);
            }
            else
            {
                $attendances = $attendances->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->take(200);
            }
            $requestContent = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [ 
                    'data' => $attendances->toArray(),
                    'location' => $name,
                    'ip_address' => $add
                ]
            ];
            $client = new \GuzzleHttp\Client();
    
            $apiRequest = $client->request('POST', $system."/save-attendance", $requestContent);
    
            $response = json_decode($apiRequest->getBody());
            $zk->disconnect();   
            }
            else
            {
                return "renz-errorconnection";
            }

        }
        return "renz";
    }
    public function dept()
    {
        info("START Get Attendance");
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $zk = new ZKTeco($add);
            $zk->connect();   
            $zk->getAttendance();
            $system = config('app.system');
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id/".$add);
            
            $response = json_decode($request->getBody());
            // dd($response);
            if($response->id)
            {

                $attendances = collect($zk->getAttendance())->where('timestamp','>=',$response->id)->take(200);
            }
            else
            {
                $attendances = collect($zk->getAttendance())->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->take(200);
            }
            $requestContent = [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [ 
                    'data' => $attendances->toArray(),
                    'location' => $name,
                    'ip_address' => $add
                ]
            ];
            $client = new \GuzzleHttp\Client();
    
            $apiRequest = $client->request('POST', $system."/save-attendance", $requestContent);
    
            $response = json_decode($apiRequest->getBody());
            $zk->disconnect();   
        }
        
        info("End Get Attendance");

    }
}
