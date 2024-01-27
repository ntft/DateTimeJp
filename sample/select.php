<?php
/**
 * サンプル - 取得処理
 */

// ファイルの読み込み
require_once '../PDODB.php';

// PDODBクラスのオブジェクトを生成
$db = new PDODB('test_db');

// PDODBには、引数方式と外部指定方式という2つのSQL実行方式がある。
// その名の通り、引数方式は引数をSQLを生成する方式で、
// 外部指定方式は、プロパティ$sql, $aryBindに直接SQLを代入する方式である。
// テーブル同士を結合させる場合、外部指定方式を使う。

// insert, update, delete の場合はトランザクションを使用する
// しかし、MySQLには非トランザクションのストレージエンジンのものもあるため、
// 使えない場合もある。
// 非トランザクションの場合、PDOは成功の振る舞いをする。

// ----------------------------------------------------------------------
// 単一取得
// ----------------------------------------------------------------------

try {

	// (1) 引数方式
	$aryBind = array(':id' => 1);
	$aryRet = $db->select('*', 'profile_t', 'id = :id', $aryBind);

	// 直前に実行したSQL情報を取得
	$sql = $db->getSQL();
	var_dump($sql, $aryRet);
	echo '<br /><br />' . PHP_EOL;


	// (2) 外部指定方式
	$db->sql = 'SELECT * FROM profile_t WHERE id = :id';
	$db->aryBind = array(':id' => 2);
	$aryRet = $db->select();

	// 直前に実行したSQL情報を取得
	$sql = $db->getSQL();
	var_dump($sql, $aryRet);
	echo '<br /><br />' . PHP_EOL;

// ----------------------------------------------------------------------
// 複数取得
// ----------------------------------------------------------------------

	// (1) 引数方式
	$aryBind = array(':id' => 10);
	$aryRet = $db->selectAll('*', 'profile_t', 'id > :id', $aryBind);

	// 直前に実行したSQL情報を取得
	$sql = $db->getSQL();
	var_dump($sql, $aryRet);
	echo '<br /><br />' . PHP_EOL;


	// (2) 外部指定方式
	$db->sql = 'SELECT * FROM profile_t';
	$aryRet = $db->selectAll();

	// 直前に実行したSQL情報を取得
	$sql = $db->getSQL();
	var_dump($sql, $aryRet);
	echo '<br /><br />' . PHP_EOL;
} catch (PDOException $e) {
	// 処理終了
	exit;
}
?>