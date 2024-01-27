<?php
// ファイルの読み込み
require_once 'PDODB.ini.php';
require_once 'Log/Log.php';

/**
 * PDODBクラス
 *
 * @version 1.1.0
 * @charset UTF-8
 * @create 2010/10/03
 * @update 2014/12/24
 * @author ntft
 * @copyrignht ntft
 * @license MIT License
 * @memo
 *	- PDOクラスを継承。
 *	- プレースホルダ機能を使う場合は、外部指定用SQLとバインド配列を使うこと。
 * @cation PHP 5.1.0 以上必須
 *	以下のファイルは必須
 *	PDODB.ini.php	PDODBクラスの設定ファイル
 *	Log.php			ログクラス
 */
class PDODB extends PDO
{
	public $sql;		// 外部指定用SQL
	public $aryBind;	// 外部指定用バインド配列
	public $preSql;		// 直前に使用したSQL
	public $aryPreBind;	// 直前に使用したバインド配列

	public $isErrDisp;	// 例外表示フラグ

	private $stmt;		// PDOStatementオブジェクト
	private $isPrepare;	// プリペアドステートメントフラグ
	private $logSql;	// SQLログオブジェクト
	private $logErrSql;	// SQLエラーログオブジェクト

	/**
	 * コンストラクタ
	 *
	 * @param string $dbNm PDODB.ini.php で定義された $aryInfoDB のキー値
	 * @param boolean $isErrDisp エラー表示設定 (def:TRUE)
	 * @param boolean $isConnect DBに接続するか否か (def:TRUE)
	 */
	public function __construct($dbNm, $isErrDisp = TRUE, $isConnect = TRUE)
	{
		// 本番フラグ
		global $isRelease;
		// エラー表示設定
		global $errDisp;

		// 本番
		if ($isRelease) {
			// PDODB.ini.php の 本番フラグに影響される
			$this->isErrDisp = $errDisp;
		}
		// デバッグ
		else {
			// エラー表示設定：指定値
			$this->isErrDisp = $isErrDisp;
		}
		// プリペアドステートメントフラグ
		$this->isPrepare = FALSE;

		if ($isConnect === TRUE) {
			// DB接続
			$this->connect($dbNm, $this->isErrDisp);
		}
	}

	/**
	 * DBに接続する
	 *
	 * @param string $dbNm PDODB.ini.php で定義された $aryInfoDB のキー値
	 * @param boolean エラー表示フラグ
	 * @return void
	 * @memo DBの接続先を変える際はこのメソッドを使用する
	 */
	public function connect($dbNm, $isErrDisp = TRUE)
	{
		global $aryInfoDB;
		global $aryInfoLog;

		// SQLログ情報配列
		if (! isset($aryInfoLog['sql'])) {
			die('SQLログ情報 "sql" が PDODB.ini.php で定義されていません。');
		}
		// SQLエラーログ情報配列
		if (! isset($aryInfoLog['sqlErr'])) {
			die('SQLエラーログ情報 "sqlErr" が PDODB.ini.php で定義されていません。');
		}

		$infoLog = $aryInfoLog['sql'];
		// 使用する場合
		if (is_array($infoLog)) {
			// SQLログオブジェクト
			$this->logSql = new Log($infoLog['name'], $infoLog['path'],
									$infoLog['YmDiv'], $infoLog['errDisp']);
		}

		$infoLog = $aryInfoLog['sqlErr'];
		// 使用する場合
		if (is_array($infoLog)) {
			// SQLエラーログオブジェクト
			$this->logErrSql = new Log($infoLog['name'], $infoLog['path'],
									  $infoLog['YmDiv'], $infoLog['errDisp']);
		}

		try {
			// 指定されたキー値が定義されていない場合
			if (! isset($aryInfoDB[$dbNm])) {
				throw new PDOException('指定されたDB "' . $dbNm . '" は、PDODB.ini.php で定義されていません。');
			}
			$infoDB = $aryInfoDB[$dbNm];

			// 親クラスのコンストラクタをコール
			parent::__construct($infoDB['dsn'], $infoDB['username'],
								$infoDB['password'], $infoDB['driver_options']);
		} catch (PDOException $e) {
			if (is_object($this)) {
				// 例外内容の出力
				$this->writeException($e, debug_backtrace());
			}
			// 親クラスのコンストラクタに失敗した場合
			else {
				// 処理終了
				$msg = '<b>DB接続エラー</b><br />' . PHP_EOL
					 . 'エラーメッセージ：' . $e->getMessage() . '<br />' . PHP_EOL
					 . '(PDODB.ini.php の設定を見直してください)';
				if ($isErrDisp) echo $msg;
				exit;
			}
		}
	}

