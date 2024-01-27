<?php
/**
 * サンプル - 削除処理
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
	$aryBind = array(':id' => 1);
	$delCnt = $db->delete('profile_t', 'id = :id', $aryBind);
	// ---
	// 直前に実行したSQL情報を取得
	$sql = var_export($db->getSQL());
	echo $sql . PHP_EOL . '削除した行数：' . $delCnt . '<br /><br />' . PHP_EOL;

	// (2) 外部指定方式
	$db->sql = 'DELETE FROM profile_t';
	$delCnt = $db->delete();
	// ---
	// 直前に実行したSQL情報を取得
	$sql = var_export($db->getSQL());
	echo $sql . PHP_EOL . '削除した行数：' . $delCnt;

	// 成功：コミット
	$db->commit();
} catch (PDOException $e) {
	// 失敗：ロールバック
	$db->rollBack();
	// 処理終了
	exit;
}
?>