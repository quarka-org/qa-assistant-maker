# QA Assistant Maker

QA Assistants / QA ZERO 用のアシスタントプラグインを **AI で生成する**ためのキットです。

**正本は製品のコードそのもの**——このリポジトリは仕様書の写しを持ちません。人が維持するのは指示書2ページ（`AGENTS.md`・`guide.html`）だけです。

## 必要なもの

- **AI エージェント**——`AGENTS.md`（この一式の指示書）のように、**フォルダの中のファイルを読んで、検証コマンドを走らせられる**もの。**動作確認済みは Claude Code**（`AGENTS.md` を置いてあるので、`AGENTS.md` を読む AI ならそのまま使えます）。コマンドを実行できない AI でも生成はできます（その場合、検証の1行だけ人が打ってください）。
- **製品のコードを手元に置く**——**配布 ZIP を展開したフォルダ**（`qa-heatmap-analytics/` か `qa-zero/`）、または**自分のサイトに入れてあるプラグインのフォルダをそのままコピー**したもの。検証（`validate.php`）は製品側の検証エンジンを手元で読んで動くため、実物が要ります。**サイトに入れてある版と同じものを置いてください**（対応版数がずれると、通るはずのものが通らなくなります）。
- **PHP 7.4 以上**（検証に使います）

**動作確認した製品の版**＝この一式は **QA Assistants 5.3.0.0** で確認しています（2026-09）。製品の版が変わったときは、`AGENTS.md` の中の「この版では／次の版から」の注記を確認してください。

### フォルダを開けない AI（チャット型）で作るとき

**ChatGPT（有料版・Web）でも作れます。** このフォルダを、**製品のコードを置いた状態のまま1つの ZIP にして添付**し、次の文と一緒に「作ってほしいもの」を送ってください。ZIP を展開して指示書と見本を読み、5ファイルを作り、検証コマンドを自分で実行して VALID を出し、できたフォルダを ZIP で返すところまで自走することを 2026-09 に確認しています（製品のコード込みで**展開後 20MB 前後・圧縮すると 7MB 程度**・1,300 ファイルほどになります）。

```
添付の ZIP は「QA Assistant Maker」のフォルダを丸ごと固めたものです。展開して、直下の AGENTS.md を AI 向けの指示書として読み、これに従ってください。製品のコードは同じ ZIP の中の qa-heatmap-analytics フォルダに入っています。見本は examples フォルダにあります。

作ってほしいもの＝（ここに頼みたい内容を書く）

検証コマンド（php validate.php ./qa-heatmap-analytics ./qa-assistant-作ったフォルダ名）はあなたの側で実行し、VALID になった出力をそのまま貼ってください。INVALID なら直して、VALID になるまで繰り返してください。指示書の決まりで作れない項目があれば、勝手に別のものに置き換えず、何が作れないかを先に報告してください。

できたら、アシスタントのフォルダ（manifest.json、lang/ja.json、lang/en.json、PHP、icon.png）を丸ごと1つの ZIP にして、ダウンロードできるようにしてください。
```

- **チャット型は `AGENTS.md` を自動では読みません。** 上の文の1行目がその代わりです（この一言が抜けると、指示書を読まないまま作り始めます）。
- **無料版は対象外**です（確認しているのは有料版だけです）。
- 返ってきた ZIP を展開すると `qa-assistant-（名前）/` ができます。そのまま WordPress の「プラグインのアップロード」に使えます。手元に PHP があれば `php validate.php` を1回かけてから入れると確実です。

## 使い方

1. このリポジトリをクローンする
2. **製品のコードのフォルダ**（上記）をリポジトリ直下に置く
3. リポジトリ直下で **AI エージェントを起動する**
   - **エージェント型（フォルダを開ける AI）は、多くが `AGENTS.md` を自動で読み込みます**（Codex・Cursor・GitHub Copilot のコーディングエージェント・Jules・Devin・Windsurf・Zed・Aider など。**対応しているツールの最新の一覧は [agents.md](https://agents.md/) が正本**です）。**Claude Code は `CLAUDE.md` 経由**で同じものを読みます。**設定が要るもの・自動で読まないもの**（Gemini CLI 等）には「`AGENTS.md` を読んでから作って」と伝えてください。
   - **チャット型（ChatGPT の Web 等）は自動では読みません**＝上の「フォルダを開けない AI（チャット型）で作るとき」に従ってください。
4. 「〜を分析するアシスタントを作りたい」等、ふつうの日本語で伝える
5. AI が生成 → **`php validate.php <製品のコードのフォルダ> <生成物>` で検証** → VALID を確認して完成（**AI がコマンドを実行できない場合は、この1行は人が打ってください**）

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

- **仕様の説明書はこのリポジトリにありません。** schema・データ材料の一覧（semantics 宣言つき）・QAL 文法・動作コードは、すべて**製品のコードの中の実物**を読みます（`AGENTS.md` がパスを案内します）。写しが無いので古くなりません。

## 生成されるファイル

| ファイル | 内容 |
|---------|------|
| `manifest.json` | アシスタントの動作定義 |
| `lang/ja.json` | 日本語翻訳 |
| `lang/en.json` | 英語翻訳 |
| `qa-assistant-{name}.php` | WordPress プラグインヘッダー |
| `icon.png` | アイコン（無指定なら `assets/default-icon.png` をコピー） |

生成したプラグインフォルダを、QA Assistants / QA ZERO が入った WordPress の `wp-content/plugins/` に置いて有効化すると、「AI アシスタント」画面で動きます。

## 動作環境

- 生成したアシスタントの実行: QA Assistants / QA ZERO（WordPress プラグイン）。QAL でデータを取るアシスタントも両環境で動作（`wp_posts` マテリアルのみ QA Assistants 限定）。
- 検証ラッパー: PHP 7.4+（製品のコードに入っている検証エンジンを使用）

## ライセンス

**GPL-2.0-or-later**（全文は `LICENSE`）。この一式で生成したアシスタントは、**各自の著作権表示のもとで同じライセンスで配布**できます。

Licensed under **GPL-2.0-or-later** (see `LICENSE`). Assistants generated with this kit may be distributed under the same license, under each author's own copyright notice.

## 由来・問い合わせ

本リポジトリは開発元の Schema・サンプルを反映した配布の器です。最新化は適時行われます。

- **開発元**＝ウェブジョブズ（QA Assistants / QA ZERO の開発元）
- **質問・不具合**＝このリポジトリの [Issues](https://github.com/quarka-org/qa-assistant-maker/issues) へどうぞ
