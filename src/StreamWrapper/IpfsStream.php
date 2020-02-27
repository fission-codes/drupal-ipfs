<?php

namespace Drupal\ipfs\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use IPFS\StreamWrapper\IpfsStreamWrapper;

/**
 * Defines a Drupal stream wrapper for IPFS.
 */
class IpfsStream implements StreamWrapperInterface {

  use StringTranslationTrait;

  /**
   * The existing IPFS StreamWrapper.
   *
   * @var IPFS\StreamWrapper\IpfsStreamWrapper
   */
  protected $streamWrapper;

  public function __construct() {
    $this->streamWrapper = new IpfsStreamWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::WRITE_VISIBLE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('IPFS');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('InterPlanetary File System.');
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalUrl() {
    return $this->getUri();
  }

  /**
   * {@inheritdoc}
   */
  public function realpath() {
    return $this->getUri();
  }

  /**
   * {@inheritdoc}
   */
  public function dirname($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->getUri();
    }

    list($scheme, $target) = explode('://', $uri, 2);
    $dirname = dirname($target);

    if ($dirname == '.') {
      $dirname = '';
    }

    return $scheme . '://' . $dirname;
  }


  /* Pass all of these through */

// NOT STANDARD INTERFACE FOR THE FIRST THREE???

  public static function register($ipfs_host = 'http://127.0.0.1:5001', array $http_client_config = [])
  {
      if (in_array(static::PROTOCOL, stream_get_wrappers())) {
          stream_wrapper_unregister(static::PROTOCOL);
      }

      stream_wrapper_register(static::PROTOCOL, static::class, STREAM_IS_URL);

      self::setOption('ipfs_host', $ipfs_host);
      self::setOption('http_client_config', $http_client_config);
  }

  /**
   * Sets the absolute stream resource URI.
   *
   * @param string $uri
   *   A string containing the URI that should be used for this instance.
   */
  public function setUri($uri)
  {
      $this->streamWrapper->setUri($uri);
  }

  /**
   * Returns the stream resource URI.
   *
   * @return string
   *   Returns the current URI of the instance.
   */
  public function getUri()
  {
      return $this->streamWrapper->getUri();
  }

  /**
   * Support for dir_closedir().
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.dir-closedir.php
   */
  public function dir_closedir()
  {
      return $this->streamWrapper->dir_closedir();
  }

  /**
   * Support for dir_opendir().
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($path, $options)
  {
    return $this->streamWrapper->dir_opendir($path, $options);
  }

  /**
   * Support for dir_readdir().
   *
   * @return string|false
   *   Returns a string representing the next filename, or FALSE if there is
   *   no next file.
   *
   * @see http://php.net/manual/streamwrapper.dir-readdir.php
   */
  public function dir_readdir()
  {
    return $this->streamWrapper->dir_readdir();
  }

  /**
   * Support for dir_rewinddir().
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir()
  {
    return $this->streamWrapper->dir_rewinddir();
  }

  /**
   * Support for mkdir().
   *
   * Creating an empty directory in IPFS uses the mutable files API, which
   * manipulates IPFS objects as if they were a unix filesystem.
   *
   * @param string $path
   *   Directory which should be created.
   * @param int $mode
   *   The value passed to mkdir().
   * @param array $options
   *   A bit mask of values, such as STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.mkdir.php
   */
  public function mkdir($path, $mode, $options)
  {
    return $this->streamWrapper->mkdir($path, $mode, $options);
  }

  /**
   * Support for stream_cast().
   *
   * @param int $cast_as
   *   Can be STREAM_CAST_FOR_SELECT when stream_select() is calling
   *   stream_cast(), or STREAM_CAST_AS_STREAM when stream_cast() is called
   *   for other uses.
   *
   * @return resource|false
   *   The underlying stream resource or FALSE if stream_select() is not
   *   supported.
   *
   * @see http://php.net/manual/streamwrapper.stream-cast.php
   */
  public function stream_cast($cast_as)
  {
    return $this->streamWrapper->stream_cast($cast_as);
  }

  /**
   * Support for stream_close().
   *
   * @see http://php.net/manual/streamwrapper.stream-close.php
   */
  public function stream_close()
  {
    return $this->streamWrapper->stream_close();
  }