	/**
	 * データを単一取得する
	 *
	 * @param string $fld フィールド
	 * @param string $table テーブル
	 * @param string $where WHERE句
	 * @param array $aryBind バインドする配列
	 * @param string $option オプション(ORDER BY, LIMIT, OFFSET)
	 * @return object 結果オブジェクト / boolean FALSE(取得する行がない場合)
	 */
	public function select($fld = NULL, $table = NULL,
						   $where = NULL, $aryBind = NULL, $option = NULL)
	{
		// 内部メソッドをコール
		return $this->_fetchOrFetchAll('fetch', $fld, $table,
									   $where, $aryBind, $option);
	}

	/**
	 * データを複数取得する
	 *
	 * @param string $fld フィールド
	 * @param string $table テーブル
	 * @param string $where WHERE句
	 * @param array $aryBind バインドする配列
	 * @param string $option オプション(ORDER BY, LIMIT, OFFSET)
	 * @return arrary object 結果オブジェクト配列
	 */
	public function selectAll($fld = NULL, $table = NULL,
							  $where = NULL, $aryBind = NULL, $option = NULL)
	{
		// 内部メソッドをコール
		return $this->_fetchOrFetchAll('fetchAll', $fld, $table,
									   $where, $aryBind, $option);
	}

	/**
	 * データを単数 or 複数取得する
	 * select() と selectAll() の中身はほとんど同じなので、
	 * 処理はこのメソッドに任せる
	 *
	 * @param string $fetchStyle 取得方法('fetch' or 'fetchAll')
	 * @param string $fld フィールド
	 * @param string $table テーブル
	 * @param string $where WHERE句
	 * @param array $aryBind バインドする配列
	 * @param string $option オプション(ORDER BY, LIMIT, OFFSET)
	 * @return arrary mixed 結果('fetch'の場合は、object or FALSE。
	 *							 'fetchAll'の場合は、array)
	 */
	private function _fetchOrFetchAll($fetchStyle, $fld, $table,
									  $where, $aryBind, $option)
	{
		try {
			// 引数がNULLでない場合
			// (引数を使用する)
			if (isset($fld) ||isset($table) || isset($where) ||
				isset($aryBind) || isset($option)) {
				$sql = 'SELECT ' . $fld . ' FROM ' . $table;
				// WHERE句
				if ($where !== NULL) {
					$sql .= ' WHERE ' . $where;
				}
				// オプション
				if ($option !== NULL) {
					$sql .= ' ' . $option;
				}
				$bind = $aryBind;
			}
			// 引数が無い場合
			// (プロパティ$sqlを使用する)
			else {
				$sql = $this->sql;
				// バインドを行うか否かを調べる
				$this->_chkBind();
				$bind = $this->aryBind;
			}
			// プリペアドステートメント未使用の場合、
			// またはPDOStatementがNULLの場合
			if ($this->isPrepare === FALSE || $this->stmt === NULL) {
				// SQL実行準備
				$this->stmt = $this->prepare($sql);
				// エラー
				if ($this->stmt === FALSE) {
					// 実行前に状態を保存する
					$this->_saveExecute($sql, $bind);
					throw new PDOException('PDO::prepara() - DBがSQL文を準備出来ませんでした。');
				}
			}
			// プリペアドステートメント使用モード、
			// かつ、PDOStatementがNULL以外(1回は PDO::prepara() が実行されている)の場合
			// 前回使用したPDOStatementを使用する
			else {
				// 特に何もしない(上のコメントが書きたかっただけ)
			}

			// バインド値が存在する場合
			if (count($bind) > 0) {
				// 注意：参照foreach(参照値を渡す必要がある)
				foreach ($bind as $key => &$val) {
					// 変数名にパラメータをバインド
					$bRet = $this->stmt->bindParam($key, $val);
				}
				// (参照元ではなく)参照を削除
				unset($val);
			}

			// 実行前に状態を保存する
			$this->_saveExecute($sql, $bind);
			// SQLの実行
			// _chkExecute() で実行内容を評価するので、ここでは戻り値を見ない
			$this->stmt->execute();
			// 実行した内容をチェック
			$this->_chkExecute();
			// 結果の取得(オブジェクト)
			$ret = $this->stmt->{$fetchStyle}(PDO::FETCH_OBJ);

			return $ret;
		} catch (Exception $e) {
			// 例外内容の出力
			$this->writeException($e, debug_backtrace());
		}
	}

