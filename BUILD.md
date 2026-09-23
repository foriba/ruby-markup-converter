# 開発・配布・WordPress.org公開手順

この文書は毎回のリリースで使用する手順です。コマンドはまとめて実行せず、各段階の結果を確認して進めます。
配布準備スクリプトはローカルファイルを作るだけです。SVNへの反映・公開は手動で行います。

## 用語とディレクトリ

| 用途 | 内容 |
| --- | --- |
| 開発用プロジェクト | Gitで管理するプラグインのソース。以下では `PROJECT` |
| 配布準備用フォルダ | 一時ディレクトリ内の `ruby-markup-converter-dist/`。以下では `DIST` |
| SVN作業コピー | WordPress.org用の `trunk/`・`tags/`・`assets/` を含むフォルダ。以下では `SVN` |
| インストール用フォルダ | ZIP内部またはWordPress上での `ruby-markup-converter/` |

配布準備用の名前には `-dist` を付けますが、SVNにはその「中身」を同期します。
`trunk/ruby-markup-converter-dist/` という入れ子にはしません。
一時フォルダはOSに削除される可能性があります。見つからない場合はスクリプトを再実行します。

## 開発時のオートロード

プラグイン直下で実行します。

```sh
composer install
composer autoload:runtime
```

WordPress形式のクラスファイル名を維持するため、Composerのclassmap方式を使用しています。
クラスを追加・移動・名前変更した後は `composer autoload:runtime` を再実行します。
既存メソッドの本文だけの変更なら再生成は不要です。

- `vendor-dev/`：PHPStan・WPCSなど開発用。
- `vendor/`：プラグイン実行用。開発依存を含めず生成。
- 関数ファイルは明示的な読み込みを維持。フック登録のタイミングも維持。
- 自動生成されたvendorディレクトリはGitにコミットしません。
- 利用者側にComposerは不要です。配布物に実行用オートローダーを同梱します。

現在、実行時の外部パッケージ依存はありません。将来追加した場合、開発環境の
`vendor/` にも実行用パッケージをインストールするよう、生成手順を見直してください。

## 1. リリース内容を確定する

開発用プロジェクトのルートで、今回使用する変数を設定します。同じターミナルで作業してください。
以下の `VERSION` は例です。必ず今回の公開番号に置き換えます。SVNのタグ名には `v` を付けません。

```sh
PROJECT="$PWD"
VERSION="1.2.3"
WP_ROOT="/path/to/wordpress"
PHP_MIN="/path/to/minimum-supported-php"
SVN="/path/to/svn/ruby-markup-converter"
```

`WP_ROOT`・`PHP_MIN`・`SVN` は例示用のパスです。実際の環境に合わせて必ず変更します。
最低対応PHPを変更したらテスト用実行パスも更新します。
`PROJECT` と `SVN` は別のディレクトリです。

公開前の確認事項：

- メインPHPのヘッダー `Version` と、readme.txtの `Stable tag` が今回の番号と一致する。
- Changelog・Upgrade Noticeに今回の変更が反映されている。
- `Requires at least`・`Requires PHP` は実装に合っており、`Tested up to` は実際に検証した版である。
- `Plugin_Info` がバージョンをヘッダーから取得するため、別途番号を書き換えない。
- ブロックのバージョンは配布準備時にヘッダーから同期する。翻訳ファイルは必要に応じて更新する。
- JavaScriptを変更した場合は、プロジェクトのビルド手順で `editor/build/` を更新する。
- 設定の保存、ブロック・ショートコード・全文変換、ルビ・傍点の表示を実画面でも確認する。

検証コマンド：

```sh
composer autoload:runtime
WP_ROOT="$WP_ROOT" composer test
WP_ROOT="$WP_ROOT" "$PHP_MIN" tests/run.php
vendor-dev/bin/phpcs -d memory_limit=512M
vendor-dev/bin/phpcs tests
vendor-dev/bin/phpstan analyse --no-progress --debug --memory-limit=512M
git diff --check
git status --short
```

すべて成功したら、必要な変更をコミットし、リリース対象をmainへマージしてGitタグを作成します。
GitHubへの反映も運用に従って済ませます。配布物はリリース対象のコミットから作成し、
`git status --short` に意図しない変更がないことを確認してください。
このスクリプトはGitタグを自動チェックアウトせず、現在の作業ファイルをコピーします。

