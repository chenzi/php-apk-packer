<?php

namespace ApkPacker;

require "Lib/ZipStream.php";
require "Exception/ApkPackerException.php";

use ApkPacker\Exception\ApkPackerException;
use ApkPacker\Lib\ZipStream;

/**
 * Create apk packer
 *
 * Class Packer
 */
class ApkPacker {

	private $zipStream;

	/**
	 * ApkPacker constructor.
	 */
	public function __construct() {
		$this->zipStream = new ZipStream();
	}

	/**
	 * packer single apk file
	 *
	 * @param string $apkFile     source apk package
	 * @param string $channelName write channel mark to apk
	 * @param string $output      if output is not null then will create new apk file
	 *
	 * @return bool
	 * @throws \ApkPacker\Exception\ApkPackerException
	 */
	public function packerSingleApk( $apkFile, $channelName, $output = '' ) {
		if ( ! file_exists( $apkFile ) ) {
			throw new ApkPackerException( 'apk file not found' );
		}
		if ( empty( $channelName ) ) {
			throw new ApkPackerException( 'channel name is require' );
		}

		$apkFileHandle = fopen( $apkFile, 'rb' );
		if ( $apkFileHandle == false ) {
			throw new ApkPackerException( 'failed to open the apk file' );
		}

		$apkFileInfo = fstat( $apkFileHandle );
		var_dump( $apkFileInfo );
		$apkFileSize = isset( $apkFileInfo['size'] ) ? $apkFileInfo['size'] : 0;
		if ( $apkFileSize == 0 ) {
			throw new ApkPackerException( 'apk file size error' );
		}

		if ( $output ) {
			if ( file_exists( $output ) ) {
				throw new ApkPackerException( 'apk file is already exists' );
			}

			//create new apk file
			$newApkFile = fopen( $output, 'wb' );
			if ( $newApkFile == false ) {
				throw new ApkPackerException( 'failed to create new apk file' );
			}

			if ( copy( $apkFile, $output ) ) {
				if ( $this->_setApkComment( $output, $channelName ) == false ) {
					throw new ApkPackerException( 'failed to write comment' );
				}
			} else {
				throw new ApkPackerException( 'failed to copy apk file' );
			}
		} else {
			if ( $this->_setApkComment( $apkFile, $channelName ) == false ) {
				throw new ApkPackerException( 'failed to write comment' );
			}
		}

		return true;
	}

	private function _setApkComment( $apkFile, $comment ) {
		$this->zipStream->open( $apkFile );

		return $this->zipStream->setZipComment( $comment );
	}

}