  /**
   * Support for stream_eof().
   *
   * @return bool
   *   Returns TRUE if the read/write position is at the end of the stream
   *   and if no more data is available to be read, or FALSE otherwise.
   *
   * @see http://php.net/manual/streamwrapper.stream-eof.php
   */
  public function stream_eof()
  {
    return $this->streamWrapper->stream_eof();
  }

  /**
   * Support for stream_flush() / fflush().
   *
   * @return bool
   *   Returns TRUE if the cached data was successfully stored (or if there
   *   was no data to store), or FALSE if the data could not be stored.
   *
   * @see http://php.net/manual/streamwrapper.stream-flush.php
   */
  public function stream_flush()
  {
    return $this->streamWrapper->stream_flush();
  }

  /**
   * Support for stream_lock().
   *
   * @param int $operation
   *   One the following: LOCK_SH, LOCK_EX, LOCK_UN or LOCK_NB.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation)
  {
    return $this->streamWrapper->stream_lock($operation);
  }

  /**
   * Support for stream_open().
   *
   * @param string $path
   *   Specifies the URL that was passed to the original function.
   * @param string $mode
   *   The mode used to open the file, as detailed for fopen().
   * @param int $options
   *   Holds additional flags set by the streams API.
   * @param string $opened_path
   *   If the path is opened successfully, and STREAM_USE_PATH is set in
   *   $options, $opened_path should be set to the full path of the
   *   file/resource that was actually opened.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.stream-open.php
   */
  public function stream_open($path, $mode, $options, &$opened_path) 
  {
    return $this->streamWrapper->stream_open($path, $mode, $options, $opened_path);
  }

  /**
   * Support for stream_read().
   *
   * @param int $count
   *   How many bytes of data from the current position should be returned.
   *
   * @return string
   *   If there are less than $count bytes available, returns as many as are
   *   available. If no more data is available, returns an empty string.
   *
   * @see http://php.net/manual/streamwrapper.stream-read.php
   */
  public function stream_read($count)
  {
    return $this->streamWrapper->stream_read($count);
  }

  /**
   * Support for stream_seek().
   *
   * @param int $offset
   *   The stream offset to seek to.
   * @param int $whence
   *   Possible values:
   *   - SEEK_SET - Set position equal to offset bytes.
   *   - SEEK_CUR - Set position to current location plus offset.
   *   - SEEK_END - Set position to end-of-file plus offset.
   *
   * @return bool
   *   Returns TRUE if the position was updated, FALSE otherwise.
   *
   * @see http://php.net/manual/streamwrapper.stream-seek.php
   */
  public function stream_seek($offset, $whence = SEEK_SET)
  {
    return $this->streamWrapper->stream_seek($offset, $whence = SEEK_SET);
  }

  /**
   * Support for stream_set_option().
   *
   * This method is called to set options on the stream.
   *
   * @param int $option
   *   One of:
   *   - STREAM_OPTION_BLOCKING: The method was called in response to
   *     stream_set_blocking().
   *   - STREAM_OPTION_READ_TIMEOUT: The method was called in response to
   *     stream_set_timeout().
   *   - STREAM_OPTION_WRITE_BUFFER: The method was called in response to
   *     stream_set_write_buffer().
   * @param int $arg1
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: The requested blocking mode:
   *     - 1 means blocking.
   *     - 0 means not blocking.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in seconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The buffer mode, STREAM_BUFFER_NONE or
   *     STREAM_BUFFER_FULL.
   * @param int $arg2
   *   If option is:
   *   - STREAM_OPTION_BLOCKING: This option is not set.
   *   - STREAM_OPTION_READ_TIMEOUT: The timeout in microseconds.
   *   - STREAM_OPTION_WRITE_BUFFER: The requested buffer size.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise. If $option is not implemented, FALSE
   *   should be returned.
   */
  public function stream_set_option($option, $arg1, $arg2)
  {
    return $this->streamWrapper->stream_set_option($option, $arg1, $arg2);
  }

  /**
   * Support for stream_stat().
   *
   * @return array
   *   An array as returned by stat().
   *
   * @see http://php.net/manual/streamwrapper.stream-stat.php
   * @see http://php.net/manual/function.stat.php
   */
  public function stream_stat()
  {
    return $this->streamWrapper->stream_stat();
  }

