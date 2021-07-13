<?php

namespace Elveneek;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Utils;

class LogCustomFilesHandler extends \Monolog\Handler\AbstractProcessingHandler{
    
	 protected const MAX_CHUNK_SIZE = 2147483647;

    /** @var resource|null */
    protected $stream;
	
	protected $parentRuleset;
	
    /** @var ?string */
    protected $url = null;
    /** @var ?string */
    private $errorMessage = null;
    /** @var ?int */
    protected $filePermission;
    /** @var bool */
    protected $useLocking;
    /** @var true|null */
    private $dirCreated = null;

    /**
     * @param resource|string $stream         If a missing path can't be created, an UnexpectedValueException will be thrown on first write
     * @param int|null        $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param bool            $useLocking     Try to lock log file before doing any writes
     *
     * @throws \InvalidArgumentException If stream is not a resource or string
     */
    public function __construct($stream, $level = Logger::DEBUG, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false)
    {
        parent::__construct($level, $bubble);
        if (is_resource($stream)) {
            $this->stream = $stream;
            stream_set_chunk_size($this->stream, self::MAX_CHUNK_SIZE);
        } elseif (is_string($stream)) {
            $this->url = Utils::canonicalizePath($stream);
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }

        $this->filePermission = $filePermission;
        $this->useLocking = $useLocking;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->dirCreated = null;
    }

    /**
     * Return the currently active stream if it is open
     *
     * @return resource|null
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Return the stream URL if it was configured with a URL and not an active resource
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record): void
    {
		
		//Проверяем стрим на изменение		
		$url = $this->url;
		
		$newFileName = $this->parentRuleset->customFilename($record);
		if($newFileName !==false){
			$newUrl = Utils::canonicalizePath($this->parentRuleset->getPath() . $newFileName);
			if($url !==$newUrl){
				$this->url = $newUrl;
				//Меняем стрим
				if (is_resource($this->stream)){
					$this->close();
				}
			}
		}
		
        if (!is_resource($this->stream)) {
            $url = $this->url;
			
			
			
            if (null === $url || '' === $url) {
                throw new \LogicException('Missing stream url, the stream can not be opened. This may be caused by a premature call to close().');
            }
            $this->createDir($url);
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            $stream = fopen($url, 'a');
            if ($this->filePermission !== null) {
                @chmod($url, $this->filePermission);
            }
            restore_error_handler();
            if (!is_resource($stream)) {
                $this->stream = null;

                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened in append mode: '.$this->errorMessage, $url));
            }
            stream_set_chunk_size($stream, self::MAX_CHUNK_SIZE);
            $this->stream = $stream;
        }

        $stream = $this->stream;
        if (!is_resource($stream)) {
            throw new \LogicException('No stream was opened yet');
        }

        if ($this->useLocking) {
            // ignoring errors here, there's not much we can do about them
            flock($stream, LOCK_EX);
        }

        $this->streamWrite($stream, $record);

        if ($this->useLocking) {
            flock($stream, LOCK_UN);
        }
    }

    /**
     * Write to stream
     * @param resource $stream
     * @param array    $record
     *
     * @phpstan-param FormattedRecord $record
     */
    protected function streamWrite($stream, array $record): void
    {
        fwrite($stream, (string) $record['formatted']);
    }

    private function customErrorHandler(int $code, string $msg): bool
    {
        $this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);

        return true;
    }

    private function getDirFromStream(string $stream): ?string
    {
        $pos = strpos($stream, '://');
        if ($pos === false) {
            return dirname($stream);
        }

        if ('file://' === substr($stream, 0, 7)) {
            return dirname(substr($stream, 7));
        }

        return null;
    }

    private function createDir(string $url): void
    {
        // Do not try to create dir if it has already been tried.
        if ($this->dirCreated) {
            return;
        }

        $dir = $this->getDirFromStream($url);
        if (null !== $dir && !is_dir($dir)) {
            $this->errorMessage = null;
            set_error_handler([$this, 'customErrorHandler']);
            $status = mkdir($dir, 0777, true);
            restore_error_handler();
            if (false === $status && !is_dir($dir)) {
                throw new \UnexpectedValueException(sprintf('There is no existing directory at "%s" and it could not be created: '.$this->errorMessage, $dir));
            }
        }
        $this->dirCreated = true;
    }
	
	public function setParentRuleset($parentClass){
		$this->parentRuleset = $parentClass;
	}
	
	/*
	foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $sapi = php_sapi_name();
                $handler->setFilenameFormat("{filename}-$sapi-{date}", 'Y-m-d');
            }
        }
	
	*/
}