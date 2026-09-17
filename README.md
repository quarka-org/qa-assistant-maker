# QA Assistant Maker

QA Assistants / QA ZERO 用のアシスタントプラグインを **AI で生成する**ためのツールです。

**正本は製品のコードそのもの**——このリポジトリは仕様書の写しを持ちません。人が維持するのは指示書2ページ（`AGENTS.md`・`guide.html`）だけです。

## 必要なもの

| もの | 用途 | 補足 |
|------|------|------|
| **AI** | アシスタントを書く・検証を回す | 2種類のどちらでも作れます。**フォルダの中で動かせる AI**（AI 自身がこのフォルダを開いて読み、検証コマンドを打つ。Claude Code など。PC 上でも Web 上でも構いません）と、**フォルダを ZIP で渡す AI**（AI がフォルダを見られないので、チャットに ZIP を添付する。ChatGPT など）。動作確認済みは Claude Code と ChatGPT（有料版）。無料版のチャット AI は対象外です（確認しているのは有料版だけです） |
| **製品のコード** | AI が読む正本。検証もこの中の検証エンジンで動きます | **サイトに入れてある版と同じもの**を使います。配布 ZIP を展開したフォルダ（`qa-heatmap-analytics/` か `qa-zero/`）、または自分のサイトに入れてあるプラグインのフォルダのコピー |
| **PHP 7.4 以上** | 検証コマンド `validate.php` を動かす | **AI が検証コマンドを打つ場所に要ります**。手元の PC で AI を動かすなら PC に。Web 上の AI（Claude Code の Web 版や ChatGPT）なら AI 側にあるので、手元には不要 |

**動作確認した製品の版**＝この一式は **QA Assistants 5.3.0.0** で確認しています（2026-09）。製品の版が変わったときは、`AGENTS.md` の中の「この版では／次の版から」の注記を確認してください。

## 使い方

### 準備（どちらの AI でも共通）

1. このリポジトリをクローンする（git が無ければ「Code → Download ZIP」で展開する）
2. **製品のコードのフォルダ**をリポジトリ直下に置く

```
qa-assistant-maker/
├── AGENTS.md                 指示書（AI が読む）
├── validate.php              検証プログラム
├── examples/                 見本4本
├── qa-heatmap-analytics/     ← 2 で置く製品のコード（サイトと同じ版）
└── qa-assistant-{name}/      ← AI がここに作る
```

### AI に作らせる（使う AI に合わせて A か B）

**A. フォルダの中で動かせる AI（Claude Code など）**

