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

1. **`examples/`（この一式に同梱）** — 動く見本4本。**近い型をまねる**：
   - `qa-assistant-sample`＝データを見せる型（ページ別ランキング＋メモ保存）
   - `qa-assistant-form-sample`＝入力・設定だけの型
   - `qa-assistant-hitokoto`＝**データ源ゼロの最小形**（ボタン1つで一言。どの環境でも動く）
   - `qa-assistant-lp-bounce`＝**最大形**（`data_sources` 2本＝サイト全体〔`keep: []`〕＋ページ別／`scorecard`／`table` の `color_thresholds` と **`row_action`**（行クリックで詳細へ）／`tally`／`if` の `all`・`else`）
   ⚠️ **見本は「動く見本」であって「正しい指標の見本」ではない**（MUST 3・4 を必ず読む）。
2. **構造（何が書けるか）** — `assistant-schema/manifest.schema.json` → `assistant-schema/definitions/*.json`。
   **schema に無い書き方は存在しない**（runtime に実装があっても、schema が許さないなら使えない）。
   **★唯一の例外＝テンプレート文字列の中の書式指定子（`{$var|round:1}` 等）。** これは schema が見ない層なので、**使えるものの一覧は `assistant-schema/vocab-ledger.json` の `format_specifiers`**（`min_core_version` つき）＝**使う前にここを見る**（`integer` / `float` / `percentage` / `duration` / `round` / `before` / `after`）。
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

   ⚠️ **`eq` / `neq` は「どこに書くか」で変わる**（一括で対象外ではない）:
   - **`transform.filter` の `eq` / `neq`** … 値が何であっても（`"$var"` でも数値でも）**版数の宣言は要らない**。
   - **`if` の `eq` / `neq`** … **ただの文字列リテラル**（`"4"` 等）と `null` なら要らない。**`"$var"`（`"$sys.*"` を含む）は演算子によらず `2.11.0`**（表の 2.11.0 行のとおり）。**数値・真偽値のリテラルも `2.11.0`**——旧コアの `if` は厳密比較（`===`）で、フォームの値は必ず文字列＝**数値リテラルとの比較は旧コアでは永遠に成立しなかった**ため。
     実測（β2・2026-08-25）＝`if` は `value: "4"`（文字列）・`value: null` が VALID／**`value: "$satisfaction"`・`value: 4`・`value: true` は `E_REF_MIN_CORE_VERSION` で INVALID**。`transform.filter` は `eq: "$total_pv"`・`neq: "$total_pv"` とも VALID（**`gte: "$total_pv"` だけ INVALID**）。**同梱の見本 `qa-assistant-form-sample` が満足度を数値 `4` と比べている**ので、`gte` を `eq` に変えるだけで踏む。
   ⚠️ **`if` に `all`／`else`／`then.set`／`is: "in"` を1つでも使ったら `2.13.0`**——`$var` 比較の `2.11.0` から類推して低く宣言すると `E_REF_MIN_CORE_VERSION` で止まる（初見が実際に踏んだ・2026-08-25）。

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
   使える相対トークンは `assistant-schema/definitions/step.schema.json` の `field.default` の description が正本＝**`last_7_days` / `last_30_days` / `this_week` / `last_week` / `this_month` / `last_month` の6つだけ**。
   - **「先々月」に相当するトークンは無い**。期間比較で2つ目の月が要るときは、ユーザーに選ばせるか、**絶対範囲 `"YYYY-MM-DD/YYYY-MM-DD"` を `default` に書く**（これは効く＝動作確認用サイトの実機で反映を確認・2026-08-21）。
   - ⚠️ **ただし絶対範囲は、その環境の計測データ開始日に引きずられる。** 範囲**全体**が開始日より前だと**無選択**になり（「(未入力)」のまま submit でき、取得が `Invalid time_range` で失敗する＝動作確認用サイトの実機・2026-08-21）、範囲が開始日を**またぐ**と**開始側が黙って開始日にクランプされる**（指定と違う期間の数字が出る）。＝**絶対範囲を使うなら、その環境にデータが在る期間を選ぶ。**
   - フォームの値は **`<key>` 自身（`YYYY-MM-DD/YYYY-MM-DD` の1本の文字列）に入り、あわせて `<key>_start` / `<key>_end` へ分解される**＝**3つとも使える**（key が `period` なら `$period` / `$period_start` / `$period_end`）。**クエリの `time` に渡すのは分解後の2つ**。
   - 固定日付（`"2026-02-03"`）を書くと翌月には陳腐化する。
   - ⚠️ **QAL（`make` の `filter`）の `eq`/`neq` は型まで厳密**（`1` と `"1"` は別物＝0件になり得る）。分類列の絞り込みは配列形（IN・ゆるい比較）が安全。ブラウザ側 `transform.filter` は文字列に揃えて比べる＝**同じ書き方でもサーバー側と結果が違う**（実測）。
   - ⚠️ **`transform.lookup` はキー未ヒットのとき null を返す**（エラーにならない）＝表示前に `if` か既定値で受ける。

