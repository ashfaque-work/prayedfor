@extends('layouts.app')

@section('content')
<div>
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show pray-alert-message ms-5" role="alert">
         {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show pray-alert-message ms-5" role="alert">
          {{ session('error') }}
        </div>
    @endif
    <h2 class="text-center">Settings</h2>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link link-dark active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">General</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link link-dark" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">Template</button>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show mt-4 me-4 active" id="home" role="tabpanel" aria-labelledby="home-tab">
            <div class="card w-75 mx-auto">
                <div class="card-body">
                    <form method="POST" action="{{ route('church.settings') }}">
                        @csrf
                        <div class="row">
                            <div class="col">
                                <div class="form-group mb-4">
                                    <label for="prayer_w_qnty" class="mb-2">Prayer Request Circulation Volume</label>
                                    <input type="number" class="form-control" name="prayer_w_qnty" id="prayer_w_qnty" required placeholder="Prayer Warriors Quantity" value="{{ $churchSetting->prayer_w_qnty ?? '' }}">
                                    <small id="emailHelp" class="form-text text-muted">Set how many #PrayerWarriors will receive each incoming #PrayerRequest</small>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group mb-4">
                                    <label for="prayer_req_qnty" class="mb-2">Prayer Warrior Daily Limit</label>
                                    <input type="number" class="form-control" name="prayer_req_qnty" required id="prayer_req_qnty" placeholder="Prayer Request Per Day" value="{{ $churchSetting->prayer_req_qnty ?? '' }}">
                                    <small id="emailHelp2" class="form-text text-muted">The number of #PrayerRequests each #PrayerWarrior receives per day</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col">
                                <div class="form-group mb-4">
                                    <label for="prayer_req_alltime" class="mb-2">Max Prayer Request Forwarding</label>
                                    <input type="number" class="form-control" name="prayer_req_alltime" required id="prayer_req_alltime" placeholder="Max Prayer Request Forwarding" value="{{ $churchSetting->prayer_req_alltime ?? '' }}">
                                    <small id="emailHelp3" class="form-text text-muted">Maximum no of time a prayer request message will be forwarded to all prayer warriors for all eternity.</small>
                                </div>
                            </div>
                            <div class="col">
                                @if(isset($churchSetting))
                                <div class="form-group mb-4">
                                    <label for="time_interval" class="mb-2">Time Interval</label>
                                    <select class="form-select" aria-label="Select Time Interval" name="time_interval">
                                        <option value="" disabled>Select Time Interval</option>
                                        <option value="30" {{ old('time_interval', $churchSetting->time_interval) == 30 ? 'selected' : '' }}>30 Minutes</option>
                                        <option value="1" {{ old('time_interval', $churchSetting->time_interval) == 1 ? 'selected' : '' }}>One Hour</option>
                                        <option value="2" {{ old('time_interval', $churchSetting->time_interval) == 2 ? 'selected' : '' }}>Two Hours</option>
                                        <option value="3" {{ old('time_interval', $churchSetting->time_interval) == 3 ? 'selected' : '' }}>Three Hours</option>
                                    </select>
                                    <small id="emailHelp2" class="form-text text-muted">Time gap after which the second prayer request will be sent to the prayer warrior</small>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-4">
                              <label for="exampleFormControlInput1" class="mb-2">Time Gap to get prayer request</label><br>
                              <div class="row">
                                <div class="col-sm d-flex align-items-center justify-content-between">
                                    <label for="exampleFormControlInput1" class="d-inline">From</label>
                                    <input type="time" name="time_gap_from" required class="form-control w-75 d-inline" id="exampleFormControlInput1" placeholder="Prayer Request Per Day" value="{{ $churchSetting->time_gap_from ?? '' }}">
                                </div>
                                <div class="col-sm d-flex align-items-center justify-content-between">
                                    <label for="exampleFormControlInput1" class="d-inline">To</label>
                                    <input type="time" name="time_gap_to" required class="form-control w-75 d-inline" id="exampleFormControlInput1" placeholder="Prayer Request Per Day" value="{{ $churchSetting->time_gap_to ?? '' }}">
                                </div>
                              </div>
                            <small id="emailHelp5" class="form-text text-muted">The time gap of day that these request will be received</small>
                        </div>
                        @if(isset($churchSetting))
                        <div class="form-group mb-4">
                            <label for="time_zone" class="mb-2">Time Zone</label>
                            <select class="form-select" aria-label="Select Time Zone" name="time_zone">
                                <option value="" disabled>Select Time Zone</option>
                                @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ old('time_zone', $churchSetting->time_zone) == $tz ? 'selected' : '' }}>
                                        {{ $tz }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Select the time zone for your church's location.</small>
                        </div>
                        @endif
                        <button type="submit" class="btn btn-dark">Save</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="tab-pane fade mt-4 me-4" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('church.savetemplateSettings') }}">
                        @csrf
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="w-25">Keywords</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                   @if(isset($templates) && count($templates) > 0)
                                        @foreach($templates as $template)
                                            <?php $templateData = json_decode($template->template, true); ?>
                                            @foreach ($templateData as $keyword => $message)
                                                <tr>
                                                    <td>{{ $keyword }}</td>
                                                    <td>
                                                        <textarea class="form-control" name="{{ $keyword }}" id="{{ $keyword }}" rows="4" required>
                                                            {{ $message ?? '' }}
                                                        </textarea>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endif
                                </tr>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-dark">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Add event listener to restrict input length
    const inputs = document.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.length > 3) {
                this.value = this.value.slice(0, 3);
            }
        });
    });
</script>
@endsection