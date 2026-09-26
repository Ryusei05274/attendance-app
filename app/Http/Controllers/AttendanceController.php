<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakLog; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController 
{
    /**
     * 勤怠画面の表示 (FN019 / FN020)
     */
    public function create()
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
        $nowTime = Carbon::now()->format('H:i:s'); // FN018: 現在日時の取得
        
        $action = $request->input('action');

        // 今日の出勤レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        // 現在の「未完了の休憩」があるか確認
        $isBreaking = false;
        if ($attendance) {
            $isBreaking = BreakLog::where('attendance_id', $attendance->id)
                ->whereNull('break_out')
                ->exists();
        }

        switch ($action) {
            case 'clock_in': // 出勤ボタン (FN020)
                // 【ガード】すでに今日出勤している場合は何もしない（FN020-2: 1日1回だけ）
                if ($attendance) {
                    return redirect()->route('attendance.register')->with('error', '本日はすでに出勤しています。');
                }

                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'clock_in' => $nowTime, // FN020-4: 出勤時刻の正確な記録
                ]);
                break;

            case 'break_in': // 休憩入ボタン (FN021)
                // 【ガード】出勤レコードがあり、かつ退勤しておらず、さらに「休憩中」ではない時だけ（FN021-1: 出勤中のみ）
                if (!$attendance || $attendance->clock_out || $isBreaking) {
                    return redirect()->route('attendance.register')->with('error', '不正な操作です。');
                }

                BreakLog::create([
                    'attendance_id' => $attendance->id,
                    'break_in' => $nowTime,
                ]);
                break;

            case 'break_out': // 休憩戻ボタン (FN021)
                // 【ガード】「休憩中」の時だけ処理（FN021-4）
                if (!$attendance || !$isBreaking) {
                    return redirect()->route('attendance.register')->with('error', '不正な操作です。');
                }

                $currentBreak = BreakLog::where('attendance_id', $attendance->id)
                    ->whereNull('break_out')
                    ->first();
                if ($currentBreak) {
                    $currentBreak->update(['break_out' => $nowTime]); // FN021-5-a: レコードを確定
                }
                break;

            case 'clock_out': // 退勤ボタン (FN022)
                // 【ガード】出勤レコードがあり、まだ退勤しておらず、かつ「休憩中ではない」時だけ（FN022-1: 出勤中のみ）
                if (!$attendance || $attendance->clock_out || $isBreaking) {
                    return redirect()->route('attendance.register')->with('error', '不正な操作です。');
                }

                $attendance->update(['clock_out' => $nowTime]); // FN022-5: 退勤時刻の記録
                
                // 退勤時はメッセージを送る (FN022-3) 
                return redirect()->route('attendance.register')->with('status_message', 'お疲れ様でした。');
        }

        // 処理が終わったら元の打刻画面に戻る
        return redirect()->route('attendance.register');
    }

public function index(Request $request)
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

      /**💡【共通URL】勤怠詳細画面の表示分岐（一般ユーザー or 管理者）*/
      
    public function show($attendance_id)
    {
        // 🔒 一般側のコントローラーを通る際にも、ログイン中ユーザーが管理者であればメモリ上で admin_status を true に設定
        if (auth()->check() && auth()->user()->role === 'admin') {
            auth()->user()->admin_status = true;
        }

        // 👑 1. 管理者用の勤怠詳細取得（Eager LoadingでN+1問題を回避し超高速化！）
        $attendance = Attendance::with(['user', 'breakLogs'])->findOrFail($attendance_id);
        $user = $attendance->user;
        $dateObj = \Carbon\Carbon::parse($attendance->date);

        // 休憩ログをループして配列に格納
        $breaks = [];
        foreach ($attendance->breakLogs as $break) {
            $breaks[] = [
                'break_in'  => $break->break_in ? \Carbon\Carbon::parse($break->break_in)->format('H:i') : '',
                'break_out' => $break->break_out ? \Carbon\Carbon::parse($break->break_out)->format('H:i') : '',
            ];
        }

        // 👑 2. お手元のBladeファイルが100%求めているデータ構造（$attendanceRecord）をここで完璧に作成！
        $attendanceRecord = [
            'id'        => $attendance->id,
            'year'      => $dateObj->format('Y年'),
            'date'      => $dateObj->format('n月j日'),
            'clock_in'  => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '',
            'clock_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '',
            'breaks'    => $breaks,
            'comment'   => $attendance->comment ?? '',
        ];

        // 👑 3. 権限による頑丈な分岐：ログイン中ユーザーが「管理者」の場合
        if (
            (auth()->check() && auth()->user()->role === 'admin') || 
            (auth()->check() && auth()->user()->admin_status) ||
            request()->is('admin/*') || 
            request()->routeIs('admin.*')
        ) {
            // 管理者用のBlade（admin-detail）を呼び出す（エラーが起きていた207行目です）
            return view('admin.admin-detail', compact('attendanceRecord', 'user'));
        }

        // 👤 一般ユーザーの場合（※もしuser側にファイルがまだ無ければエラーになるため、一旦同じくadmin用を開くか、実際のファイル名にしてください）
        return view('admin.admin-detail', compact('attendanceRecord', 'user'));
    }

    /**
     * 💡【共通URL】勤怠データの保存・修正処理（バリデーションとエラーの共通化）
     */
    public function update(Request $request, $attendance_id)
    {
        // 🛠【バリデーションとエラーメッセージの共通化】
        $request->validate([
            'new_clock_in'       => 'required|date_format:H:i',
            'new_clock_out'      => 'required|date_format:H:i|after:new_clock_in', 
            'new_break_in.*'     => 'nullable|date_format:H:i|after_or_equal:new_clock_in|before:new_clock_out', 
            'new_break_out.*'    => 'nullable|date_format:H:i|after:new_break_in.*|before_or_equal:new_clock_out', 
            'comment'            => 'required|string', 
        ], [
            'new_clock_out.after'              => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.after_or_equal'    => '休憩時間が不適切な値です',
            'new_break_in.*.before'            => '休憩時間が不適切な値です',
            'new_break_out.*.after'            => '休憩時間が不適切な値です',
            'new_break_out.*.before_or_equal'  => '休憩時間もしくは退勤時間が不適切な値です',
            'comment.required'                 => '備考を記入してください',
        ]);

        $attendance = Attendance::findOrFail($attendance_id);

        // 👑 権限による分岐：管理者の場合は、申請を挟まずに「直接DBを上書き修正」
        if (auth()->user()->role === 'admin' || auth()->user()->admin_status) {
            
            $attendance->update([
                'clock_in'  => $request->new_clock_in,
                'clock_out' => $request->new_clock_out,
                'comment'   => $request->comment,
            ]);

            // 休憩ログを一度リセットして再登録
            $attendance->breakLogs()->delete(); 
            if ($request->has('new_break_in')) {
                foreach ($request->new_break_in as $index => $breakIn) {
                    $breakOut = $request->new_break_out[$index] ?? null;
                    if ($breakIn && $breakOut) {
                        $attendance->breakLogs()->create([
                            'break_in'  => $breakIn,
                            'break_out' => $breakOut,
                        ]);
                    }
                }
            }

            // 管理者用の一覧画面へリダイレクト
            return redirect()->route('admin.attendance.list', ['date' => $attendance->date])
                             ->with('success', '勤怠情報を直接修正しました。');
        }

        // 👤 一般ユーザーの場合の保存（申請）処理はここに記述します
        return redirect()->route('attendance.list')->with('success', '修正申請を送信しました。');
    }
}