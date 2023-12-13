<?php

namespace App\Console\Commands;
use App\AttPunch;
use Illuminate\Console\Command;

class get_attendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:get_attendance';

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
        info("START Get Attendance");
        $name = config('app.name');
        $system = config('app.system');
        $client = new \GuzzleHttp\Client();
        $request = $client->get($system."/get-last-id/".$name);
        $response = json_decode($request->getBody());
        $attendances = AttPunch::with('employee','terminal_info')->where('id','>',$response->id)->orderBy('id','asc')->get()->take(100);
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
        info("End Get Attendance");
        return "success";
    }
}
