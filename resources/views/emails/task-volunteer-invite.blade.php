@php
    $event = $task->event;
    $organization = $event?->organization;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Needed</title>
</head>
<body style="margin: 0; padding: 0; background: #f8fafc; color: #0f172a; font-family: Arial, sans-serif;">
    <div style="max-width: 620px; margin: 0 auto; padding: 28px 16px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
            <div style="background: #0f4c81; color: #ffffff; padding: 22px 24px;">
                <p style="margin: 0 0 6px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em;">VolunteerHub Task Invite</p>
                <h1 style="margin: 0; font-size: 22px; line-height: 1.3;">{{ $task->title }}</h1>
            </div>

            <div style="padding: 24px;">
                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">
                    Hello {{ $volunteer->name }},
                </p>

                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">
                    {{ $organization?->name ?? 'A partner organization' }} is looking for volunteers for this task under
                    <strong>{{ $event?->title ?? 'an upcoming event' }}</strong>.
                </p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin: 18px 0;">
                    <p style="margin: 0 0 8px; font-size: 13px;"><strong>Event:</strong> {{ $event?->title ?? 'Event details unavailable' }}</p>
                    <p style="margin: 0 0 8px; font-size: 13px;"><strong>Organization:</strong> {{ $organization?->name ?? 'Partner Organization' }}</p>
                    <p style="margin: 0 0 8px; font-size: 13px;"><strong>Location:</strong> {{ $event?->location ?? 'To be announced' }}</p>
                    <p style="margin: 0 0 8px; font-size: 13px;">
                        <strong>Schedule:</strong>
                        @if($event?->start_time && $event?->end_time)
                            {{ $event->start_time->format('F d, Y h:i A') }} to {{ $event->end_time->format('F d, Y h:i A') }}
                        @else
                            To be announced
                        @endif
                    </p>
                    <p style="margin: 0; font-size: 13px;">
                        <strong>Needed skills:</strong>
                        @if($task->skills->count() > 0)
                            {{ $task->skills->pluck('name')->join(', ') }}
                        @else
                            Open to all approved volunteers
                        @endif
                    </p>
                </div>

                <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6;">
                    If you are available, please log in to VolunteerHub and apply from your volunteer dashboard.
                </p>

                <p style="margin: 18px 0 0; font-size: 12px; line-height: 1.5; color: #64748b;">
                    This email was sent because your VolunteerHub account is approved and matches the organization outreach criteria for this task.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
