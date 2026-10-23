# QA アシスタント作成 — AI 向け指示書

あなたは、**QA Assistants / QA ZERO の製品 ZIP** を材料にして、その「AIアシスタント」画面で動く**アシスタントプラグインを1本**作る。

---

## 0. 正本（これを最初に頭に入れる）

- **正本は製品のコードである。** 一緒に渡された **展開済みの製品 ZIP**（`qa-heatmap-analytics/` または `qa-zero/`）が唯一の真実。
- **説明文よりコードを信じる。** このファイルの記述とコードが食い違ったら、**コードが正しい**。食い違いを見つけたら、直さずに**報告**する（このファイルは人が直す）。
- **写しは一つも無い。** schema も検証器も QAL の文法も、すべて**製品 ZIP の中の実物**を読む。だから「古い写しを読んで間違える」ことが構造的に起きない。
- 以下、パスは**すべて製品 ZIP の展開先からの相対パス**。

---

## 1. どこに何があるか（読む順序）

1. **`examples/`（この一式に同梱）** — 動く見本5本。**近い型をまねる**：
   - `qa-assistant-sample`＝データを見せる型（ページ別ランキング＋メモ保存）
   - `qa-assistant-form-sample`＝入力・設定だけの型
   - `qa-assistant-hitokoto`＝**データ源ゼロの最小形**（ボタン1つで一言。どの環境でも動く）
   - `qa-assistant-lp-bounce`＝**最大形**（`data_sources` 2本＝サイト全体〔`keep: []`〕＋ページ別／`scorecard`／`table` の `color_thresholds` と **`row_action`**（行クリックで詳細へ）／`tally`／`if` の `all`・`else`）
   - `qa-assistant-monthly-compare`＝**期間の比較の型**（★β3 から＝期間欄の `compare` で比較期間を受け取り、同じクエリを2回取って「今・前・差」を `rows` の表と scorecard に出す。差の列は `signed`。`min_core_version` 2.25.0）
   ⚠️ **見本は「動く見本」であって「正しい指標の見本」ではない**（MUST 3・4 を必ず読む）。
2. **構造（何が書けるか）** — `assistant-schema/manifest.schema.json` → `assistant-schema/definitions/*.json`。
   **schema に無い書き方は存在しない**（runtime に実装があっても、schema が許さないなら使えない）。
   **★唯一の例外＝テンプレート文字列の中の書式指定子（`{$var|round:1}` 等）。** これは schema が見ない層なので、**使えるものの一覧は `assistant-schema/vocab-ledger.json` の `format_specifiers`**（`min_core_version` つき）＝**使う前にここを見る**（`integer` / `float` / `percentage` / `duration` / `round` / `before` / `after`。**★次の版から `weekday`（仕様 2.18.0）・`signed`（仕様 2.21.0）が加わる**＝製品 ZIP の台帳に無いものは、その版では使えない）。
3. **データの材料（何が取れるか）** — `yaml/materials-manifest.php`。
   フィールド名・型・**そのフィールドの本当の意味（`search_hint` の `note`）**を実物から取る。**推測で書かない。**
   **絞り込み可否（QAL の `filter` に書ける列）は materials-manifest には書かれていない。** 正本は **`yaml/qal-validation-2026-05-11.php` の `filter` の description（「Supported fields depend on material type」＝材料ごとの対象列の列挙）**。`note` に出てくる **`post_filter` は内部の実行方式の呼び名であって manifest に書ける語彙ではない**（「遅い」という性能の注記）。
4. **QAL の文法（どう取るか）** — `yaml/qal-validation-2026-05-11.php`（＝クエリの仕様の正本。`make` / `keep` / `calc` / `filter` / `sort` の書き方）と、実装 `class-qahm-qal-executor.php`。
   ※ manifest の schema は QAL の中身を検証しない（実行器に委譲している）。**だから文法はここを読む。推測で書くと、VALID なのに動かない/違う数字が出る。**
5. **書く → 検証**（MUST 1）→ VALID になるまで直す。

**「VALID なのに結果がおかしい」ときに読むコード**（説明を探さない）:

| 症状 | 読む場所 |
|------|---------|
| 数字が合わない／絞り込みが効かない | `js/qahm-assistant-runtime.js`（`transform` の実装＝`group_by`/`agg`/`filter`/`calc`/`set_var`） |
| データが取れない・列が空 | `class-qahm-qal-executor.php`（集約は keep 列でグループ化される）／`yaml/materials-manifest.php` |
| フィールドの意味・型 | `yaml/materials-manifest.php`（`search_hint` に注意書きがある） |
| その列を QAL の `filter` に書けるか | `yaml/qal-validation-2026-05-11.php`（`filter` の description＝材料ごとの対象列）。**summary 系の数値列は書いても黙って無視される**（MUST 4） |
| 設定の読み書きの戻り値・権限エラー | `class-qahm-assistant-runtime-handler.php` |
| 表示ブロック（callout / divider / html）の見た目 | `js/qahm-assistant-blocks.js` |
| 表の列の型・並び替え・書式 | `js/qahm-assistant-table.js` |

※ `html` step は**自由装飾の道具ではない**——サニタイザ（`js/qahm-html-sanitizer.js`）が `style`・`img`・`svg`・許可外クラスを除去する。華やかさは callout の `level`（good/mid/bad/info）・scorecard・絵文字・シーン進行の「間」で作る。
※ 飾り枠（message 本文の中に置く形）は **フェンス3行**で、色は **`{level=<良し悪し>}`** で指定する。**開始行・本文・閉じ `:::` を別の行に分ける**（1行に詰めると画面へマークアップが生のまま出る）。

```
:::callout{level=info}
ここが本文。**太字**・[リンク](url)・{$var} が使える。
:::
```

**間違えやすい2つ（どちらも検証は通り、console も無言のまま静かに崩れる＝実測）:**
- **`:::callout info`（空白区切り）→ 画面へマークアップが生のまま出る**（検証器は message 本文のトークン構文を見ない）。**フェンスの3行は行頭から始め、余分な空白を付けない**（開始行のインデント・閉じ `:::` の行末スペースでも同じく生のまま出る）。
- **`:::callout{info}`（`level=` を書き忘れた波括弧）→ 箱にはなるが色が付かない**（無色の note になる）。**`{level=warning}` のように4つ以外の色名を書いた場合も同じ**。**`{level="info"}` のように引用符で囲んだ場合・`{level=INFO}` のように大文字にした場合も無色になる**——`level` の値は**引用符なし・小文字でそのまま**書く。`runtime.js` は波括弧の中を `level=(\w+)` でしか読まず（`"` は `\w` ではない）、読めた値も `good` / `mid` / `bad` / `info` の**4つ以外は無色へ倒す**ため。
  ⚠️ **兄弟トークンの `::stat` は引用符を受け付ける**（`::stat{label="直帰率" value="50.0" level=good}`＝実測）。**`::stat` の書き方を `:::callout` へ写すと、ここで静かに色が落ちる**（正規表現が別＝`::stat` は `="..."` も読む）。
