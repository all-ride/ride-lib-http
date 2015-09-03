<?php

namespace ride\library\http;

use ride\library\http\exception\HttpException;

/**
 * Encoder and decoder for a Data URI
 * @see https://tools.ietf.org/html/rfc2397
 */
class DataUri {

    /**
     * Default MIME when not defined
     * @var string
     */
    const DEFAULT_MIME_TYPE = 'text/plain';

    /**
     * Default charset encoding when no MIME or encoding is defined
     * @var string
     */
    const DEFAULT_ENCODING = 'US-ASCII';

    /**
     * Actual data
     * @var mixed
     */
    private $data;

    /**
     * MIME type of the data
     * @var string
     */
    private $mime;

    /**
     * Charset encoding of the data
     * @var string
     */
    private $encoding;

    /**
     * Flag to see if it should be base64 encoded
     * @var boolean
     */
    private $isBase64;

    /**
     * Constructs a new data URI
     * @param mixed $data Actual data
     * @param string|null $mime MIME type of the data
     * @param string|null $encoding Charset encoding of the data
     * @param boolean $isBase64 Flag to see if the data should be (or was)
     * base64 encoded
     * @return null
     */
    public function __construct($data, $mime = null, $encoding = null, $isBase64 = false) {
        $this->data = $data;
        $this->mime = $mime;
        $this->encoding = $encoding;
        $this->isBase64 = $isBase64;
    }

    /**
     * Gets the actual data of this URI
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Gets the MIME type of the data
     * @return string|null
     */
    public function getMimeType() {
        if ($this->mime === null) {
            return self::DEFAULT_MIME_TYPE;
        }

        return $this->mime;
    }

    /**
     * Gets the charset encoding of the data
     * @return string|null
     */
    public function getEncoding() {
        if ($this->mime === null && $this->encoding === null) {
            return self::DEFAULT_ENCODING;
        }

        return $this->encoding;
    }

    /**
     * Gets whether the data should be (or was) base64 encoded
     * @return boolean
     */
    public function isBase64() {
        return $this->isBase64;
    }

    /**
     * Encodes the data of this instance into a data URI
     * @return string
     */
    public function encode() {
        $dataUri = 'data:';

        $mime = $this->getMimeType();
        $encoding = $this->getEncoding();
        $isDefaultMime = $mime === self::DEFAULT_MIME_TYPE && $encoding === self::DEFAULT_ENCODING;

        if (!$isDefaultMime) {
            $dataUri .= $mime;
            if ($encoding) {
                $dataUri .= ';charset=' . $encoding;
            }
        }

        $data = $this->data;

        if ($this->isBase64) {
            $dataUri .= ';base64,';
            $data = base64_encode($data);
        } else {
            $dataUri .= ',';
        }

        $data = rawurlencode($data);

        return $dataUri . $data;
    }

    /**
     * Decodes a data URI into an instance to work with
     * @return DatUri
     * @throws \ride\library\http\exception\HttpException when the provided data
     * could not be decoded
     */
    public static function decode($data) {
        $positionSeparator = strpos($data, ',');

        if (!substr($data, 0, 5) === 'data:') {
            throw new HttpException('Could not decode the provided data URI: string should start with \'data:\'');
        } elseif (!$positionSeparator) {
            throw new HttpException('Could not decode the provided data URI: no comma separator found between media type and actual data');
        }

        if ($positionSeparator === 5) {
            $type = null;
            $mime = self::DEFAULT_MIME_TYPE;
            $encoding = self::DEFAULT_ENCODING;
        } else {
            $type = substr($data, 5, $positionSeparator - 5);
            $encoding = null;
        }

        $data = substr($data, $positionSeparator + 1);
        $isBase64 = false;

        if (strpos($type, ';')) {
            $tokens = explode(';', $type);

            while ($token = array_shift($tokens)) {
                if ($token === 'base64') {
                    $isBase64 = true;
                } elseif (strpos($token, '=') !== false) {
                    $subtokens = explode('=', $token, 2);
                    if ($subtokens[0] == 'charset') {
                        $encoding = $subtokens[1];
                    }
                } else {
                    $mime = $token;
                }
            }
        } elseif ($type !== null) {
            $mime = $type;
        }

        $data = rawurldecode($data);
        if ($isBase64) {
            $data = base64_decode($data);
        }

        return new self($data, $mime, $encoding, $isBase64);
    }

}
