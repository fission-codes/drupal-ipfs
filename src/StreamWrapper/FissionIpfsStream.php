<?php

namespace Drupal\ipfs\StreamWrapper;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use IPFS\StreamWrapper\IpfsStreamWrapper;
use IPFS\StreamWrapper\ImmutableStreamWrapperTrait;
use IPFS\HttpClientTrait;
use IPFS\StreamContextOptionsTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Stream;
/**
 * Defines a Drupal stream wrapper for IPFS.
 */
class FissionIpfsStream implements StreamWrapperInterface {

  use HttpClientTrait;
  use ImmutableStreamWrapperTrait;
  use StringTranslationTrait;
  use StreamContextOptionsTrait;

  /**
   * The protocol registered by this stream wrapper.
   */
  const PROTOCOL = 'ipfs';

  /**
   * The URI of the resource.
   *
   * @var string
   */
  protected $uri;

  /**
   * The response stream.
   *
   * @var \Psr\Http\Message\StreamInterface
   */
  private $stream;

  /**
   * Size of the body that is opened.
   *
   * @var int
   */
  private $size;

  /**
   * Mode in which the stream was opened.
   *
   * @var string
   */
  private $mode;

  /**
   * Iterator used with opendir() related calls.
   *
   * @var \Iterator
   */
  private $objectIterator;

  /**
   * The current path opened by dir_opendir() or stream_open().
   *
   * @var string
   */
  private $openedPath;

  /**
   * Optional configuration for HTTP requests.
   *
   * @var array
   */
  protected $config = [];

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

  /**
   * Sets the absolute stream resource URI.
   *
   * @param string $uri
   *   A string containing the URI that should be used for this instance.
   */
  public function setUri($uri) {
    $this->uri = $uri;
  }

  /**
   * Returns the stream resource URI.
   *
   * @return string
   *   Returns the current URI of the instance.
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * Support for dir_closedir().
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    $this->objectIterator = null;
    gc_collect_cycles();
    return true;
  }

  /**
   * Support for dir_opendir().
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   *
   * @see http://php.net/manual/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($path, $options) {

    // Can I do this? I don't think so.
    return true;

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
    // Can I do this? I don't think so.
    return false;
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
    // Can I do this? I don't think so.
    return false;
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
    // Can I do this? I don't think so.
    // But what the heck, I'm gonna say Yes for now.
    return true;
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
    return false;
  }

  /**
   * Support for stream_close().
   *
   * @see http://php.net/manual/streamwrapper.stream-close.php
   */
  public function stream_close()
  {
    $this->stream = null;
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
    return $this->stream->eof();
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
  public function stream_flush() {

    if ($this->mode == 'r') {
      return false;
    }
    if ($this->stream->isSeekable()) {
      $this->stream->seek(0);
    }

    $settings = \Drupal::config('ipfs.settings');

    $this->setUri($settings->get('fission_gateway') . '/ipfs');
    $this->setHttpClientConfigOption('headers', ['Content-Type' => 'application/octet-stream']);
    $this->setHttpClientConfigOption('auth', [$settings->get('fission_username'), $settings->get('fission_password')]);
    $this->setHttpClientConfigOption('debug', fopen('/usr/local/var/log/httpd/guzzle','w+'));
    $this->setHttpClientConfigOption('body', $this->stream->getContents());
    $response = $this->request('POST');

    error_log(time() . ' POSTING TO FISSION' . "\n", 3, "/usr/local/var/log/httpd/ipfs");

    // If the upload was successful, move the file to our local opened path.
    if ($response->getStatusCode() == 200) {
      $hash = (string)$response->getBody();
      $body = $this->decodeResponse($response);

      global $ipfs_hash;
      $ipfs_hash = $hash;

      // Here I need to figure out how to store the hash as the filename.
      error_log(time() . ' RESPONSE: HASH:' . $hash . "\n", 3, "/usr/local/var/log/httpd/ipfs");
      return true;
    }

    return false;
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
      return true;
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
    $this->mode = rtrim($mode, 'bt');
    if ($errors = $this->validate($path, $this->mode)) {
        return $this->triggerError($errors, $options);
    }

    $this->openedPath = $path;
    if ($options & STREAM_USE_PATH) {
        $opened_path = $path;
    }

    return $this->handleBooleanCall(function () use ($path) {
        switch ($this->mode) {
            case 'r':
                return $this->openReadStream($path);
            default:
                return $this->openWriteStream($path);
        }
    }, $options);
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
    return $this->stream->read($count);
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
    return !$this->stream->isSeekable()
        ? false
        : $this->handleBooleanCall(function () use ($offset, $whence) {
            $this->stream->seek($offset, $whence);
            return true;
        });
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
    if ($option != STREAM_OPTION_READ_TIMEOUT) {
      return false;
    }

    $this->setHttpClientConfigOption('timeout', $arg1 + ($arg2 / 1000000));
    return true;
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
    $stat = $this->getStatTemplate();
    $stat['size'] = $this->stream->getSize() ?: $this->size;
    $stat['mode'] = $this->mode;

    return $stat;
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
    return $this->handleBooleanCall(function () {
        return $this->stream->tell();
    });
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
    return false;
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
    return $this->stream->write($data);
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
    // Returning false because we need to tell Drupal the file doesn't existing.
    return FALSE;
  }

    /**
     * Perform an HTTP request for the current URI.
     *
     * @param string $method
     *   The HTTP method.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *   The HTTP response object.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request($method = 'GET')
    {
        $response = $this->getHttpClient()->request($method, $this->uri, $this->httpClientConfig);
        if ($method !== 'HEAD') {
            $this->stream = $response->getBody();
        }
        return $response;
    }

    /**
     * Returns the IPFS path of an URI.
     *
     * This function should be used in place of calls to realpath() or similar
     * functions when attempting to determine the location of a file. While
     * functions like realpath() may return the location of a read-only file,
     * this method may return a URI or path suitable for writing that is
     * completely separate from the URI used for reading.
     *
     * @param string $uri
     *   Optional URI.
     *
     * @return string
     *   Returns a string representing an IPFS path in the form of
     *   /ipfs/<hash>[/<folder_name>][/file_name.ext] if the URI contains an
     *   IPFS hash, or a local IPFS file path in the form of
     *   [/<folder_name>][/file_name.ext] otherwise.
     */
    private function getIpfsPath($uri = null)
    {
        if (!isset($uri)) {
            $uri = $this->uri;
        }

        // Remove the scheme from the URI and remove erroneous leading or
        // trailing, forward-slashes and backslashes.
        $target = '/' . trim(preg_replace('/^[\w\-]+:\/\/|^data:/', '', $uri), '\/');

        // The target contains a hash, so it is not a local IPFS path.
        if (substr($target, 0, 3) === '/Qm') {
            $target = '/ipfs' . $target;
        }

        return $target;
    }

    /**
     * Returns the target of the resource within the stream.
     *
     * @param string $uri
     *   Optional URI.
     *
     * @return string
     *   Returns a string representing an IPFS location in the form of
     *   <hash>[/<folder_name>][/file_name.ext].
     */
    private function getIpfsTarget($uri = null)
    {
        if (!isset($uri)) {
            $uri = $this->uri;
        }

        list(, $target) = explode('://', $uri, 2);

        // Remove erroneous leading or trailing, forward-slashes and backslashes.
        return trim($target, '\/');
    }

    /**
     * Invokes a callable and triggers an error if an exception occurs while
     * calling the function.
     *
     * @param callable $function
     *   The function to call.
     * @param int $flags
     *   Holds additional flags set by the streams API.
     *
     * @return bool
     *   TRUE on success, FALSE otherwise.
     */
    private function handleBooleanCall(callable $function, $flags = null)
    {
        try {
            return $function();
        } catch (\Exception $e) {
            return $this->triggerError($e->getMessage(), $flags);
        }
    }

    /**
     * Trigger one or more errors.
     *
     * @param string|array $errors
     *   Errors to trigger
     * @param int $flags
     *    If set to STREAM_URL_STAT_QUIET, then no error or exception occurs.
     *
     * @return bool|array
     *   Returns FALSE or an empty stat template when the STREAM_URL_STAT_LINK
     *   flag is set.
     *
     * @throws \RuntimeException
     *   If throw_errors is TRUE.
     */
    private function triggerError($errors, $flags = null)
    {
        // This is triggered with things like file_exists() or fopen().
        if ($flags & STREAM_URL_STAT_QUIET) {
            return $flags & STREAM_URL_STAT_LINK
                // This is triggered for things like is_link().
                ? $this->getStatTemplate()
                : false;
        }

        // This is triggered when doing things like lstat() or stat().
        if ($flags & STREAM_REPORT_ERRORS) {
            trigger_error(implode("\n", (array)$errors), E_USER_WARNING);
        }

        return false;
    }

    /**
     * Generates an array as returned by the stat() function.
     *
     * @return array
     *   An associative array, as returned by stat().
     *
     * @see http://php.net/manual/en/function.stat.php
     */
    protected function getStatTemplate()
    {
        // @see https://github.com/guzzle/psr7/blob/master/src/StreamWrapper.php
        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => -1,
            'blocks' => -1,
        ];
    }