※ ブラウザ側の条件比較（`transform.filter`／`tally` の `when`／`if`）は **`matchOperator` 1本**（`js/qahm-assistant-runtime.js`）＝`$var` は全演算子で解決され、解決できない `$var` は**全演算子で不成立＋console 警告**（fail-closed）。`gt`/`gte`/`lt`/`lte`・`contains` に `$var` を書く・`if` の `value` に `$var` を書くなら **`min_core_version` を `2.11.0` 以上で宣言**する（検証器 `E_REF_MIN_CORE_VERSION` が要求する。旧コアでは比較が無言で効かない）。**「`$var` が全演算子で解決される」ことと「版数の宣言が要る」ことは別**＝`transform.filter` の `eq`/`neq` に `$var` を書くのは解決もされるし宣言も要らない。**ただし `if` に書くなら演算子によらず `2.11.0` が要る**（**版数要求の一覧と場所ごとの違い＝MUST 2 の表と ⚠️**）。⚠️ **`tally` の `when` は検証器が見ていない**＝`when` に `gt`/`gte`/`lt`/`lte`/`contains` × `$var` を書いても **`2.11.0` は要求されない**（**実測＝`min_core_version: "2.10.0"` のまま VALID・同じ比較を `filter` に移すと INVALID**）。**旧コアでは実際に解決されず静かに不成立になるので、`tally.when` で `$var` を比較するなら自分で `2.11.0` を宣言する。**

---

## 2. MUST（間違えると壊れる規則。これ以外は schema と examples に従えばよい）

### 検証・互換

1. **書いたら必ず検証する。VALID 以外を完成としない。**
   ```sh
   # validate.php はこの指示書と同じフォルダにある（製品ZIPの1つ上の階層）
   php validate.php <展開した製品ZIPのパス> <作ったディレクトリ>
   ```
   `E_SCHEMA_*` / `E_REF_*` / `E_PKG_*` が 0 件で `VALID` と出るまで直す。**検証を通さずに「できました」と言わない。**
   検証が見るもの＝①manifest の**構造**（`E_SCHEMA_*`）②**参照の整合**（`E_REF_*`＝goto の飛び先・未宣言の変数・データ源/表/グラフの参照・計算式の文法・テンプレートの書き間違い・新しい書き方に要る `min_core_version`）③**翻訳**（`lang/*.json` の `t:` キー欠落・翻訳文の中のテンプレート）④**パッケージ構成**（`E_PKG_*`＝許可外のファイル・スタブ PHP の形・アイコン）。
   **★次の版から `E_PKG_UNSCANNED` が増える**＝**検証が中を見なかった場所にファイルがあれば、その件数を報告する**（対象は `.git/` のみ。`".git/" contains N file(s) that were not inspected …`）。**中身は見ない＝「あることだけ言う」**（黙って見ないのをやめた、という趣旨）。⇒ **配るものに `.git/` を入れない。** `.DS_Store` / `Thumbs.db` / `.gitkeep` は従来どおり黙って無視される。**自分の版で出るかは、製品 ZIP の `class-qahm-assistant-schema-validator.php` に `E_PKG_UNSCANNED` が在るかで判る。**
   ⚠️ **VALID は「壊れていない」であって「正しい」ではない。** **QAL の中身（材料名・列名・集計の意味）は検証されない。** そこは自分で確かめる（MUST 3〜11）。
   ※ ラッパーが `検証エンジンが壊れています` と言ったら、製品 ZIP を展開し直す。その状態の判定は信用しない。
   ※ ラッパーが `古い世代です` と表示したら、その製品 ZIP は②〜④を持たない＝構造だけの判定。goto の飛び先・変数・翻訳キーは自分で確かめる（MUST 15・16）。
   ※ **`E_SCHEMA_ONEOF` は原因のキー名を言わない**＝エラーの `/scenes/...` パス直下の step を `definitions/*.json` と見比べる（よくある実例＝`config_write.value` はオブジェクト必須・`if` の then/else に書けるのは `set`/`goto` だけ）。

