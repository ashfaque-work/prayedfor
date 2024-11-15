<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\churchSetting;
use App\Models\Location;
use App\Models\Contact;
use App\Models\ResponseTemplate;
use App\Models\PrayerRequest;
use App\Models\PrayerWarrior;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Services\TemplateService;

class MessageForwardController extends Controller
{
    public function saveUserByRole(){
        try {
            $baseUrl = 'https://services.leadconnectorhq.com';
            $endpoint = '/contacts/';
            $client = new \GuzzleHttp\Client();
            
            $locations = Location::get();
            if ($locations) {
                foreach($locations as $loc){
                    $location_id = $loc->locationId;
                    $accessToken = $loc->access_token;
                    
                    $location = Location::where('locationId', $location_id)->first();
                    if ($location && $location_id != "Agency" && ($accessToken != NULL || !empty($accessToken)) ) {
                        $apiVersion = '2021-07-28';

                        $response = $client->request('GET', $baseUrl . $endpoint, [
                            'headers' => [
                                'Accept' => 'application/json',
                                'Authorization' => 'Bearer ' . $accessToken,
                                'Version' => $apiVersion,
                            ],
                            'query' => [
                                'locationId' => $location_id,
                            ],
                        ]);
                        $responseData = json_decode($response->getBody(), true);

                        foreach ($responseData['contacts'] as $contact) {
                            $contactId = $contact['id'];
                            $contact = Contact::where('contactId', $contactId)->first();
                            if ($contact) {
                                if (isset($contact['tags']) && !empty($contact['tags']) && strpos($contact['tags'], '#prayerwarrior') !== false) {
                                    PrayerWarrior::updateOrCreate(
                                            ['contact_id' => $contact->id],
                                            [
                                                'location_id' => $loc->id,
                                            ]
                                        );
                                }
                                else {
                                    // Delete PrayerWarrior record if the tag is removed
                                    PrayerWarrior::where('contact_id', $contact->id)->delete();
                                }
        
                                if (isset($contact['tags']) && !empty($contact['tags']) && strpos($contact['tags'], '#prayerrequest') !== false) {
                                    PrayerRequest::updateOrCreate(
                                            ['contact_id' => $contact->id],
                                            [
                                                'location_id' => $loc->id,
                                            ]
                                        );
                                    
                                }
                                else {
                                    // Delete PrayerRequest record if the tag is removed
                                    PrayerRequest::where('contact_id', $contact->id)->delete();
                                }
                            }
                            else {
                                // Delete records if contact doesn't exist
                                PrayerWarrior::where('contact_id', $contactId)->delete();
                                PrayerRequest::where('contact_id', $contactId)->delete();
                            }
                            
                        }
                    }
                }
            }
        } 
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }
    
    public function countFlagged(){
        $prayerRequests = PrayerRequest::get();
        if ($prayerRequests){
            $flagged_contacts =[];
            foreach($prayerRequests as $prayerRequest){
                if ($prayerRequest->flagged_req == "1"){
                    array_push($flagged_contacts, $prayerRequest->contact_id);
                }
            }
            $count = array_count_values($flagged_contacts);
            if ($count){
                foreach($count as $c_id=>$c_value){
                    $contact = Contact::where('id',$c_id)->first();
                    $contact->flagged = $c_value;
                    $contact->save();
                }
            }
        }
    }
    
