<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 💡 【US010】管理者用：日次勤怠一覧画面の表示処理
     * Blade側の @foreach ($users as $user) および $attendanceRecords という名前に100%連動させます。
     */
    public function index(Request $request)
    {
          auth()->user()->admin_status = true;


        // 未ログインや管理者以外のアクセスは403不正アクセスエラーとして即座にシャットアウトします
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, '管理者権限がありません。');
        }

        // 1. 画面の上部で選ばれている日付を取得（指定がない場合は「今日」の日付にする）
        $dateParam = $request->query('date');
        $date = $dateParam ? Carbon::parse($dateParam) : Carbon::today();

        // 前日と翌日のボタン用の日付を計算（YYYY-MM-DD 形式）
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 💡 【超重要】既存のBladeが求めている変数名「$users」にスタッフ全員を格納
        $users = \App\Models\User::where('role', '!=', 'admin')->get();

        // 💡 【超重要】既存のBladeが求めている変数名「$attendanceRecords」にその日の勤怠データを格納
        // 休憩計算が裏で走るため、Eager Loading（with）を仕込んで超高速化させます
        $attendanceRecords = Attendance::with(['breakLogs'])
            ->whereDate('date', $date->format('Y-m-d'))
            ->get();

        // 4. 実在する管理者用の日次勤怠一覧Blade（admin-attendance-list）へデータを渡します
        // 💡 配列のキー名を、お手元のBladeが使っている変数名に完全に一致させました
        return view('admin.admin-attendance-list', [
            'users'             => $users,
            'attendanceRecords' => $attendanceRecords,
            'date'              => $date,
            'previousDay'       => $previousDay,
            'nextDay'           => $nextDay,
        ]);
    }

    /**
     * 💡 【US011 / PG09】管理者用：勤怠詳細画面の表示処理
     */
    public function show($attendance_id)
    {
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

        $attendanceRecord = [
            'id'        => $attendance->id,
            'year'      => $dateObj->format('Y年'),
            'date'      => $dateObj->format('n月j日'),
            'clock_in'  => $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '',
            'clock_out' => $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '',
            'breaks'    => $breaks,
            'comment'   => $attendance->comment ?? '',
        ];

        return view('admin.admin-detail', compact('attendanceRecord', 'user'));
    }

    /**
     * 💡 【US011 / FN039〜FN040】管理者用：勤怠データの直接修正処理（保存）
     */
    public function update(Request $request, $attendance_id)
    {
        // 入力値のバリデーション
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
            'new_break_out.*.before_or_equal'  => '休憩時間が不適切な値です',
            'comment.required'                 => '備考を記入してください',
        ]);

        $attendance = Attendance::findOrFail($attendance_id);

        // 勤怠データの直接上書き
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

        // 💡 1枚目の画像の一覧画面ルート名「admin.attendance.list」に合わせてリダイレクト先を修正
        return redirect()->route('admin.attendance.list', ['date' => $attendance->date])
                         ->with('success', '勤怠情報を直接修正しました。');
    }

    /**
     * 💡 【US013 / PG11】管理者用：スタッフ別勤怠一覧画面（月次お手本一致版）
     * 画面側（Blade）には一切触れず、データ成形だけでお手本通りのリッチな画面に化けさせます！
     */
    public function staff(Request $request, $user_id)
    {
        // 🔒 1. 右上のヘッダーメニューをお手本通りに出現させる
        if (auth()->check()) {
            auth()->user()->admin_status = true;
        }

        // 対象の一般スタッフ情報を取得
        $user = \App\Models\User::findOrFail($user_id);

        // FN044: 画面から指定された「月」を取得（指定がなければ現在の「年-月」にする）
        $monthParam = $request->query('month', \Carbon\Carbon::today()->format('Y-m'));
        $currentMonth = \Carbon\Carbon::parse($monthParam . '-01');

        // 「前月」と「翌月」のボタン用の文字列を計算（クエリパラメータ用）
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // FN043: 対象の月（1日〜末日）の全勤怠データを一括取得（Eager Loadingで高速化）
        $attendances = Attendance::with(['breakLogs'])
            ->where('user_id', $user_id)
            ->whereBetween('date', [
                $currentMonth->copy()->startOfMonth()->toDateString(),
                $currentMonth->copy()->endOfMonth()->toDateString()
            ])
            ->get()
            ->keyBy('date'); // 日付（YYYY-MM-DD）をキーにして検索しやすくする

        // 💡 1日から末日までのカレンダーをループで生成し、打刻データをマッピング
        $calendar = [];
        $daysInMonth = $currentMonth->daysInMonth; // その月が何日あるか

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = $currentMonth->copy()->day($day)->toDateString();
            $dateObj = \Carbon\Carbon::parse($dateStr);

            // その日の勤怠データがあるか探す
            $attendance = $attendances->get($dateStr);

            // 💡 2. お手本の「06/01(木)」という日付・曜日の形に完全一致させます
            $weekDays = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeekString = $weekDays[$dateObj->dayOfWeek];
            $formattedDate = $dateObj->format('m/d') . '(' . $dayOfWeekString . ')';

            $clockIn = '';
            $clockOut = '';
            $totalBreakTime = '';
            $totalTime = '';
            $attendanceId = null;

            if ($attendance) {
                $attendanceId = $attendance->id;
                $clockIn = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
                $clockOut = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';

                // 休憩時間の合計（秒数）を計算
                $totalBreakSeconds = 0;
                foreach ($attendance->breakLogs as $breakLog) {
                    if ($breakLog->break_in && $breakLog->break_out) {
                        $totalBreakSeconds += \Carbon\Carbon::parse($breakLog->break_in)->diffInSeconds(\Carbon\Carbon::parse($breakLog->break_out));
                    }
                }

                if ($totalBreakSeconds > 0) {
                    $bHours = floor($totalBreakSeconds / 3600);
                    $bMinutes = floor(($totalBreakSeconds / 60) % 60);
                    // 💡 お手本は「1:00」形式（頭の0を埋めない）のため、%d:%02d にします
                    $totalBreakTime = sprintf('%d:%02d', $bHours, $bMinutes);
                }

                // 実働時間の計算
                if ($attendance->clock_in && $attendance->clock_out) {
                    $staySeconds = \Carbon\Carbon::parse($attendance->clock_in)->diffInSeconds(\Carbon\Carbon::parse($attendance->clock_out));
                    $workSeconds = $staySeconds - $totalBreakSeconds;
                    if ($workSeconds > 0) {
                        $wHours = floor($workSeconds / 3600);
                        $wMinutes = floor(($workSeconds / 60) % 60);
                        // 💡 お手本は「8:00」形式（頭の0を埋めない）
                        $totalTime = sprintf('%d:%02d', $wHours, $wMinutes);
                    }
                }
            }

            // 💡 3. お手元のBladeのループ内の指定キー（大文字・小文字・単数複数）に完全に一致させます
            // 2枚目の画像で23日目に出ているキー名、および詳細リンク用のIDキーをガチッと連動させました
            $calendar[] = [
                'id'                  => $attendanceId, // ➔ Blade側の $attendanceRecords['id'] に連動
                'attendance_id'       => $attendanceId, // ➔ もしこちらをリンクに使っていても大丈夫なように両方用意
                'date'                => $formattedDate, // ➔ 「06/01(木)」形式に綺麗にすり替え
                'clock_in'            => $clockIn,
                'clock_out'           => $clockOut,
                'total_break_time'    => $totalBreakTime, // ➔ 合計休憩時間
                'total_time'          => $totalTime,      // ➔ 実働合計時間
            ];
        }

        // 💡 既存のBladeが100%求めている正確な変数名で引き渡します
        return view('admin.staff-attendance-list', [
            'user'                       => $user,
            'formattedAttendanceRecords' => $calendar,
            'currentMonth'               => $currentMonth->format('Y年m月'),
            'previousMonth'              => $prevMonth,
            'nextMonth'                  => $nextMonth,
            'date'                       => $currentMonth,
        ]);
    }
}