2. **`min_core_version` は、宣言するなら製品 ZIP の `qahm-const.php` の `QAHM_ASSISTANT_SPEC_VERSION` 以下の値にする。**
   宣言すると、それより古いコアでは**起動時にエラーで一切動かない**（`E_CORE_TOO_OLD`）。**判断できないなら省略する**（省略＝最大互換）。**根拠のない数字を書かない。**
   **どの書き方に何が要るか**（製品 ZIP で読める写し＝`class-qahm-assistant-schema-validator.php` の `check_min_core_version` の要求一覧。おおもとは製品の仕様書。忘れても検証器が `E_REF_MIN_CORE_VERSION` で止めて教えてくれる）:

   | 要る版 | それを要求する書き方 |
   |---|---|
   | `2.10.0` | `tally`／`agg` の `count_distinct` |
   | `2.11.0` | **`filter` の `gt`・`gte`・`lt`・`lte`・`contains` に `"$var"` を書く**／`if` の `value` に `"$var"` を書く |
   | `2.12.0` | `transform` の `extract_host` |
   | `2.13.0` | **`if` の器の拡張＝`all` / `else` / `then.set` / `is: "in"`** |
   | `2.14.0` | テンプレートの書式指定子 `round` / `before` / `after` |
   | `2.16.0` | ★次の版から＝`date_range` の相対トークン `last_28_days` / `last_90_days`（MUST 9） |
   | `2.17.0` | ★次の版から＝`date_range` の分解値 `<key>_days` / `<key>_start_dow` / `<key>_end_dow` を宣言して使う（MUST 9） |
   | `2.18.0` | ★次の版から＝書式指定子 `weekday`（MUST 9） |
   | `2.19.0` | ★次の版から＝**`if` の `is: "contains"`**（下の ⚠️） |
   | `2.20.0` | ★次の版から＝システム変数 **`$sys.today` / `$sys.hour` / `$sys.dow`**（下の ⚠️） |
   | `2.21.0` | ★次の版から＝書式指定子 **`signed`**（MUST 10） |
   | `2.22.0` | ★次の版から＝manifest の root の **`notify`**（定期通知・下の ⚠️） |
   | `2.23.0` | ★次の版から＝`date_range` の `default` の **`previous_of:<key>` / `year_ago_of:<key>`**（2つ目の期間の既定値・MUST 9） |
   | `2.24.0` | ★β3 から＝`date_range` 欄の **`compare`**（選んだ期間に付いてくる比較期間）を書く／**`<key>_compare_*`** を宣言する（MUST 9） |
   | `2.25.0` | ★β3 から＝表の列の **`type_options.signed`**・**`tables.<name>.rows`**・グラフの **`series[].dashed`**（MUST 10） |

   **宣言する版は1つ**＝使った書き方のうち**いちばん高い版**を書けば、それより下の要求もまとめて満たす（例＝次の版の書き方を `signed` まで使うなら `"min_core_version": "2.21.0"`）。
   **★この表の「次の版から」「β3 から」の書き方（2.16.0〜2.25.0）は、QA Assistants 5.3.1.0 β3（仕様 2.25.0）にすべて入っている。** 手元の製品で使えるのは、製品 ZIP の `qahm-const.php` の `QAHM_ASSISTANT_SPEC_VERSION` 以下の行だけ。

   ⚠️ **`eq` / `neq` は「どこに書くか」で変わる**（一括で対象外ではない）:
   - **`transform.filter` の `eq` / `neq`** … 値が何であっても（`"$var"` でも数値でも）**版数の宣言は要らない**。
   - **`if` の `eq` / `neq`** … **ただの文字列リテラル**（`"4"` 等）と `null` なら要らない。**`"$var"`（`"$sys.*"` を含む）は演算子によらず `2.11.0`**（表の 2.11.0 行のとおり）。**数値・真偽値のリテラルも `2.11.0`**——旧コアの `if` は厳密比較（`===`）で、フォームの値は必ず文字列＝**数値リテラルとの比較は旧コアでは永遠に成立しなかった**ため。
     実測（β2・2026-08-25）＝`if` は `value: "4"`（文字列）・`value: null` が VALID／**`value: "$satisfaction"`・`value: 4`・`value: true` は `E_REF_MIN_CORE_VERSION` で INVALID**。`transform.filter` は `eq: "$total_pv"`・`neq: "$total_pv"` とも VALID（**`gte: "$total_pv"` だけ INVALID**）。**同梱の見本 `qa-assistant-form-sample` が満足度を数値 `4` と比べている**ので、`gte` を `eq` に変えるだけで踏む。
   ⚠️ **`if` に `all`／`else`／`then.set`／`is: "in"` を1つでも使ったら `2.13.0`**——`$var` 比較の `2.11.0` から類推して低く宣言すると `E_REF_MIN_CORE_VERSION` で止まる（初見が実際に踏んだ・2026-08-25）。
   ⚠️ **★次の版（仕様 2.19.0）から、`if` の `is` に `contains`（部分一致）が書ける**＝`{"if":{"var":"os_answer","is":"contains","value":"Windows"},"then":{"goto":"win"}}` は、答えが「Windows NT 6.1」でも成立する（`eq` だと不成立）。**大文字と小文字は区別する**（`"windows"` では不成立＝`transform.filter` の `contains` と同じ判定）。**`value` は空でない文字列か `"$var"`**＝空文字・省略・数値は検証が `E_REF_IF_CONTAINS_OPERAND` で止める（空は何にでも当たり、分岐が黙っていつも同じ方へ進むため）。**「含まない」の演算子は無い**＝`else` で書く。`all` の中に書いても同じ。**使うなら `2.19.0` 以上を宣言する**（古い製品は `contains` を知らないので、宣言が無いと開けないか会話がエラーで止まり、理由も伝わらない。宣言があれば「QA Assistants を更新してください」になる）。
   ⚠️ **★次の版（仕様 2.20.0）から、システム変数に `$sys.today`（今日の日付＝`YYYY-MM-DD`）・`$sys.hour`（いまの「時」＝0〜23 の数）・`$sys.dow`（曜日＝0〜6・日曜＝0）が加わる**（それまでは `tracking_id` / `locale` / `tz` の3つ）。値は**サイトのタイムゾーンで、アシスタントのカードを選んだ時刻**に決まり、会話の途中では変わらない（見ている人の PC の時計は使わない）。曜日名は `{$sys.dow|weekday}`。**`if` の `var` は変数名なので `$sys.*` は書けない**＝いったん `set` で変数に写してから比べる（`hour` は数＝`gte` がそのまま数で比べる）:
   ```json
   { "set": { "h": "$sys.hour" } },
   { "if":   { "var": "h", "is": "gte", "value": 12 },
     "then": { "set": { "greeting": "こんにちは" } },
     "else": { "set": { "greeting": "おはようございます" } } },
   { "message": "{$greeting}。今日は {$sys.today}（{$sys.dow|weekday}）です。" }
   ```
   **計算式（`expr`）に使えるのは数の `$sys.hour` / `$sys.dow` だけ**＝`$sys.today` は文字列なので、式に書くと失敗して空になる（検証は参照名しか見ないので止まらない）。**使うなら `2.20.0` 以上を宣言する**——宣言せずに古い製品へ入れると、**翻訳文（`lang/*.json`）の中で使った場合は開けてしまい、値が空のまま黙って間違う**（午後でも「おはようございます」になる）。検証は翻訳文の中の使用も見て要求する。
   ⚠️ **★次の版（仕様 2.22.0）から、manifest の root に `notify`（定期通知＝決まった時刻にメールで一言）が書ける**。予約と送信は製品がする（アシスタントは PHP も WP-Cron も書かない）:
   ```json
   "min_core_version": "2.22.0",
   "notify": [
     { "id": "hello", "every": "daily", "at": "12:00", "message": "t:notify.hello" }
   ]
   ```
   - `id`＝`^[a-z0-9_]{1,32}$`・アシスタントの中で重複しない（重複は検証が `E_REF_NOTIFY_ID` で止める）。**許可は `id` ごとに記録される＝名前を変えると別の通知（未許可）になる。** `every` は `"daily"` だけ。`at` はサイトのタイムゾーンの `"HH:MM"`（時は2桁＝`"9:00"` は不可）。`to` は省略か `"mail"` だけ。**1アシスタント5件まで。**
   - `message`＝固定文か `t:` の翻訳参照。**変数（`{$x}`）や数字は入らない**（送るときは会話もクエリも動かない）＝「今日の数字」は送れない。画面を開くきっかけの一言に使う。**翻訳の解決後 500 文字まで**（超えると検証が `E_REF_NOTIFY_LENGTH` で止める）。文の言語は見ている人ではなくサイトの言語。
   - **入れただけでは何も届かない**＝サイトの持ち主がアシスタント一覧のカードで通知ごとに ON にしたものだけ届く（既定 OFF・押せるのは WordPress の管理者）。宛先と件名は書けない（宛先＝製品の設定のメールアドレス）。届く時刻は「宣言の時刻**以降に、サイトが最初に開かれたとき**」＝アクセスの少ないサイトでは遅れる。
   - **QA ZERO では動かない**（製品が無視する）。検証は `W_NOTIFY_WP_ONLY` の警告を出すが VALID のまま＝アシスタント自体は ZERO でも動く。
   - **使うなら `2.22.0` 以上を宣言する**（古い製品は root の知らないキーとして配信時に止まる）。

### データの正しさ（ここが最も間違えやすい。検証器は助けてくれない）

3. **見本の指標をそのまま信じない。** 材料の列を使う前に、必ず `yaml/materials-manifest.php` を読む。見る順序：
   - まず **`semantics` 宣言**（あれば）＝その列の意味の機械可読な正本。`id`＝識別・分類番号／`ordinal`＝位置番号（まとまりの中で 1 に戻る）／`rate`＝比率（分子・分母を別々に合計してから割る。単純な合計・平均は狂う）／`percent`＝1行ごとの実測％（平均は正当・合計は無意味）。**`id`・`ordinal` の SUM/AVERAGE は書かない**。無宣言＝ふつうの量（制約なし）。
   - 次に `search_hint` の `note`（日本語の判断文脈）。
   実例（2026-07 に実際に起きた誤り）: `allpv.pv` は「**セッション内の PV 番号（1＝ランディング）**」＝`semantics: ordinal`＝位置番号であって閲覧回数ではない。合計すると 5PV のセッションが 1+2+3+4+5＝15 を積む（実データで実 PV 333 に対し 1,252 と表示された）。

