<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $noticeTitle }}</title>
</head>
<body style="margin: 0; padding: 0; background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif;">
    <div style="max-width: 620px; margin: 0 auto; padding: 28px 16px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
            <div style="background: #0097d6; color: #ffffff; padding: 22px 24px;">
                <p style="margin: 0 0 6px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em;">{{ $organization->name }}</p>
                <h1 style="margin: 0; font-size: 22px; line-height: 1.3;">{{ $noticeTitle }}</h1>
            </div>
            <div style="padding: 24px;">
                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">Hello {{ $volunteer->name }},</p>
                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; white-space: pre-line;">{{ $noticeMessage }}</p>

                <p style="margin: 18px 0 8px; font-size: 13px; font-weight: bold; color: #0073a8;">New events</p>
                @if($events->isEmpty())
                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #64748b;">There are no new events open right now.</p>
                @else
                    @foreach($events as $event)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin: 0 0 8px;">
                            <p style="margin: 0 0 4px; font-size: 14px; font-weight: bold;">{{ $event->title }}</p>
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #475569;">
                                {{ $event->location }}
                                @if($event->start_time)
                                    · {{ $event->start_time->format('M d, Y g:i A') }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                @endif

                <p style="margin: 18px 0 8px; font-size: 13px; font-weight: bold; color: #0073a8;">Tasks assigned to you</p>
                @if($assignments->isEmpty())
                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #64748b;">You have no open tasks assigned right now.</p>
                @else
                    @foreach($assignments as $assignment)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin: 0 0 8px;">
                            <p style="margin: 0 0 4px; font-size: 14px; font-weight: bold;">{{ $assignment->task->title ?? 'Assigned task' }}</p>
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #475569;">
                                {{ $assignment->event->title ?? 'Event' }}
                                @if($assignment->task?->due_date)
                                    · Due {{ $assignment->task->due_date->format('M d, Y g:i A') }}
                                @endif
                            </p>
                        </div>
                    @endforeach
                @endif

                <p style="margin: 18px 0 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                    This was also posted on your VolunteerHub account. Sign in to see it there.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
