<?php

namespace morgue\zip;

use morgue\zip\extraField\Zip64ExtendedInformation;

final class ExtraField implements ExtraFieldInterface
{
    /**
     * Minimum length an extra field if the data is empty
     */
    const MIN_LENGTH = 4;

    /**
     * Maximum length an extra field can have if the data is completely used
     */
    const MAX_LENGTH = self::MIN_LENGTH + self::DATA_MAX_LENGTH;

    /**
     * Data can not be longer than this (the length field has only 2 bytes)
     */
    const DATA_MAX_LENGTH = (255 * 255) - 1;

    /**
     * Mapping from header ID => implementing class
     * @var array
     */
    private static $extraFieldTypes = [
        Zip64ExtendedInformation::ID => Zip64ExtendedInformation::class,
    ];

    /**
     * @var int
     */
    private $headerId;

    /**
     * @var string
     */
    private $data;

    public function __construct(int $headerId, string $data = "")
    {
        $this->headerId = $headerId;
        $this->data = $data;
    }

    public static function parse(string $input, $context = null)
    {
        $parsed = \unpack(
            'vheaderId'
            . '/vdataLength',
            $input
        );

        if (isset(self::$extraFieldTypes[$parsed['headerId']])) {
            return self::$extraFieldTypes[$parsed['headerId']]::parse(\substr($input, 0, self::MIN_LENGTH + $parsed['dataLength']), $context);
        } else {
            return new self($parsed['headerId'], \substr($input, self::MIN_LENGTH, $parsed['dataLength']));
        }
    }

    /**
     * @param string $extraFieldData
     * @param null $context The context this extra field comes from, e.g. a CentralDirectoryHeader
     * @return ExtraFieldInterface[]
     */
    public static function parseAll(string $extraFieldData, $context = null)
    {
        $fields = [];

        $offset = 0;
        while ($offset < \strlen($extraFieldData)) {
            $fields[] = $field = self::parse(\substr($extraFieldData, $offset), $context);
            $offset += self::MIN_LENGTH + $field->getDataSize();
        }
        return $fields;
    }

    public function getHeaderId()
    {
        return $this->headerId;
    }

    public function getDataSize()
    {
        return \strlen($this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Register a custom extra field type that can parse extra fields identified by $id.
     * The class must implement ExtraFieldInterface
     *
     * @param int $id
     * @param string $className
     */
    public static function registerExtraFieldType(int $id, string $className)
    {
        if (!\is_a($className, ExtraFieldInterface::class, true)) {
            throw new \InvalidArgumentException("Extra field implementations must implement " . ExtraFieldInterface::class);
        }

        self::$extraFieldTypes[$id] = $className;
    }

    /**
     * Convert an extra field to it's binary string representation
     * @param ExtraFieldInterface $extraField
     * @return string
     */
    public static function marshal(ExtraFieldInterface $extraField)
    {
        return \pack('vv', $extraField->getHeaderId(), $extraField->getDataSize()) . $extraField->getData();
    }
}
