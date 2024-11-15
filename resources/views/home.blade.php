@extends('layouts.app')

@section('content')
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show pray-alert-message ms-5" role="alert">
              <strong></strong> {{ session('success') }}
            </div>
        @endif
        @if (session('message'))
            <div class="alert alert-success alert-dismissible fade show pray-alert-message ms-5" role="alert">
              <strong></strong> {{ session('message') }}
            </div>
        @endif
        @if(isset($message))
            <div class="alert alert-danger alert-dismissible fade show ms-5" role="alert">
                {{ $message }}
                @if(isset($generateAccessTokenLink))
                    <a href="{{ $generateAccessTokenLink }}">here.</a>
                @endif
            </div>
        @endif
        @if(isset($agencyMessage))
            <div class="alert alert-danger alert-dismissible fade show ms-5" role="alert">
                {{ $agencyMessage }}
                @if(isset($generateAgencyAccessTokenLink))
                    <a href="{{ $generateAgencyAccessTokenLink }}">here.</a>
                @endif
            </div>
        @endif
        
        @php
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $isSafari = strpos($ua, 'Safari') !== false && strpos($ua, 'Chrome') === false;
        @endphp
        
        @if ($isSafari)
            <div class="alert alert-danger alert-dismissible fade show ms-5" role="alert">Not Accessible for Safari currently. Meanwhile open in a new tab
                <a class="text-danger" href="https://prayedfor.ddns.net/?locationid={{ $locationId }}" target="_blank">click here</a>
            </div>
        @endif
        
        <div>
            <h2 class="text-center mb-5">PrayedFor.io</h2>
            <table class="table table-bordered mt-2">
                <thead>
                    <tr>
                        <th>Keywords</th>
                        <th>Usage</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#PrayerRequest</td>
                        <td>This will add a prayer request for that user.</td>
                        <td>#PrayerRequest Pray for my good marks in exam.</td>
                    </tr>
                    <tr>
                        <td>#PrayerWarrior</td>
                        <td>This will make a user prayed warrior and he/she wll get prayer requests to pray for.</td>
                        <td>#PrayerWarrior I want to be a prayer warrior</td>
                    </tr>
                    <tr>
                        <td>#FrequencyN</td>
                        <td>This will set frequency of getting N no. of prayer request per day.</td>
                        <td>#Frequency5</td>
                    </tr>
                    <tr>
                        <td>#ChangeFrequency</td>
                        <td>This will change frequency of getting prayer request.</td>
                        <td>#ChangeFrequency #Frequency8 I want to change my frequency for getting prayer requests.</td>
                    </tr>
                    <tr>
                        <td>#Cancel</td>
                        <td>This will cancel prayer warrior role for that user/contact.</td>
                        <td>#Cancel I don't want to be prayer warrior anymore.</td>
                    </tr>
                    <tr>
                        <td>#ProfileUpdate</td>
                        <td>This will update users first name and last name.</td>
                        <td>#ProfileUpdate FirstName: John LastName: Doe </td>
                    </tr>
                    <tr>
                        <td>#Inappropriate</td>
                        <td>If a prayer warrior get any inappropriate prayer request they can inform church amdin by sending tags with prayer request Id</td>
                        <td>#Inappropriate #PR7 </td>
                    </tr>
                </tbody>
            </table>
        </div>
@endsection

