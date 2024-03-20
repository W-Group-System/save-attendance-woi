<?php

namespace App\Http\Controllers;
use App\AttPunch;
use Illuminate\Http\Request;
use App\Vms;

use Rats\Zkteco\Lib\ZKTeco;
class AttendanceController extends Controller
{
    //
    public function index()
    {
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $system = config('app.system');
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id-hk/".$add);
            
            $response = json_decode($request->getBody());
            if($response->id)
            {

                $attendances = Vms::where('time_input',)->where('last_id','>=',$response->id)->orderBy('id','desc')->get()->take(100);
            }
            else
            {
                $attendances = Vms::where('date_time','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->orderBy('id','desc')->get()->take(100);
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
    
            $apiRequest = $client->request('POST', $system."/save-attendance-hk", $requestContent);
    
            $response = json_decode($apiRequest->getBody());
        }

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
            $system = config('app.system');
            $client = new \GuzzleHttp\Client();
            $request = $client->get($system."/get-last-id/".$add);
            
            $response = json_decode($request->getBody());
            // dd($response);
            if($response->id != 0)
            {

                $attendances = collect($zk->getAttendance())->where('timestamp','>=',$response->id)->take(100);
            }
            else
            {
                $attendances = collect($zk->getAttendance())->where('timestamp','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->take(100);
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
            dd($apiRequest);
            $zk->disconnect();   
        }
        
        info("End Get Attendance");

    }
    public function get_users()
    {
        $location = config('app.location');
        $address = explode(',',config('app.address'));
        $name = config('app.name');
        foreach($address as $add)
        {
            $zk = new ZKTeco($add);
            $zk->connect();
            $users =  $zk->getUser();
            return json_encode($users);
            dd( $zk->getUser());
        }

    }
}
