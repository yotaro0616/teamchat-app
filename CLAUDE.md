# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## このリポジトリの位置づけ

Tutorial 16「総仕上げ」の課題リポジトリ。**チームチャットアプリ**（社内向け・20人規模）を、仕様書とモックアップから設計 → 実装まで一本作り切る。

素の Laravel 10（PHP 8.1+ / MySQL 8.4 / Sail）の上に、次の3つが載っている：

| | 役割 |
|:--|:--|
| `docs/spec.md` | クライアントの要求。**変更しない**。読んで分からないことは `docs/design/questions.md` へ |
| `mockup/` | 画面の完成見本（静的HTML 10枚）。**見た目はこれが正**。`mockup/design-guide.md` に色・字・余白・状態の値が全部ある |
| `docs/design/` | 自分が書く設計書7ファイル。**これが提出物の中心** |

**アプリの機能コードはまだ1行も無い。**`app/` `routes/` `database/migrations/` は Laravel の初期状態のまま。設計書7ファイルは全部書き終わっている（features / questions / screens / data / permissions-api / behavior / acceptance）ので、次に来る作業は原則「設計書に書いてあることの実装」になる。

---

## 開発コマンド

Docker（Laravel Sail）で動かす。`./vendor/bin/sail` を通す。

```bash
# 初回のみ: vendor/ が無い状態から composer を回す
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
  laravelsail/php82-composer:latest composer install

cp .env.example .env                      # 初回のみ
./vendor/bin/sail up -d                   # 起動（mysql が Up (healthy) になるまで約30秒）
./vendor/bin/sail artisan key:generate    # 初回のみ
./vendor/bin/sail artisan migrate
./vendor/bin/sail stop                    # 止める（データは残る）
./vendor/bin/sail down -v                 # DBの中身ごと消す
```

> ⚠️ **ポート80と3306は他のプロジェクトと共有している。**`post-app-practice` などが起動していると `Bind for 0.0.0.0:80 failed` で上がらない。そのフォルダで `./vendor/bin/sail stop` してから。
> `SQLSTATE[HY000] [2002] Connection refused` は起動直後に出る。`sail ps` の mysql が `Up (healthy)` になってから叩き直せば通る。

```bash
./vendor/bin/sail artisan test                    # 全テスト
./vendor/bin/sail artisan test --filter=ChannelTest        # クラス単位
./vendor/bin/sail artisan test --filter=test_なんとか       # メソッド単位
./vendor/bin/sail artisan test tests/Feature/ChannelTest.php
./vendor/bin/sail php vendor/bin/pint             # 整形（Laravel Pint）
./vendor/bin/sail npm install && ./vendor/bin/sail npm run dev   # Vite（node_modules は未インストール）
```

**テストは実DBを使う。**`phpunit.xml` は `DB_DATABASE=testing` を指定するだけで接続自体は mysql のまま。`testing` データベースは compose.yaml の初期化スクリプトがコンテナ作成時に作る。したがって **Feature テストにも `sail up` が必要**。

---

## 進め方（設計書ファースト）

このリポジトリの中心はコードではなく `docs/design/` の7ファイル。順序を守る。

1. **`docs/spec.md` は変えない。**書かれていないことに気づいたら自分で埋めず、`questions.md` に「〜という想定ですが、〜ですか」の形＋想定を添えて追加する
2. **`questions.md` の未回答を勝手に確定させない。**状態が「回答なし」「一部回答」の項目は、まだクライアントの答えが無い。とくに**検索（F-17）と公開API（F-18/F-19）を実装する前には必ず `questions.md` を読み直す**。この2機能に未確定が集中している。暫定で進めるときは「暫定」と明記して `questions.md` と該当設計書の両方に残す
3. **決めが変わったらコードより先に設計書を直す。**実物と設計書が食い違ったまま進めない
4. **発展課題も、設計を `docs/design/` に足してから**着手する