## 2. 配布用ファイルを抽出する

Bash・rsync・Composer・PHP・Node.js・npmが必要です。JavaScriptの依存を未導入の場合は、先にプロジェクト直下で `npm ci` を実行してください。開発用プロジェクトで実行します。

```sh
cd "$PROJECT"
bash scripts/prepare-dist.sh
```

スクリプトは毎回新しい一時フォルダを作り、以下を実行します。

1. メインPHPのヘッダー `Version:` を `editor/src/` 内の各 `block.json` に同期。
2. `npm run build` でブロックとマニフェストを再生成。
3. `.distignore` に従って配布対象をコピー。
4. コピー先で開発依存を除いた `vendor/` を生成。
5. プラグインのクラスが読み込めることを確認。
6. 成功時に `ruby-markup-converter-dist/` の絶対パスを表示。

ソースの `block.json` と `editor/build/` は更新されます。依存パッケージは変更しません。
初回の同期や再ビルドで差分が出た場合は、検証してコミットしてからGitタグを作成してください。
すでにタグを作成していた場合は、そのまま公開せずリリース対象のコミットを見直してください。
途中で失敗した出力は配布しないでください。
ZIP作成、アップロード、回帰テストは自動実行しません。
`readme.txt` の `Stable tag` や `package.json` のバージョンは同期しません。

表示された実際のパスを設定します。次のパスは置き換えが必要です。

```sh
DIST="/スクリプトが表示した絶対パス/ruby-markup-converter-dist"
test -f "$DIST/ruby-markup-converter.php"
test -f "$DIST/readme.txt"
test -f "$DIST/vendor/autoload.php"
```

どれかが失敗したら先へ進まず、パスと生成結果を確認します。
`scripts/` はコピーされませんが、開発用プロジェクト側には残るので、そこから実行できます。
`.distignore` は実行用vendorも除外するため、開発用プロジェクトで
`wp dist-archive .` を実行するだけでは、現在の構成の配布物は完成しません。

配布用ファイルに対する回帰テストは、出力を汚さないよう別のコピーで実施します。

```sh
CHECK="$(mktemp -d)"
cp -R "$DIST/." "$CHECK/"
cp -R "$PROJECT/tests" "$CHECK/tests"
WP_ROOT="$WP_ROOT" php "$CHECK/tests/run.php"
WP_ROOT="$WP_ROOT" "$PHP_MIN" "$CHECK/tests/run.php"
```

このテスト用コピーは配布しません。テスト後、`DIST` に開発用の `vendor-dev/`、
`node_modules/`、`tests/`、`scripts/`、`.git/` がなく、
実行用の `vendor/` と `editor/build/` があることを確認します。

## 3. SVN作業コピーを確認する

SVNクライアントが必要です。初回で作業コピーがない場合のみ、未使用の保存先へ取得します。

```sh
svn checkout https://plugins.svn.wordpress.org/ruby-markup-converter/ "$SVN"
```

既存の作業コピーではcheckoutは不要です。次を確認します。

```sh
svn info "$SVN"
svn status "$SVN"
```

URLが対象プラグインのリポジトリであること、未処理の変更がないことを確認します。
変更がある場合は勝手に破棄せず、内容を整理してから進めます。その後、更新します。

```sh
svn update "$SVN"
svn status "$SVN"
```

競合があれば解消してから進めます。過去の公開済みタグは書き換えません。

## 4. trunkへの反映を予行演習する

コピー元とコピー先の存在を再確認します。失敗した場合は停止してください。

```sh
test -f "$DIST/vendor/autoload.php"
test -d "$SVN/trunk"
rsync -avn --delete --exclude='.svn/' "$DIST/" "$SVN/trunk/"
```

`-n` は予行演習で、実ファイルは変更しません。
`--delete` はコピー元にないファイルをコピー先から削除する指定です。
表示された `deleting ...` が意図した削除か必ず確認します。
両方のパスは引用符で囲み、コピー元の末尾の `/` を残します。

