<?php
/**
 * ログクラス
 *
 * @version 1.1.1
 * @charset UTF-8
 * @create 2011/02/15
 * @update 2014/12/24
 * @license MIT
 * @author ntft
 * @copyright ntft
 * @cation PHP 5.0 以上必須
 */
class Log
{
	private $fullPath;	/** ログファイルのフルパス */
	private $logNm;		/** ログファイル名 */
	private $logDir;	/** ログファイルのディレクトリパス */
	private $isErrDisp;	/** エラー時にメッセージを表示するかどうか */

	/**
	 * コンストラクタ
	 *
	 * @param string $logNm ログファイル名 (def:'error.log')
	 * @param string $logDir ログファイルのディレクトリ (def:'.')
	 * @param boolean $isYmDiv ログファイル名に年月を追加してファイル分けるかどうか (def:TRUE)
	 * @param boolean $isErrDisp エラー時にメッセージを表示するかどうか (def:TRUE)
	 */
	public function __construct($logNm = 'error.log', $logDir = '.',
								$isYmDiv = TRUE, $isErrDisp = TRUE)
	{
		// 年月で分ける場合
		if ($isYmDiv == TRUE) {
			// ファイル名がドットを含む場合
			if (($dotPos = mb_strrpos($logNm, '.')) !== FALSE) {
				// ファイル名
				$fileNm = mb_substr($logNm, 0, $dotPos);
				// 拡張子名
				$extNm = mb_substr($logNm, $dotPos + 1);
				$logNm = $fileNm . date('Ym') . '.' . $extNm;
			}
			else {
				// 年月を付加
				$logNm .= date('Ym');
			}
		}

		// 最後の文字を取得
		$lastChar = mb_substr($logDir, -1, 1);
		// 最後の文字がセパレータでない場合
		if (! ($lastChar === '/' || $lastChar === '\\')) {
			$logDir = $logDir . DIRECTORY_SEPARATOR;
		}

		// ログファイル名
		$this->logNm = $logNm;
		// ログファイルのフルパス
		$this->fullPath = $logDir . $logNm;

		// ログファイルのディレクトリパス
		$this->logDir = $logDir;
		// エラー時にメッセージを表示するかどうか
		$this->isErrDisp = $isErrDisp;

		// _check() は LogExceptionを返す
		try {
			// ログを書き込めるかチェックする
			$this->_check();
		} catch (LogException $e) {
			if ($this->isErrDisp) {
				// エラーメッセージを表示する
				echo ($e->getMessage());
			}
			// 処理終了
			exit;
		}
	}

	/**
	 * ログファイルに書き込む
	 *
	 * @return boolean 書き込みの成否
	 * @memo 引数に指定されたモノをカンマで結合し出力
	 */
	public function out()
	{
		// 可変長の引数を取得
		$aryArg = func_get_args();
		// 3桁0埋めのミリ秒文字列
		$msec = $this->_getMSecStr();

		// 変数の中身を文字列に変換
		foreach ($aryArg as $key => $val) {
			$aryArg[$key] = var_export($val, TRUE);
		}
		// カンマで連結
		$msg = date('[Y/m/d H:i:s.') . $msec . '] ' . implode(', ', $aryArg) . PHP_EOL;

		// ログに書き込む
		$bRet = error_log($msg, 3, $this->fullPath);
		// 書き込みに失敗、かつエラー表示設定の場合
		if ($bRet === FALSE && $this->isErrDisp === TRUE) {
			echo 'ログの出力に失敗しました。 (' . $this->fullPath . ')';
		}
		return $bRet;
	}

	/**
	 * ログファイル名を返す
	 *
	 * @return string ログファイルのフルパス
	 */
	public function getLogNm() {
		return $this->logNm;
	}

	/**
	 * ログファイルのフルパスを返す
	 *
	 * @return string ログファイルのフルパス
	 */
	public function getLogFullPath() {
		return $this->fullPath;
	}

	/**
	 * ログディレクトリのパスを返す
	 *
	 * @return string ログディレクトリのパス
	 */
	public function getLogDirPath() {
		return $this->logDir;
	}

	/**
	 * ログを書き込めるかチェックする
	 *
	 * @Excption LogExceptionクラス
	 */
	private function _check()
	{
		// ディレクトリが存在しない場合
		if (! file_exists($this->logDir)) {
			throw new LogException('ログディレクトリが存在しません。 (' . $this->logDir . ')');
		}
		// 書き込み権限がない場合
		if (! is_writable($this->logDir)) {
			throw new LogException('ログディレクトリに書き込み権限がありません。 (' . $this->logDir . ')');
		}
	}

	/**
	 * 現在のミリ秒を3桁0埋めの文字列を返す
	 *
	 * @return ミリ秒(3桁0埋めの文字列)
	 */
	private function _getMSecStr()
	{
		// 現在のマイクロ秒を取得
		$aryTime = explode(' ', microtime());
		// 1000倍する
		$msec = ((double)$aryTime[0]) * 1000;
		// 3桁0埋めで返す
		return sprintf('%03d', (int)$msec);
	}
}

/**
 * LogExceptionクラス
 *
 * @memo Exceptionクラスを継承
 */
class LogException extends Exception
{
	// 特に何もしない
}
?>