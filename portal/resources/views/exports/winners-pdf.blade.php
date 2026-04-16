<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Winners Report — AEA 2026</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #1e293b; }
    .cover { text-align: center; padding: 60px 40px; background: #1a365d; color: white; margin-bottom: 30px; }
    .cover h1 { font-size: 22pt; font-weight: bold; margin-bottom: 8px; }
    .cover h2 { font-size: 14pt; opacity: 0.8; }
    .cover p  { font-size: 10pt; opacity: 0.6; margin-top: 10px; }
    .category-section { margin-bottom: 30px; page-break-inside: avoid; }
    .cat-header { padding: 10px 16px; color: white; border-radius: 8px; margin-bottom: 12px; }
    .cat-header h2 { font-size: 13pt; font-weight: bold; }
    .winner-card { border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
    .winner-rank { display: inline-block; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold; font-size: 12pt; margin-right: 8px; }
    .rank-1 { background: #d4a843; color: white; }
    .rank-2 { background: #94a3b8; color: white; }
    .rank-3 { background: #d97706; color: white; }
    .winner-info { padding: 10px 14px; }
    .winner-name { font-size: 11pt; font-weight: bold; color: #1a365d; }
    .winner-meta { font-size: 8pt; color: #64748b; margin-top: 2px; }
    .winner-score { font-size: 16pt; font-weight: bold; color: #2563eb; float: right; }
    .footer { text-align: center; color: #94a3b8; font-size: 8pt; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="cover">
    <h1>Alumni Excellence Awards 2026</h1>
    <h2>COMSATS University Islamabad</h2>
    <p>Winners Report — Registrar Secretariat · {{ now()->format('d M Y') }}</p>
</div>

@foreach($winners as $catData)
<div class="category-section">
    <div class="cat-header" style="background:{{ $catData['category']->color }}">
        <h2>{{ $catData['category']->name }}</h2>
    </div>

    @if($catData['top3']->isEmpty())
    <p style="color:#94a3b8; font-size:9pt; padding: 0 4px;">No completed reviews for this category yet.</p>
    @else
    @foreach($catData['top3'] as $i => $winner)
    <div class="winner-card">
        <div class="winner-info">
            <div style="display:flex; align-items:center; justify-content:space-between">
                <div>
                    <span class="winner-rank rank-{{ $i+1 }}">{{ $i+1 }}</span>
                    <span class="winner-name">{{ $winner['student']->name }}</span>
                    <p class="winner-meta" style="margin-left:36px">
                        #{{ $winner['student']->submission_id }} ·
                        {{ $winner['student']->department ?? '' }} ·
                        {{ $winner['student']->campus ?? '' }}
                    </p>
                </div>
                <div style="text-align:right">
                    <span class="winner-score">{{ $winner['avg'] }}</span>
                    <p style="font-size:7.5pt; color:#64748b; margin-top:2px">/100 avg reviewer</p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>
@endforeach

<div class="footer">
    COMSATS University Islamabad — Registrar Secretariat · CUI-Reg/Notif-06/26/14 · Confidential
</div>

</body>
</html>
