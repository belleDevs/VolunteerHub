@if($report->audience === 'admin')
    <x-layout.admin activeTab="reports">
        @include('reports.body')
    </x-layout.admin>
@elseif($report->audience === 'organization')
    <x-layout.org active="reports">
        @include('reports.body')
    </x-layout.org>
@else
    <x-layout.volunteer>
        <div class="lg:col-span-12">
            @include('reports.body')
        </div>
    </x-layout.volunteer>
@endif
