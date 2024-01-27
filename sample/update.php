<?php
/**
 * サンプル - 更新処理
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

try {
	// トランザクション開始
	$db->beginTransaction();

	// (1) 引数方式
	$aryFld = array(':name' => 'name', ':age' => 'age');
	$aryBind = array(
		':id' => 1,
		':name' => 'updated!!',
		':age' => 99
	);
	// 返り値は更新した値
	// 更新前と更新後の値が等しい場合は更新したとは言わない
	$upCnt = $db->update($aryFld, 'profile_t', 'id = :id', $aryBind);
	// ---
	// 直前に実行したSQL情報を取得
	$sql = var_export($db->getSQL());
	echo $sql . PHP_EOL . '更新した行数：' . $upCnt . '<br /><br />' . PHP_EOL;

	// (2) 外部指定方式
	$db->sql = 'UPDATE profile_t SET name = :name WHERE id = :id';
	$db->aryBind = array(
		':id' => 2,
		':name' => 'up!!up!!up!!'
	);
	$upCnt = $db->update();
	// ---
	// 直前に実行したSQL情報を取得
	$sql = var_export($db->getSQL());
	echo $sql . PHP_EOL . '更新した行数：' . $upCnt;

	// 成功：コミット
	$db->commit();
} catch (PDOException $e) {
	// 失敗：ロールバック
	$db->rollBack();
	// 処理終了
	exit;
}
?>