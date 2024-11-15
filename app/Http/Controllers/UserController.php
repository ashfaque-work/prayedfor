<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\churchSetting;
use App\Models\Contact;
use App\Models\ResponseTemplate;
use App\Models\PrayerRequest;
use Illuminate\Support\Facades\Redirect;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Services\TemplateService;

class UserController extends Controller
{   
    //Display Home
    public function home(Request $request){
        try {
            $locationId = $request->query('locationid');
            
            //If locationId exists in query parameter
            if ($locationId !== null) {
                // Check if the location_id in session is different from the new locationId in query parameter
                if (session()->has('location_id') && session('location_id') != $locationId) {
                    // Unset the old location_id in session
                    session()->forget('location_id');
                }
            }

            if (!session()->has('location_id')) {
                session(['location_id' => $locationId]);
            }
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if (!$location) {
                if ($locationId !== null) {
                    $location = new Location();
                    $location->locationId = $locationId;
                    $location->save();
                    
                    $churchSettings = new ChurchSetting();
                    $churchSettings->location_id = $location->id;
                    $churchSettings->save();
                }
                
                //For Agency code - start
                $agencyLocation = Location::where('locationId', 'Agency')->first();
                if (!empty($agencyLocation) && $agencyLocation) {
                    $accessToken = $agencyLocation->access_token;
                    if(empty($accessToken) && $accessToken === Null){
                        $agencyMessage = "Access token is not yet created for Agency. Please switch to Agency view and Authorize by clicking on this link";
                        $generateAgencyAccessTokenLink = route('church.initiateOAuth');
                        return view('home', compact('agencyMessage', 'generateAgencyAccessTokenLink', 'locationId'));
                    }
                }
                //For Agency code - end
            
                if (empty($location->access_token)) {
                    $message = "Access token is not yet created for your location. Please Authorize by clicking on this link";
                    $generateAccessTokenLink = route('church.initiateOAuth');
                    return view('home', compact('message', 'generateAccessTokenLink', 'locationId'));
                }
                return view('home', compact('locationId'));
            }
            
            //For Agency code - start
            $agencyLocation = Location::where('locationId', 'Agency')->first();
            if (!empty($agencyLocation) && $agencyLocation) {
                $accessToken = $agencyLocation->access_token;
                if(empty($accessToken) && $accessToken === Null){
                    $agencyMessage = "Access token is not yet created for Agency. Please switch to Agency view and Authorize by clicking on this link";
                    $generateAgencyAccessTokenLink = route('church.initiateOAuth');
                    return view('home', compact('agencyMessage', 'generateAgencyAccessTokenLink', 'locationId'));
                }
            }
            //For Agency code - end
            
            if (($location) && empty($location->access_token)) {
                $message = "Access token is not yet created for your location. Please Authorize by clicking on this link";
                $generateAccessTokenLink = route('church.initiateOAuth');
                return view('home', compact('message', 'generateAccessTokenLink', 'locationId'));
            }

            return view('home', compact('locationId'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }
    
    //Showing & saving contacts in Contacts Page by fetching from API
    public function listContacts(){
        try {
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if (!empty($locationId) && $location) {
                $accessToken = $location->access_token;
                if(!empty($accessToken) && $accessToken){
                    $baseUrl = 'https://services.leadconnectorhq.com';
                    $endpoint = '/contacts/';
                    $apiVersion = '2021-07-28';
                    $client = new \GuzzleHttp\Client();
                    $response = $client->request('GET', $baseUrl . $endpoint, [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Version' => $apiVersion,
                        ],
                        'query' => [
                            'locationId' => $locationId,
                        ],
                    ]);
                    $responseData = json_decode($response->getBody(), true);
                    
                    foreach ($responseData['contacts'] as $contact) {
                        $tags = isset($contact['tags']) && is_array($contact['tags']) ? json_encode($contact['tags']) : null;
                        $customFields = isset($contact['customFields']) && is_array($contact['customFields']) ? json_encode($contact['customFields']) : null;
                        
                        $contactData = [
                            'contactId' => $contact['id'],
                            'location_id' => $location->id,
                            'name' => $contact['contactName'],
                            'email' => $contact['email'],
                            'tags' => $tags,
                            'customFields' => $customFields,
                        ];
                        
                        // Check if phone number exists
                        if (isset($contact['phone'])) {
                            $existingContact = Contact::where('phone', $contact['phone'])->first();
                            if ($existingContact) {
                                // Update existing record
                                $existingContact->update($contactData);
                            } else {
                                // Create new record
                                Contact::create(array_merge($contactData, ['phone' => $contact['phone']]));
                            }
                        } else {
                            // Create new record without phone number
                            Contact::create($contactData);
                        }
                    }
                    
                    //Deleting contact records if it is directly deleted from GHL
                    $apiContactIds = array_column($responseData['contacts'], 'id');
                    // Fetch existing contacts from the database
                    $contacts = Contact::where('location_id', $location->id)->get();
                    foreach ($contacts as $contact) {
                        if (!in_array($contact->contactId, $apiContactIds)) {
                            // Delete the contact record from the database
                            $contact->delete();
                        }
                    }
                    
                    //Adding Flag count in contacts table
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
        
                    $contactsPaginated = Contact::where('location_id', $location->id)->paginate(10);
                    return view('contacts', ['contacts' => $contactsPaginated]);
                }
                else{
                    return view('contacts');
                }
            }
            else{
                return view('contacts');
            }
                
        } 
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }
    
    //Display Settings
    public function settings(Request $request){
        try {
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
    
            if ($location) {
                $churchSetting = churchSetting::where('location_id', $location->id)->first();
                $templates = ResponseTemplate::where('location_id', $location->id)->get();
                if ($templates->isEmpty()) {
                    $templates = ResponseTemplate::whereNull('location_id')->get();
                }
                
                return view('settings', compact('churchSetting', 'templates'));
            }
            // return view('settings');
            return Redirect::route('home');
        } catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occurred!';
        }
    }
    
    //Save general Settings
    public function saveChurchSettings(Request $request){
        try {
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if ($location) {
                $churchSetting = churchSetting::where('location_id', $location->id)->first();
                if ($churchSetting) {
                    if($request->time_gap_to != null){
                        $churchSetting->prayer_w_qnty = $request->prayer_w_qnty;
                        $churchSetting->prayer_req_qnty = $request->prayer_req_qnty;
                        $churchSetting->prayer_req_alltime = $request->prayer_req_alltime;
                        $churchSetting->time_gap_from = $request->time_gap_from;
                        $churchSetting->time_gap_to = $request->time_gap_to;
                        $churchSetting->time_interval = $request->time_interval;
                        $churchSetting->time_zone = $request->time_zone;
                        $churchSetting->save();
                        
                        
                        //For Agency code - start
                        $agencyLocation = Location::where('locationId', 'Agency')->first();
                        if (!empty($agencyLocation) && $agencyLocation) {
                            $accessToken = $agencyLocation->access_token;
                            if(!empty($accessToken) && $accessToken){
                                $baseUrl = 'https://services.leadconnectorhq.com';
                                $endpoint = '/locations/'.$locationId;
                                $companyId = 'XKgit2K0t9ASzkjnr0fZ';
                                $timezone = $request->time_zone;
                                $client = new \GuzzleHttp\Client();
                        
                                $response = $client->request('PUT', $baseUrl . $endpoint, [
                                              'body' => '{
                                              "companyId": "'.$companyId.'" ,
                                              "timezone": "'.$timezone.'"
                                            }',
                                              'headers' => [
                                                'Accept' => 'application/json',
                                                'Authorization' => 'Bearer ' . $accessToken,
                                                'Content-Type' => 'application/json',
                                                'Version' => '2021-07-28',
                                              ],
                                            ]);
                            }
                        }
                        else{
                            return redirect()->back()->with('success', 'Settings saved successfully! & agency locationId not found!');
                        }
                        //For Agency code - end
                        
                        
                        return redirect()->back()->with('success', 'Settings saved successfully!');
                    }
                    else{
                        return redirect()->back()->with('error', 'Please enter all your details!');
                    }
                } 
            }
            return redirect()->back()->with('error', 'Whoops! Settings not saved.');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
    
    //Save template Settings
    public function savetemplateSettings(Request $request){
        try {
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if ($location) {
                $locationById = $location->id;
                $template = ResponseTemplate::where('location_id', $locationById)->first();
            
                // If no existing template, create a new one
                if (!$template) {
                    $template = new ResponseTemplate();
                    $template->location_id = $locationById;
                }
                
                // Build the template data from the form submission
                $templateData = [];
                foreach ($request->all() as $keyword => $message) {
                    if (!in_array($keyword, ['_token'])) {
                        $templateData[$keyword] = $message;
                    }
                }
                // Update the template content and save
                $template->template = json_encode($templateData);
                $template->save();
                return redirect()->back()->with('success', 'Templates updated successfully.');
            }
            
            return redirect()->back()->with('error', 'Whoops! Settings not saved.');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }

    // route handler to initiate the OAuth flow
    public function initiateOAuth(){
        try {
            $clientId = '64ef132ac2f61efe4c33fdf8-llxkkg62';
            $redirectUri = 'https://prayedfor.ddns.net/get-token';
            $scope = 'businesses.readonly businesses.write calendars.readonly calendars.write calendars/events.readonly calendars/events.write campaigns.readonly conversations.readonly conversations.write conversations/message.readonly conversations/message.write contacts.readonly contacts.write forms.readonly forms.write links.readonly links.write locations.write locations.readonly locations/customValues.readonly locations/customValues.write locations/customFields.readonly locations/customFields.write locations/tasks.readonly locations/tasks.write locations/tags.readonly locations/tags.write locations/templates.readonly medias.readonly medias.write opportunities.readonly opportunities.write surveys.readonly users.readonly users.write workflows.readonly snapshots.readonly oauth.write oauth.readonly';
            $authorizationUrl = "https://marketplace.gohighlevel.com/oauth/chooselocation"
                . "?response_type=code"
                . "&client_id={$clientId}"
                . "&redirect_uri={$redirectUri}"
                . "&scope={$scope}";
    
            return Redirect::away($authorizationUrl);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }

    // route to handle the redirect after the user grants permission
    public function handleOAuthRedirect(Request $request){
        $authorizationCode = $request->query('code');
        return Redirect::route('users.createacctoken', ['code' => $authorizationCode]);
    }
    
    //Creating Access Token and saving into DB
    public function create_acc_token(Request $request){
        try {
            $authorizationCode = $request->query('code');
            session(['code' => $authorizationCode]);
            $clientId = '64ef132ac2f61efe4c33fdf8-llxkkg62';
            $clientSecret = 'dd29dac2-b8ea-4a50-8015-aad909210b47';
            $redirectUri = 'https://prayedfor.ddns.net/get-token';
            $client = new Client([
                'base_uri' => 'https://services.leadconnectorhq.com/',
            ]);
            
            //For Agency code - start
            $agencyLocation = Location::where('locationId', 'Agency')->first();
            if (!empty($agencyLocation) && $agencyLocation) {
                $accessToken = $agencyLocation->access_token;
                if(empty($accessToken) && $accessToken === Null){
                    $response = $client->post('oauth/token', [
                        'form_params' => [
                            'grant_type' => 'authorization_code',
                            'code' => $authorizationCode,
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'redirect_uri' => $redirectUri,
                        ],
                    ]);
                    $data = json_decode($response->getBody(), true);
                    $accessToken = $data['access_token'];
                    $refreshToken = $data['refresh_token'];
                    $companyId = $data['companyId'];
                    
                    $agencyLocation->access_token = $accessToken;
                    $agencyLocation->refresh_token = $refreshToken;
                    $agencyLocation->companyId = $companyId;
                    $agencyLocation->save();
                    return Redirect::route('home')->with('message', 'Agency access Token created successfully!');
                }
            }
            //For Agency code - end
            
            $locationId = session('location_id');
            $locations = Location::where('locationId', $locationId)->first();
            
            if ($locations && empty($locations->access_token)) {
                
                // Create Access Token
                $response = $client->post('oauth/token', [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $authorizationCode,
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'redirect_uri' => $redirectUri,
                    ],
                ]);
                $data = json_decode($response->getBody(), true);
                $accessToken = $data['access_token'];
                $refreshToken = $data['refresh_token'];
                $companyId = $data['companyId'];
                
                $locations->access_token = $accessToken;
                $locations->refresh_token = $refreshToken;
                $locations->companyId = $companyId;
                $locations->save();
                
                session(['access_token' => $accessToken]);
                return Redirect::route('home')->with('message', 'Access Token created successfully!');
            }    
            elseif($locations && !empty($locations->access_token)) {
                $accessTokenCreatedAt = $locations->updated_at;
                $expirationTime = $accessTokenCreatedAt->addHour();
                
                // Compare with the current time
                if(Carbon::now()->gt($expirationTime)) {
                    $refreshToken = $locations->refresh_token;
                    
                    // Regenerate Access Token by refresh token
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
                    
                    $locations->access_token = $accessToken;
                    $locations->refresh_token = $refreshToken;
                    $locations->companyId = $companyId;
                    $locations->save();
                    session(['access_token' => $accessToken]);
                    
                    return Redirect::route('home')->with('message', 'Access Token refreshed successfully!');
                }
                else{
                    return Redirect::route('home')->with('message', 'The access token has already been generated and is still valid, as it has not yet expired.');
                }
            }
            else{
                // dd('Everything setup');
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !!';
        }
    }
    
    //Showing & saving contacts for all location ids in users Page by fetching from API
    public function fetchContacts(){
        try {
            $baseUrl = 'https://services.leadconnectorhq.com';
            $endpoint = '/contacts/';
            $client = new \GuzzleHttp\Client();
            $apiVersion = '2021-07-28';
            
            $locations = Location::get();
            if ($locations) {
                foreach($locations as $loc){
                    $location_id = $loc->locationId;
                    $accessToken = $loc->access_token;
                    
                    $location = Location::where('locationId', $location_id)->first();
                    if ($location && $location_id != 'Agency') {
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
                            $tags = isset($contact['tags']) && is_array($contact['tags']) ? json_encode($contact['tags']) : null;
                            $customFields = isset($contact['customFields']) && is_array($contact['customFields']) ? json_encode($contact['customFields']) : null;
                            
                            $contactData = [
                                'contactId' => $contact['id'],
                                'location_id' => $location->id,
                                'name' => $contact['contactName'],
                                'email' => $contact['email'],
                                'tags' => $tags,
                                'customFields' => $customFields,
                            ];
                            
                            // Check if phone number exists
                            if (isset($contact['phone'])) {
                                $existingContact = Contact::where('phone', $contact['phone'])->first();
                                if ($existingContact) {
                                    // Update existing record
                                    $existingContact->update($contactData);
                                } else {
                                    // Create new record
                                    Contact::create(array_merge($contactData, ['phone' => $contact['phone']]));
                                }
                            } else {
                                // Create new record without phone number
                                Contact::create($contactData);
                            }
                        }
                        
                        
                        //Deleting contact records if it is directly deleted from GHL
                        $apiContactIds = array_column($responseData['contacts'], 'id');
                        // Fetch existing contacts from the database
                        $contacts = Contact::where('location_id', $loc->id)->get();
                        foreach ($contacts as $contact) {
                            if (!in_array($contact->contactId, $apiContactIds)) {
                                // Delete the contact record from the database
                                $contact->delete();
                            }
                        }
                        
                        //Adding Flag count in contacts table
                        $prayerRequests = PrayerRequest::where('location_id', $loc->id)->get();
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
                                    $contact = Contact::where('location_id', $loc->id)->where('id',$c_id)->first();
                                    $contact->flagged = $c_value;
                                    $contact->save();
                                }
                            }
                        }
                        
                    }
                }
            }
        } 
        catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
    
    //Showing individual contact info
    public function contactShow($id){
        $locationId = session('location_id');
        $location = Location::where('locationId', $locationId)->first();
        if ($location) {
            try{
                $contact = Contact::where('id', $id)->first();
                $contactName = $contact->name;
                $prayerRequest = PrayerRequest::where('contact_id', $id)
                                                ->whereNotNull('prayedfor_msg')
                                                ->whereNotNull('flagged_req')
                                                ->where('flagged_req', '<>', '')
                                                ->paginate(10);

                return view('contactShow', ['prayerRequests' => $prayerRequest, 'contactName'=> $contactName]);
            }
            catch(\Exception $e){
                return 'Some error occured !';
            }
        }
    }
    
    // refresh Token - currently being done by automation
    public function refreshToken(){
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
                echo 'Token successfully regenerated for all Locations!';
            }
            return view('home');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            // return 'Some error occured !';
        }
    }
    
    //Display Global Message UI
    public function viewGlobalCustomMesage(){
        return view('message');
    }
    
    //Send Global Message from church
    public function sendGlobalCustomMesage(Request $request){
        try {
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if (!empty($locationId) && $location) {
                $accessToken = $location->access_token;
                if(!empty($accessToken) && $accessToken){
                    $client = new \GuzzleHttp\Client();
                    $access_token = $location->access_token;
                    $apiVersion = '2021-04-15';
                    
                    $custom_subject = $request->custom_subject;
                    $custom_message = $request->custom_message;
                    $custom_message = str_replace("\n", ' ', $custom_message);
                    $custom_message = str_replace("\r", '', $custom_message);
                    $send_to = $request->send_to;
                    if($send_to === 'prayer_warriors'){
                        $contacts = Contact::where('location_id', $location->id)
                                            ->whereJsonContains('tags', '#prayerwarrior')
                                            ->get();
                        if($contacts){
                            foreach($contacts as $contact){
                                $contact_id = $contact->contactId;
                                $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                                      'body' => '{
                                                      "type": "SMS",
                                                      "contactId": "'.$contact_id.'" ,
                                                      "message": "'.$custom_message.'" ,
                                                      "subject": "'.$custom_subject.'"
                                                    }',
                                                      'headers' => [
                                                        'Accept' => 'application/json',
                                                        'Authorization' => 'Bearer '. $access_token,
                                                        'Content-Type' => 'application/json',
                                                        'Version' => $apiVersion,
                                                      ],
                                                    ]);
                                
                            }
                            return redirect()->back()->with('success', 'Messages sent successfully to all Prayer Warriors!');
                        }
                        else{
                            return redirect()->back()->with('success', 'No Prayer Warriors exist!');
                        }
                    }
                    elseif($send_to === 'all'){
                        $contacts = Contact::where('location_id', $location->id)->get();
                        foreach($contacts as $contact){
                            $contact_id = $contact->contactId;
                            $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                                      'body' => '{
                                                      "type": "SMS",
                                                      "contactId": "'.$contact_id.'" ,
                                                      "message": "'.$custom_message.'" ,
                                                      "subject": "'.$custom_subject.'"
                                                    }',
                                                      'headers' => [
                                                        'Accept' => 'application/json',
                                                        'Authorization' => 'Bearer '. $access_token,
                                                        'Content-Type' => 'application/json',
                                                        'Version' => $apiVersion,
                                                      ],
                                                    ]);
                            
                        }
                        return redirect()->back()->with('success', 'Messages sent successfully to all!');
                    }
                    return redirect()->back()->with('error', 'Whoops! Your message not sent.');
                }
                else{
                    return redirect()->back()->with('error', 'Please authorize access token first.');
                }
            }
            return redirect()->back()->with('error', 'Location ID does not exist.');
        } catch (\Exception $e) {
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
    
    //Approve flagged message
    public function approveMessage($id, $locationId = null){
        try{
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if (!empty($locationId) && $location) {
                $accessToken = $location->access_token;
                if(!empty($accessToken) && $accessToken){
                    $locationId = $location->id;
                    $prayerRequest = PrayerRequest::where('location_id', $locationId)->where('id', $id)->first();
                    $contact_id = $prayerRequest->contact_id;
                    $pr_id = '#PR'.$prayerRequest->id;
                    
                    //Template fetch from DB
                    $temps = TemplateService::getTemplates($locationId);
                    $approve = $temps["approve"] ?? null;
                    if (strpos($approve, '{{pr_id}}') !== false) {
                        $message = str_replace('{{pr_id}}', $pr_id, $approve);
                    } else {
                        $message = $approve;
                    }
                    
                    $contact = Contact::where('id', $contact_id)->first();
                    $contactId = $contact->contactId;
                    $prayedfor_msg = $prayerRequest->prayedfor_msg;
                    
                    // Sending message
                    $client = new \GuzzleHttp\Client();
                    $apiVersion = '2021-04-15';
                    $access_token = $location->access_token;
                    $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                                  'body' => '{
                                                  "type": "SMS",
                                                  "contactId": "'.$contactId.'" ,
                                                  "message": "'.$message.'" ,
                                                  "subject": "Message from Church Admin"
                                                }',
                                                  'headers' => [
                                                    'Accept' => 'application/json',
                                                    'Authorization' => 'Bearer '. $access_token,
                                                    'Content-Type' => 'application/json',
                                                    'Version' => $apiVersion,
                                                  ],
                                                ]);
                    $prayerReqByContId = PrayerRequest::where('contact_id', $contact_id)->where('prayedfor_msg', $prayedfor_msg)->first();
                    if ($prayerReqByContId) {
                        $prayerReqByContId->flagged_req = 0;
                        $prayerReqByContId->save();
                        
                        $contact->flagged -= 1;
                        $contact->save();
                    }
                    return redirect()->back()->with('success', 'This Prayer Request is approved and the message has been sent.');
                }
                return redirect()->back()->with('error', 'Please authorize your token.');
            }
            return redirect()->back()->with('error', 'Some error occurred.');
        }
        catch(\Exception $e){
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
    
    //Disapprove flagged message
    public function disapproveMessage($id, $locationId = null){
        try{
            $locationId = session('location_id');
            $location = Location::where('locationId', $locationId)->first();
            if ($location) {
                    $locationId = $location->id;
                    $prayerRequest = PrayerRequest::where('id', $id)->first();
                    $contact_id = $prayerRequest->contact_id;
                    $pr_id = '#PR'.$prayerRequest->id;
                    
                    //Template fetch from DB
                    $temps = TemplateService::getTemplates($locationId);
                    $disapprove = $temps["disapprove"] ?? null;
                    if (strpos($disapprove, '{{pr_id}}') !== false) {
                        $message = str_replace('{{pr_id}}', $pr_id, $disapprove);
                    } else {
                        $message = $disapprove;
                    }
                    
                    $contact = Contact::where('id', $contact_id)->first();
                    $contactId = $contact->contactId;
                    
                    // sending message
                    $client = new \GuzzleHttp\Client();
                    $apiVersion = '2021-04-15';
                    $access_token = $location->access_token;
                    if($access_token && !empty($access_token)){
                        $response = $client->request('POST', 'https://services.leadconnectorhq.com/conversations/messages', [
                                                      'body' => '{
                                                      "type": "SMS",
                                                      "contactId": "'.$contactId.'" ,
                                                      "message": "'.$message.'" ,
                                                      "subject": "Message from Church Admin"
                                                    }',
                                                      'headers' => [
                                                        'Accept' => 'application/json',
                                                        'Authorization' => 'Bearer '. $access_token,
                                                        'Content-Type' => 'application/json',
                                                        'Version' => $apiVersion,
                                                      ],
                                                    ]);
                    }  
                    return redirect()->back()->with('success', 'Your message has been sent.');
            }
            return redirect()->back()->with('error', 'Some error occurred.');
        }
        catch(\Exception $e){
            // return response()->json(['error' => $e->getMessage()], 500);
            return 'Some error occured !';
        }
    }
    
    //test
    public function testOpenAi(Request $request){
        // dd('test AI');
        // $openaiApiKey = config('services.openai.api_key');
        $openaiApiKey = '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';
        // Create a Guzzle client instance
        $client = new Client();
        // if there are any quotes in the message we will have to remove it first
        $msg = "please pray for me I want to commit suicide";
        // Make a POST request to OpenAI's moderation endpoint
        try {
            $response = $client->post('https://api.openai.com/v1/moderations', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $openaiApiKey,
                ],
                'json' => [
                    'input' => $msg,
                ],
            ]);

            // Get the response body as JSON
            $data = json_decode($response->getBody(), true);
            // dd($data);
            // dd($data["results"][0]["categories"]);
            
            //to get the category in which it fall as spam
            // foreach ($data["results"][0]["categories"] as $key => $value){
            //     if ($value == true){
            //         var_dump($key);// will give the category which is true
            //     }
            //     var_dump($value);
            // }
            
            //here it can be tuned to the score that we want to set
            foreach ($data["results"][0]["category_scores"] as $key => $value){
                if ($value > 0.3){
                    var_dump($key);// will give the category which is the score is above
                }
                var_dump($value);
            }
            
            // Process the response as needed
            return response()->json($data);
        } catch (\Exception $e) {
            // Handle any exceptions that occur during the request
            return response()->json(['error' => $e->getMessage()], 500);
        }
    
    }
}