4. **材料の選び方**:
   - **単一期間**のページ別ランキング・集計 → **`summary_allpage`**（`pv_count` ＝指定期間のページビュー累計）
   - 訪問の生ログを1件ずつ見る分析 → `allpv`
   - **期間の比較（先月 vs その前の月 など）→ `allpv` を使う。** `summary_allpage` には**日付の列が一つも無い**（指定期間の合計しか返らない）ので、1本のクエリの中で期間を割ることができない。PV 数は **`COUNT(allpv.pv_id)`**（allpv は 1行 = 1PV）。**`allpv.pv` は連番なので絶対に使わない**（MUST 3）。
   - **ゴール（CV）分析 → `allpv` の仮想列 `is_goal_1`〜`is_goal_10`（0/1）を使う。** `goal_1` 等の材料は `session_id` を持たず、訪問単位の集計ができない。
   - **直帰率・訪問数（セッション数）→ `summary_days_access_detail`（デバイス×流入元の粒度・名前に days とあるが日付の列は無い）か `summary_landingpage`（入口ページ単位）。** 直帰率の分母は **`session_count`**（製品実装＝`bounce_count / session_count * 100`）。**`summary_allpage` は `bounce_count` は在るのに `session_count` だけが無い**——分子だけ揃うので `pv_count`／`lp_count` を分母に代用したくなるが、VALID のまま静かに間違う。
   - ⚠️ **summary 系（`summary_allpage`／`summary_landingpage`／`summary_days_access_detail`）の数値列（`pv_count`・`session_count`・`bounce_count`・`user_count` 等）は、QAL の `filter` に書いても効かない。** 保存層が評価する列は `utm_source` / `utm_medium` / `utm_campaign` / `device_id` / `is_newuser` / `is_QA`（＋`page_id`・landingpage は `second_page`）だけで、それ以外のキーは**エラーにならず黙って無視される**＝**VALID のまま全件が返り、数字が静かに違う**（検証器は QAL の中身を見ない）。**「訪問数 10 以上の入口だけ」のような数値の足切りは、ブラウザ側 `transform.filter`（`gte` 等）で行う。** `note` の「数値範囲フィルタは post_filter」は、この列が QAL で絞れるという意味ではない（上の §1-3）。
   - ⚠️ **`gsc`（Search Console）も同じ構えで**＝QAL の `filter` の正本の一覧に載っているのは **`search_type` / `keyword` / `ctr` / `position` / `position_weighted`** だけで、**`clicks` / `impressions` は載っていない**（`yaml/qal-validation-2026-05-11.php` の `filter.description`＝材料ごとの列挙）。**一覧に無い列は当てにしない。「表示回数100以上のキーワードだけ」のような数値の足切りは、集計したあとブラウザ側 `transform.filter` で行う。** `materials-manifest.php` の `note` の「数値範囲フィルタは post_filter」は、実行方式の呼び名であって「QAL に書ける」の保証ではない（上の §1-3）。
   - **`click_event`（クリック計測）を使うなら、`to_url` と `element_text` は空になりうることを前提に組む。** 実データで**内部クリック 467 件中 320 件（68.5%）が両方とも空**だった例がある（動作確認用サイトでの実測・2026-08-25）。**そのまま `to_url`＋`element_text` でグループ化すると、「いちばん押されている箇所＝312回・（空欄）」が1位に出る**——数字は正しいのに答えになっていない。**`action_id`（`1:click` / `2:form` / `3:tel` / `4:mailto`）で切り分ける**（上の例の空欄 307 件は `2:form`＝**クリックが `<form>` 領域内で起きたもの**＝入力欄のクリックで、リンク先も表示文字列も無いのが正常）。要素を指したいときは `selector` / `element_id` / `element_class` もある（**manifest に書くのはこの名前**＝`materials-manifest.php` の `material_column`。生の列DB で見える `selector_id` 等の物理名を書くと**実行時に止まる**＝`keep`／`columns` なら `E_UNKNOWN_COLUMN`・**QAL の** `filter` なら `E_FILTER_INVALID`。**検証器は通る**〔QAL の中身を見ないため〕＝**実行して初めて止まる**〔その data_source を取りに行った時点でエラー／`on_error` へ〕）。
     ⚠️ **止まるのは QAL 側だけ。ブラウザ側 `transform.filter` は列名を一切見ない**＝存在しない列名を書いてもエラーにも警告にもならず、**その列は「空文字」として比較される**（例＝`neq: "google"` は全行が通り＝絞ったつもりで絞れていない／**`neq: ""` は逆に全行が落ちて 0 件**／`gte`・`contains` も 0 件）。**打ち間違いと、`group_by` で消えた列を後段で絞る形が、どちらも無言で通る。**
     **★次の版から、この「無言」のうち一部が声を出す**（結果は従来どおり変わらない）＝**その列がどの行にも無いとき**、console に `[assistant] filter field "<列名>" is not present in any row (result is empty)` が1回出る（**`[assistant]` が前置される＝ノイズの多い console から拾うときはこの語で絞る**）。`tally` の `when` も同じで、**バケツごとに名指しされる**（`tally "<into>" when field "…"`）。**自分の版で出るかは、製品 ZIP の `js/qahm-assistant-runtime.js` に `warnMissingConditionFields` が在るかで判る**（在れば該当・無ければ従来どおり無言。**版数ヘッダでは判別できない**）。
     ⚠️ **声が出ても「無言で通る」形が全部消えるわけではない**＝**1行でもそのキーが在れば警告は出ない**（値が `null` や空文字でも「在る」扱い）ため、**行によって有無が分かれる列の打ち間違いは今までどおり無言**。また**行が 0 件のときも警告は出ない**（列の有無を判断できないため）。**警告が出ないことを「列名が正しい」の根拠にしないこと。**
   - ⚠️ **数値なのに「文字列」になっている列を `sort` すると、辞書順に並ぶ**（`1, 10, 2`／「上位3」が `5, 3, 20` になる）。**エラーも警告も出ない。** 文字列になる経路は**2つ**＝①**DB から取る材料**（`page_version` 等。実装が `ARRAY_A` で取り出すため、数値の列も文字列で届く）②**`group_by` のキー**（グループキーは文字列で書き戻される＝`group_by` の直後に同じキーで `sort` すると必ず踏む）。
     **自分の版で起きるかは、製品 ZIP の `js/qahm-assistant-runtime.js` の `transformSort` を見れば判る**＝**`typeof … === 'number'` で判定していたら該当**／`isNumericValue` になっていたら解消済み。**版数ヘッダでは判別できない**（`Version:` も `QAHM_ASSISTANT_SPEC_VERSION` も、修正の前後で同じ値のため）。**次の版で解消される。** それまでの確認＝**数値で並べたい列がこの2経路のどちらかなら、並び順を実データで一度目視する。**
   - ⚠️ **`click_event.to_url` は表示用であって、リンクとしての正確さは保証されない。** **この版では、保存時に必ず小文字化される**（受信データを記録する直前で無条件に変換している＝β2 実物で確認。動作確認用サイトの URL 辞書も 58 件すべて小文字＝2026-08-25 実測）。**大文字小文字が意味を持つ URL は、そのまま `a` タグにすると開けない**（実例＝YouTube のチャンネル ID `.../channel/ucnkqpbeihuim8zp9tipzqog`・GA の `_gl=` パラメータ）。**「どこへ出ていったか」の傾向を見る用途に使い、クリックできるリンクとして出すなら別途確かめる。**
     **★次の版から、小文字化されるのは内部リンクだけになる**（外部リンクは**原文のまま**記録される）。内部の判定は3条件＝①`/` 始まりの相対パス ②`https://` ＋計測サイトのドメインの前方一致 ③`http://` ＋同ドメインの前方一致。**判定から漏れた形は「外部＝原文のまま」へ倒れる。** **自分の版で該当するかは、製品 ZIP 直下の `class-qahm-behavioral-data.php` に `normalize_click_transition_case` が在るかで判る**（在れば該当・無ければ従来どおり全部小文字。**版数ヘッダでは判別できない**＝QA Assistants は β を跨いでも `Version: 5.3.0.0` のまま）。
     ⚠️ **切り替わっても、過去に記録された分は小文字のまま戻らない。** クリック先の辞書は URL の文字列そのものを見出しにするため、**同じ外部リンクが「切り替え前＝小文字」と「切り替え後＝原文」の2エントリに分かれる**＝**`to_url` でグループ化する集計は、境界をまたぐ期間だけ同じリンクが2行に割れる**（1行あたりの数字は正しいが、1本に見えない）。**内部リンクは従来どおり小文字化される**（サイト設定に従わせるかは次版で別途扱う）。

