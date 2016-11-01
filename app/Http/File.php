<?php

namespace App\Http;

/**
 * 檔案 處理器
 *
 * @package App\Http
 */
class File extends \SplFileInfo
{
    private $originalName;
    private $mimeType;
    private $size;
    private $error;

    /**
     * constructor
     *
     * @param string $path
     * @param string $originalName
     * @param null|string $mimeType
     * @param null|int $size
     * @param null|int $error
     * @throws \Exception
     */
    public function __construct($path, $originalName, $mimeType = null, $size = null, $error = null)
    {
        if (ini_get('file_uploads') == false) {
            throw new \Exception('File uploads are not allowed in your php config!');
        }

        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->error = $error;

        parent::__construct($path);
    }

    /**
     * 取得檔案型態
     * @return null|string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * 取得原始檔名
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * 檔案是否有效
     * @return bool
     */
    public function isValid()
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->getPathname());
    }

    /**
     * 檔案是否為圖檔
     * @return int|false
     */
    public function isImage()
    {
        return exif_imagetype($this->getPathname());
    }

    /**
     * 移動檔案
     * @param string $target
     * @return string
     * @throws \Exception
     */
    public function move($target)
    {
        if ($this->isValid()) {
            if (!@move_uploaded_file($this->getPathname(), $target)) {
                $error = error_get_last();
                throw new \Exception(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
            }
            return $target;
        }
    }
}