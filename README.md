# QA Assistant Maker

QA Assistants / QA ZERO 用のアシスタントプラグインを **AI で生成する**ためのキットです。

**正本は製品のコード（配布 ZIP）**——このリポジトリは仕様書の写しを持ちません。人が維持するのは指示書2ページ（`CLAUDE.md`・`guide.html`）だけです。

## 必要なもの

- **AI エージェント**——`CLAUDE.md`（この一式の指示書）のように、**フォルダの中のファイルを読んで、検証コマンドを走らせられる**もの。**動作確認済みは Claude Code**。コマンドを実行できない AI でも生成はできます（その場合、検証の1行だけ人が打ってください）。
- **製品のコードを手元に置く**——**配布 ZIP を展開したフォルダ**（`qa-heatmap-analytics/` か `qa-zero/`）、または**自分のサイトに入れてあるプラグインのフォルダをそのままコピー**したもの。検証（`validate.php`）は製品側の検証エンジンを手元で読んで動くため、実物が要ります。**サイトに入れてある版と同じものを置いてください**（対応版数がずれると、通るはずのものが通らなくなります）。
- **PHP 7.4 以上**（検証に使います）

## 使い方

1. このリポジトリをクローンする
2. **製品のコードのフォルダ**（上記）をリポジトリ直下に置く
3. リポジトリ直下で **AI エージェントを起動する**（`CLAUDE.md` が自動で読み込まれます）
4. 「〜を分析するアシスタントを作りたい」等、ふつうの日本語で伝える
5. AI が生成 → **`php validate.php <製品ZIPの展開先> <生成物>` で検証** → VALID を確認して完成（**AI がコマンドを実行できない場合は、この1行は人が打ってください**）

人向けの詳しい説明（渡すもの・正本マップ・「VALID」の意味・動かないときの3チェック）は **[guide.html](./guide.html)** を参照してください。

## ファイル構成

```
qa-assistant-maker/
├── CLAUDE.md              AI 向け指示書（間違えると壊れる規則＋どこを読むか、だけ）
├── guide.html             人間向け使い方ページ
├── validate.php           検証ラッパー（製品 ZIP 内の検証エンジンを呼ぶだけの glue＝構造・参照の整合・翻訳・パッケージ構成の4検査を全部呼ぶ）
├── assets/
│   └── default-icon.png   デフォルトアイコン
└── examples/              動く見本4本（qa-assistant-sample / qa-assistant-form-sample / qa-assistant-hitokoto / qa-assistant-lp-bounce）
```

- **仕様の説明書はこのリポジトリにありません。** schema・データ材料の一覧（semantics 宣言つき）・QAL 文法・動作コードは、すべて**製品のコードの中の実物**を読みます（`CLAUDE.md` がパスを案内します）。写しが無いので古くなりません。

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


## 由来

本リポジトリは開発元の Schema・サンプルを反映した配布の器です。最新化は適時行われます。