5. **期間の比較のしかた**（1本の QAL は時間窓を1つしか持てない）:
   - 両方の月をまたぐ**1つの窓**を張り、`allpv.access_time`（**UNIX 秒**）の `filter` で2つのビューに割る。
   - ページ単位で突き合わせるのは QAL の **`join`**（`if not match: keep-left`）。**ブラウザ側に join は無い**（`transform.lookup` は manifest に静的に書いた辞書を引くだけ）。「2本の data_source を取って後で結合する」ことはできない。
   - **join した相手の列は `keep` に「視図名.列名」（例 `pagestat.bounce`）で書かないと、エラーなしで何も足されない**（`add` に書けば止まるが `keep` 漏れは無言＝実測）。
   - **join のキーは整数 ID 列（`page_id` 等）だけ。** 内部で `(int)` 化され 0 以下は捨てられるため、`utm_source` 等のラベル文字列で join すると **VALID のまま1件もマッチしない**（`keep-left` では右側の列が全て null＝集計は 0・`drop` では 0 行。ID が 0 の行〔direct 流入等〕も同様に落ちる）。名前で束ねたいときは join せず、1つのビューの中で `keep`＋`calc` で集計する。
   - `date_range` が返すのは **`YYYY-MM-DD` の文字列**で、UNIX 秒への変換手段は manifest 側に無い。境界の作り方に確信が持てないときは、**推測で書かず、ユーザー（人間）に確認する**。

6. **集計は QAL 側（`make.calc`）でやる。** ブラウザ側の `transform.group_by` に頼らない。
   理由＝`result.limit` は **group_by より前**に効く単純な切り出しで、`summary_allpage` は「ページ × デバイス × 流入元 …」の粒度で行を返す。生行をブラウザへ運ぶと、**上限を超えたページが丸ごと・無警告で消える**。
   ```json
   "make": { "pages": {
     "from": ["summary_allpage"],
     "keep": ["summary_allpage.page_id", "summary_allpage.title", "summary_allpage.url"],
     "calc": { "pv_count": "SUM(summary_allpage.pv_count)" },
     "add":  ["pv_count"]
   }}
   ```
   （`calc` は `keep` の列で暗黙にグループ化される＝1ページ1行になる）

7. **全クエリに `tracking_id` と期間（`time`）を入れる。** 入れないと全サイト・全期間を舐めて、遅くなるか誤った数字が出る。

8. **比率（CTR 等）は `agg` の `avg` で出さない。** 分子・分母をそれぞれ `sum` してから `calc` で割る（`avg` は「平均の平均」になり間違う）。
   ※ QAL 側（`make.calc`）で使える関数は **`COUNT` / `COUNTUNIQUE` / `SUM` / `AVERAGE` / `MIN` / `MAX`** の6つだけ（`AVG` という綴りは無い）。ブラウザ側（`transform.agg`）とは別の語彙。
   ※ **`COUNTUNIQUE` の結果は、あとから足してはいけない。** `keep` に列を入れると、その粒度ごとに重複が排除される＝**同じセッションが複数の行に現れる**。それをブラウザ側で `sum()` すると二重・三重に数える。実例（2026-08 実測）＝ページ別に `COUNTUNIQUE(allpv.session_id)` を出して合計すると **556**、実際の訪問は **309**（複数ページを見た訪問が重なった）。**サイト全体の値が欲しいときは、`keep` を空にした別の集計で取り直す**（`keep: []` ＋ `calc` のアグリゲート・MUST 6 の形）。VALID のまま、もっともらしい数字になる。

