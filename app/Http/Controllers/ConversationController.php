<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\Location;
use App\Models\Contact;
use App\Models\churchSetting;
use App\Models\ResponseTemplate;
use App\Models\PrayerRequest;
use App\Models\PrayerWarrior;
use App\Services\TemplateService;

class ConversationController extends Controller
{
    //For getting only "inbound" messages and sending msg to #prayerWarrior & #prayerRequest and adding #frequency 
    public function message($locationId = null)
    {
        $client = new \GuzzleHttp\Client();
        $profanityFilter = app('profanityFilter');   // Check for offensive words
        
        // for checking spam messages using openAI
        function spam_OpenAi($message)
        {
            $openaiApiKey = 'XXXXXXXXXXXX';
            // Create a Guzzle client instance
            $client = new Client();
            
            // Note: If you want accuracy boost->replace `pray` with `I want`
            
            // if there are any quotes in the message we will have to remove it first
            $cleanedMessage = str_replace(["'", '"'], "", $message);
            // Make a POST request to OpenAI's moderation endpoint
            try {
                $response = $client->post('https://api.openai.com/v1/moderations', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $openaiApiKey,
                    ],
                    'json' => [
                        'input' => $cleanedMessage,
                    ],
                ]);
    
                // Get the response body as JSON
                $data = json_decode($response->getBody(), true);
                // dd( $data);
                // if($data["results"][0]["flagged"] == true){
                //     return "spam";
                // }
                // here it can be tuned to the score that we want to set
                foreach ($data["results"][0]["category_scores"] as $key => $value){
                    // spam score filter
                    if ($value > 0.1){
                        return $key;
                    }
                }
                return $message;
            } 
            