1. リポジトリ直下で AI を起動する。指示書 `AGENTS.md` が自動で読み込まれます（Codex・Cursor・GitHub Copilot のコーディングエージェント・Jules・Windsurf・Zed・Aider など。Claude Code は `CLAUDE.md` 経由で同じものを読みます。Gemini CLI は設定が要ります。対応しているツールの最新の一覧は [agents.md](https://agents.md/) を参照）。自動で読み込まれない AI では、最初に「`AGENTS.md` に従って作って」と伝えてください。
2. 「〜を分析するアシスタントを作りたい」等、ふつうの日本語で頼む。AI は構成を一度だけ確認してから生成に入ります。
3. AI が自分で `php validate.php ./qa-heatmap-analytics ./qa-assistant-{name}` を回して **VALID** を報告します。示されなければ「検証は通った？」と聞いてください。

**B. フォルダを ZIP で渡す AI（ChatGPT など）**

1. 2 で製品のコードを置いた状態のリポジトリのフォルダを、**フォルダごと1つの ZIP** にする（展開後 20MB 前後・圧縮すると 7MB 程度・1,300 ファイルほど）。製品のコードが同じ ZIP に入っていないと検証が動きません。
2. 新しいチャットにその ZIP を添付し、次の文と一緒に「作ってほしいもの」を送る。フォルダを見られない AI は `AGENTS.md` を自動では読まないので、この文の1行目がその代わりです。

```
添付の ZIP は「QA Assistant Maker」のフォルダを丸ごと固めたものです。展開して、直下の AGENTS.md を AI 向けの指示書として読み、これに従ってください。製品のコードは同じ ZIP の中の qa-heatmap-analytics フォルダに入っています。見本は examples フォルダにあります。

作ってほしいもの＝（ここに頼みたい内容を書く）

検証コマンド（php validate.php ./qa-heatmap-analytics ./qa-assistant-作ったフォルダ名）はあなたの側で実行し、VALID になった出力をそのまま貼ってください。INVALID なら直して、VALID になるまで繰り返してください。指示書の決まりで作れない項目があれば、勝手に別のものに置き換えず、何が作れないかを先に報告してください。

できたら、アシスタントのフォルダ（manifest.json、lang/ja.json、lang/en.json、PHP、icon.png）を丸ごと1つの ZIP にして、ダウンロードできるようにしてください。
```

3. AI は ZIP の中の指示書と見本を読み、5ファイルを作って自分で検証を回し、VALID の出力とアシスタントの ZIP を返します。作れない項目があれば先に報告してくるので、別の内容に変えて続けてください。ZIP を展開すると `qa-assistant-{name}/` ができます（2026-09 に ChatGPT 有料版で確認）。

### 仕上げ（どちらの AI でも共通）

1. **WordPress に入れる**＝`qa-assistant-{name}/` を `wp-content/plugins/` に置いて有効化する（ZIP のまま「プラグインのアップロード」でも可）。「AI アシスタント」画面にカードとして現れます。
2. **実データで数字を見る**＝VALID は「壊れていない」であって「数字が正しい」ではありません。管理画面の数字と桁が合っているか、期間を広げて数字が減らないかを見てください。おかしければ AI に「この数字が管理画面と合わない。材料の選び方を実コードで確認して」と伝えます。

人向けの詳しい説明（渡すもの・正本マップ・「VALID」の意味・動かないときの3チェック）は **[guide.html](./guide.html)** を参照してください。

## ファイル構成

```
qa-assistant-maker/
├── AGENTS.md              AI 向け指示書（間違えると壊れる規則＋どこを読むか、だけ）
├── CLAUDE.md              上の1行だけの読み込み用（Claude Code はこれを見る）
├── guide.html             人間向け使い方ページ
├── validate.php           検証ラッパー（製品のコードに入っている検証エンジンを呼ぶだけの glue＝構造・参照の整合・翻訳・パッケージ構成の4検査を全部呼ぶ）
├── assets/
│   └── default-icon.png   デフォルトアイコン
└── examples/              動く見本4本（qa-assistant-sample / qa-assistant-form-sample / qa-assistant-hitokoto / qa-assistant-lp-bounce）
```

- **仕様の説明書はこのリポジトリにありません。** schema・データ材料の一覧（semantics 宣言つき）・QAL 文法・動作コードは、すべて**製品のコードの中の実物**を読みます（`AGENTS.md` がパスを案内します）。

## 生成されるファイル

| ファイル | 内容 |
|---------|------|
| `manifest.json` | アシスタントの動作定義 |
| `lang/ja.json` | 日本語翻訳 |
| `lang/en.json` | 英語翻訳 |
| `qa-assistant-{name}.php` | WordPress プラグインヘッダー |
| `icon.png` | アイコン（無指定なら `assets/default-icon.png` をコピー） |

## 動作環境

- 生成したアシスタントの実行: QA Assistants / QA ZERO（WordPress プラグイン）。QAL でデータを取るアシスタントも両環境で動作（`wp_posts` マテリアルのみ QA Assistants 限定）。
- 検証ラッパー: PHP 7.4+（製品のコードに入っている検証エンジンを使用）

## ライセンス

**GPL-2.0-or-later**（全文は `LICENSE`）。この一式で生成したアシスタントは、**各自の著作権表示のもとで同じライセンスで配布**できます。

Licensed under **GPL-2.0-or-later** (see `LICENSE`). Assistants generated with this tool may be distributed under the same license, under each author's own copyright notice.

## 由来・問い合わせ

本リポジトリは開発元の Schema・サンプルを反映した配布の器です。最新化は適時行われます。

- **開発元**＝ウェブジョブズ（QA Assistants / QA ZERO の開発元）
- **質問・不具合**＝このリポジトリの [Issues](https://github.com/quarka-org/qa-assistant-maker/issues) へどうぞ