9. **「先月」「今月」などの相対期間は、日付をベタ書きせず `date_range` フォームで受ける。**
   使える相対トークンは `assistant-schema/definitions/step.schema.json` の `field.default` の description が正本＝**`last_7_days` / `last_28_days` / `last_30_days` / `last_90_days` / `this_week` / `last_week` / `this_month` / `last_month` の8つだけ**（次の版から、別の期間欄を基準にする `previous_of:<key>` / `year_ago_of:<key>` も＝下の ⚠️）。
   - **★`last_28_days`（過去28日）／`last_90_days`（過去90日）は次の版（仕様 2.16.0）から**（パートナーの要望で追加）。規則は `last_7_days` / `last_30_days` と同じ（最新の集計済みの日で終わる N 日間）。**カレンダーのプリセットボタンにも「過去28日間」「過去90日間」が出る**＝`default` の初期値は、そのボタンを押した状態と同じ。
     **使うなら `"min_core_version": "2.16.0"` 以上を宣言する**（宣言が無い・低いと検証が `E_REF_MIN_CORE_VERSION` で止める）。宣言があれば、それより古い製品では配信時に「QA Assistants を更新してください」（`E_CORE_TOO_OLD`）で止まる＝利用者に理由が伝わる。**宣言せずに古い製品へ入れると**「知らないトークン」扱いで期間が未選択のまま送信でき、**データ取得が `Invalid time_range` で失敗する**（console に警告は出る）。
   - **今の版には、2つ目の期間（直前の期間・前年の同じ期間）を既定値で入れるトークンは無い**。期間比較で2つ目の期間が要るときは、ユーザーに選ばせるか、**絶対範囲 `"YYYY-MM-DD/YYYY-MM-DD"` を `default` に書く**（これは効く＝動作確認用サイトの実機で反映を確認・2026-08-21）。
   - ⚠️ **★次の版（仕様 2.23.0）から、2つ目の期間の既定値を「別の期間欄を基準に」書ける**＝`default` に **`previous_of:<key>`**（欄 `<key>` の期間の**直前の同じ長さ**）か **`year_ago_of:<key>`**（**前年の同じ期間**）。`<key>` は**同じ form で自分より前に並ぶ `date_range` 欄**の key（英数字と `_` だけ）。画面を開いた瞬間に A＝先月・B＝その直前の同じ日数（9月なら 8/2〜8/31）が入る:
     ```json
     { "key": "period",  "type": "date_range", "default": "last_month" },
     { "key": "compare", "type": "date_range", "default": "previous_of:period" }
     ```
     前年の同じ月と比べるなら `"default": "year_ago_of:period"`。B は普通の期間欄なので、`$compare_start` / `$compare_end`（2.17.0 からは `$compare_days` 等も）がそのまま使える。
     - **直前の同じ長さ**＝**常に同じ日数を直前に置く**（暦の月・週は見ない）＝9月まるごと（30日）の前は **8/2〜8/31**（8月まるごとではない）・今月 10/1〜10/16（16日）の前は 9/15〜9/30・先週の前は先々週・過去28日の前はその前の28日。GA4 の期間比較の「前の期間」と同じ決め方。
     - **前年の同じ期間**＝両端をそれぞれ1年戻すだけ。**月末で終わっても前年側を月末に合わせない**（2025年2月 → 2024-02-01〜02-28＝GA4 の「前年」と同じ）。**曜日はそろわない。**
     - **既定値は開いた瞬間に1回だけ決まる**＝ユーザーが A を手で変えても、B は動かない（普通の期間欄として振る舞う）。A がデータ開始日で縮んだときは、縮んだ後の A が基準になる。結果の全体がデータ開始日より前なら B は無選択（上の絶対範囲と同じ）。
     - **参照先の誤りは検証が `E_REF_DATE_RANGE_REF` で止める**＝欄が無い・自分自身・後ろにある・`date_range` 以外・**参照先に `default` が無い**・key に英数字と `_` 以外。2つの期間の日数が違うとき（B がデータ開始日で縮んだとき・前年が 2/29 をまたぐとき）に注意文を出すなら、`$period_days` と `$compare_days` を `if` の `neq` で比べる。
     - **使うなら `"min_core_version": "2.23.0"` 以上を宣言する**（宣言が無い・低いと検証が `E_REF_MIN_CORE_VERSION` で止める。古い製品は知らないトークンとして B を無選択のまま送信させ、取得が `Invalid time_range` で失敗するため）。
   - ⚠️ **★β3（仕様 2.24.0）から、期間欄1つで比較期間も受け取れる**＝`date_range` 欄に **`compare`** を書くと、カレンダーの中に「比較」の選択が出て、**選んだ期間に比較期間が付いてくる**（期間を変えれば比較期間も変わる。製品の管理画面のカレンダーと同じ部品・同じ計算）:
     ```json
     { "key": "period", "type": "date_range", "default": "last_month", "compare": "previous" }
     ```
     - 値＝**開いたときのモード**（利用者はカレンダーの中で変えられる・保存はされない）。書けるのは5つだけ＝`none`（比較しない）／`previous`（直前の同じ日数＝`previous_of` と同じ計算）／`previous_dow`（前の期間を週単位で戻す＝開始の曜日がそろう）／`year_ago`（前年＝`year_ago_of` と同じ計算）／`year_ago_dow`（364日戻す＝曜日がそろう）。先月＝09-01〜09-30 なら、順に 08-04〜09-02・08-02〜08-31・2025-09-01〜09-30・2025-09-02〜10-01。利用者が手で選ぶ「カスタム」は書けない（固定の日付で比べたいなら上の `previous_of` の2つ目の欄）。
     - **送信すると5つの変数が入る**（使うものを `vars` に宣言する＝`_days` と同じ）＝`<key>_compare_start` / `<key>_compare_end`（`YYYY-MM-DD`）・`<key>_compare_days`（両端を含む日数）・`<key>_compare_mode`（上の値か、利用者が手で選んだときは `custom`）・`<key>_compare_clamped`（`"1"`＝比較期間がデータ開始日をまたいだので開始側を詰めた／`"0"`）。
     - **比較なし（`none` を選んだ・比較期間が成り立たない）のときは5つとも空文字**＝`{"if":{"var":"period_compare_start","is":"empty"},"then":{"goto":"no_compare"}}` で分ける。**`clamped` が `"1"` のときは比較の数字が期間の一部しか無い**＝製品の管理画面はこのとき差を出さない（「比較期間の一部にデータがありません」）。同じ扱いにするなら `if` の `eq: "1"` で分ける。
     - **比較期間の数字は、同じクエリを2本目の data_source として取る**＝`time` を `"start": "$period_compare_start"`・`"end": "$period_compare_end"` にするだけ。差の作り方と表への並べ方は MUST 10 の `rows`。形は `examples/qa-assistant-monthly-compare` をまねる。
     - `previous_of:<key>` との使い分け＝`compare` は「**選んだ期間に付いてくる**」（欄は1つ）／`previous_of` は「**開いた瞬間だけ埋まる2つ目の欄**」（その後は別々に選ぶ）。同じフォームに両方書いてよい。
     - **検証が止める形**＝`date_range` 以外の欄に `compare` を書いた／**`<key>_compare_*` を宣言したのに、その key の欄に `compare` が無い**（書き忘れ＝値が置かれず毎回「比較なし」になる）＝どちらも `E_REF_DATE_RANGE_COMPARE`。値の綴り違い（`true` など）は `E_SCHEMA_*`。**`compare` を書く・`<key>_compare_*` を宣言するなら `"min_core_version": "2.24.0"` 以上を宣言する**（古い製品は欄の知らないキーとして配信時に止まる）。
   - ⚠️ **ただし絶対範囲は、その環境の計測データ開始日に引きずられる。** 範囲**全体**が開始日より前だと**無選択**になり（「(未入力)」のまま submit でき、取得が `Invalid time_range` で失敗する＝動作確認用サイトの実機・2026-08-21）、範囲が開始日を**またぐ**と**開始側が黙って開始日にクランプされる**（指定と違う期間の数字が出る）。＝**絶対範囲を使うなら、その環境にデータが在る期間を選ぶ。**
   - フォームの値は **`<key>` 自身（`YYYY-MM-DD/YYYY-MM-DD` の1本の文字列）に入り、あわせて `<key>_start` / `<key>_end` へ分解される**＝**3つとも使える**（key が `period` なら `$period` / `$period_start` / `$period_end`）。**クエリの `time` に渡すのは分解後の2つ**。**使う分解値は `vars` に宣言する**（宣言せずに参照すると検証が `E_REF_VAR_UNDEFINED` で止める）。
     **★次の版（仕様 2.17.0）から、日数と曜日にも分解される**＝`<key>_days`（両端を含む日数・1日だけなら 1）／`<key>_start_dow`・`<key>_end_dow`（**曜日＝0〜6・日曜＝0**・月曜＝1 … 土曜＝6）。1日あたりの平均（`$total / $period_days`）や、2つの期間の日数・曜日がそろっているかの `if` に使える。**これも `vars` に宣言して使い、宣言した manifest は `"min_core_version": "2.17.0"` 以上が必須**（無いと検証が `E_REF_MIN_CORE_VERSION` で止める。古い製品は値を置かないので、宣言時の既定値のまま＝日数 0 のような静かな誤答になるため）。`vars` 以外（`set` 等）で宣言した場合も同じ。曜日を画面に「月」「火」と出したいときは、`if` の `then.set` で 0〜6 を振り分ける（`transform.lookup` はデータの行の列にしか効かず、この変数には使えない）。
     **★さらに次の版（仕様 2.18.0）から、書式指定子 `{$x|weekday}` で曜日を名前で出せる**＝`{$period_start_dow|weekday}`（0〜6）でも `{$period_start|weekday}`（`YYYY-MM-DD`）でもよい（日本語サイト「火」・英語サイト「Tue」＝**表記はサイトの言語で決まる**・引数なし）。「2026-10-10（土）」なら `{$period_start}（{$period_start|weekday}）`、「10-10（土）」なら `{$period_start|after:"-"}（{$period_start|weekday}）`。日付の形を変える指定子（`M/D` 等）は無い。**使うなら `"min_core_version": "2.18.0"` 以上を宣言する**（宣言が無い・低いと検証が `E_REF_MIN_CORE_VERSION` で止める・古い製品では数字や日付がそのまま出てしまうため）。読めない値（`7`・`abc` 等）はそのまま出る。
   - 固定日付（`"2026-02-03"`）を書くと翌月には陳腐化する。
   - ⚠️ **QAL（`make` の `filter`）の `eq`/`neq` は型まで厳密**（`1` と `"1"` は別物＝0件になり得る）。分類列の絞り込みは配列形（IN・ゆるい比較）が安全。ブラウザ側 `transform.filter` は文字列に揃えて比べる＝**同じ書き方でもサーバー側と結果が違う**（実測）。
   - ⚠️ **`transform.lookup` はキー未ヒットのとき null を返す**（エラーにならない）＝表示前に `if` か既定値で受ける。

