<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('dashboard.pdf.title') }}</title>
    <style>
        @font-face {
            font-family: 'Cairo';
            src: local('Cairo');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #1F2937;
            direction: rtl;
            background: #fff;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .header .meta {
            display: flex;
            gap: 24px;
            font-size: 11px;
            opacity: 0.9;
        }

        .meta-item {
            display: inline-block;
            margin-left: 20px;
        }

        .meta-label {
            font-weight: 600;
            opacity: 0.8;
        }

        .section {
            margin-bottom: 20px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            overflow: hidden;
        }

        .section-heading {
            background: #F9FAFB;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            border-bottom: 1px solid #E5E7EB;
        }

        .section-body {
            padding: 16px;
        }

        .stats-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .stat-row {
            display: table-row;
        }

        .stat-cell {
            display: table-cell;
            width: 33.33%;
            padding: 12px;
            border: 1px solid #E5E7EB;
            text-align: center;
            vertical-align: middle;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #F97316;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 10px;
            color: #6B7280;
            margin-top: 4px;
        }

        .stat-cell.danger .stat-value { color: #EF4444; }
        .stat-cell.success .stat-value { color: #22C55E; }
        .stat-cell.warning .stat-value { color: #F59E0B; }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data-table th {
            background: #F3F4F6;
            padding: 8px 10px;
            text-align: right;
            font-weight: 600;
            color: #374151;
            border: 1px solid #E5E7EB;
        }

        table.data-table td {
            padding: 8px 10px;
            border: 1px solid #E5E7EB;
            color: #4B5563;
        }

        table.data-table tr:nth-child(even) td {
            background: #F9FAFB;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 12px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
        }

        .badge-info { background: #DBEAFE; color: #1D4ED8; }
        .badge-success { background: #DCFCE7; color: #15803D; }
        .badge-warning { background: #FEF3C7; color: #B45309; }
        .badge-danger { background: #FEE2E2; color: #B91C1C; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <h1>{{ __('dashboard.pdf.title') }}</h1>
        <div class="meta">
            <span class="meta-item">
                <span class="meta-label">{{ __('dashboard.pdf.generated_by') }}:</span>
                {{ $user?->name ?? 'N/A' }}
            </span>
            <span class="meta-item">
                <span class="meta-label">{{ __('dashboard.pdf.role') }}:</span>
                {{ $role }}
            </span>
            <span class="meta-item">
                <span class="meta-label">{{ __('dashboard.pdf.date') }}:</span>
                {{ $generated }}
            </span>
            <span class="meta-item">
                <span class="meta-label">{{ __('dashboard.pdf.date_range') }}:</span>
                {{ $startDate }} {{ __('dashboard.pdf.to') }} {{ $endDate }}
            </span>
        </div>
    </div>

    {{-- Pending Approvals --}}
    @if(class_exists(\Wezlo\FilamentApproval\Models\Approval::class) && \Illuminate\Support\Facades\Schema::hasTable('approvals'))
    @php
        $pendingApprovals = \Wezlo\FilamentApproval\Models\Approval::where('status', \Wezlo\FilamentApproval\Enums\ApprovalStatus::Pending)->count();
    @endphp
    <div class="section">
        <div class="section-heading">{{ __('dashboard.pdf.action_items') }}</div>
        <div class="section-body">
            <div class="stats-grid">
                <div class="stat-row">
                    <div class="stat-cell {{ $pendingApprovals > 0 ? 'danger' : 'success' }}">
                        <div class="stat-value">{{ $pendingApprovals }}</div>
                        <div class="stat-label">{{ __('dashboard.widgets.pending_approvals') }}</div>
                    </div>
                    @if(\Illuminate\Support\Facades\Schema::hasTable('meeting_tasks'))
                    @php
                        $overdue = \Webkul\Meetings\Models\MeetingTask::whereDate('due_date', '<', now())
                            ->whereNotIn('status', ['completed','cancelled'])->count();
                    @endphp
                    <div class="stat-cell {{ $overdue > 0 ? 'danger' : 'success' }}">
                        <div class="stat-value">{{ $overdue }}</div>
                        <div class="stat-label">{{ __('dashboard.widgets.overdue_tasks') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Upcoming Meetings --}}
    @if(\Illuminate\Support\Facades\Schema::hasTable('meetings'))
    @php
        $meetings = \Webkul\Meetings\Models\Meeting::whereBetween('meeting_date', [now(), now()->addDays(7)])
            ->where('status', 'confirmed')
            ->orderBy('meeting_date')
            ->limit(5)
            ->get();
    @endphp
    @if($meetings->isNotEmpty())
    <div class="section">
        <div class="section-heading">{{ __('dashboard.widgets.upcoming_meetings') }}</div>
        <div class="section-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.table.title') }}</th>
                        <th>{{ __('dashboard.table.date') }}</th>
                        <th>{{ __('dashboard.table.location') }}</th>
                        <th>{{ __('dashboard.table.type') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($meetings as $meeting)
                    <tr>
                        <td>{{ $meeting->title }}</td>
                        <td>{{ $meeting->meeting_date?->format('d M Y · h:i A') }}</td>
                        <td>{{ $meeting->location ?? '—' }}</td>
                        <td><span class="badge badge-info">{{ $meeting->type }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif

    {{-- Active Projects --}}
    @if(\Illuminate\Support\Facades\Schema::hasTable('projects_projects'))
    @php
        $projects = \Webkul\Project\Models\Project::where('is_active', true)->limit(8)->get();
    @endphp
    @if($projects->isNotEmpty())
    <div class="section">
        <div class="section-heading">{{ __('dashboard.widgets.active_projects') }}</div>
        <div class="section-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('dashboard.table.name') }}</th>
                        <th>{{ __('dashboard.table.due_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($projects as $project)
                    <tr>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->end_date?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ __('dashboard.pdf.footer', ['brand' => brand_name()]) }} &nbsp;·&nbsp; {{ now()->format('d M Y · h:i A') }}
    </div>

</body>
</html>