各設計書の冒頭に「このファイルは〜だけを根拠にしている」と書いてあり、末尾に「根拠にした資料」の一覧がある。設計書を追記・修正するときはこの体裁を崩さない。仕様書・モックアップに直接の根拠が無い判断には **「※設計判断」** と付けて理由を添える、というルールで全ファイルが書かれている。

図は **Markdown 内の Mermaid**（画像ファイルは置かない）。ファイル名は `docs/design/README.md` の表のとおりに固定（完成確認がこの表と突き合わせられる）。書く量の上限も同 README にある（画面項目定義は主要画面だけ／シーケンス図は代表1本／受け入れ条件は実装単位ごと2〜4行／クラス図は作らない）。

---

## イシューとプルリクエスト

**イシュー1件につきブランチ1本**を切り、プルリクエストで受け取る。`main` に直接コミットしない。

イシューの単位は `acceptance.md` の実装単位（認証(1)／チャンネル(2)／メンバー管理(3)／メッセージ(4)／スレッド(5)／検索(6)／公開API(7)）に対応する。受け入れ条件が実装単位ごとに2〜4行に絞ってあるのは、**PRで1行ずつ人が確かめる**ためなので、PR本文には次の3つを必ず載せる。

1. **テストの実行結果**（`sail artisan test` の出力）
2. **受け入れ条件ごとの判定** — そのイシューに対応する `AC-<単位番号>-<連番>` を1行ずつ挙げ、満たしたかどうかを書く
3. **`Closes #<イシュー番号>`** を最後に置く

設計と実装がずれたときは、コードだけ直して済ませず `docs/design/` の該当ファイルも同じPRに含める（「決めが変わったらコードより先に設計書を直す」の続き）。

この3つをどう作るか（テスト出力の取り方・受け入れ条件の引き方・`Closes` の置き場所）は `.claude/skills/pr/` にある。着手のしかたは `.claude/skills/start/`。

---

## 守ってほしい書き方

実装を始めた瞬間に破られやすい決めごとを、1行ずつ挙げる。**理由はすべて在りかの設計書に書いてあるので、ここには写さない。**手が動く前に在りかを開く。

| 決めごと | 在りか |
|:--|:--|
| 画面に出す文言・エラー文を自分で考えない。決まっているものはそのまま使う | `screens.md` 4章 ／ `design-guide.md` §4 |
| 入力の上限値をうろ覚えで書かない | `docs/spec.md` §5-1 ／ `screens.md` 4章 |
| 画面の項目を勝手に増やさない・減らさない | `screens.md` 3章 |
| 色・寸法・角丸を直値で書かない。CSS変数がある | `design-guide.md` ／ `mockup/styles.css` |
| 日時の表記をその場で決めない | `design-guide.md` §2 |
| URLとHTTPメソッドを自分で決めない。一覧が決まっている | `permissions-api.md` 2章 |
| 公開APIのレスポンスのキーを自分で決めない | `permissions-api.md` 3章 |
| `SoftDeletes` を反射で付けない | `data.md` 0章・2-4 |
| 「編集済み」を `updated_at` で判定しない | `data.md` 2-4 |
| 返信を別テーブルにしない | `data.md` 1章 |
| 返信件数の列を持たない | `data.md` 2-4 |
| 見えないチャンネルに403を返さない | `behavior.md` 3章 |
| チャンネルの作成者に、他人のメッセージへの特権を持たせない | `permissions-api.md` 1章 注記[2] |
| UIで隠した・押せなくしたことを、サーバ側の判定の代わりにしない | `behavior.md` 3章 |
| JSを文字数カウンタとボタンの活性・非活性以外に使わない | `screens.md` 4章 |
| `questions.md` が未回答の項目を、自分で確定させて進めない | `questions.md` ／ `docs/design/README.md` |
| コードを直してから設計書を直す、の順にしない | `docs/design/README.md` |

このうち複数の設計書にまたがっていて、在りかを1つ開くだけでは足りないものだけ、次の節で説明する。

---

## 実装で踏み抜きやすい設計上の約束

詳細は各設計書が正本。ここには**複数ファイルにまたがっていて、見落とすと壊れる**ものだけ挙げる。