	/**
	 * データを追加する
	 *
	 * @param $aryFld フィールド配列
	 * @param $table テーブル
	 * @param $aryBind バインド配列
	 * @return TRUE(OK) / 例外発生(NG)
	 */
	public function insert($aryFld = NULL, $table = NULL, $aryBind = NULL)
	{
		try {
			// 引数がある場合
			// (引数を使用する)
			if (func_num_args() != 0) {
				// バインド用配列作成
				foreach ((array)$aryFld as $val) {
					$aryInsBind[] = ':' . $val;
				}
				$sql  = 'INSERT INTO ' . $table;
				$sql .= ' (' . implode(', ', $aryFld) . ') ';
				$sql .= ' VALUES (' . implode(', ', $aryInsBind) . ')';
				$bind = $aryBind;
			}
			// 引数が無い場合
			// (プロパティ$sqlを使用する)
			else {
				$sql = $this->sql;
				// バインドを行うか調べる
				$this->_chkBind();
				$bind = $this->aryBind;
			}

			// プリペアドステートメント未使用の場合、
			// またはPDOStatementがNULLの場合
			if ($this->isPrepare === FALSE || $this->stmt === NULL) {
				// SQL実行準備
				$this->stmt = $this->prepare($sql);
				// エラー
				if ($this->stmt === FALSE) {
					// 実行前に状態を保存する
					$this->_saveExecute($sql, $bind);
					throw new PDOException('PDO::prepara() - DBがSQL文を準備出来ませんでした。');
				}
			}
			// プリペアドステートメント使用モード、
			// かつ、PDOStatementがNULL以外(1回は PDO::prepara() が実行されている)の場合
			// 前回使用したPDOStatementを使用する
			else {
				// 特に何もしない(上のコメントが書きたかっただけ)
			}

			// バインド値が存在する場合
			if (count($bind) > 0) {
				// 注意：参照foreach(参照値を渡す必要がある)
				foreach ($bind as $key => &$val) {
					// 変数名にパラメータをバインド
					$bRet = $this->stmt->bindParam($key, $val);
				}
				// (参照元ではなく)参照を削除
				unset($val);
			}

			// 実行前に状態を保存する
			$this->_saveExecute($sql, $bind);
			// SQLの実行
			$bRet = $this->stmt->execute();
			// 実行した内容をチェック
			$this->_chkExecute();

			return $bRet;
		} catch (Exception $e) {
			// 例外内容の出力
			$this->writeException($e, debug_backtrace());
		}
	}

	/**
	 * データを更新する
	 *
	 * @param $aryFld フィールド配列
	 * @param $table テーブル
	 * @param $where WHERE句
	 * @param $aryBind バインド配列
	 * @return 更新した行数 / 例外発生(失敗)
	 */
	public function update($aryFld = NULL, $table = NULL, $where = NULL, $aryBind = NULL)
	{
		try {
			// 引数がある場合
			// (引数を使用する)
			if (func_num_args() != 0) {
				// バインド用配列作成
				foreach ((array)$aryFld as $val) {
					$aryUpdate[] = $val . ' = :' . $val;
				}
				$sql  = 'UPDATE ' . $table . ' SET ';
				$sql .= implode(', ', $aryUpdate);
				if ($where !== NULL) {
					$sql .= ' WHERE ' . $where;
				}
				$bind = $aryBind;
			}
			// 引数が無い場合
			// (プロパティ$sqlを使用する)
			else {
				$sql = $this->sql;
				// バインドを行うか調べる
				$this->_chkBind();
				$bind = $this->aryBind;
			}

			// プリペアドステートメント未使用の場合、
			// またはPDOStatementがNULLの場合
			if ($this->isPrepare === FALSE || $this->stmt === NULL) {
				// SQL実行準備
				$this->stmt = $this->prepare($sql);
				// エラー
				if ($this->stmt === FALSE) {
					// 実行前に状態を保存する
					$this->_saveExecute($sql, $bind);
					throw new PDOException('PDO::prepara() - DBがSQL文を準備出来ませんでした。');
				}
			}
			// プリペアドステートメント使用モード、
			// かつ、PDOStatementがNULL以外(1回は PDO::prepara() が実行されている)の場合
			// 前回使用したPDOStatementを使用する
			else {
				// 特に何もしない(上のコメントが書きたかっただけ)
			}

			// バインド値が存在する場合
			if (count($bind) > 0) {
				// 注意：参照foreach(参照値を渡す必要がある)
				foreach ($bind as $key => &$val) {
					// 変数名にパラメータをバインド
					$bRet = $this->stmt->bindParam($key, $val);
				}
				// (参照元ではなく)参照を削除
				unset($val);
			}

			// 実行前に状態を保存する
			$this->_saveExecute($sql, $bind);
			// SQLの実行
			$this->stmt->execute();
			// 実行した内容をチェック
			$this->_chkExecute();

			// 更新した行数を返す
			return $this->stmt->rowCount();
		} catch (Exception $e) {
			// 例外内容の出力
			$this->writeException($e, debug_backtrace());
		}
	}

