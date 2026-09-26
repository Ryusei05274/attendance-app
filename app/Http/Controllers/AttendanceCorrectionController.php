<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakLog;
use App\Models\AttendanceCorrection; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class AttendanceCorrectionController extends Controller
{
   /**
     * 💡 【タスク2】FN023: 勤怠一覧画面の表示処理
     */
    public function index(Request $request)
    {
        $user = Auth::user();

if ($user && $user->role === 'admin') {
            
            // データベースからすべての申請を取得（ユーザー情報も同時に読み込み）
            $allCorrections = AttendanceCorrection::with('user')->latest()->get();

            // Blade側がループして判定している「$applications」のデータ構造を組み立てます
            $applications = $allCorrections->map(function ($correction) {
                
                // statusの値（0や1）を、Bladeが40行目で判定している日本語のステータス文字に変換
                $approvalStatus = $correction->status === 0 ? '承認待ち' : '承認済み';

                return (object)[
                    'id'              => $correction->id,
                    'user'            => $correction->user,       // 氏名表示用
                    'date'            => $correction->date,       // 対象日時
                    'comment'         => $correction->comment,    // 申請理由
                    'created_at'      => $correction->created_at, // 申請日
                    'approval_status' => $approvalStatus,         // Bladeの「$application->approval_status」と結線
                ];
            });

            // 👑 管理者用のBladeに、Bladeが探している変数名「applications」で渡します
            return view('admin.admin-application-list', [
                'applications' => $applications,
            ]);
        }

        // 【FN024用：日付オブジェクトの用意】※タスク3で詳細を解説します
        $dateParam = $request->query('date');
        $date = $dateParam ? Carbon::parse($dateParam) : Carbon::now();
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 1. ログインユーザーの、指定された年月に合致する勤怠レコードをすべて取得
        // 💡一覧取得のパフォーマンスを高める「Eager Loading(with)」はタスク10でさらに強化します
        $attendanceRecords = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'asc')
            ->get();

        // 2. 【FN023】UIと同じ構成になるようにデータをループ処理で綺麗に整形
        $formattedAttendanceRecords = $attendanceRecords->map(function ($record) {
            
            // 休憩レコードから合計休憩時間を算出
            $totalBreakSeconds = 0;
            // 💡実態のリレーションメソッド名（breakLogs）に合わせてループを回します
            foreach ($record->breakLogs as $breakLog) {
                if ($breakLog->break_in && $breakLog->break_out) {
                    $totalBreakSeconds += Carbon::parse($breakLog->break_in)->diffInSeconds(Carbon::parse($breakLog->break_out));
                }
            }

            // 出退勤データが存在する場合のみ、時間を整形（ない場合は要件通り空文字にする）
            $clockIn = $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '';
            $clockOut = $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '';

            // 休憩時間のフォーマット（1分以上の場合のみ H:i 形式に、ないフィールドは空白）
            $totalBreakTime = '';
            if ($totalBreakSeconds > 0) {
                $hours = floor($totalBreakSeconds / 3600);
                $minutes = floor(($totalBreakSeconds / 60) % 60);
                $totalBreakTime = sprintf('%02d:%02d', $hours, $minutes);
            }

            // 稼働合計時間（合計時間 ＝ 退勤 － 出勤 － 休憩）
            $totalTime = '';
            if ($record->clock_in && $record->clock_out) {
                $workSeconds = Carbon::parse($record->clock_in)->diffInSeconds(Carbon::parse($record->clock_out));
                $actualWorkSeconds = $workSeconds - $totalBreakSeconds;
                if ($actualWorkSeconds > 0) {
                    $wHours = floor($actualWorkSeconds / 3600);
                    $wMinutes = floor(($actualWorkSeconds / 60) % 60);
                    $totalTime = sprintf('%02d:%02d', $wHours, $wMinutes);
                }
            }

            return [
                'id'               => $record->id,
                'date'             => Carbon::parse($record->date)->isoFormat('MM/DD(ddd)'), // UIに合わせた日付表示
                'clock_in'         => $clockIn,
                'clock_out'        => $clockOut,
                'total_break_time' => $totalBreakTime, // 休憩がないフィールドは空白
                'total_time'       => $totalTime,       // 勤怠情報がない、または打刻途中は空白
            ];
        });

        // 3. 整形したデータを勤怠一覧Blade（user-attendance-list.blade.php）に渡して表示
        return view('user.user-attendance-list', [
            'user'                       => $user,
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
            'date'                       => $date,
            'previousMonth'              => $previousMonth,
            'nextMonth'                  => $nextMonth,
        ]);
    }

      /**
     * 💡 【タスク4】FN025 / FN026 / FN027: 勤怠詳細画面の表示処理 (GET)
     */
    public function show($attendance_id)
    {
        $user = Auth::user();

        // 1. 指定されたIDの勤怠レコードを、ログイン中のユーザーのデータから厳格に取得 (FN025)
        $attendance = Attendance::where('id', $attendance_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 2. この勤怠に対して、現在「承認待ち(status=0)」の修正申請データがあるかデータベースを確認 (FN027)
        $correction = \App\Models\AttendanceCorrection::where('attendance_id', $attendance->id)
            ->where('user_id', $user->id)
            ->where('status', 0) // 0: 承認待ち
            ->first();

        // 3. 休憩データをすべて取得
        $breakLogs = \App\Models\BreakLog::where('attendance_id', $attendance->id)
            ->orderBy('break_in', 'asc')
            ->get();

        // 4. 【FN026】UIに表示するための日付（年、月日）をそれぞれ整形
        $attendanceDate = \Carbon\Carbon::parse($attendance->date);
        $yearFormatted  = $attendanceDate->isoFormat('YYYY年');
        $dateFormatted  = $attendanceDate->isoFormat('M月D日');
        $formattedDate  = $attendanceDate->isoFormat('YYYY年MM月DD日(ddd)');

        // 5. 【FN026】画面の連想配列の形にデータをすべて組み立てます
        // 💡承認待ち申請（$correction）がある場合は、申請中の「修正後の時間や備考」を表示させます (FN027)
        $data = [
            'id'          => $attendance->id,
            'date'        => $attendance->date,
            'year'        => $yearFormatted,  // 「2026年」など
            'date_md'     => $dateFormatted,  // 「9月21日」など
            'clock_in'    => $correction ? \Carbon\Carbon::parse($correction->new_clock_in)->format('H:i') : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : ''),
            'clock_out'   => $correction ? \Carbon\Carbon::parse($correction->new_clock_out)->format('H:i') : ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : ''),
            'comment'     => $correction ? $correction->comment : ($attendance->comment ?? ''),
            'application' => $correction ? $correction : null, // 💡これがBlade側での承認待ちモードの判定に使われます
            'breaks'      => $breakLogs,
        ];

        // 6. 組み立てた $data を一般ユーザー用の詳細Blade（user-detail.blade.php）に渡して表示
        return view('user.user-detail', [ 
            'user'          => $user,         // FN026項目1: 自分の氏名表示用
            'data'          => $data,         // 項目2〜4: 紐づく日付・出退勤・休憩データ
            'formattedDate' => $formattedDate,
        ]);
    }
}
