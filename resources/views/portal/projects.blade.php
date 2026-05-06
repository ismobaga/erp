<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.projects.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
        .project-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #e5eaf2;
            box-shadow: 0 1px 4px rgba(0,32,69,0.05);
        }
        .project-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }
        .project-name { font-size: 16px; font-weight: 800; color: #002045; }
        .project-service { font-size: 13px; color: #57657a; margin-top: 2px; }
        .progress-bar-track {
            background: #e5eaf2;
            border-radius: 999px;
            height: 6px;
            overflow: hidden;
            margin-top: 14px;
        }
        .progress-bar-fill {
            height: 6px;
            border-radius: 999px;
            background: linear-gradient(90deg, #002045, #1a365d);
        }
        .project-meta { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 12px; }
        .project-meta-item { font-size: 12px; color: #57657a; }
        .project-meta-item strong { color: #002045; }
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @if($projects->isEmpty())
        <div class="card">
            <div class="empty">
                <div class="empty-icon">🚀</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.projects.empty') }}</p>
                <p>{{ __('erp.portal.projects.empty_hint') }}</p>
            </div>
        </div>
    @else
        <div class="card-title" style="padding:0 0 16px;">{{ __('erp.portal.projects.title') }}</div>

        @foreach($projects as $project)
            @php
                $progressPct = match($project->status) {
                    'planned'     => 0,
                    'in_progress' => 50,
                    'on_hold'     => 40,
                    'completed'   => 100,
                    'cancelled'   => 0,
                    default       => 0,
                };
            @endphp
            <div class="project-card">
                <div class="project-header">
                    <div>
                        <div class="project-name">{{ $project->name }}</div>
                        @if($project->service)
                            <div class="project-service">{{ $project->service->name }}</div>
                        @endif
                    </div>
                    <span class="badge badge-{{ $project->status }}">
                        {{ __('erp.portal.projects.statuses.' . $project->status) ?: $project->status }}
                    </span>
                </div>

                @if($project->description)
                    <p style="font-size:14px;color:#374151;line-height:1.6;margin-bottom:10px;">{{ $project->description }}</p>
                @endif

                <div class="project-meta">
                    @if($project->start_date)
                        <div class="project-meta-item">
                            <strong>{{ __('erp.portal.projects.start_date') }} :</strong> {{ $project->start_date->format('d/m/Y') }}
                        </div>
                    @endif
                    @if($project->due_date)
                        <div class="project-meta-item">
                            <strong>{{ __('erp.portal.projects.due_date') }} :</strong> {{ $project->due_date->format('d/m/Y') }}
                        </div>
                    @endif
                    @if($project->assignee)
                        <div class="project-meta-item">
                            <strong>{{ __('erp.portal.projects.assigned_to') }} :</strong> {{ $project->assignee->name }}
                        </div>
                    @endif
                </div>

                @if($project->status !== 'cancelled')
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" style="width: {{ $progressPct }}%;"></div>
                    </div>
                    <div style="font-size:11px;color:#57657a;margin-top:4px;text-align:right;">{{ $progressPct }}%</div>
                @endif
            </div>
        @endforeach
    @endif
</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