	/**
	 * データを削除する
	 *
	 * @param $table テーブル
	 * @param $where WHERE句
	 * @param $aryBind バインド配列
	 * @return 削除した行数 / 例外発生(失敗)
	 */
	public function delete($table = NULL, $where = NULL, $aryBind = NULL)
	{
		try {
			// 引数がある場合
			// (引数を使用する)
			if (func_num_args() != 0) {
				$sql  = 'DELETE FROM ' . $table;
				if ($where !== NULL) {
					$sql .= ' WHERE ' . $where;
				}
				$bind = $aryBind;
			}
			// 引数が無い場合
			// (プロパティ$sqlを使用する)
			else {
				$sql = $this->sql;
				// バインドを行うか否かを調べる
				$this->_chkBind();
				$bind = $this->aryBind;
			}

			// プリペアドステートメント未使用の場合、
			// またはPDOStatementがNULLの場合
			if ($this->isPrepare === FALSE || $this->stmt === NULL) {
				// SQL実行準備
				$this->stmt = $this->prepare($sql);
				// エラー
				if ($this->stmt === FALSE) {
					// 実行前に状態を保存する
					$this->_saveExecute($sql, $bind);
					throw new PDOException('PDO::prepara() - DBがSQL文を準備出来ませんでした。');
				}
			}
			// プリペアドステートメント使用モード、
			// かつ、PDOStatementがNULL以外(1回は PDO::prepara() が実行されている)の場合
			// 前回使用したPDOStatementを使用する
			else {
				// 特に何もしない(上のコメントが書きたかっただけ)
			}

			// バインド値が存在する場合
			if (count($bind) > 0) {
				// 注意：参照foreach(参照値を渡す必要がある)
				foreach ($bind as $key => &$val) {
					// 変数名にパラメータをバインド
					$bRet = $this->stmt->bindParam($key, $val);
				}
				// (参照元ではなく)参照を削除
				unset($val);
			}

			// 実行前に状態を保存する
			$this->_saveExecute($sql, $bind);
			// SQLの実行
			$this->stmt->execute();
			// 実行した内容をチェック
			$this->_chkExecute();

			// 削除した行数を返す
			return $this->stmt->rowCount();
		} catch (Exception $e) {
			// 例外内容の出力
			$this->writeException($e, debug_backtrace());
		}
	}

	/**
	 * ワイルドカード「_」「%」をクォートする
	 *
	 * @param string $str ワイルドカードが含まれる文字列
	 * @return string クォート後の文字列
	 */
	public function wildCardQuote($str)
	{
		// ワイルドカード「_」「 %」をクォート
		return str_replace(array('_', '%'), array('\_', '\%'), $str);
	}

	/**
	 * プリペアドステートメントモードを設定する
	 *
	 * @param boolean $mode プリペアドステートメントモード
	 * @memo プリペアドステートメントモードはSQL実行後、自動的にFALSEに設定される
	 */
	public function preparaMode() {
		$this->isPrepare = TRUE;
	}

	/**
	 * バインドを行うか調べる
	 *
	 * @return void
	 */
	private function _chkBind() {
		// バインドフラグ
		$bindFlg = TRUE;
		foreach((array)$this->aryBind as $key => $val) {
			// SQL内にバインド文字列が含まれていない場合
			if (mb_strpos($this->sql, $key) === FALSE) {
				$bindFlg = FALSE;
				break;
			}
		}

		// SQL内に全てのバインド文字列が存在した場合、バインドを行う(そのまま)
		// 1つでも存在しない場合、バインドを行わない(NULLで上書き)
		if (! $bindFlg) {
			$this->aryBind = NULL;
		}
	}

