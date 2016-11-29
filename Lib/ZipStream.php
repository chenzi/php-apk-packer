<?php

namespace ApkPacker\Lib;

/**
 * Read zip stream
 *
 * Not support zip64
 *
 * see https://pkware.cachefly.net/webdocs/casestudies/APPNOTE.TXT
 */
class ZipStream {

	/**
	 * end of central dir signature    4 bytes  (000x06054b50)
	 */
	const MAGIC = 0x06054b50;

	/**
	 * end of central dir signature    4 bytes  (00x07064b50)
	 */
	const MAGIC_64 = 0x07064b50;

	/**
	 * @var string archive file end mark
	 */
	private $stringEndArchive = "PK\005\006";

	/**
	 * @var string 64ZIP archive file end mark
	 */
//	private $stringEndArchive64 = "PK\x06\x06";

	/**
	 * @var int max comment size
	 */
	private $maxComment = ( 1 << 16 ) - 1;

	private $fileHandle;
	private $fileInfo;
	private $apkFile;

	/**
	 * ZipStream constructor.
	 */
	public function __construct() {

	}

	/**
	 * open file
	 *
	 * @param $apkFile
	 */
	public function open( $apkFile ) {
		$this->apkFile    = $apkFile;
		$this->fileHandle = fopen( $this->apkFile, 'r+b' );
		$this->fileInfo   = fstat( $this->fileHandle );
	}

	/**
	 * read zip comment
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function readZipComment() {
		$signatureString = $this->stringEndArchive;
		$fileSize        = $this->fileInfo['size'];
		$startPosition   = max( $fileSize - ( $this->maxComment ) - 22, 0 );

		fseek( $this->fileHandle, $startPosition );
		$data = fread( $this->fileHandle, $fileSize - $startPosition );
		$pos  = strpos( $data, $signatureString );
		if ( $pos === false ) {
			return '';
		}

		$endRecordPosition = $startPosition + $pos;
		fseek( $this->fileHandle, $endRecordPosition );
		$sig = fread( $this->fileHandle, 4 );
		if ( ! $this->isSignature( $sig ) ) {
			return '';
		}
		fseek( $this->fileHandle, $endRecordPosition + 20 );
		$commentLength = unpack( 'v', fread( $this->fileHandle, 2 ) );
		fseek( $this->fileHandle, $endRecordPosition + 22 );
		$comment = fread( $this->fileHandle, $commentLength[1] );

		$comment = trim( $comment );

		fclose( $this->fileHandle );

		return $comment;
	}

	/**
	 * Setting zip comment
	 *
	 * @param string $comment
	 *
	 * @return bool
	 */
	public function setZipComment( $comment ) {
		if ( strlen( $comment ) > $this->maxComment ) {
			return false;
		}

		$signatureString = $this->stringEndArchive;
		$fileSize        = $this->fileInfo['size'];
		$startPosition   = max( $fileSize - ( $this->maxComment ) - 22, 0 );
		fseek( $this->fileHandle, $startPosition );
		$data = fread( $this->fileHandle, $fileSize - $startPosition );
		$pos  = strpos( $data, $signatureString );
		if ( $pos === false ) {
			return false;
		}

		$signaturePosition = $startPosition + $pos;
		fseek( $this->fileHandle, $signaturePosition );
		$sig = fread( $this->fileHandle, 4 );
		if ( ! $this->isSignature( $sig ) ) {
			return false;
		}

		//seek to endLocator start
		fseek( $this->fileHandle, $signaturePosition + 20 );
		$commentLength = unpack( 'v', fread( $this->fileHandle, 2 ) )[1];
		fseek( $this->fileHandle, $signaturePosition + 20 );

		//write apk mark
		if ( ! fwrite( $this->fileHandle, pack( 'v', strlen( $comment ) ) ) || ! fwrite( $this->fileHandle, $comment ) ) {
			return false;
		}

		if ( $commentLength > 0 ) {
			$tmpFile   = tempnam( sys_get_temp_dir(), 'php_apk_packer_' );
			$tmpHandle = fopen( $tmpFile, "w+b" );
			rewind( $this->fileHandle );
			if ( stream_copy_to_stream( $this->fileHandle, $tmpHandle, $signaturePosition + 22 + strlen( $comment ) ) ) {
				copy( $tmpFile, $this->apkFile );
			}
			fclose( $tmpHandle );
		}

		fclose( $this->fileHandle );

		return true;
	}

	/**
	 * Check signature
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public function isSignature( $string ) {
		return $string == pack( "V", self::MAGIC );
	}
}