■PDODBについて

PDODBはPHP 5.1.0で標準装備されたPDOを使って作られています。
そのため、PDOがサポートする以下の機能を利用することが出来ます。

・プレースホルダ
・プリペアドステートメント
・トランザクション、オートコミット(ドライバがサポートしている場合のみ)

PDOの詳しい説明は以下のページを参照してください。
PHP: PDO - Manual
http://php.net/manual/ja/book.pdo.php

また、PDODBはログ機能を備えています。
指定のディレクトリ(デフォルトではlogs)に、
発行したSQL、SQLエラー時の内容をログとして出力します。

エラーが発生した場合にそのメッセージを画面に出力するかどうかも設定できます。
詳しくは設定ファイル(PDODB.ini.php)を参照してください。


■ディレクトリ構成

/ReadMe.txt				本ファイル
/PDODB.php				PDODBメインファイル
/PDODB.ini.php			PDODB設定ファイル
/Log/Log.php			ログファイル
/logs/					ログディレクトリ

/sample/				サンプルディレクトリ
/sample/select.php		取得サンプル
/sample/insert.php		追加サンプル
/sample/update.php		更新サンプル
/sample/delete.php		削除
/sample/prepara.php		プリペアドステートメントサンプル
/sample/wildcard.php	ワイルドカードを使う時の注意点

/document/html/index.html	HTMLドキュメント