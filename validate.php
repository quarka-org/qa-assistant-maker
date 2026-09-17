<?php
/**
 * アシスタント検証ラッパー（glue のみ・検証ロジックは持たない）
 *
 * 製品（QA Assistants / QA ZERO）の配布 ZIP に同梱されている検証エンジン
 * （QAHM_Assistant_Schema_Validator）を WordPress 無しで呼び出すだけの薄いラッパー。
 * スキーマも判定ロジックも製品側が正本で、このファイルは「読み込んで呼ぶ」以上の
 * ことをしない（検査の判定はホスト側の PHP に一本化されている）。
 *
 * 使い方:
 *   php validate.php <展開した製品ZIPのパス> <アシスタントのフォルダ>
 *
 * 例:
 *   php validate.php ./qa-heatmap-analytics ./my-assistant
 *
 * 終了コード: 0 = VALID / 1 = INVALID / 2 = 使い方の誤り・検証エンジンが壊れている
 *
 * 見るもの（製品の検証エンジンが持つ検査を全部呼ぶ）:
 *   1. manifest.json の構造（E_SCHEMA_*）と参照の整合（E_REF_*）
 *        ＝goto の飛び先・未宣言の変数・データ源/表/グラフの参照・計算式の文法・
 *          テンプレートの書き間違い・新しい書き方に要る min_core_version の宣言
 *   2. 翻訳（lang/*.json）＝`t:` キーの欠落・翻訳文の中のテンプレートの書き間違い
 *   3. パッケージ構成（E_PKG_*）＝許可外のファイル・スタブ PHP の形・アイコンの形式
 * 見ないもの:
 *   QAL の中身（材料名・列名・集計の意味）。VALID は「壊れていない」であって
 *   「正しい」ではない（数字が正しいかは実データで確かめる）。
 *
 * 古い製品 ZIP（翻訳・パッケージ検査を持たない世代）を渡された場合は、その旨を
 * 表示したうえで構造検査だけで判定する（黙って縮退しない）。
 */

$argvv = $argv;
array_shift( $argvv );

if ( count( $argvv ) < 2 ) {
	fwrite( STDERR, "usage: php validate.php <plugin-zip-dir> <assistant-dir>\n" );
	fwrite( STDERR, "  plugin-zip-dir: 製品ZIPを展開したフォルダ（中に vendor/ と assistant-schema/ がある階層）\n" );
	fwrite( STDERR, "  assistant-dir : manifest.json のあるフォルダ\n" );
	exit( 2 );
}

$plugin_dir    = rtrim( $argvv[0], "/\\" );
$assistant_dir = rtrim( $argvv[1], "/\\" );

// --- 検証エンジンの前提を自己点検する ------------------------------------
// 製品の validate() は「スキーマが読めない」とき fail-open（valid: true）を返す設計。
// 壊れた検証エンジンが「VALID」と言うのを防ぐため、呼ぶ前に前提を確かめる。
// （これは検証ロジックではなく、glue が自分の足場を確認しているだけ）
$autoload    = $plugin_dir . '/vendor/autoload.php';
$validator   = $plugin_dir . '/class-qahm-assistant-schema-validator.php';
$schema_dir  = $plugin_dir . '/assistant-schema';
$root_schema = $schema_dir . '/manifest.schema.json';

function engine_broken( $why ) {
	fwrite( STDERR, "ERROR: 検証エンジンが壊れています（この状態の VALID は信用できません）\n" );
	fwrite( STDERR, "       $why\n" );
	fwrite( STDERR, "       製品ZIPを展開し直してください。\n" );
	exit( 2 );
}

foreach ( array( $autoload, $validator, $root_schema ) as $needed ) {
	if ( ! file_exists( $needed ) ) {
		engine_broken( "見つかりません: $needed" );
	}
}

$definitions = glob( $schema_dir . '/definitions/*.json' );
if ( empty( $definitions ) ) {
	engine_broken( "スキーマの部品がありません: $schema_dir/definitions/*.json" );
}

// 検証クラスは WordPress 前提のガードを持つので、CLI 用に定数だけ用意する。
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_dir );
}

require $autoload;
require $validator;

// --- セルフテスト：既知の壊れた manifest を本当に弾けるか ------------------
// 弾けないなら、エンジンが fail-open している（＝以降の VALID は無意味）。
$canary = array( 'id' => 'qa-assistant-canary', 'version' => '1.0.0.0', 'name' => 123, 'scenes' => array( 'start' => array() ) );
$canary_res = QAHM_Assistant_Schema_Validator::validate( $canary );
if ( ! empty( $canary_res['valid'] ) ) {
	engine_broken( 'セルフテスト失敗＝明らかに壊れた manifest を VALID と判定しました（スキーマの部品欠落・fail-open の疑い）。' );
}

// --- エンジンの世代を見る（参照の整合・翻訳・パッケージ検査を持っているか） -----
// 参照の整合（E_REF_* 全種）と翻訳を validate() に渡す口、そしてパッケージ検査は、
// 製品の検証エンジンが 2026-08 に得たもの。古い製品 ZIP には
// 無いので、無い検査を「やった」ことにしないよう、ここで有無を見て表示に出す。
// （load_translations の有無が世代の目印。参照検査の有無もこれで判定する）
$has_ref_checks = method_exists( 'QAHM_Assistant_Schema_Validator', 'load_translations' );
$has_package    = method_exists( 'QAHM_Assistant_Schema_Validator', 'validate_package' );