	/**
	 * SQL実行時の状態を保存する
	 *
	 * @param string $sql SQL
	 * @param array $aryBind バインド配列
	 * @return void
	 */
	private function _saveExecute($sql, $aryBind)
	{
		$this->preSql     = $sql;
		$this->aryPreBind = $aryBind;
	}

	/**
	 * 直前に実行したSQL文を取得する
	 *
	 * @return array 直前に実行したSQL情報配列
	 */
	public function getSQL()
	{
		return array('sql' => $this->preSql, 'bind' => $this->aryPreBind);
	}

	/**
	 * 直前に実行したSQL文をチェックする
	 * (エラー有りの場合、エラー内容を表示して例外をスロー)
	 *
	 * @return void
	 * @exception PDOException
	 */
	private function _chkExecute()
	{
		// プリペアドステートメントモードを未使用に戻す
		if ($this->isPrepare === TRUE) {
			$this->isPrepare = FALSE;
		}

		// SQLログを使用する場合
		if (is_object($this->logSql)) {
			// 実行したSQLをログに出力
			$this->logSql->out($this->getSQL());
		}

		// エラー無し：何もしない
		if ($this->stmt->errorCode() === '00000') {
			return;
		}
		// エラーあり
		$aryErr = $this->stmt->errorInfo();
		$strExc  = 'SQL STATE error code            : ' . $aryErr[0] . "<br />" . PHP_EOL;
		$strExc .= 'Peculiar error code to driver   : ' . $aryErr[1] . "<br />" . PHP_EOL;
		$strExc .= 'Peculiar error message to driver: <b>' . $aryErr[2] . "</b><br /><br />" . PHP_EOL;

		// SQLエラーログを使用する場合
		if (is_object($this->logErrSql)) {
			// HTMLタグを取り除いてエラーログに出力する
			$this->logErrSql->out(strip_tags($strExc));
		}

		// エラー内容を表示する
		if ($this->isErrDisp) {
			echo $strExc;
		}
		// 例外：PDOExceptionをスロー
		throw new PDOException('SQL文の実行に失敗しました。');
	}

	/**
	 * 例外内容を出力する
	 *
	 * @param object $e 例外オブジェクト
	 * @param array $aryDebug 呼び出し元でCallしたdebug_backtrace()の戻り値
	 * @return void
	 * @exception PDOException
	 */
	public function writeException($e, $aryDebug = NULL)
	{
		// クラス名を取得
		$clsNm = get_class($e);

		// debug_backtraceが指定された場合
		if (is_array($aryDebug)) {
			// 呼び出し元の情報を取得
			$aryCallOrg = $this->_getCallOrgInfo($aryDebug);
			// SQL実行時の情報
			$fileNm = $aryCallOrg['file'];
			$lineNm = $aryCallOrg['line'];
		}
		// 指定されなかった場合
		else {
			// 例外投入時の情報
			$fileNm = $e->getFile();
			$lineNm = $e->getLine();
		}
		$strExc = '<b>' . $clsNm . '</b>: ' . $e->getMessage() .
				  ' in <b>' . $fileNm . '</b> on line ' . $lineNm . "<br /><br />" . PHP_EOL;
		// 直前のSQL情報を取得する
		$infSQL = var_export($this->getSQL(), TRUE);
		$strExc .= $infSQL;

		// SQLエラーログを使用する場合
		if (is_object($this->logErrSql)) {
			// HTMLタグを取り除いてエラーログに出力する
			$this->logErrSql->out(strip_tags($strExc));
		}

		// 例外を表示する
		if ($this->isErrDisp) {
			echo $strExc;
		}
		// 再度、例外をスロー
		throw new PDOException();
	}

	/**
	 * 呼び出し元情報を取得する
	 *
	 * @param array $aryDebug debug_backtraceの戻り値
	 * @return array 呼び出し元情報
	 */
	private function _getCallOrgInfo($aryDebug)
	{
		foreach((array)$aryDebug as $aryDbg) {
			// このファイルの場合はスルー
			if (__FILE__ === $aryDbg['file']) {
				continue;
			}
			$aryRet = $aryDbg;
		}
		return $aryRet;
	}
}
?>