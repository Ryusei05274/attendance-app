@extends('layouts.app')

@section('content')

<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<div class="attendance-container">
    {{-- 画面タイトル --}}
    <h1 class="page-title">勤怠一覧</h1>

    {{-- 月選択ナビゲーション (FN024) --}}
    <div class="month-selector">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="month-nav-btn">← 前月</a>
        
        <div class="current-month-box">
            <span class="calendar-icon">📅</span>
            <span class="current-month-text">{{ $currentMonth }}</span>
        </div>
        
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="month-nav-btn">翌月 →</a>
    </div>

    {{-- 勤怠一覧テーブル (FN023 / FN025) --}}
    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($formattedAttendanceRecords as $attendanceRecord)
                <tr>
                    {{-- 日付 --}}
                    <td class="col-date">{{ $attendanceRecord['date'] }}</td>
                    
                    {{-- 出勤・退勤・休憩・合計（空の場合は空白になります） --}}
                    <td class="col-time">{{ $attendanceRecord['clock_in'] ?? '' }}</td>
                    <td class="col-time">{{ $attendanceRecord['clock_out'] ?? '' }}</td>
                    <td class="col-time">{{ $attendanceRecord['total_break_time'] ?? '' }}</td>
                    <td class="col-time">{{ $attendanceRecord['total_time'] ?? '' }}</td>
                    
                    {{-- 詳細リンク --}}
                    <td class="col-detail">
                        @if (!empty($attendanceRecord['id']))
            <a href="{{ url('/attendance/detail/' . $attendanceRecord['id']) }}" class="detail-link">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

