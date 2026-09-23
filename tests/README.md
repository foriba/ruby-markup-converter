# 回帰テスト

プラグイン直下で実行します。追加のテストライブラリは不要です。
初回およびクラスの追加・移動後は `composer autoload:runtime` を実行してください。
`vendor-dev/` は開発ツール用、`vendor/` はプラグイン実行用です。
後者は開発依存を含めず生成するため、PHP 7.4 でも起動できます。

```sh
WP_ROOT="/path/to/wordpress" composer test
```

`WP_ROOT` は `wp-includes` を含む WordPress 6.8 以降のディレクトリです。
Composer を使わない場合も同じテストを実行できます。

```sh
WP_ROOT="/path/to/wordpress" php tests/run.php
```

成功時は `PASS` と検証数、PHP・WordPress のバージョンを表示します。
失敗時は `FAIL` と期待値・実際の値などを表示し、終了コード 1 を返します。
PHP の警告もテスト失敗として扱います。テストは CLI 専用です。

## 構成

- `unit/values.php`: 値クラスの候補、変換、不正値、共有インスタンス。
- `integration/conversion.php`: 全記法、HTML 保護、文字参照、両描画方式、再変換の抑止。
- `integration/settings.php`: 保存形式、初期値、全解除、不正値、各呼び出し元とプレビュー。
- `bootstrap.php`: WordPress の部分読み込みとテスト用の外部依存。

旧実装を正解として実行せず、固定の期待結果と重要な性質を検証します。
不具合修正時は、修正前には失敗するケースを該当ファイルへ追加してください。
`check_same()` は厳密比較を行います。

## 安全性と対象範囲

`wp-load.php`、`wp-config.php`、データベース接続は読み込みません。
設定値はメモリ上の配列で扱い、書き込み用の関数は例外を投げます。
プラグイン本体を読み込むため、実際のクラス読み込み順も検証します。

HTML API、`esc_html()`、`esc_attr()`、`wp_kses()`、サニタイズ処理は
指定した WordPress の実装を使用します。設定取得、翻訳、URL、管理画面判定、
UTF-8 判定・文字コード名、許可プロトコルはテスト用の代替実装です。
そのため、実 DB への保存、翻訳、権限・nonce、ブラウザ表示、WordPress 全体の
起動や登録処理を保証するテストではありません。画面での確認も継続してください。

テストは PHP 7.4 で使える構文に限定していますが、対応バージョンの保証には
各 PHP と、それに対応する WordPress の組み合わせで実行する必要があります。
一つの環境での成功を全バージョンの検証済みとは扱いません。

既存の WPCS・PHPStan は本体のコードを対象とし、この軽量テストハーネスは対象外です。
`tests/` は `.distignore` により配布から除外します。
- `unit/autoload.php`: クラスの遅延読み込み・参照先と開発依存の分離。
