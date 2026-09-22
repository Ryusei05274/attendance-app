<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakLog; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 勤怠画面の表示 (FN019 / FN020)
     */
    public function index()
    {
        $user = Auth::user();
        
        // 画面に渡す日付と時刻のフォーマット
        $formattedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $formattedTime = Carbon::now()->format('H:i');

        // 今日の出勤レコードがあるか確認
        $today = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // データベースの状態から最新のステータスを判定
        $status = '勤務外';
        if ($attendance) {
            if ($attendance->clock_out) {
                $status = '退勤済';
            } else {
                // 未完了の休憩（break_outが空）があるか確認
                $isBreaking = BreakLog::where('attendance_id', $attendance->id)
                    ->whereNull('break_out')
                    ->exists();

                $status = $isBreaking ? '休憩中' : '出勤中';
            }
        }

        // 画面のBladeが `$user->attendance_status` を見ているため、判定したステータスをセットします
        $user->attendance_status = $status;

        return view('user.attendance-register', [
            'user' => $user,
            'formattedDate' => $formattedDate,
            'formattedTime' => $formattedTime, 
        ]);
    }

    /**
     * 各ボタン押下時の打刻・ステータス更新処理
     */
    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');
        
        $action = $request->input('action');

        // 今日の出勤レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        switch ($action) {
            case 'clock_in': // 出勤ボタン
                if (!$attendance) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'clock_in' => $nowTime, 
                    ]);
                }
                break;

            case 'break_in': // 休憩入ボタン
                if ($attendance && !$attendance->clock_out) { 
                    BreakLog::create([
                        'attendance_id' => $attendance->id,
                        'break_in' => $nowTime,
                    ]);
                }
                break;

            case 'break_out': // 休憩戻ボタン
                if ($attendance) {
                    $currentBreak = BreakLog::where('attendance_id', $attendance->id)
                        ->whereNull('break_out')
                        ->first();
                    if ($currentBreak) {
                        $currentBreak->update(['break_out' => $nowTime]);
                    }
                }
                break;

            case 'clock_out': // 退勤ボタン
                if ($attendance && !$attendance->clock_out) { 
                    $attendance->update(['clock_out' => $nowTime]); 
                    // 退勤時はメッセージを送る
                    return redirect()->route('attendance.register')->with('status_message', 'お疲れ様でした。');
                }
                break;
        }

        // 処理が終わったら元の打刻画面に戻る
        return redirect()->route('attendance.register');
    }

