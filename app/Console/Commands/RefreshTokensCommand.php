<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Location;

class RefreshTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-tokens-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $locations = Location::get();
            if($locations){
                foreach($locations as $loc){
                    $location_id = $loc->locationId;
                    $refreshToken = $loc->refresh_token;
                    if(  ($location_id || !empty($location_id)) && ($refreshToken || !empty($refreshToken))  ){
                        $clientId = '64cb3642a150b32ad247f3d5-lkup9dd0';
                        $clientSecret = '9fa9096a-35f7-4084-ad86-dbec4d818de1';
                        $redirectUri = 'https://prayedfor.ddns.net/get-token';
                        $client = new \GuzzleHttp\Client();
                        $response = $client->request('POST', 'https://services.leadconnectorhq.com/oauth/token', [
                          'form_params' => [
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'grant_type' => 'refresh_token',
                            'refresh_token' => $refreshToken,
                            'user_type' => 'Location',
                          ],
                          'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/x-www-form-urlencoded',
                          ],
                        ]);
                        $data = json_decode($response->getBody(), true);
                        $accessToken = $data['access_token'];
                        $refreshToken = $data['refresh_token'];
                        $companyId = $data['companyId'];
                        
                        $locations = Location::where('locationId', $location_id)->first();
                        if ($locations) {
                            $locations->access_token = $accessToken;
                            $locations->refresh_token = $refreshToken;
                            $locations->companyId = $companyId;
                            $locations->save();
                        }
                    }
                }
                // echo 'Token successfully regenerated for all Locations!';
            }
        } catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
}