配布準備はすでに完了しているため、ここでは `.distignore` を再適用しません。
再適用すると、生成済みの実行用オートローダーが欠けるおそれがあります。
同期先はSVNのルートではなく `trunk/` のみです。`assets/` と既存の `tags/` は対象にしません。

## 5. trunkへ反映し、追加・削除を登録する

予行演習に問題がない場合だけ、`-n` を外します。この段階ではまだWordPress.orgへ送信されません。

```sh
rsync -av --delete --exclude='.svn/' "$DIST/" "$SVN/trunk/"
cd "$SVN"
svn status
```

| 表示 | 意味と対応 |
| --- | --- |
| M | 既存ファイルの変更。差分を確認 |
| ? | 新規ファイル。配布対象であることを確認して `svn add` |
| ! | 削除済みファイル。意図した削除なら `svn delete` |
| A / D | 追加・削除の登録済み |
| C | 競合。解消するまで公開しない |

追加・削除は、表示された実際のパスを指定して登録します。
次は書式の例であり、該当パスが存在する場合だけ実行します。

```sh
svn add "trunk/追加対象のファイルまたはディレクトリ"
svn delete "trunk/削除対象のファイルまたはディレクトリ"
```

特にオートロード導入後の初回公開では、`trunk/vendor/` の追加登録を忘れないでください。
ディレクトリを追加すると、その配下も追加対象になります。無関係なファイルまで一括登録しないでください。

```sh
svn status
svn diff
```

意図しない変更や未登録の `?`・`!` が残っていないことを確認します。
バイナリや生成ファイルは差分表示だけに頼らず、ファイル一覧・内容も確認します。

## 6. リリースタグを作り、まとめて公開する

バージョン番号・ヘッダー・Stable tagを再確認します。
`tags/$VERSION` が既存なら停止し、別の新しいバージョンを準備します。
古い手順書ではtrunkとタグを別々にコミットしていましたが、ここでは公開前に両方を準備し、
一度のコミットで送信する手順に統一します。

```sh
svn copy trunk "tags/$VERSION"
svn status
svn diff --summarize
```

`A  + tags/今回の番号` と、trunkの意図した変更が表示されることを確認します。
ここからのコミットはWordPress.orgへの公開操作です。最終確認が済むまで実行しないでください。

```sh
svn commit trunk "tags/$VERSION" -m "Release version $VERSION"
```

認証が必要な場合はSVN用の認証情報を使用し、パスワードをコマンドやこの文書に保存しません。
認証・競合などで失敗した場合は成功したとみなさず、原因を確認します。
ディレクトリ掲載用の画像はSVNルートの `assets/` で別途管理します。
本手順の同期・コミットは画像の変更を含めません。

## 7. 公開結果を確認する

```sh
svn status
```

何も表示されなければ未処理のローカル変更はありません。
WordPress.org側の反映には時間がかかる場合があります。次を確認します。

- 公開バージョン・Changelog・Tested up toが意図どおり。
- ダウンロードされたZIPのバージョンが一致する。
- ZIP内に `vendor/autoload.php` と必要な生成ファイルが含まれる。
- 別のテスト用WordPressへインストールして、有効化・設定保存・変換が動く。

## 任意：ローカルでインストール用ZIPを作る

WordPress.orgへのSVN公開に、手動のZIP作成は不要です。
手元でインストール試験や直接配布をする場合は、別の新しい場所に
正しいプラグイン名のフォルダを作ってから圧縮します。`zip` コマンドが必要です。

```sh
ZIP_ROOT="$(mktemp -d)"
mkdir "$ZIP_ROOT/ruby-markup-converter"
rsync -a "$DIST/" "$ZIP_ROOT/ruby-markup-converter/"
(
    cd "$ZIP_ROOT" &&
    zip -r "ruby-markup-converter.$VERSION.zip" ruby-markup-converter
)
printf '%s\n' "$ZIP_ROOT/ruby-markup-converter.$VERSION.zip"
```

ZIP内部の最上位は必ず `ruby-markup-converter/` とします。
`ruby-markup-converter-dist/` の名前のまま圧縮しないでください。

## 参考資料

公開手順の詳細は、以下の公式資料も参照してください。

- [WordPress公式：Using Subversion](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [Composer公式：classmap](https://getcomposer.org/doc/04-schema.md#classmap)