public function list(Request $request)
    {
        $user = Auth::user();

        // 1. 【修正】Bladeのリンクに合わせて 'month' から 'date' に変更
        $monthInput = $request->input('date', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($monthInput . '-01'); // 【修正】Bladeが使う変数名 $date に合わせる

        // 2. 【修正】Bladeが使う変数名 $previousMonth と $nextMonth に合わせる
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 指定された月の出勤レコードをすべて取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'asc')
            ->get();

        // 画面表示用にデータを計算・加工する
        $attendanceList = $attendances->map(function ($attendance) {
            $punchIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $punchOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : ''; 

            $totalBreakSeconds = 0;
            $workTimeFormatted = '';
            $breakTimeFormatted = '';

            if ($attendance->clock_in && $attendance->clock_out) { 
                $inTime = Carbon::parse($attendance->clock_in);
                $outTime = Carbon::parse($attendance->clock_out);

                // 拘束時間（退勤 - 出勤）の総秒数
                $totalDiffSeconds = $outTime->diffInSeconds($inTime);

                // 紐づく休憩時間をループして合計秒数を算出
                $breaks = BreakLog::where('attendance_id', $attendance->id)->get();
                foreach ($breaks as $break) {
                    if ($break->break_in && $break->break_out) {
                        $bIn = Carbon::parse($break->break_in);
                        $bOut = Carbon::parse($break->break_out);
                        $totalBreakSeconds += $bOut->diffInSeconds($bIn);
                    }
                }

                // 休憩の合計秒数を「H:i」に変換
                $breakHours = floor($totalBreakSeconds / 3600);
                $breakMinutes = floor(($totalBreakSeconds % 3600) / 60);
                $breakTimeFormatted = sprintf('%01d:%02d', $breakHours, $breakMinutes);

                // 実働時間の計算（拘束時間 - 休憩時間）
                $workSeconds = $totalDiffSeconds - $totalBreakSeconds;
                if ($workSeconds < 0) $workSeconds = 0;

                $workHours = floor($workSeconds / 3600);
                $workMinutes = floor(($workSeconds % 3600) / 60);
                $workTimeFormatted = sprintf('%01d:%02d', $workHours, $workMinutes);
            }

            // 日付を「06/01(木)」のような形に整形
            $formattedDate = Carbon::parse($attendance->date)->isoFormat('MM/DD(ddd)');

            return [
                'id' => $attendance->id, 
                'date' => $formattedDate,
                'clock_in' => $punchIn ?: null,     
                'clock_out' => $punchOut ?: null,   
                'total_break_time' => $breakTimeFormatted ?: null, 
                'total_time' => $workTimeFormatted ?: null,        
            ];
        });

        // 3. 【修正】Bladeが必要としている名前でデータを返却する
        return view('user.user-attendance-list', [
            'previousMonth' => $previousMonth,
            'date' => $date,
            'nextMonth' => $nextMonth,
            'formattedAttendanceRecords' => $attendanceList,
        ]);
    }
 /**
     * 💡 一般ユーザー用：勤怠詳細画面の表示処理 (GET)
     */
    public function showDetail($id)
    {
        $user = Auth::user();

        // 1. 指定されたIDの勤怠レコードを取得
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // 2. 【修正】データベースの実態であるモデル「AttemdamceCorrectionRequest」を使って、承認待ちがあるか探します
        $correction = \App\Models\AttendanceCorrection::where('attendance_id', $id)
            ->where('user_id', $user->id)
            ->where('status', 0) // 0: 承認待ち
            ->first();

        // 3. 日付を「〇〇年」と「〇月〇日」に正確に分割します
        $attendanceDate = Carbon::parse($attendance->date);
        $yearFormatted  = $attendanceDate->isoFormat('YYYY年');
        $dateFormatted  = $attendanceDate->isoFormat('M月D日');

        // 4. 休憩データをすべて取得
        $breakLogs = BreakLog::where('attendance_id', $attendance->id)
            ->orderBy('break_in', 'asc')
            ->get();

        // 5. 画面に渡すデータを組み立てます
        $data = [
            'id'          => $attendance->id,
            'date'        => $attendance->date,
            'year'        => $yearFormatted,
            'date_md'     => $dateFormatted,
            'clock_in'    => $correction ? Carbon::parse($correction->new_clock_in)->format('H:i') : ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : ''),
            'clock_out'   => $correction ? Carbon::parse($correction->new_clock_out)->format('H:i') : ($attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : ''),
            'comment'     => $correction ? $correction->comment : ($attendance->comment ?? ''),
            'application' => $correction ? $correction : null,
            'breaks'      => $breakLogs,
        ];

        $formattedDate = $attendanceDate->isoFormat('YYYY年MM月DD日(ddd)');

        return view('user.user-detail', [ 
            'user'          => $user,
            'data'          => $data, 
            'formattedDate' => $formattedDate,
        ]);
    }

    /**
     * 💡 「修正」ボタン押下時の、修正申請データの保存処理 (POST)
     */
    public function updateDetailRequest(Request $request, $id)
    {
        $request->validate([
            'new_clock_in'  => 'required|date_format:H:i',
            'new_clock_out' => 'required|date_format:H:i',
            'comment'       => 'required|string',
        ], [
            'new_clock_in.required'  => '出勤時間は必須項目です。',
            'new_clock_out.required' => '退勤時間は必須項目です。',
            'comment.required'       => '備考を記入してください',
        ]);

        $user = Auth::user();

        \App\Models\AttendanceCorrection::create([
            'attendance_id' => $id,
            'user_id'       => $user->id,
            'original_date' => $request->input('new_date'),
            'new_clock_in'  => $request->input('new_clock_in'),
            'new_clock_out' => $request->input('new_clock_out'),
            'comment'       => $request->input('comment'),
            'status'        => 0, // 0を入れることで「承認待ち」状態にします
        ]);

        return redirect('/attendance/' . $id)->with('status_message', '修正申請を送信しました');
    }
} 
