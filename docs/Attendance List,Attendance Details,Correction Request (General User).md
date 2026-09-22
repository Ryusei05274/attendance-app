```mermaid
mermaidflowchart TD
    task1[1. ルーティング定義] --> task2[2. 勤怠一覧コントローラー]
    task2 --> task3[3. 前月・翌月切り替え]
    task3 --> task4[4. 詳細データ組み立て]
    task4 --> task5[5. 修正申請FormRequest]
    task5 --> task6[6. 指定文言エラー整備]
    task6 --> task7[7. 修正申請の保存処理]
    task7 --> task8[8. 承認待ちの編集不可制御]
    task8 --> task9[9. 申請一覧の出し分け]
    task9 --> task10[10. 一覧のEager Loading]

```