  /**
   * Support for stream_tell().
   *
   * @return int
   *   Returns the current position of the stream.
   *
   * @see http://php.net/manual/streamwrapper.stream-tell.php
   */
  public function stream_tell()
  {
    return $this->streamWrapper->stream_tell();
  }

  /**
   * Support for stream_truncate().
   *
   * @param int $new_size
   *   The data that should be stored into the underlying stream.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.stream-truncate.php
   */
  public function stream_truncate($new_size)
  {
    return $this->streamWrapper->stream_truncate($new_size);
  }

  /**
   * Support for stream_write().
   *
   * @param string $data
   *   The data that should be stored into the underlying stream.
   *
   * @return int
   *   Return the number of bytes that were successfully stored, or 0 if none
   *   could be stored.
   *
   * @see http://php.net/manual/streamwrapper.stream-write.php
   */
  public function stream_write($data)
  {
    return $this->streamWrapper->stream_write($data);
  }

  /**
   * Support for url_stat().
   *
   * @param string $path
   *   The file path or URL to stat.
   * @param int $flags
   *   Holds additional flags set by the streams API. It can hold one or more
   *   of the following values OR'd together:
   *   - STREAM_URL_STAT_LINK
   *   - STREAM_URL_STAT_QUIET
   *
   * @return array
   *   An associative array, as returned by stat().
   *
   * @see http://php.net/manual/streamwrapper.url-stat.php
   */
  public function url_stat($path, $flags)
  {
    return $this->streamWrapper->url_stat($path, $flags);
  }

   /**
     * Support for rename().
     *
     * The file or directory will not be renamed from the stream as IPFS
     * resources are immutable.
     *
     * @param string $path_from
     *   The URL to the current file.
     * @param string $path_to
     *   The URL which the $path_from should be renamed to.
     *
     * @return bool
     *   Returns TRUE on success or FALSE on failure.
     *
     * @see http://php.net/manual/streamwrapper.rename.php
     */
    public function rename($path_from, $path_to)
    {
      return $this->streamWrapper->rename($path_from, $path_to);
    }

    /**
     * Support for rmdir().
     *
     * The directory will not be removed from the stream as IPFS resources are
     * immutable.
     *
     * @param string $path
     *   The directory URL which should be removed.
     * @param int $options
     *   A bit mask of values, such as STREAM_MKDIR_RECURSIVE.
     *
     * @return bool
     *   Returns TRUE on success or FALSE on failure.
     *
     * @see http://php.net/manual/streamwrapper.rmdir.php
     */
    public function rmdir($path, $options)
    {
      return $this->streamWrapper->rmdir($path, $options);
    }

    /**
     * Support for stream_metadata().
     *
     * Creating an empty directory in IPFS does not make sense because the
     * resulting hash can not used subsequently, for example when trying to add
     * files to it.
     *
     * @param string $path
     *   The file path or URL to set metadata. Note that in the case of a URL,
     *   it must be a :// delimited URL. Other URL forms are not supported.
     * @param int $option
     *   One of: STREAM_META_TOUCH, STREAM_META_OWNER_NAME, STREAM_META_OWNER,
     *   STREAM_META_GROUP_NAME, STREAM_META_GROUP or STREAM_META_ACCESS.
     * @param mixed $value
     *   Depends on $option.
     *
     * @return bool
     *   Returns TRUE on success or FALSE on failure. If $option is not
     *   implemented, FALSE should be returned.
     *
     * @see http://php.net/manual/streamwrapper.stream-metadata.php
     */
    public function stream_metadata($path, $option, $value)
    {
      return $this->streamWrapper->stream_metadata($path, $option, $value);
    }

    /**
     * Support for unlink().
     *
     * The file will not be deleted from the stream as IPFS resources are
     * immutable.
     *
     * @param string $path
     *   A string containing the URI to the resource to delete.
     *
     * @return bool
     *   Returns TRUE on success or FALSE on failure.
     *
     * @see http://php.net/manual/streamwrapper.unlink.php
     */
    public function unlink($path)
    {
      return $this->streamWrapper->unlink($path);
    }





}
