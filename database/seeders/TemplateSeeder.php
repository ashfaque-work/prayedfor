<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ResponseTemplate;
use Illuminate\Support\Facades\DB;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'keyword' => 'prayerwarrior',
                'message_body' => 'Thank you for registering to be a Prayer Warrior, who will get daily prayer requests from your church family. Please reply how many prayer request would you like to receive per day? Max frequency you can set is {{max_frequency}}. Eg: #Frequency1',
            ],
            [
                'keyword' => 'prayerrequest',
                'message_body' => 'Thank you for sending Prayer Request. We will notify once your prayer is made by someone.',
            ],
            [
                'keyword' => 'notag',
                'message_body' => "If you'd like to make prayer request please start your message with #PrayerRequest . Want to become a prayer warrior who receives prayers please respond with #PrayerWarrior",
            ],
            [
                'keyword' => 'prayerwarriornotag',
                'message_body' => 'Please respond with correct tag. Eg: #Frequency_8, #ChangeFrequency, #Cancel or #Inappropriate.',
            ],
            [
                'keyword' => 'prayerrequestnotag',
                'message_body' => 'Please respond with correct tag. Eg: #PrayerRequest',
            ],
            [
                'keyword' => 'frequencyN',
                'message_body' => 'You will get maximum of {{frequency}} messages per day between {{time_gap_from}}  to {{time_gap_to}}. You can change frequency by using #ChangeFrequency and cancel using #Cancel',
            ],
            [
                'keyword' => 'changefrequency',
                'message_body' => 'Please reply how many prayer request would you like to receive per day? Eg: #Frequency1',
            ],
            [
                'keyword' => 'cancel',
                'message_body' => 'You will not receive any prayer request',
            ],
            [
                'keyword' => 'approve',
                'message_body' => 'Thankyou, Your prayer request {{pr_id}} is approved. We will notify once your prayer is made by someone.',
            ],
            [
                'keyword' => 'disapprove',
                "message_body" => "Please don't use inappropriate words in your prayer request {{pr_id}} , otherwise it will considered as spam.",
            ],
            [
                'keyword' => 'eodCount',
                'message_body' => "Total number of pople prayed over your request #pr{{pr_id}} today are {{count_today}}. Thank you.",
            
            ],
            [
                'keyword' => 'profileUpdate',
                'message_body' => "Hello! To better assist you, could you please provide your first name and last name? For example: #ProfileUpdate First name: John Last name: Doe",
            ],
            [
                'keyword' => 'inappropriate',
                'message_body' => "Thankyou to let us know about this inappropriate prayer request. We'll review it and take actions accordingly.",
            ],
            [
                'keyword' => 'inappropriateNoPRtag',
                'message_body' => "Please send prayerRequest no for which prayer request you think it was inappropriate. E.g, #Inappropriate #PR5",
            ],
            [
                'keyword' => 'pwprNotag',
                'message_body' => "PrayerWarrior as well as PrayerRequest. Use correct tags like. #Frequency, #ChangeFrequency, #Cancel or #PrayerRequest",
            ],
            [
                'keyword' => 'thankYouPrayedFor',
                'message_body' => "Thank you for praying for prayer request ",
            ],
            [
                'keyword' => 'alreadyPrayedResponse',
                'message_body' => "Thank you for praying for prayer request ",
            ],
            [
                'keyword' => 'prayedForNoPRtag',
                'message_body' => "Please send PrayerRequest number for which prayer request you have prayed. E.g, #PrayedFor #pr5",
            ],
            [
                'keyword' => 'invalidPRid',
                'message_body' => "Invalid prayer request IDs: ",
            ],
            [
                'keyword' => 'profileUpdateMsg',
                'message_body' => "Your Profile has now been successfully updated. ",
            ],
            [
                'keyword' => 'frequencyLimit',
                'message_body' => "Sorry! You can set max of {{max_frequency}} frequency.",
            ],
            [
                'keyword' => 'spam',
                'message_body' => "System has flagged your request as spam.",
            ],
            
        ];
        
        $combinedTemplate = [];
        foreach ($data as $item) {
            $combinedTemplate[$item['keyword']] = $item['message_body'];
        }
        
        // Inserting the combined record
        DB::table('response_templates')->insert([
            'location_id' => null,
            'template' => json_encode($combinedTemplate)
        ]);
    }
}
