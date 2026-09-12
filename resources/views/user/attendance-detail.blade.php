@extends('layouts.app')

@section('content')
<div class="attendance-container">
    
    <h1 class="page-title">申請一覧</h1>

    <div class="request-tabs">
        <a href="#" class="tab-item active">承認待ち</a>
        <a href="#" class="tab-item">承認済み</a>
    </div>

    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 15%;">状態</th>
                    <th style="width: 20%;">名前</th>
                    <th style="width: 20%;">対象日</th>
                    <th>申請理由</th>
                    <th style="width: 15%;">詳細</th>
                </tr>
            </thead>
            <tbody>
                {{-- 💡 もしここに間違った@foreachがあった場合は、このように正しく組むか、まずはループなしでテストします --}}
                <tr>
                    <td><span class="status-badge pending">承認待ち</span></td>
                    <td>{{ $user->name }}</td>
                    <td class="col-date">2026/09/07</td>
                    <td style="text-align: left; padding-left: 20px;">打刻漏れのため、出勤時間の修正を申請します。</td>
                    <td>
                        <a href="#" class="detail-link">詳細</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
body {
    background-color: #f7f7f7 !important;
}

.request-tabs {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e5e5e5;
    padding-bottom: 5px;
}

.tab-item {
    text-decoration: none;
    color: #666666;
    font-weight: bold;
    padding: 8px 16px;
    font-size: 15px;
}

.tab-item.active {
    color: #000000;
    border-bottom: 3px solid #000000;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.pending {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
</style>
@endsection