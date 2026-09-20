COACHTECH 勤怠管理アプリ (attendance-app)
本プロジェクトは、

📄 概要
プロジェクトの目的
お問い合わせの受付から、管理者の検索・対応状況の管理、データの安全なエクスポートまでを一気通貫で効率化することを目的としています。また、APIを公開することで外部システムとのシームレスなデータ連携を実現しています。

実装した機能の概要

📊 ER図
本プロジェクトのデータベース構造（エンティティ関係）は以下の通りです。
erDiagram
    users ||--o{ attendances : "1人のユーザーは複数の勤怠データを持つ"
    users ||--o{ attendance_corrections : "1人のユーザーは複数の修正申請を出す"
    attendances ||--o{ break_logs : "1日の勤怠に対し複数の休憩が発生する"
    attendances ||--o{ attendance_corrections : "1回の勤怠に対し複数の修正申請が紐づく"

    users {
        bigint_unsigned id PK
        string name
        string email
        string password
    }

    attendances {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "users.id"
        date date
        time clock_in
        time clock_out
    }

    break_logs {
        bigint_unsigned id PK
        bigint_unsigned attendance_id FK "attendances.id"
        time break_in
        time break_out
    }

    attendance_corrections {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "users.id"
        bigint_unsigned attendance_id FK "attendances.id"
        tinyint status "0:承認待ち, 1:承認済み"
        time original_start_time
        time original_end_time
        time new_clock_in
        time new_clock_out
        text comment
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }


🛠️ 使用技術
プロジェクトの開発および実行に使用した技術スタック一覧です。

フレームワーク : Laravel 10 (PHP 8.5)
データベース　 : MySQL 8.0
Webサーバー　 : Nginx
コンテナ環境 : Docker / Laravel Sail
🌐 APIエンドポイント一覧
実装した公開API（Api/V1）の一覧です。すべてのリクエストに対して適切なバリデーションとレスポンスコードを定義しています。

| メソッド | パス | 概要 | 主なステータスコード | | GET | /api/v1/contacts | お問い合わせ一覧の取得（検索・ページネーション機能付き） | 200 (正常), 422 (バリデーションエラー) |

| GET | /api/v1/contacts/{id} | 特定のお問い合わせ詳細情報の取得 | 200 (正常), 404 (データなし) |

| POST | /api/v1/contacts | 新規お問い合わせの作成・保存 | 201 (作成完了), 422 (バリデーションエラー) |

| PUT | /api/v1/contacts/{id} | 登録済みお問い合わせの更新処理 | 200 (更新完了), 404 (データなし), 422 (バリデーションエラー) |

| DELETE | /api/v1/contacts/{id} | 特定のお問い合わせレコードの削除 | 204 (削除完了), 404 (データなし) |

🛠️ 環境構築手順 (Setup)
1. Laravelプロジェクトの作成 (Laravel 10.x)
最新版のLaravelがインストールされるのを防ぐため、curl -s "https://laravel.build..." は使用しません。 以下のDockerコマンドを実行し、Laravel 10.x を明示的に指定してプロジェクトを作成します。

docker run --rm \
    -u "\((id -u):\)(id -g)" \
    -v "\$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer create-project laravel/laravel:"^10.0" contact-form-app
2. Laravel Sailのインストール
プロジェクト作成後、contact-form-app ディレクトリに移動し、Laravel Sailをインストール・設定します。

プロジェクトディレクトリに移動
cd contact-form-app
Laravel Sailをインストール
docker run --rm \
    -u "\((id -u):\)(id -g)" \
    -v "\$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer require laravel/sail --dev
Sailの設定ファイルをパブリッシュ (MySQLを選択)
docker run --rm \
    -u "\((id -u):\)(id -g)" \
    -v "\$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    php artisan sail:install --with=mysql

#### Sailの設定ファイルをパブリッシュ (MySQLを選択)
```bash
docker run --rm \
    -u "\((id -u):\)(id -g)" \
    -v "\$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    php artisan sail:install --with=mysql
    
    ### 3. .env ファイルの設定
データベース（DB）の接続情報を、ローカルのSail環境に合わせて設定します。
```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=example_app
DB_USERNAME=sail
DB_PASSWORD=password
※ DB_HOST は 127.0.0.1 ではなく、コンテナ名である mysql を指定してください。

4. フロントエンドのセットアップ (Vite & Tailwind CSS)
本プロジェクトでは、フロントエンドのスタイリングに Tailwind CSS を使用します。

① NPM依存パッケージのインストール
⚠️ 重要: sail npm install を実行する前に、必ずSailコンテナが起動（sail up -d）していることを確認してください。

sail npm install
② Tailwind CSS のインストール
Tailwind CSS（バージョン3.4.0系）と関連パッケージ、および Alpine.js をインストールします。

sail npm install -D tailwindcss@^3.4.0 postcss autoprefixer
sail npm install alpinejs
③ 設定ファイルの生成
sail npx tailwindcss init -p
④ Tailwind CSS のテンプレートパス設定
生成された tailwind.config.js を開き、content の項目を以下のように設定します。

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
⑤ 提供リポジトリの resources ディレクトリと入れ替え
コーディング済みの画面テンプレート（Bladeファイルなど）を反映させるため、指定のリポジトリから resources ディレクトリを取得して入れ替えます。

まず、一時的な場所にリポジトリをクローンします。

git clone https://github.com/coachtech-prepared-file/Preparedblade-ConfirmationTest-ContactForm.git
【入れ替え手順】

自分のプロジェクトフォルダを エクスプローラー（Windows）で開きます。
現在のプロジェクト内にある resources フォルダを削除します。
先ほどクローンしたリポジトリ内にある resources フォルダを、そのままプロジェクト直下にコピー＆ペーストして配置します。
(※コマンド操作に慣れている場合は、rm -rf resources のあとに cp -r でコピーしても構いません)

⑥ Vite開発サーバーの起動
フロントエンドのリアルタイムビルドを開始します。

sail npm run dev
💡 注意: sail npm run dev は実行したまま（ターミナルを閉じずに起動した状態）にしておく必要があります。画面開発を行う際はずっと立ち上げておいてください。

5. phpMyAdminの追加
compose.yaml を開き、mysql サービスの後ろに以下の設定を追加してください。

compose.yaml に追加する内容：

    phpmyadmin:
        image: 'phpmyadmin:latest'
        ports:
            - '\${FORWARD_PHPMYADMIN_PORT:-8080}:80'
        environment:
            PMA_HOST: mysql
            PMA_USER: '\${DB_USERNAME}'
            PMA_PASSWORD: '\${DB_PASSWORD}'
        networks:
            - sail
        depends_on:
            - mysql
6. Sailの起動とエイリアス設定
Sailをバックグラウンドで起動
./vendor/bin/sail up -d
エイリアスを設定して 'sail' だけでコマンドを実行できるようにする
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc
または bash の場合
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.bashrc
シェルを再起動するか、新しいターミナルを開いてエイリアスを有効にする
exec \$SHELL
7. アプリケーションキーの生成
ルートで以下のコマンドを実行する

sail artisan key:generate
8. データベースのマイグレーションと初期データ投入
以下のコマンドでテーブルを作成し、初期データを投入します。

sail artisan migrate --seed
※既存のデータベースをリセットしたい場合は以下を実行してください。

sail artisan migrate:fresh --seed
⚠️ 日本語化／翻訳について:

日本語化は FormRequest の messages() と lang/ja （認証系）で行います。
laravel-lang/ 系の外部翻訳パッケージ（composer require laravel-lang/...）は導入しないでください。同系パッケージは2026年5月のサプライチェーン攻撃でマルウェア配布に悪用された経緯があり、本課題では不要です。
🔗 開発環境URL
ローカルでの開発環境URLは以下の通りです。

アプリケーションURL: [http://localhost]
👤 作成者
[山村 龍世]