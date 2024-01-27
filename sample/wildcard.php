<?php
/**
 * サンプル - ワイルドカードを使う時の注意点
 */

// ファイルの読み込み
require_once '../PDODB.php';

// PDODBクラスのオブジェクトを生成
$db = new PDODB('test_db');

// ユーザが入力した値をWHERE句に入れて、
// ワイルドカードを使いたい場合はプレースホルダ(バインド)機能を使ってはならない。
//
// 例：後方一致のWHERE句のあるSQL文
// 「SELECT * FROM WHERE name '[ユーザ入力値]%'」
//
// この時、ユーザが「%a」というワイルドカードを含む値を入力したとする。
// すると、両方一致のWHERE区のあるSQL文になってしまう。
// 「SELECT * FROM WHERE name '%a%'」
//
// 作り手の意図しないSQLが実行されてしまう。

try {
	// こういった場合、ワイルドカードをクォートする wildCardQuote()メソッドを使って無害化する。
	$name = $db->wildCardQuote('%a');
	// プレースホルダ機能は使わないので文字列はシングルクォーテーションで囲む必要がある
	$db->sql = "SELECT * FROM profile_t WHERE name LIKE '" . $name . "%'";
	// これで作り手の意図するSQLが実行される。
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