            catch (\Exception $e) 
            {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        // Used for getting conversation details like: contactId, lastMessageDirection, lastMessageBody
        function conversation($location_id, $access_token)
        {
            $client = new \GuzzleHttp\Client();
            $apiVersion = '2021-04-15';
            $baseUrl = 'https://services.leadconnectorhq.com/conversations/search?locationId=';
            $response = $client->request('GET', $baseUrl . $location_id, [
                      'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . $access_token,
                        'Version' => $apiVersion,
                      ],
                    ]);
            $conversation_details = json_decode($response->getBody(), true);
            return $conversation_details;
        }    
        
        
        //Sending new custom msg to existing contactId
        function send_msg($cid, $access_token, $message_body)
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
        }
        
        $locations = Location::get();
        if($locations){
            foreach($locations as $loc)
            {
                $location_id = $loc->locationId;
                $access_token = $loc->access_token;

                //Check if locationID and accesstoken exist
                if ($location_id && $location_id != 'Agency' && ($access_token != NULL || !empty($access_token) ))
                {   
                    // Template fetch - default or by location id
                    $locationId = $loc->id;
                    $temps = TemplateService::getTemplates($locationId);
                    $prayerwarrior_message = $temps["prayerwarrior"] ?? null;
                    $prayerrequest_message = $temps["prayerrequest"] ?? null;
                    $notag_message = $temps["notag"] ?? null;
                    $frequencyN_message = $temps["frequencyN"] ?? null;
                    $changefrequency_message = $temps["changefrequency"] ?? null;
                    $cancel_message = $temps["cancel"] ?? null;
                    $prayerwarriornotag_message = $temps["prayerwarriornotag"] ?? null;
                    $prayerrequestnotag_message = $temps["prayerrequestnotag"] ?? null;
                    $profileUpdate_message = $temps["profileUpdate"] ?? null;
                    $inappropriate_message = $temps["inappropriate"] ?? null;
                    $inappropriateNoPRtag_message = $temps["inappropriateNoPRtag"] ?? null;
                    $pwprNotag_message = $temps['pwprNotag'] ?? null;
                    $thankYouPrayedFor_message = $temps['thankYouPrayedFor'] ?? null;
                    $alreadyPrayedResponse_message = $temps['alreadyPrayedResponse'] ?? null;
                    $prayedForNoPRtag_message = $temps['prayedForNoPRtag'] ?? null;
                    $invalidPRid_message = $temps['invalidPRid'] ?? null;
                    $profileUpdateMsg = $temps['profileUpdateMsg'] ?? null;
                    $frequencyLimit = $temps['frequencyLimit'] ?? null;
                    $spam_message = $temps['spam'] ?? null;
                    
                    $conversation_details = conversation($location_id, $access_token);
                    foreach ($conversation_details["conversations"] as $each_conversation)
                    { 
                        if ( $each_conversation["lastMessageDirection"] == 'inbound')
                        {
                            $cid = $each_conversation["contactId"];
                            $lastmsg = $each_conversation["lastMessageBody"];
                            $contact = Contact::where('contactId', $cid)->first();
                            $contact_id = $contact->id;
                            $prayerWarrior = PrayerWarrior::where('contact_id',$contact_id)->first();
                            $id_location = $loc->id;
                            $churchSetting = ChurchSetting::where('location_id', $id_location)->first();
                            $tags = json_decode($contact->tags);
                            
                            // $filteredMessage = $profanityFilter->filter($lastmsg);
                            // if ($filteredMessage !== $lastmsg) {
                            //     if ($contact) {
                            //         $contact->flagged = 'bad';
                            //         $contact->save();
                            //     }
                            // }
                            
                            //with tag
                            if (preg_match_all('/#(\\w+)/i', $lastmsg, $matches))
                            {
                                $tag = strtolower($matches[0][0]);
                                
                                // For adding tag to contact
                                if($tag == "#prayerwarrior" || $tag == "#prayerrequest")
                                {
                                    $apiVersion='2021-07-28';
                                    $response = $client->request('POST', 'https://services.leadconnectorhq.com/contacts/'.$cid.'/tags', [
                                      'body' => '{
                                      "tags": [
                                        "'.$tag.'"
                                      ]
                                    }',
                                      'headers' => [
                                        'Accept' => 'application/json',
                                        'Authorization' => 'Bearer '.$access_token.'',
                                        'Content-Type' => 'application/json',
                                        'Version' => $apiVersion,
                                      ],
                                    ]);
                                }
                                
                                //for sending messages based on condition
                                if ($tag == "#prayerwarrior")
                                {
                                    $prayer_req_qnty = $churchSetting->prayer_req_qnty;
                                    
                                    $message_body = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($prayer_req_qnty) {
                                            $variableName = $matches[1];
                                            switch ($variableName) {
                                                case 'max_frequency':
                                                    return $prayer_req_qnty;
                                                default:
                                                    return $matches[0];
                                            }
                                        }, $prayerwarrior_message);
                                    send_msg($cid, $access_token, $message_body);
                                }
                                elseif ($tag == "#prayerrequest")
                                {
                                    
                                    $filteredMessageAI = spam_OpenAi($lastmsg); // message filtered by open AI
                                    $filteredMessage = $profanityFilter->filter($lastmsg);
                                    if ($filteredMessage !== $lastmsg || $filteredMessageAI !== $lastmsg) {
                                        $contact_id = $contact->id;
                                        
                                        if ($contact) {
                                            $existingTemplate = PrayerRequest::where('contact_id', $contact_id)->where('prayedfor_msg', null)->first();
                                            if ($existingTemplate){
                                                $existingTemplate->prayedfor_msg = $lastmsg;
                                                $existingTemplate->flagged_req = true;
                                                $existingTemplate->save();
                                            }
                                            else{
                                                $existingTemp = PrayerRequest::where('contact_id', $contact_id)->where('prayedfor_msg', $lastmsg)->first();
                                                if (!is_null($existingTemp)) {
                                                    $existingTemp->prayedfor_msg = $lastmsg;
                                                    $existingTemp->flagged_req = true;
                                                    $existingTemp->save();
                                                } 
                                                else{
                                                    $newTemp = new PrayerRequest();
                                                    $newTemp->location_id = $loc->id;
                                                    $newTemp->contact_id = $contact_id;
                                                    $newTemp->prayedfor_msg = $lastmsg;
                                                    $newTemp->flagged_req = true;
                                                    $newTemp->save();
                                                }
                                            }
                                        }
                                        //sending message to #prayerrequest if they send spam message
                                        send_msg($cid, $access_token, $spam_message);
                                    }
                                    else{
                                        preg_match_all("/(.*)#prayerrequest(.*)/i", $lastmsg, $matches);
                                        if ($matches[1][0] || $matches[2][0])
                                        {
                                            $existingTemp = PrayerRequest::where('contact_id', $contact_id)->where('prayedfor_msg', $lastmsg)->first();
                                            if (!is_null($existingTemp)) {
                                                $existingTemp->prayedfor_msg = $lastmsg;
                                                $existingTemp->save();
                                                $msg = "This is a repeated request, with Prayer Request ID #pr".$existingTemp->id;
                                                send_msg($cid, $access_token, $msg);
                                            } 
                                            else{
                                                $newTemp = new PrayerRequest();
                                                $newTemp->location_id = $loc->id;
                                                $newTemp->contact_id = $contact_id;
                                                $newTemp->prayedfor_msg = $lastmsg;
                                                $newTemp->save();
                                                $msg = $prayerrequest_message . ' Prayer Request ID is #pr'.$newTemp->id;
                                                send_msg($cid, $access_token, $msg);  //Sending Prayer Request message
                                            }
                                        }
                                        elseif($matches[0][0]){
                                            send_msg($cid, $access_token, "No Prayer Request Message.");
                                        }
                                        else{
                                            send_msg($cid, $access_token, $prayerrequestnotag_message);
                                        }
                                    }
                                }
                            }
                            
                            // without tag
                            else
                            {
                                // custom msg from UI through database
                                // if they are new send use #prayerwarrior or #prayerrequst
                                if (!$tags)
                                {
                                    send_msg($cid, $access_token, $notag_message);
                                }
                                
                                elseif (in_array("#prayerwarrior", $tags) && in_array("#prayerrequest", $tags))
                                {
                                    send_msg($cid, $access_token, $pwprNotag_message);
                                }
                                
                                // if they are #warrior then send the required input tags
                                elseif (in_array("#prayerwarrior", $tags))
                                {
                                    send_msg($cid, $access_token, $prayerwarriornotag_message);
                                }
                            
                                // if they are #request then send them to use #prayerrequest
                                elseif (in_array("#prayerrequest", $tags))
                                {
                                    send_msg($cid, $access_token, $prayerrequestnotag_message);
                                }
                            }
                            
                            // frequency
                            if (in_array("#prayerwarrior", $tags))
                            {
                                if (preg_match_all('/#frequency(\\w+)/i', $lastmsg, $matches))
                                {
                                    preg_match('/\d+/', $matches[0][0], $freq);
                                    $time_gap_from = $churchSetting->time_gap_from;
                                    $time_gap_to = $churchSetting->time_gap_to;
                                    $prayer_req_qnty = $churchSetting->prayer_req_qnty;
                                    $frequency = $freq[0];
                                    if($frequency > $prayer_req_qnty){
                                        $message_body = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($prayer_req_qnty) {
                                            $variableName = $matches[1];
                                            switch ($variableName) {
                                                case 'max_frequency':
                                                    return $prayer_req_qnty;
                                                default:
                                                    return $matches[0];
                                            }
                                        }, $frequencyLimit);
                                        send_msg($cid, $access_token, $message_body);
                                    }
                                    else{
                                        $message_body = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($frequency, $time_gap_from, $time_gap_to) {
                                            $variableName = $matches[1];
                                            switch ($variableName) {
                                                case 'frequency':
                                                    return $frequency;
                                                case 'time_gap_from':
                                                    return $time_gap_from;
                                                case 'time_gap_to':
                                                    return $time_gap_to;
                                                default:
                                                    return $matches[0];
                                            }
                                        }, $frequencyN_message);
                                        send_msg($cid, $access_token, $message_body);
                                        //push it in contact table in frequency column
                                        $prayerWarrior->frequency = $frequency;
                                        $prayerWarrior->status = "available";
                                        $prayerWarrior->save();
                                        
                                        sleep(60);  //For delay one minutes after sending frequency repy.
    
                                        // Send a message asking for first name and last name
                                        $message_body = $profileUpdate_message;
                                        send_msg($cid, $access_token, $message_body);
                                    }
                                }
                                
                                elseif (preg_match_all('/#frequency/i', $lastmsg, $matches))
                                {
                                    $message_body = "Oops! Please reply with a number in tag like: #Frequency12";
                                    send_msg($cid, $access_token, $message_body);    
                                }
                                
                                //#changefrequency
                                elseif (preg_match_all('/#changefrequency/i', $lastmsg, $matches))
                                {
                                    send_msg($cid, $access_token, $changefrequency_message);
                                }
                                
                                //#cancel
                                elseif (preg_match_all('/#cancel/i', $lastmsg, $matches))
                                {
                                    $apiVersion='2021-07-28';
                                        $response = $client->request('DELETE', 'https://services.leadconnectorhq.com/contacts/'.$cid.'/tags', [
                                          'body' => '{
                                          "tags": [
                                            "#prayerwarrior"
                                          ]
                                        }',
                                          'headers' => [
                                            'Accept' => 'application/json',
                                            'Authorization' => 'Bearer '.$access_token.'',
                                            'Content-Type' => 'application/json',
                                            'Version' => $apiVersion,
                                          ],
                                        ]);
                                    $prayerWarrior->frequency = null;
                                    $prayerWarrior->status = "cancelled";
                                    $prayerWarrior->save();
                                    send_msg($cid, $access_token, $cancel_message);
                                }
                                
                                // With #inappropriate & #prN tags
                                elseif (preg_match_all('/#inappropriate/i', $lastmsg, $matches) && preg_match_all('/#pr(\d+)/i', $lastmsg, $prMatches)) {
                                    $prayerRequestIds = $prMatches[1]; // Extracting numbers from #prX
                                    $prayerRequests = PrayerRequest::whereIn('id', $prayerRequestIds)->get();
                                    
                                    $pr_id = json_decode($prayerWarrior->prayer_sent, true);
                                    $pr_id = $pr_id? $pr_id:[];
                                    $common = array_intersect($pr_id, $prayerRequestIds);
                                    if ($common)
                                    {
                                        foreach ($prayerRequests as $prayerRequest) 
                                        {   $prayerRequest->update(['flagged_req' => '1']);
                                        }
                                        send_msg($cid, $access_token, $inappropriate_message);
                                    }
                                    else{
                                        $invalidMessage = "Invalid prayer request ID";
                                        send_msg($cid, $access_token, $invalidMessage);
                                    }
                                }
                                // #inappropriate
                                elseif (preg_match_all('/#inappropriate/i', $lastmsg, $matches)){
                                    send_msg($cid, $access_token, $inappropriateNoPRtag_message);
                                }
                                
                                //#profileupdate
                                elseif (stripos($lastmsg, '#profileupdate') !== false) {
                                    $pattern = '/(?i)#profileupdate\s*[\w\s]*\bFirst(?:\s*name)?\s*[:=]\s*(\w+)\s*.*\bLast(?:\s*name)?\s*[:=]\s*(\w+)/';
                                    $firstName = null;
                                    $lastName = null;
                                    if (preg_match($pattern, $lastmsg, $matches)) {
                                        $firstName = $matches[1];
                                        $lastName = $matches[2];
                                    }
                                    if ($firstName !== null && $lastName !== null) {
                                        $client = new \GuzzleHttp\Client();
                                        $response = $client->request('PUT', 'https://services.leadconnectorhq.com/contacts/' . $cid, [
                                            'body' => '{
                                                "firstName" : "'.$firstName.'" ,
                                                "lastName" : "'.$lastName.'"
                                            }',
                                            'headers' => [
                                                'Accept' => 'application/json',
                                                'Authorization' => 'Bearer '. $access_token,
                                                'Content-Type' => 'application/json',
                                                'Version' => '2021-07-28'
                                            ],
                                        ]);
                                        send_msg($cid, $access_token, $profileUpdateMsg);
                                    }
                                }
                                
                                // With #prayedFor & #prN tags
                                elseif (preg_match_all('/#prayedFor/i', $lastmsg, $matches) && preg_match_all('/#pr(\d+)/i', $lastmsg, $prMatches)) {
                                    $prayerRequestIds = $prMatches[1]; // Extracting numbers from #prX
                                    $prayerRequests = PrayerRequest::whereIn('id', $prayerRequestIds)->get();
                                    
                                    // Check if all provided IDs exist in the database
                                    $validIds = $prayerRequests->pluck('id')->toArray();
                                    $invalidIds = array_diff($prayerRequestIds, $validIds);
                                    
                                    if (!empty($validIds)) {
                                        if ($prayerWarrior) {
                                            $prayedForTags = json_decode($prayerWarrior->prayed_for, true) ?? [];
                                            $uniquePrayerRequestIds = array_unique(array_merge($prayedForTags, $validIds));
                                            
                                            // Check for duplicate prayer request IDs before updating
                                            $newPrayerRequestIds = array_diff($uniquePrayerRequestIds, $prayedForTags);
                                            
                                            if (!empty($newPrayerRequestIds)) {
                                                // Update the prayedfor column and increment prayed_count_today for new prayer requests
                                                $prayerWarrior->prayed_for = array_values($uniquePrayerRequestIds);
                                                $prayerWarrior->save();
                                                $prayedForMessages = [];
                                                foreach ($prayerRequests as $prayerRequest) {
                                                    if (in_array($prayerRequest->id, $newPrayerRequestIds)) {
                                                        $currentCount = $prayerRequest->prayed_count_today;
                                                        $prayerRequest->update(['prayed_count_today' => $currentCount + 1]);
                                                        $prayedForMessages[] = "#pr{$prayerRequest->id}";
                                                    }
                                                }
                                                // Send a single "Thank you for praying" message for all valid prayer requests
                                                if (!empty($prayedForMessages)) {
                                                    $thankYouMessage = $thankYouPrayedFor_message . implode(' ', $prayedForMessages);
                                                    send_msg($cid, $access_token, $thankYouMessage);
                                                }
                                            }
                                            else {
                                                // Handle case where all prayer requests are already prayed for
                                                $alreadyPrayedMessages = [];
                                                foreach ($prayerRequests as $prayerRequest) {
                                                    if (in_array($prayerRequest->id, $prayedForTags)) {
                                                        $alreadyPrayedMessages[] = "#pr{$prayerRequest->id}";
                                                    }
                                                }
                                                $alreadyPrayedResponse_message .= implode(' ', $alreadyPrayedMessages);
                                                send_msg($cid, $access_token, $alreadyPrayedResponse_message);
                
                                            }
                                        }
                                    } 
                                    if (!empty($invalidIds)) {
                                        // Send a message for invalid prayer request IDs
                                        $invalidIdsMessages = [];
                                        foreach ($invalidIds as $invalidId) {
                                            $invalidIdsMessages[] = "#pr{$invalidId}";
                                        }
                                        $invalidIdsMessage = $invalidPRid_message . implode(' ', $invalidIdsMessages);
                                        send_msg($cid, $access_token, $invalidIdsMessage);
                                    }
                                    
                                }
                                
                                // #prayedFor without prayer request id
                                elseif (preg_match_all('/#prayedFor/i', $lastmsg, $matches)){
                                    send_msg($cid, $access_token, $prayedForNoPRtag_message);
                                }
                                elseif (preg_match_all('/#info/i', $lastmsg)){
                                    
                                    $name = $contact->name;
                                    $frequency= $prayerWarrior->frequency;
                                    if($frequency == null){
                                        $frequency = "Not_Set";
                                    }
                                    $infoResponse = "Name: ".$name. " Frequency: ". $frequency. " Role: " .implode(", ", $tags);
                                    send_msg($cid, $access_token, $infoResponse);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
}