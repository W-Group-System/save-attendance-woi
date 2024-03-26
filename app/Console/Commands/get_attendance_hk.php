<?php

namespace App\Console\Commands;
use App\Vms;
use Illuminate\Console\Command;

class get_attendance_hk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:get_attendance_hk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        ini_set('memory_limit', '-1');
        //
        info("START Get Attendance HK");
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

                $attendances = Vms::where('time_input','!=',"00:00:00")->where('id','>=',$response->id)->orderBy('id','asc')->get();
            }
            else
            {
                $attendances = Vms::where('time_input','!=',"00:00:00")->where('date_time','>=',date('Y-m-d 00:00:00',strtotime('2024-02-15')))->orderBy('id','asc')->get();
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
        info("END Get Attendance HK");

    }
}