10. **合計値（`set_var`）は `limit` の前に置く。** 後ろに置くと「合計 N PV」が**上位 N 件の合計**になる（画面の数字が静かに嘘になる）。
    `set_var`／全体 `calc`（`scope: "global"`）の式に書けるのは **①純集計形＝`sum` / `avg` / `count` / `count_distinct` / `min` / `max` / `first`（列名）** か **②`$var` だけで組んだ式**（`$a / $b * 100` 等）の2形——「N行目を参照する」機能は無い。**行の列名を含む式を global に書くと検証器が `E_REF_EXPR_SYNTAX` で止め、runtime も警告＋null（表示は —）**になる（黙って先頭行で計算することはもう無い）。逆に **行スコープの `calc` に純集計形（`sum(pv)`）を書くのも止められる**（合計が欲しいなら `scope: "global"`）。上位 N 件を1件ずつ使うとき（ランキングの個別発表等）は、**`first()` で先頭を変数に取り → その ID を `filter` の `neq` で除外 → 次の先頭を取る**、を繰り返す。
    ⚠️ **ただしこの繰り返しは、その data_source の `into` を空にする。** transform は**全ステップを最後まで通してから**結果を `into` に入れる（`js/qahm-assistant-runtime.js` の `applyTransforms` → `this.vars[ ds.into ] = data`）ので、5件ぶん `neq` で除外し終えた時点の**残り**が `into` に入る。⇒ **同じ `into` を後ろの `table` / `chart` でも使うと、除外した分だけ行が減る**（5件すべて除外すれば空・3件だけ発表すれば「5件のはずが2件しか出ない」）。**VALID・エラーなし・console 無言**＝静かに崩れる。**一覧表と個別発表を両方出したいなら、同じクエリを `into` を分けて2回取る**（除外ループは片方だけで回す）。※ **行の集合を別の変数へ退避する書き方は無い**＝`transform` の形は9つ（`sort`／`limit`／`filter`／`group_by`＋`agg`／`tally`／`calc`／`lookup`／`set_var`／`extract_host`）で、`set_var` に入るのは**スカラー**だけ。
    ⚠️ **`transform.sort` は複数キーを書けるが、実行時は最初の列しか使わない**（検証は通る・エラーなし＝実測）。多キーの並べ替えは 1 キーずつ `sort` を2段に分ける（先に2番目の列、次に1番目の列）。
    ⚠️ **データ0件のとき集計（`min`/`max`/`first` 等）は空欄になり、console に `[assistant] … has no numeric values` の警告が出る**（黙って 0 にはならない＝実測）。
    ⚠️ **`first()` は空文字の値も普通に拾う**（空の扱いは関数ごとに違う＝`count_distinct` は空文字を数えない）。流入元（`utm_source` 等）は**直接流入が空文字**で、サイトによっては空が最多（実測＝604PV・81.8%）＝「いちばん多いのは **** で…」と壊れた文になる。**空があり得る列を文章に使うときは、先に `filter` の `neq: ""` で空を除くか、空だった場合の文言を `if` の `not_empty` で分岐する**（`not_empty` は `if` 専用の演算子＝`transform.filter` には無い。filter で書ける演算子は `eq` / `neq` / `gt` / `gte` / `lt` / `lte` / `in` / `contains` の8つだけ）。⚠️ **この `neq: ""` で列名を打ち間違えると、警告も出ずに 0 件になる**＝MUST 4 の「ブラウザ側 `transform.filter` は列名を見ない」。
    ⚠️ **書式指定子は「見せ方」だけで、値を変換しない。** **`percentage` は 100 倍しない**（`0.0345` は `3.5%` ではなく **`0.0%`**）＝率を % で出すなら **`calc` の式で `* 100` してから**渡す。**`integer` は `parseInt`**＝`"1,234"` のような桁区切り入りの文字列は **`1`** になる（数値で渡す）。**`round` は `parseFloat`**＝`"3.567abc"` のような混ざった文字列も数値として丸める（＝壊れた値に気づけない）。**桁は `round(2)` のように指定する（既定は 0 桁）**＝`{$x|round}` は `3.567` を **`4`** にする。いずれも**エラーにも警告にもならない**。
    ※ **同じ規則の実装が2つある**＝テンプレート（`{$x|percentage}`）は `js/qahm-assistant-runtime.js` の `formatValue`／表の `columns[].type` は `js/qahm-assistant-table.js` の型。**100 倍しないのは両方とも同じだが、既定の小数桁が違う**（テンプレート 1 桁・表 2 桁＝表は `type_options.precision` で変えられる）。`round` / `before` / `after` は**テンプレート専用**（表の型には無い）。
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