// --- manifest を読んで、製品の検証器に渡す ---------------------------------
$manifest_path = $assistant_dir . '/manifest.json';
if ( ! file_exists( $manifest_path ) ) {
	fwrite( STDERR, "ERROR: manifest.json がありません: $manifest_path\n" );
	exit( 2 );
}

$json = file_get_contents( $manifest_path );
if ( null === json_decode( $json ) ) {
	fwrite( STDERR, "ERROR: manifest.json が JSON として壊れています: " . json_last_error_msg() . "\n" );
	exit( 1 );
}

// slug ＝ アシスタントのフォルダ名（製品側と同じ約束：manifest の id・スタブ PHP 名と一致する）。
$real_dir = realpath( $assistant_dir );
$slug     = basename( false !== $real_dir ? $real_dir : $assistant_dir );

$notes = array();

// 1+2. manifest（構造＋参照の整合）と翻訳。翻訳は製品側の load_translations() で読む（複製しない）。
$translations = null;
if ( $has_ref_checks ) {
	$translations = QAHM_Assistant_Schema_Validator::load_translations( $assistant_dir );
	if ( null === $translations ) {
		$notes[] = 'lang/*.json が見つからないため、翻訳キー（t:）の検査と翻訳文のテンプレート検査は行われていません（翻訳は ja/en の両方を作る）。';
	}
	$res = QAHM_Assistant_Schema_Validator::validate( $json, $translations );
} else {
	$notes[] = 'この製品 ZIP の検証エンジンは古い世代です＝参照の整合（goto の飛び先・未宣言の変数・翻訳キーの欠落）は検査されていません。構造だけの判定です。';
	$res = QAHM_Assistant_Schema_Validator::validate( $json );
}

// 3. パッケージ構成。ホスト側は既定で「警告のみ（一覧から消さない）」だが、配布前に
//    直すのが筋なので、ここでは不合格として扱う（理由は同じ E_PKG_* で表示する）。
$pkg = null;
if ( $has_package ) {
	$pkg = QAHM_Assistant_Schema_Validator::validate_package( $assistant_dir, $slug );
} else {
	$notes[] = 'この製品 ZIP の検証エンジンはパッケージ検査を持たない世代です＝ファイル構成（許可外のファイル・スタブ PHP の形・アイコン）は検査されていません。';
}

// --- 結果を表示する ---------------------------------------------------------
echo "manifest  : $manifest_path\n";
echo "slug      : $slug\n";
echo "validator : $validator\n";
echo 'schema    : ' . $root_schema . ' ＋ definitions ' . count( $definitions ) . " 本\n";
echo 'checks    : 構造(E_SCHEMA_*)'
	. ( $has_ref_checks ? ' ＋ 参照の整合(E_REF_*) ＋ 翻訳(lang/*.json)' : '（参照の整合は見ない世代）' )
	. ( $has_package ? ' ＋ パッケージ構成(E_PKG_*)' : '' ) . "\n\n";

$caveat = <<<TXT
※ VALID は「壊れていない」であって「正しい」ではありません。
   この検証が見ていないもの:
   - QAL の中身（材料名・列名・集計の意味）
     例: 「セッション内の PV 番号」（連番）を合計して PV として表示しても VALID になります。
   数字が正しいかは、実データで確かめてください。
TXT;

// canary（セルフテスト）が fail-open を検知するのが第一の守り。unverified も直接見て二重にする
// （残る窓＝無傷の束で validate 中に Throwable が出たときの静かな合格を、ここで拒否する）。
if ( ! empty( $res['unverified'] ) ) {
	fwrite( STDERR, "ERROR: 検証エンジンが検証を実行できていません（unverified）。製品 ZIP を展開し直してください。\n" );
	exit( 2 );
}

$errors     = ( isset( $res['errors'] ) && is_array( $res['errors'] ) ) ? $res['errors'] : array();
$pkg_errors = ( null !== $pkg && isset( $pkg['errors'] ) && is_array( $pkg['errors'] ) ) ? $pkg['errors'] : array();
$all_ok     = ! empty( $res['valid'] ) && ( null === $pkg || ! empty( $pkg['valid'] ) );

function print_error( $e, $kind ) {
	$code = isset( $e['code'] ) ? $e['code'] : '(no code)';
	$path = isset( $e['path'] ) && '' !== $e['path'] ? $e['path'] : '(root)';
	$msg  = isset( $e['message'] ) ? $e['message'] : '';
	echo "  [$code] $kind$path\n";
	echo "      $msg\n";
	if ( ! empty( $e['did_you_mean'] ) ) {
		echo '      did you mean: ' . $e['did_you_mean'] . "\n";
	}
	echo "\n";
}

function print_notes( $notes ) {
	foreach ( $notes as $n ) {
		echo "! $n\n";
	}
	if ( ! empty( $notes ) ) {
		echo "\n";
	}
}

if ( $all_ok ) {
	echo "VALID\n\n";
	print_notes( $notes );
	echo $caveat . "\n";
	exit( 0 );
}

echo 'INVALID (manifest ' . count( $errors ) . ' / package ' . count( $pkg_errors ) . ")\n\n";

foreach ( $errors as $e ) {
	print_error( $e, '' );
}
foreach ( $pkg_errors as $e ) {
	print_error( $e, 'package:' );
}

print_notes( $notes );
echo $caveat . "\n";
exit( 1 );
