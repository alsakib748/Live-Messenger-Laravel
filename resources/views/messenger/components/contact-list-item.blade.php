<div class="wsus__user_list_item messenger-list-item" data-id="{{ $user->id }}">
    <div class="img">
        <img src="{{ asset($user->avatar) }}" alt="User" class="img-fluid" />
        <span class="active"></span>
    </div>
    <div class="text">
        <h5>{{ $user->name }}</h5>
        @if ($lastMessage->from_id == auth()->user()->id)
            <p><span>You</span> {{ $lastMessage->body }}</p>
        @else
            <p>{{ $lastMessage->body }}</p>
        @endif
    </div>
    {{-- <span class="time"></span> --}}
    @if ($unseenCounter !== 0)
        <span class="badge bg-danger text-light unseen_count time">{{ $unseenCounter }}</span>
    @endif
</div>