### messages の `deleted_at` は Laravel の SoftDeletes とは意味が違う

- 削除は論理削除だが、**`body` はクリアしない**。削除済みメッセージは「このメッセージは削除されました」という枠として**画面に残り続ける**（投稿者名・日時・返信件数も残る）。返信も続けられる
- つまりチャンネル表示（F-06）・スレッド表示（F-16）は `deleted_at` を問わず**全件取得**する必要がある。`SoftDeletes` トレイトのグローバルスコープは既定でこれを弾くので、**トレイトを使うかどうかは実装時に明示的に決めて `docs/design/` に記録する**
- 逆に**検索（F-17）と公開API（F-19）は `deleted_at IS NULL` を明示的に条件へ入れる**。ここを落とすと削除済みの本文が漏れる（`data.md` 0章・4章が繰り返し警告している箇所）
- 一方、**チャンネルの削除は物理削除**。`channel_user` と `messages` を `ON DELETE CASCADE` で道連れにする。メッセージの論理削除とは別物

### スレッド返信は独立テーブルではない

`messages` の自己参照（`parent_message_id`）。NULL なら本流、値が入っていれば返信。返信は1段まで（ネスト禁止）だが **DB制約ではなくアプリ側でチェックする**。`channel_id` は返信も持つ（親と常に一致する意図的な冗長化）。

### プライベートチャンネルは「403」ではなく「存在しない扱い」

自分がメンバーでないプライベートチャンネルは、一覧にもサイドバーにも出さず、URLを直接叩かれても**存在しないIDと同じ404**を返す。公開APIも同じ（`{ "message": "指定されたチャンネルが見つかりません" }`）。存在有無で応答を変えると存在自体が漏れる、という一貫した方針。

### 公開APIはプライベート情報を一切含めない

`GET /api/channels` は `type='public'` のみ、`GET /api/channels/{channel}/messages` は対象が public かをレスポンス生成の中で確認する。**チャンネル名すら漏らさない**（spec §3-7 の強い要求）。認証不要・読み取り専用で、書き込み系は作らない。

### 権限はサーバ側で必ず再確認する

UIでボタンを隠していても、作成者チェック・投稿者本人チェック・削除確認入力の一致は**すべてサーバ側で再検証**する。とくに「チャンネルの作成者であっても他人のメッセージは編集・削除できない」（作成者に特権は無い）。

### JavaScript の使用範囲は意図的に絞っている

使うのは **文字数カウンタの追随表示** と、それに連動する **送信・実行系ボタンの活性/非活性** だけ。画面遷移は JS に頼らず、`PATCH`/`DELETE` は `@method()` によるメソッド偽装で送る。スレッドパネルもチャンネル編集の削除確認カードも、専用URLや同一ページ内アンカーで実現する設計になっている。

### 画面

Blade + セッション認証。**PCのみ（最小幅1280px、レスポンシブ対応しない）**。ピクセル一致は不要で、合わせるのは**並び・項目・言葉づかい・状態の見せ方**。色・サイズ・状態の値は `mockup/design-guide.md` の表と `mockup/styles.css` の CSS 変数がそのまま使える。日時の表記は `2026/08/17 14:32` に固定。画面に出すアプリ名は「チームチャットアプリ」で統一。エラー文は1文・句点なし（例:「チャンネル名を入力してください」）。

### 初期データ

`docs/spec.md` §5-4 に、アカウント3件（`sato@example.com` / `suzuki@example.com` / `takahashi@example.com`、いずれもパスワード `password`）・チャンネル4件・メッセージの条件（公開チャンネルに各10件前後、返信が付いたメッセージが2件以上・うち1件は返信3件以上、編集済み1件以上、削除済み1件以上、プライベートにも数件）が固定で指定されている。`acceptance.md` の受け入れ条件はこのデータが入っている前提で書かれているので、シーダーは仕様どおりの内容にする。

---

## ドキュメントの言語

`docs/` `mockup/` はすべて日本語。設計書・コミットメッセージ・Issue も日本語で書く。
