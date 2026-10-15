<x-layout.volunteer>
    @if($section === 'events')
        @include('volunteer.sections.events')
    @elseif($section === 'tasks')
        @include('volunteer.sections.tasks')
    @elseif($section === 'availability')
        @include('volunteer.sections.availability')
    @elseif($section === 'notifications')
        @include('volunteer.sections.notifications')
    @elseif($section === 'certificates')
        @include('volunteer.sections.certificates')
    @elseif($section === 'skills')
        @include('volunteer.sections.skills')
    @elseif($section === 'chatbot')
        @include('volunteer.sections.chatbot')
    @elseif($section === 'messages')
        @include('volunteer.sections.messages')
    @elseif($section === 'settings')
        @include('volunteer.sections.settings')
    @else
        @include('volunteer.sections.home')
    @endif
</x-layout.volunteer>
