@extends('layouts.app') {{-- ※環境に合わせて親レイアウト名は変更してください --}}

@section('content')
<div class="attendance-container">
    
    <!-- 画面タイトル（左側の黒い縦線デザイン） -->
    <h1 class="page-title">申請一覧</h1>

    <!-- タブ切り替え（「承認待ち」や「承認済み」） -->
    <div class="request-tabs">
        <a href="#" class="tab-item active">承認待ち</a>
        <a href="#" class="tab-item">承認済み</a>
    </div>

    <!-- テーブル（見本のカード型・等間隔デザイン） -->
    <div class="table-wrapper">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th style="width: 15%;">状態</th>
                    <th style="width: 15%;">名前</th>
                    <th style="width: 20%;">対象日時</th>
                    <th style="width: 20%;">申請理由</th>
                    <th style="width: 20%;">申請日時</th>
                    <th style="width: 10%;">詳細</th>
                </tr>
            </thead>
            <tbody>
                <!-- 💡 データの表示（見本用ダミーデータ） -->
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $user->name }}</td>
                    <td>2023/06/01</td>
                    <td>遅延のため</td>
                    <td>2023/06/02</td>
                    <td>
                        <a href="#" class="detail-link">詳細</a>
                    </td>
                </tr>
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $user->name }}</td>
                    <td>2023/06/01</td>
                    <td>遅延のため</td>
                    <td>2023/06/02</td>
                    <td>
                        <a href="#" class="detail-link">詳細</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 申請一覧画面用の追加装飾スタイル -->
<style>
/* 画面全体の背景色を薄いグレーに固定 */
body {
    background-color: #f7f7f7 !important;
    font-family: 'Helvetica Neue', Arial, sans-serif;
}

/* メインコンテンツを中央寄せにして適切な横幅に */
.attendance-container {
    max-width: 950px;
    margin: 50px auto;
    padding: 0 20px;
}

/* 画面タイトル（左側の細い黒色の縦線マーク） */
.page-title {
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 40px;
    color: #000000;
}
.page-title::before {
    content: "";
    display: inline-block;
    width: 4px;
    height: 24px;
    background-color: #000000;
}

/* 見本の細いタブメニュー */
.request-tabs {
    display: flex;
    gap: 50px;
    margin-bottom: 25px;
    border-bottom: 2px solid #e5e5e5;
    padding-left: 20px;
}

.tab-item {
    text-decoration: none;
    color: #a0a0a0;
    font-weight: bold;
    padding-bottom: 12px;
    font-size: 15px;
    position: relative;
    bottom: -2px;
}

.tab-item.active {
    color: #000000;
    border-bottom: 3px solid #000000;
}

/* テーブルを乗せる白いカード（ドロップシャドウをほぼ無くして平坦に） */
.table-wrapper {
    background-color: #ffffff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    padding: 10px 0;
}

/* テーブルスタイル（文字を細めに、余白を均等に） */
.attendance-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

.attendance-table th,
.attendance-table td {
    padding: 16px 24px;
    font-size: 14px;
    color: #333333;
}

/* 薄いグレーのヘッダーテキスト */
.attendance-table th {
    color: #b0b0b0;
    font-weight: bold;
    border-bottom: 1px solid #f0f0f0;
}

/* 各行の下の非常に薄い区切り線 */
.attendance-table tbody tr {
    border-bottom: 1px solid #fcfcfc;
}
.attendance-table tbody tr:last-child {
    border-bottom: none;
}

/* 詳細リンク（下線なしの細い黒文字） */
.detail-link {
    color: #000000;
    font-weight: bold;
    text-decoration: none;
}
.detail-link:hover {
    text-decoration: underline;
}
</style>
@endsection