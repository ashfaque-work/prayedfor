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
            
            <a class="btn btn-dark mb-4" href="{{ route('church.listContacts') }}"><i class="fas fa-arrow-left"></i> Back</a>
            <h4>Name: {{$contactName}}</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Message</th>
                        <th>Flagged</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($prayerRequests) && count($prayerRequests) > 0)
                        @foreach ($prayerRequests as $prayerrequest)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{ $prayerrequest['prayedfor_msg'] }}</td>
                                    <td>{{ $prayerrequest['flagged_req'] }}</td>
                                    <td>
                                        @if ($prayerrequest['flagged_req'] == 1)
                                            <a class="btn btn-outline-success" href="{{ route('church.approveMessage', $prayerrequest['id']) }}"><i class="fas fa-check"></i></i> Approve</a>
                                            <a class="btn btn-outline-danger" href="{{ route('church.disapproveMessage', $prayerrequest['id']) }}"><i class="fas fa-times"></i> Disapprove</a>
                                        @endif
                                    </td>
                                </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center">No Prayer Request found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <!-- Add pagination links below the table -->
            <div class="d-flex justify-content-center">
                @if(isset($prayerRequests) && count($prayerRequests) > 0)
                    {{ $prayerRequests->links('pagination::bootstrap-4') }}
                @endif
            </div>
        </div>

@endsection