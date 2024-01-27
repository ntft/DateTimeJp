<?php
/**
 * PDODBクラスの設定ファイル
 *
 * @version 1.1.0
 * @charset UTF-8
 * @create 2010/10/03
 * @update 2014/12/24
 * @author ntft
 * @copyrignht ntft
 * @license MIT License
 */

/** 本番フラグ */
$isRelease = FALSE;
//$isRelease = TRUE;

// 本番の場合
if ($isRelease === TRUE) {
	$errDisp = FALSE;
}
// デバッグの場合
else {
	$errDisp = TRUE;
}

// DB情報配列
// キー値をDB名にする
$aryInfoDB = array();

// 以下の様に、接続するDB情報を記述していく
// PDOのコンストラクタと同じ内容
// http://www.php.net/manual/ja/pdo.construct.php
//
// dsn：データソース名(Data Source Name)またはDSN
// username：DSN 文字列のユーザ名(オプション)
// password：パスワード(オプション)
// driver_options：ドライバ固有の接続オプションを指定するキー=> 値の配列(オプション)

$aryInfoDB['phpmyadmin'] = array(
	'dsn'				=> 'mysql:host=localhost;port=3306;dbname=phpmyadmin;charset=utf8;',
	'username'			=> 'root',
	'password'			=> NULL,
	'driver_options'	=> NULL
);

$aryInfoDB['mysql'] = array(
	'dsn'				=> 'mysql:host=localhost;port=3306;dbname=mysql;charset=utf8;',
	'username'			=> 'root',
	'password'			=> NULL,
	'driver_options'	=> NULL
);

$aryInfoDB['test_db'] = array(
	'dsn'				=> 'mysql:host=localhost;port=3306;dbname=test_db;charset=utf8;',
	'username'			=> 'root',
	'password'			=> NULL,
	'driver_options'	=> NULL
);


// ログ情報配列
$aryInfoLog = array();

$dir = dirname(__FILE__);

// ※注意：ログを出力すると処理負荷がかかるため特定の場合のみ出力するか、
// 　マシンスペックの高いサーバでのみ出力すること！
//
// Logクラスのコンストラクタに使用される値
//
// name：ログファイル名
// path：ログファイルのディレクトリ(PDODBディレクトリから見た)
// YmDiv：ログファイル名に年月を追加してファイル分けるかどうか
// errDisp：ログエラー時に表示するかどうか

/*
// SQLログ
$aryInfoLog['sql'] = array(
	'name'		=> 'sql.log',
	'path'		=> $dir . '/logs',
	'YmDiv'		=> TRUE,
	'errDisp'	=> $errDisp
);

// SQLエラーログ
$aryInfoLog['sqlErr'] = array(
	'name'		=> 'sqlerr.log',
	'path'		=> $dir . '/logs',
	'YmDiv'		=> TRUE,
	'errDisp'	=> $errDisp
);
*/

// 使用しない場合は FALSE を代入するとログは出力されなくなる
// 例)
$aryInfoLog['sql'] = FALSE;
$aryInfoLog['sqlErr'] = FALSE;
?>