10. **合計値（`set_var`）は `limit` の前に置く。** 後ろに置くと「合計 N PV」が**上位 N 件の合計**になる（画面の数字が静かに嘘になる）。
    `set_var`／全体 `calc`（`scope: "global"`）の式に書けるのは **①純集計形＝`sum` / `avg` / `count` / `count_distinct` / `min` / `max` / `first`（列名）** か **②`$var` だけで組んだ式**（`$a / $b * 100` 等）の2形——「N行目を参照する」機能は無い。**行の列名を含む式を global に書くと検証器が `E_REF_EXPR_SYNTAX` で止め、runtime も警告＋null（表示は —）**になる（黙って先頭行で計算することはもう無い）。逆に **行スコープの `calc` に純集計形（`sum(pv)`）を書くのも止められる**（合計が欲しいなら `scope: "global"`）。上位 N 件を1件ずつ使うとき（ランキングの個別発表等）は、**`first()` で先頭を変数に取り → その ID を `filter` の `neq` で除外 → 次の先頭を取る**、を繰り返す。
    ⚠️ **ただしこの繰り返しは、その data_source の `into` を空にする。** transform は**全ステップを最後まで通してから**結果を `into` に入れる（`js/qahm-assistant-runtime.js` の `applyTransforms` → `this.vars[ ds.into ] = data`）ので、5件ぶん `neq` で除外し終えた時点の**残り**が `into` に入る。⇒ **同じ `into` を後ろの `table` / `chart` でも使うと、除外した分だけ行が減る**（5件すべて除外すれば空・3件だけ発表すれば「5件のはずが2件しか出ない」）。**VALID・エラーなし・console 無言**＝静かに崩れる。**一覧表と個別発表を両方出したいなら、同じクエリを `into` を分けて2回取る**（除外ループは片方だけで回す）。※ **行の集合を別の変数へ退避する書き方は無い**＝`transform` の形は9つ（`sort`／`limit`／`filter`／`group_by`＋`agg`／`tally`／`calc`／`lookup`／`set_var`／`extract_host`）で、`set_var` に入るのは**スカラー**だけ。
    ⚠️ **`transform.sort` は複数キーを書けるが、実行時は最初の列しか使わない**（検証は通る・エラーなし＝実測）。多キーの並べ替えは 1 キーずつ `sort` を2段に分ける（先に2番目の列、次に1番目の列）。
    ⚠️ **データ0件のとき集計（`min`/`max`/`first` 等）は空欄になり、console に `[assistant] … has no numeric values` の警告が出る**（黙って 0 にはならない＝実測）。
    ⚠️ **`first()` は空文字の値も普通に拾う**（空の扱いは関数ごとに違う＝`count_distinct` は空文字を数えない）。流入元（`utm_source` 等）は**直接流入が空文字**で、サイトによっては空が最多（実測＝604PV・81.8%）＝「いちばん多いのは **** で…」と壊れた文になる。**空があり得る列を文章に使うときは、先に `filter` の `neq: ""` で空を除くか、空だった場合の文言を `if` の `not_empty` で分岐する**（`not_empty` は `if` 専用の演算子＝`transform.filter` には無い。filter で書ける演算子は `eq` / `neq` / `gt` / `gte` / `lt` / `lte` / `in` / `contains` の8つだけ）。⚠️ **この `neq: ""` で列名を打ち間違えると、警告も出ずに 0 件になる**＝MUST 4 の「ブラウザ側 `transform.filter` は列名を見ない」。
    ⚠️ **書式指定子は「見せ方」だけで、値を変換しない。** **`percentage` は 100 倍しない**（`0.0345` は `3.5%` ではなく **`0.0%`**）＝率を % で出すなら **`calc` の式で `* 100` してから**渡す。**`integer` は `parseInt`**＝`"1,234"` のような桁区切り入りの文字列は **`1`** になる（数値で渡す）。**`round` は `parseFloat`**＝`"3.567abc"` のような混ざった文字列も数値として丸める（＝壊れた値に気づけない）。**桁は `{$x|round:2}` のように `:` で指定する（既定は 0 桁）**＝`{$x|round}` は `3.567` を **`4`** にする。ここまでの見せ方の違いは、いずれも**エラーにも警告にもならない**。
    ※ **`round(2)` のような括弧の形は書けない**＝展開されず文字のまま画面に出るので、検証が `E_REF_TEMPLATE_SYNTAX` で止める。`round` を使う manifest は `min_core_version` 2.14.0 以上の宣言が要る（上の「唯一の例外」の台帳どおり）。
    ※ **★次の版（仕様 2.21.0）から、書式指定子 `signed` で正の値に「+」を付けられる**＝`前月比 {$diff|signed:1} ポイント` は、2.63 なら「前月比 +2.6 ポイント」、-1.24 なら「-1.2」（「+」を付けるための `if` の振り分けは要らない）。**桁は `signed:1` のように `signed` 自身に書く**（0〜6・丸め方は `round` と同じ）＝**指定子は1つしか付けられない**ので `{$diff|round:1|signed}` は書けない（検証が `E_REF_TEMPLATE_SYNTAX` で止める）。**桁を省くと丸めない**（`{$diff|signed}` は 2.6333 を「+2.6333」＝省くと 0 桁に丸める `round` とは逆）。**0 には符号を付けない**（-0.04 を `signed:1` にすると「0.0」＝「-0.0」は出ない）。数値に読めない値（「データなし」等）はそのまま出る。**使うなら `"min_core_version": "2.21.0"` 以上を宣言する**（古い製品は知らない指定子を素通りさせ、「+」も桁も効かない「2.63」がエラーなしで出るため）。翻訳文の中で使った場合も検証が要求する。
    ※ **★β3（仕様 2.25.0）から、表とグラフで「今・前・差」を並べる書き方が3つ増える**（どれか1つでも使うなら `"min_core_version": "2.25.0"` 以上を宣言する）:
      - **表の列の `"type_options": { "signed": true }`**＝`integer` / `float` / `percentage` の列で、正の値に `+` が付く（`+1,234`・`+2.60`・`+12.3%`）。規則はテンプレートの `signed` と同じ＝**表示の桁に丸めた結果が 0 なら付けない**・負はそのまま。CSV・JSON の書き出しにも `+` が入る。増減の色は付かない（色は `color_thresholds`）。**値は `true` / `false` だけ・この3型の列だけ**＝文字列の `"true"` やほかの型は検証が `E_REF_TABLE_SIGNED` で止める（`"true"` は黙って無視されるため）。古い製品は黙って `+` を付けない。
      - **`tables.<name>.rows`**＝データの行（`source`）の代わりに、**集計済みの変数を1行ずつ並べた表**を書く（「セッション数／ユーザー数／PV × 今・前・差」のような表）。**`source` と `rows` はどちらか一方**（両方・どちらも無しは `E_SCHEMA_*`）。
        ```json
        "rows": [
          { "metric": "t:metric.sessions", "now": "$now_sessions", "prev": "$prev_sessions", "diff": "$diff_sessions" },
          { "metric": "t:metric.pv",       "now": "$now_pv",       "prev": "$prev_pv",       "diff": "$diff_pv" }
        ]
        ```
        - 値は3種だけ＝**`"$var"`**（変数の値まるごと＝数値は数値のまま＝並べ替え・桁区切り・`signed` が効く）／**`"t:キー"`**／**リテラル**。**`{$x}` のテンプレートは書けない**（文字列になって並べ替えも `signed` も効かなくなる）。**行のキーは `columns` の `key` のどれか**（列に無いキー＝打ち間違えた列は、空の列として黙って隠れるため止める）。どちらも検証が `E_REF_TABLE_ROWS` で止める。`$var` は宣言が要る（`E_REF_VAR_UNDEFINED`）。列のキーを行に書かないのはよい（そのセルは空）。
        - 行の値は、**`table` のステップを置いた時点**の変数の値。
        - **差は行の中では計算できない**＝先に変数にしておく。`set_var` は fetch の transform の中にしか置けないので、**2本目の fetch の transform で、1本目の変数と引き算する**（`{ "set_var": "diff_sessions", "expr": "$now_sessions - $prev_sessions" }`・率なら `($now_sessions - $prev_sessions) / $prev_sessions * 100`）。`$var` だけの式は行が 0 件でも評価される。形は `examples/qa-assistant-monthly-compare` をまねる。
      - **グラフの `series[].dashed: true`**＝折れ線の系列を点線にする（比較期間の系列など・`line` の系列だけに効く）。あわせて**この版から、系列の値が無い（`null`・未定義・空文字）点は描かない**＝折れ線は点を描かず線を切る・棒（`bar`・`hbar`）は棒を描かない（それまでは 0 の点・0 の棒として描いていた）。`pie` / `doughnut` は従来どおり 0。
    ※ **同じ規則の実装が2つある**＝テンプレート（`{$x|percentage}`）は `js/qahm-assistant-runtime.js` の `formatValue`／表の `columns[].type` は `js/qahm-assistant-table.js` の型。**100 倍しないのは両方とも同じだが、既定の小数桁が違う**（テンプレート 1 桁・表 2 桁＝表は `type_options.precision` で変えられる）。`round` / `before` / `after`（次の版から `weekday` / `signed` も）は**テンプレート専用**（表の型には無い。表で `+` を付けるのは β3 からの列の `type_options.signed`＝上の ※）。
    ※ **行スコープの `calc` からは、先に `set_var` した `$var` を参照できる**（`{"calc":"share","expr":"session_count / $total_sessions * 100","scope":"row"}` のように、その行の列と全体の合計を混ぜてよい＝実測）。禁じられているのは**行スコープに純集計形を書くこと**（上記）であって、`$var` の参照ではない。ただし **`set_var` の順序が意味を持つ**＝参照する `$var` は、その `calc` より**前**の行で作っておく。