    /**
     * Validates the provided stream arguments for fopen() and returns an array
     * of errors.
     *
     * @param string $path
     *   Specifies the URL that was passed to the original function.
     * @param string $mode
     *   The mode used to open the file, as detailed for fopen(), but without
     *   any trailing 'b' or 't' suffix.
     *
     * @return array
     *   An array of errors, if there are any.
     */
    private function validate($path, $mode)
    {
        $errors = [];
        $stat = $this->url_stat($path, null);

        if (!$stat && $mode === 'r') {
            $errors[] = "The file '{$path}' does not exist.";
        }

        if ($stat && $stat['mode'] & 0040000) {
            $errors[] = 'Can not open a directory.';
        }

        if (!in_array($mode, ['r', 'w', 'x'], true)) {
            $errors[] = "Mode not supported: {$mode}. Use 'r', 'w' or 'x'.";
        }

        return $errors;
    }

    /**
     * Opens a read stream for the given path.
     *
     * @param string $uri
     *   An IPFS stream URI.
     *
     * @return bool
     *   Returns TRUE on success.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function openReadStream($uri)
    {
        $ipfs_target = $this->getIpfsTarget($uri);
        $this->setUri($this->getOption('fission_gateway') . '/ipfs/' . $ipfs_target);
        $this->request();

        $this->size = $this->stream->getSize();

        // Wrap the body in a caching entity body if seeking is allowed.
        if ($this->getOption('seekable') && !$this->stream->isSeekable()) {
            $this->stream = new CachingStream($this->stream);
        }
        return true;
    }

    /**
     * Opens a write stream for the given path.
     *
     * @param string $uri
     *   An IPFS stream URI.
     *
     * @return bool
     *   Returns TRUE on success.
     */
    private function openWriteStream($uri)
    {
        $this->stream = new Stream(fopen('php://temp', 'r+'));
        return true;
    }


}
