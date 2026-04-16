<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Student Review — {{ $student->name }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1e293b; }
    .header { background: #1a365d; color: white; padding: 16px 20px; margin-bottom: 16px; }
    .header h1 { font-size: 14pt; font-weight: bold; }
    .header p  { font-size: 9pt; opacity: 0.7; margin-top: 3px; }
    .section { margin-bottom: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 6px; }
    .section h2 { font-size: 10pt; font-weight: bold; color: #1a365d; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; margin-bottom: 8px; }
    .info-grid { display: table; width: 100%; }
    .info-row  { display: table-row; }
    .info-label { display: table-cell; width: 30%; color: #64748b; padding: 2px 4px; font-size: 8pt; }
    .info-value { display: table-cell; font-weight: bold; padding: 2px 4px; font-size: 8pt; }
    table.scores { width: 100%; border-collapse: collapse; font-size: 8pt; }
    table.scores th { background: #f8fafc; padding: 5px 8px; text-align: left; border: 1px solid #e2e8f0; font-size: 7.5pt; }
    table.scores td { padding: 4px 8px; border: 1px solid #e2e8f0; }
    .dim-header { background: #1a365d; color: white; font-weight: bold; }
    .total-row { background: #f1f5f9; font-weight: bold; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: 7pt; font-weight: bold; }
    .footer { text-align: center; color: #94a3b8; font-size: 7pt; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>Alumni Excellence Award 2026 — Review Sheet</h1>
    <p>COMSATS University Islamabad — Registrar Secretariat</p>
</div>

{{-- Student profile --}}
<div class="section">
    <h2>Student Profile</h2>
    <div class="info-grid">
        <div class="info-row"><span class="info-label">Name</span><span class="info-value">{{ $student->name }}</span></div>
        <div class="info-row"><span class="info-label">Submission ID</span><span class="info-value">#{{ $student->submission_id }}</span></div>
        <div class="info-row"><span class="info-label">Category</span><span class="info-value">{{ $student->category->name }}</span></div>
        <div class="info-row"><span class="info-label">Department</span><span class="info-value">{{ $student->department ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Campus</span><span class="info-value">{{ $student->campus ?? '—' }}</span></div>
        <div class="info-row"><span class="info-label">Reg No.</span><span class="info-value">{{ $student->batch ?? '—' }}</span></div>
    </div>
</div>

{{-- Score table --}}
<div class="section">
    <h2>Scores</h2>
    <table class="scores">
        <thead>
            <tr>
                <th style="width:35%">Indicator</th>
                <th style="width:8%" class="text-center">Max</th>
                <th style="width:10%" class="text-center">Self</th>
                @foreach($student->reviews as $review)
                <th style="width:15%" class="text-center">{{ $review->reviewer->name }}<br>({{ ucfirst($review->status) }})</th>
                @endforeach
                @if($student->reviews->where('status','completed')->count() > 1)
                <th style="width:12%" class="text-center">Avg</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($rubricConfig as $dimKey => $dim)
            <tr class="dim-header">
                <td colspan="{{ 3 + $student->reviews->count() + ($student->reviews->where('status','completed')->count() > 1 ? 1 : 0) }}">
                    {{ $dim['label'] }} ({{ $dim['weight'] }}% — max {{ $dim['total'] }} pts)
                </td>
            </tr>
            @foreach($dim['items'] as $item)
            @php
                $ri = $rubricItems->first(fn($r) => $r->sub_indicator_key === $item['key']);
                $selfScore = $ri ? ($selfScoreMap->get($ri->id)?->score ?? 0) : 0;
            @endphp
            <tr>
                <td>{{ $item['label'] }}</td>
                <td style="text-align:center">{{ $item['max'] }}</td>
                <td style="text-align:center; font-weight:bold">{{ $selfScore }}</td>
                @foreach($student->reviews as $review)
                @php $rs = $ri ? $review->scores->firstWhere('rubric_item_id', $ri->id) : null; @endphp
                <td style="text-align:center">{{ $rs ? $rs->score : '—' }}</td>
                @endforeach
                @if($student->reviews->where('status','completed')->count() > 1)
                @php
                    $completedRevs = $student->reviews->where('status','completed');
                    $avg = $ri ? $completedRevs->map(fn($r) => $r->scores->firstWhere('rubric_item_id', $ri->id)?->score ?? 0)->avg() : 0;
                @endphp
                <td style="text-align:center; color:#2563eb">{{ number_format($avg, 1) }}</td>
                @endif
            </tr>
            @endforeach
            @endforeach
            <tr class="total-row">
                <td colspan="2">GRAND TOTAL</td>
                <td style="text-align:center">{{ number_format($selfScoreMap->sum('score'), 1) }}</td>
                @foreach($student->reviews as $review)
                <td style="text-align:center">{{ $review->status === 'completed' ? number_format($review->totalScore(), 1) : '—' }}</td>
                @endforeach
                @if($student->reviews->where('status','completed')->count() > 1)
                @php $avgTotal = $student->reviews->where('status','completed')->map(fn($r) => $r->totalScore())->avg(); @endphp
                <td style="text-align:center; color:#2563eb">{{ number_format($avgTotal, 1) }}</td>
                @endif
            </tr>
        </tbody>
    </table>
</div>

{{-- Reviewer remarks --}}
@foreach($student->reviews->where('status','completed') as $review)
<div class="section">
    <h2>Remarks: {{ $review->reviewer->name }}</h2>
    <p style="font-size:8.5pt; line-height:1.5; color:#374151">{{ $review->overall_remarks ?? 'No remarks provided.' }}</p>
</div>
@endforeach

<div class="footer">
    Generated {{ now()->format('d M Y H:i') }} · CUI Alumni Excellence Awards 2026 · Confidential
</div>

</body>
</html>
