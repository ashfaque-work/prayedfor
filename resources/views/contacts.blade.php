@extends('layouts.app')

@section('content')
        <div>
            <h2 class="text-center">Contact List</h2>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Tags</th>
                        <th>Flagged Request</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($contacts) && count($contacts) > 0)
                        @foreach ($contacts as $contact)
                            <tr>
                                <td>{{ $contact['name'] }}</td>
                                <td>{{ $contact['phone'] }}</td>
                                <td>
                                    @if($contact->tags)
                                        @foreach(json_decode($contact->tags) as $tag)
                                            {{ $tag }}
                                            @if(!$loop->last)
                                                ,
                                            @endif
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $contact['flagged'] }}</td>
                                <td class="text-center">
                                    @if($contact->tags && in_array('#prayerrequest', json_decode($contact->tags)))
                                        <a href="{{ route('church.contactShow', $contact['id']) }}" style="color: black;"><i class="fa fa-eye"></i></a>    
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center">No contacts found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="d-flex justify-content-center">
                @if(isset($contacts) && count($contacts) > 0)
                    {{ $contacts->links('pagination::bootstrap-4') }}
                @endif
            </div>
        </div>            
@endsection

