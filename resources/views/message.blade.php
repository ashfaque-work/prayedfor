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
    <h2 class="text-center">Message</h2>
    <div class="card w-50 mx-auto position-rel">
        <div class="card-body">
            <form method="POST" id="global_msg_prayedfor" action="{{ route('church.sendGlobalCustomMesage') }}">
                @csrf
                 <div class="form-group mb-4">
                    <label for="custom_subject" class="form-label">Subject</label>
                    <input class="form-control" id="custom_subject" type="text" placeholder="Enter your subject" name="custom_subject" aria-label="default input example">
                  </div>
                  <div class="form-group mb-4">
                    <label for="custom_message" class="form-label">Message</label>
                    <textarea class="form-control" id="custom_message" rows="7" name="custom_message" placeholder="Enter your message"></textarea>
                  </div>
                  <div class="form-group mb-4">
                        <label for="send_to" class="form-label">Send To</label>
                        <select class="form-select" id="send_to" aria-label="Default select example" name="send_to">
                          <option selected disabled>Select option</option>
                          <option value="all">All</option>
                          <option value="prayer_warriors">Prayer Warriors</option>
                        </select>
                  </div>
                  <button type="submit" class="btn btn-dark">Send</button>
            </form>
            <div id="prayedfor_loader" class="prayedfor_loader"></div>
        </div>
    </div>
</div>
@endsection