    public function messageForwarding(){
        
        function msg_to_pw($cid, $message_body, $access_token)
        {
            $client = new \GuzzleHttp\Client();
            $apiVersion = '2021-04-15';
            $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                  'body' => '{
                                  "type": "SMS",
                                  "contactId": "'.$cid.'" ,
                                  "message": "'.$message_body.'",
                                  "subject": "test"
                                }',
                                  'headers' => [
                                    'Accept' => 'application/json',
                                    'Authorization' => 'Bearer '. $access_token,
                                    'Content-Type' => 'application/json',
                                    'Version' => $apiVersion,
                                  ],
                                ]);
            return true;
        }
        
        $locations = Location::get();
        if($locations){
            foreach($locations as $loc)
            {
                $location_id = $loc->locationId;
                $access_token = $loc->access_token;
    
                if ($location_id && $location_id != "Agency" && ($access_token != NULL || !empty($access_token) ))
                {
                    $loc_id = $loc->id;
                    $churchSetting = ChurchSetting::where('location_id', $loc_id)->first();
                    
                    if ($churchSetting)
                    {
                        $time_gap_from = $churchSetting->time_gap_from;
                        $time_gap_to = $churchSetting->time_gap_to;
                        $max_prayer_warrior = $churchSetting->prayer_w_qnty;
                        $prayer_recieve_max_each = $churchSetting->prayer_req_qnty;
                        $time_interval = $churchSetting->time_interval;
                        $time_zone = $churchSetting->time_zone;
                        // dd($time_zone);
                        $startTime = \Carbon\Carbon::createFromFormat('H:i', $time_gap_from, $time_zone);
                        $endTime = \Carbon\Carbon::createFromFormat('H:i', $time_gap_to, $time_zone);
                        $currentTime = \Carbon\Carbon::now($time_zone);
                        // dd($currentTime);
                        if($currentTime->between($startTime, $endTime, true)){
                            // dd($time_interval); 
                            
                            if ($time_interval == 30) {
                                $timeAgoUTC = $currentTime->copy()->subMinutes($time_interval);
                            } 
                            elseif ($time_interval) {
                                $timeAgoUTC = $currentTime->copy()->subHours($time_interval);
                            }
                            // Convert UTC time difference to location's time zone
                            $timeAgo = $timeAgoUTC->setTimezone($time_zone);
                            // dd($timeAgo);
                            
                            $prayerWarriors = PrayerWarrior::where('location_id', $loc_id)
                                                            ->where('frequency','>' ,'count')
                                                            ->where('last_time', '<=', $timeAgo)
                                                            ->orWhereNull('last_time')
                                                            ->where('status', 'available')
                                                            ->inRandomOrder()
                                                            ->take($max_prayer_warrior) 
                                                            ->get();
                            // dd($prayerWarriors);
                            
                            if ($prayerWarriors->count() > 0) {
                                $prayerRequests = PrayerRequest::where('location_id', $loc_id)
                                                                ->whereNot('status', 'fulfilled')
                                                                ->orWhereNull('status')
                                                                ->whereNotNull("prayedfor_msg")
                                                                ->where(function ($query) use ($prayer_recieve_max_each) {
                                                                        $query->where('prayer_sent_today', '<', $prayer_recieve_max_each)
                                                                            ->orWhereNull('prayer_sent_today');                                                                        })
                                                                ->whereNot('flagged_req', "1")
                                                                ->inRandomOrder()
                                                                ->get();
                            
                                // dd($prayerRequests);
                                
                                foreach ($prayerRequests as $prayerRequest) 
                                {
                                    $pr_cid = $prayerRequest->contact_id;
                                    $pr_msg = $prayerRequest->prayedfor_msg;
                                    $prayerRequest_id = $prayerRequest->id;
                                    $prayer_msgTag = "#pr" . $prayerRequest_id . " " . $pr_msg;
                                    //18-08-23- only sending id's now, previously #pr4
                                    // $msgTag = "#pr$prayerRequest_id";
                                    
                                    
                                    foreach ($prayerWarriors as $prayerWarrior) 
                                    {
                                        $pw_cid = $prayerWarrior->contact_id;
                                        $cid = Contact::where('id', $pw_cid)->first()->contactId;
                                        
                                        if ($prayerWarrior) {
                                            $frequency = $prayerWarrior->frequency;
                                            $count = $prayerWarrior->count;
                                            $pw_last_time = $prayerWarrior->last_time;
                                            $pr_id = json_decode($prayerWarrior->prayer_sent, true);
                                            $pr_id = $pr_id? $pr_id:[];
                                                
                                            if($frequency > $count && $pr_cid != $pw_cid &&  $pw_last_time <= $timeAgo)
                                            {
                                                if (!in_array($prayerRequest_id, $pr_id))
                                                {
                                                    $pr_id[] = $prayerRequest_id;
                                                    $prayerWarrior->prayer_sent = json_encode($pr_id);
                                                    $prayerWarrior->save();
                                                    
                                                    //Sending Message
                                                    $messageSent = msg_to_pw($cid, $prayer_msgTag, $access_token);
                                                    
                                                    if ($messageSent) {
                                                        $prayerRequest->prayer_sent_today = $prayerRequest->prayer_sent_today + 1;
                                                        $prayerRequest->save();
                                                        $prayerWarrior->last_time = \Carbon\Carbon::now($time_zone);
                                                        $prayerWarrior->count = $count +1;
                                                        $prayerWarrior->save();
                                                        usleep(100000);// require
                                                        // break;
                                                    }
                                                }    
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    //End of the Day Mesage+Update count to PR
    public function prayerCountMsg(){
        
        //Send message
        function msg_to_pr($cid, $message_body, $access_token)
        {
            $client = new \GuzzleHttp\Client();
            $apiVersion = '2021-04-15';
            $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                  'body' => '{
                                  "type": "SMS",
                                  "contactId": "'.$cid.'" ,
                                  "message": "'.$message_body.'",
                                  "subject": "test"
                                }',
                                  'headers' => [
                                    'Accept' => 'application/json',
                                    'Authorization' => 'Bearer '. $access_token,
                                    'Content-Type' => 'application/json',
                                    'Version' => $apiVersion,
                                  ],
                                ]);
            return true;
        }
        
        $locations = Location::get();
        if($locations){
            foreach($locations as $loc)
            {
                $location_id = $loc->locationId;
                $access_token = $loc->access_token;
    
                if ($location_id && $location_id != "Agency" && ($access_token != NULL || !empty($access_token) ))
                {
                    $loc_id = $loc->id;
                    $churchSetting = churchSetting::where('location_id',$loc_id)->first();
                    $prayer_req_alltime = $churchSetting->prayer_req_alltime;
                    
                    $prayerRequests = PrayerRequest::where('location_id', $loc_id)
                                                    ->whereNot('status', 'fulfilled')
                                                    ->orWhereNull('status')
                                                    ->whereNotNull('prayedfor_msg')
                                                    ->whereNotNull('prayed_count_today')
                                                    ->where('flagged_req',"0")
                                                    ->orWhereNull('prayed_count_all')
                                                    ->where('prayed_count_all', '<', $prayer_req_alltime)
                                                    ->get();
                    
                    $prayerWarriors = PrayerWarrior::where('location_id', $loc_id)
                                                    ->whereNotNull('count')
                                                    ->get();
                                        
                    // dd($prayerRequests);
                    $lastTime = $churchSetting->time_gap_to;
                    $time_zone = $churchSetting->time_zone;
                    $currentTime = Carbon::now($time_zone);
                    $startRange = Carbon::parse($lastTime, $time_zone)->subMinutes(5);
                    $endRange = Carbon::parse($lastTime, $time_zone);
                    
                    // dd($currentTime,  $startRange,   $endRange);
                    if ($currentTime >= $startRange && $currentTime <= $endRange) {
                        // Updating total count and sending eod msg to Requestee
                        // sent_today and count_today made 0
                        // dd("inside if");
                        if ($prayerRequests->isNotEmpty())
                        {
                            foreach($prayerRequests as $prayerRequest){
                                $cid = $prayerRequest->contact_id;
                                $pr_id = $prayerRequest->id;
                                $contact = Contact::where('id', $cid)->first();
                                $contactId = $contact->contactId;
                                // dd($contactId);
                                $count_today = $prayerRequest->prayed_count_today;
                                $total_count = $prayerRequest->prayed_count_all;
                                
                                if($total_count != null){
                                    $prayerRequest->prayed_count_all += $count_today;
                                }
                                else{
                                    $prayerRequest->prayed_count_all = $count_today;
                                }
                                $total_count = $prayerRequest->prayed_count_all;
                                if($total_count >= $prayer_req_alltime){
                                    $prayerRequest->status ="fulfilled";
                                }
                                $prayerRequest->prayed_count_today = null;
                                $prayerRequest->prayer_sent_today = null;
                                $prayerRequest->save();
                                if($count_today > 0);
                                {
                                    // dd("count");
                                    $temps = TemplateService::getTemplates($loc_id);
                                    $eodCount = $temps["eodCount"] ?? null;
                                    $replacements = [
                                                        '{{pr_id}}' => $pr_id,
                                                        '{{count_today}}' => $count_today,
                                                    ];
                                                    
                                    $message = preg_replace_callback('/\{\{.*?\}\}/', function ($match) use ($replacements) {
                                                                                    return isset($replacements[$match[0]]) ? $replacements[$match[0]] : $match[0];
                                                                                }, $eodCount);
                                    
                                    // dd($message);                                             
                                    msg_to_pr($contactId, $message, $access_token);
                                }
                            }
                        }
                        
                        // Updating count(msg sent to warrior today) to null
                        if ($prayerWarriors->isNotEmpty())
                        {
                            foreach ($prayerWarriors as $prayerWarrior){
                                $prayerWarrior->count=null;
                                $prayerWarrior->save();
                            }
                        }
                    }
                }
            }
        }
    }
}