11. **schema が受け付けない書き方は、runtime に実装があっても使えない。**
    実例（2026-08 時点）＝`data_sources[*].queries[]`（複数クエリ）・`type: "native_qal"`・`transform` の `combine` / `for_each` / `manifest.params`（＝多クエリ層・意図して凍結＝`transform-step.schema.json` の `$comment` に理由）。**runtime のコードには存在するが schema が拒否する**（＝到達不能な機能）。※ `transform` の `extract_host` は 2026-07-22 に正式語彙へ昇格済み（schema にある・`min_core_version` 2.12.0 以上を宣言）。runtime を読んで「使えそう」と思ったら、**まず捨て駒の manifest を作って検証にかける**（MUST 1 のラッパーで数秒で分かる）。

### 動く形にする

12. **`id` はディレクトリ名と一致させる**（`qa-assistant-{name}`）。ずれると読み込まれない。
13. **`start` シーンを必ず定義する。** 無いと起動時に何も出ない。
14. **`config_read` / `config_write` を使うなら `permissions` を宣言する。** 宣言なしは実行時にサーバーが拒否する。
    さらに2つ: **①「全サイト表示」（`tracking_id: "all"`）では config の読み書きは常にサーバーが拒否される**（設定はサイト個別）——読み失敗時の `on_error` 導線を必ず作る。**②保存したオブジェクトの中身をブラウザ側で取り出す手段は無い**（`if`／`set`／テンプレートはドットパス非対応）——**保存する値は「読み戻してそのまま使える形」**（例: フィルタ条件そのもの）にする。
15. **翻訳は `lang/ja.json` と `lang/en.json` の両方を作る（ネスト構造で）。** 翻訳 JSON はネスト（`{"meta":{"name":…}}`）が必須＝フラットな `"meta.name"` キーは not found になる（実測）。 片方に無いキーは画面に `t:xxx` の生キーが出る。検証器は `lang/*.json` を読んで **`t:` キーの欠落を `E_REF_TRANSLATION` で止める**（`lang/` が無いと検査自体がスキップされ、ラッパーがその旨を表示する＝スキップを合格と読まない）。
    ⚠️ **ただしキーの存在は「`en` と `ja` をまとめた集合」で見る**（`en` も `ja` も1つも無いときだけ、全 locale をまとめた集合にフォールバックする）。だから **`ja` にだけ在るキーは VALID を通る**し、逆に **`fr` にだけ在るキーは `en`/`ja` があると弾かれる**（どちらも実測）。つまり**上の「片方に無いキーは生キーが出る」事故は、検証をすり抜ける**。**`ja` と `en` を両方そろえる責任は書き手にある。** 一方、**翻訳文の中身**（テンプレートの書式指定子・`min_core_version` の要求）は **locale ごとに**検査される。
    ⚠️ **翻訳文の中の `{$var}` が実在する変数かは検査されない**（manifest に直接書いたテンプレートは `E_REF_VAR_UNDEFINED` で止まる）。**画面に出る文はほぼ全部 `t:` 参照なので、変数名のタイポは自分で確かめる**（runtime は console に警告を出し、画面はその部分が空になる）。
16. **`goto` の飛び先が実在するシーンか、検証で確かめる。** 検証器が `E_REF_SCENE` で止める（`did you mean` 付き）。古い製品 ZIP（ラッパーが「古い世代」と表示）では見ていないので、そのときだけ自分で確認する。
17. **`icon.png` を同梱する**（用意が無ければ `assets/default-icon.png` をコピー）。無いと管理画面でアイコンが欠ける。

---

## 3. 作るもの

```
qa-assistant-{name}/
├── manifest.json                  動作定義（本体）
├── lang/ja.json  lang/en.json     翻訳（両方必須）
├── qa-assistant-{name}.php        WordPress プラグインヘッダー
└── icon.png                       アイコン
```

進め方＝**要望を聞く（質問攻めにしない）→ 構成を一度だけ確認する → 生成 → 検証 → VALID を見せて完了**。
ユーザーとの会話では技術用語（manifest・schema・QAL・step 等）を出さず、日常語で話す。

---

## 4. このファイルについて

ここに書いてよいのは「**間違えると壊れる規則**」と「**どこを読むか**」だけ。仕様の説明・機能の網羅・見本の再掲は書かない（コードから読めるものを二重に持たない＝乖離を作らないため）。
