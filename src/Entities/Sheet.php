<?php

namespace Kerroline\PhpGoExcel\Entities;

use Kerroline\PhpGoExcel\Interfaces\SerializableEntityInterface;

class Sheet implements SerializableEntityInterface
{
    public const CellValueAddressKey = 'address';
    public const CellValueValueKey   = 'value';

    public const MergeFromKey = 'from';
    public const MergeToKey   = 'to';

    public const COLUMN_SETTINGS_KEY = 'columnSettings';
    public const COLUMN_WIDTHS_KEY = 'widths';
    public const COLUMN_AUTO_SIZE_KEY = 'autoSize';
    public const COLUMN_ALL_AUTO_SIZE_KEY = 'allAutoSize';
    /**
     * [Description for $title]
     *
     * @var string
     */
    protected $title;

    /**
     * [Description for $filledCell]
     *
     * @var array
     */
    protected $filledCellList;

    /**
     * [Description for $styleList]
     *
     * @var array
     */
    protected $styleList;

    /**
     * [Description for $maxRow]
     *
     * @var int
     */
    protected $maxRowIndex;

    /**
     * [Description for $maxColumn]
     *
     * @var int
     */
    protected $maxColumnIndex;

    /**
     * [Description for $mergeCellList]
     *
     * @var array
     */
    protected $mergeCellList;

    /** @var array<string,float> */
    protected $columnsWidth;

    /** @var array<string,string> */
    protected $columnsAutoSize;
    
    /** @var bool */
    protected $allColumnsAutoSize;

    /**
     * @var array
     */
    protected $rowHeightList;


    public function __construct(string $title)
    {
        $this->setTitle($title);

        $this->filledCellList = [];

        $this->styleList = [];

        $this->mergeCellList = [];

        $this->columnsWidth = [];
        $this->columnsAutoSize = [];
        $this->allColumnsAutoSize = false;

        $this->rowHeightList = [];
    }

    public function serialize(): array
    {
        $serializedStyleList = [];

        foreach ($this->styleList as $styleSettings) {

            $serializedStyle = array_merge($styleSettings, $styleSettings['style']->serialize());

            unset($serializedStyle['style']);

            $serializedStyleList[] = $serializedStyle;
        }
        
        $columnsWidth = empty($this->columnsWidth) ? null : $this->columnsWidth;
        $columnsWithAutoSize = empty($this->columnsAutoSize) ? null : $this->columnsAutoSize;
        $rowsHeight = empty($this->rowHeightList) ? null : $this->rowHeightList;

        return [
            'title'           => $this->title,
            'cellList'        => array_values($this->filledCellList),
            'styleList'       => $serializedStyleList,
            'mergeList'       => array_values($this->mergeCellList),
            'rowHeightList'   => $rowsHeight,

            self::COLUMN_SETTINGS_KEY => [
                self::COLUMN_WIDTHS_KEY        => $columnsWidth,
                self::COLUMN_AUTO_SIZE_KEY     => $columnsWithAutoSize,
                self::COLUMN_ALL_AUTO_SIZE_KEY => $this->allColumnsAutoSize,
            ],
        ];
    }


    public function setTitle(string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }


    #region Test Garbage
    public function getMaxRowIndex(): int
    {
        return $this->maxRowIndex;
    }

    public function getMaxColumnIndex(): int
    {
        return $this->maxColumnIndex;
    }

    public function getMaxColumnSymbol(): int
    {
        return static::stringFromColumnIndex($this->maxColumnIndex);
    }
    #endregion Test Garbage

    #region Set Cell Value

    //TODO: Validate string and array cell index
    /**
     * @param string $cell
     * @param mixed $value
     */
    public function setCellValueByAddress(string $cell, $value): void
    {
        [$colSymbol, $rowIndex] = static::coordinateFromString($cell);

        $colIndex = static::columnIndexFromString($colSymbol);

        $this->setCellValue($colIndex, $rowIndex, $value);
    }

    /**
     * [Description for setCellValueByCoordinate]
     *
     * @param int $colIndex
     * @param int $rowIndex
     * @param mixed $value
     */
    public function setCellValueByCoordinates(int $colIndex, int $rowIndex, $value): void
    {
        $this->setCellValue($colIndex, $rowIndex, $value);
    }

    public function setRow(int $rowIndex, array $values): void
    {
        // Обрезаем ключи, если вдруг они там заданы
        $values = array_values($values);

        foreach ($values as $colIndex => $value) {
            $colIndex++;

            $this->setCellValueByCoordinates($colIndex, $rowIndex, $value);
        }
    }
    #endregion Set Cell Value

    #region Set Cell Style

    public function setCellStyle(string $cell, Style &$style)
    {
        $this->styleList[] = [
            'range' => [
                'from'  => $cell,
                'to'    => $cell,
            ],
            'style' => $style,
        ];
    }

    public function setCellRangeStyle(string $fromCell, string $toCell, Style &$style)
    {
        $this->styleList[] = [
            'range' => [
                'from'  => $fromCell,
                'to'    => $toCell,
            ],
            'style' => $style,
        ];
    }

    public function setCellStyleByCoordinates(int $colIndex, int $rowIndex, Style &$style)
    {
        $cell = $this->calculateCellAddress($colIndex, $rowIndex);

        $this->styleList[] = [
            'range' => [
                'from'  => $cell,
                'to'    => $cell,
            ],
            'style' => $style,
        ];
    }

    public function setCellStyleByRangeCoordinates(int $fromColIndex, int $fromRowIndex, int $toColIndex, int $toRowIndex, Style $style)
    {
        $fromCell = $this->calculateCellAddress($fromColIndex, $fromRowIndex);
        $toCell = $this->calculateCellAddress($toColIndex, $toRowIndex);

        $this->styleList[] = [
            'range' => [
                'from'  => $fromCell,
                'to'    => $toCell,
            ],
            'style' => $style,
        ];
    }
    #endregion Set Cell Style

    #region Merge Cell
    public function mergeCellByAddress(string $fromCell, string $toCell)
    {
        $this->mergeCellList[] = [
            self::MergeFromKey => $fromCell,
            self::MergeToKey   => $toCell,
        ];
    }

    public function mergeCellByCoordinate(int $fromColIndex, int $fromRowIndex, int $toColIndex, int $toRowIndex)
    {
        $fromCell = $this->calculateCellAddress($fromColIndex, $fromRowIndex);
        $toCell = $this->calculateCellAddress($toColIndex, $toRowIndex);

        $this->mergeCellList[] = [
            self::MergeFromKey => $fromCell,
            self::MergeToKey   => $toCell,
        ];
    }

    // public function mergeRow(string $fromCell, int $count)
    // {
    // }

    // public function mergeCol(string $fromCell, int $count)
    // {
    // }
    #endregion Merge Cell

    #region Column Size
    //TODO: Validate Sheet set columns width methods

    /**
     * Устанавливает ширину в пикселях для колонки
     */
    public function setColumnWidthByAddress(string $colSymbol, float $width): Sheet
    {
        $this->columnsWidth[$colSymbol] = $width;

        return $this;
    }

    /**
     * Устанавливает ширину в пикселях для колонки переданной в качестве индекса.
     * Прим. (А = 1)
     */
    public function setColumnWidthByIndex(int $colIndex, float $width): Sheet
    {
        $colSymbol = static::stringFromColumnIndex($colIndex);

        $this->columnsWidth[$colSymbol] = $width;

        return $this;
    }

    /**
     * ['A' => 10, 'B' => 12, ... 'E' => 5, ...] is associative 
     * or 
     * ['key1' => 10, 'key2' => 12, 'key3' => 3 ...] is not associative ($isAssociative = false)
     * auto transform to associative ['A' => 10, 'B' => 12, 'C' => 3 ...]
     */
    public function setColumnsWidth(array $columns, bool $isAssociative = true): void
    {
        if ($isAssociative) {
            foreach ($columns as $colSymbol => $width) {
                $this->columnsWidth[$colSymbol] = $width;
            }

            return;
        }

        foreach (array_values($columns) as $index => $width) {
            $colSymbol = static::stringFromColumnIndex($index + 1);

            $this->columnsWidth[$colSymbol] = $width;
        }
    }

    public function setAllColumnsAutoSize(bool $value = true): Sheet
    {
        $this->allColumnsAutoSize = $value;

        return $this;
    }

    public function setColumnAutoSizeByAddress(string $colSymbol, bool $autoSize = true): Sheet
    {
        $this->setColumnAutoSize($colSymbol, $autoSize);

        return $this;
    }

    public function setColumnAutoSizeByIndex(int $colIndex, bool $autoSize = true): Sheet
    {
        $colSymbol = static::stringFromColumnIndex($colIndex);

        $this->setColumnAutoSize($colSymbol, $autoSize);

        return $this;
    }


    private function setColumnAutoSize(string $colSymbol, bool $autoSize): void
    {
        if ($autoSize) {
            $this->columnsAutoSize[$colSymbol] = $colSymbol;
        } else {
            unset($this->columnsAutoSize[$colSymbol]);
        }
    }
    #endregion Column Size

    #region Row Size
    public function setRowHeight(int $rowIndex, int $height): void
    {
        $this->rowHeightList[$rowIndex] = $height;
    }

    public function setRowsHeight(array $rows): void
    {
        foreach ($rows as $rowIndex => $height) {
            $this->rowHeightList[$rowIndex] = $height;
        }
    }
    #endregion Row Size

    #region PHP Spreadsheet Coordinate methods

    /**
     * A1 = 1, 1
     *
     * @param int $colIndex
     * @param int $rowIndex
     *
     * @return [type]
     *
     */
    protected function calculateCellAddress(int $colIndex, int $rowIndex): string
    {
        $symbol = static::stringFromColumnIndex($colIndex);

        $address = "{$symbol}{$rowIndex}";

        $this->validateCellAddress($address);

        return $address;
    }

    /**
     * Column index from string.
     *
     * @param string $pString eg 'A'
     *
     * @return int Column index (A = 1)
     */
    public static function columnIndexFromString(string $pString): int
    {
        //    Using a lookup cache adds a slight memory overhead, but boosts speed
        //    caching using a static within the method is faster than a class static,
        //        though it's additional memory overhead
        static $indexCache = [];

        if (isset($indexCache[$pString])) {
            return $indexCache[$pString];
        }
        //    It's surprising how costly the strtoupper() and ord() calls actually are, so we use a lookup array rather than use ord()
        //        and make it case insensitive to get rid of the strtoupper() as well. Because it's a static, there's no significant
        //        memory overhead either
        static $columnLookup = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9, 'J' => 10, 'K' => 11, 'L' => 12, 'M' => 13,
            'N' => 14, 'O' => 15, 'P' => 16, 'Q' => 17, 'R' => 18, 'S' => 19, 'T' => 20, 'U' => 21, 'V' => 22, 'W' => 23, 'X' => 24, 'Y' => 25, 'Z' => 26,
            'a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6, 'g' => 7, 'h' => 8, 'i' => 9, 'j' => 10, 'k' => 11, 'l' => 12, 'm' => 13,
            'n' => 14, 'o' => 15, 'p' => 16, 'q' => 17, 'r' => 18, 's' => 19, 't' => 20, 'u' => 21, 'v' => 22, 'w' => 23, 'x' => 24, 'y' => 25, 'z' => 26,
        ];

        //    We also use the language construct isset() rather than the more costly strlen() function to match the length of $pString
        //        for improved performance
        if (isset($pString[0])) {
            if (!isset($pString[1])) {
                $indexCache[$pString] = $columnLookup[$pString];

                return $indexCache[$pString];
            } elseif (!isset($pString[2])) {
                $indexCache[$pString] = $columnLookup[$pString[0]] * 26 + $columnLookup[$pString[1]];

                return $indexCache[$pString];
            } elseif (!isset($pString[3])) {
                $indexCache[$pString] = $columnLookup[$pString[0]] * 676 + $columnLookup[$pString[1]] * 26 + $columnLookup[$pString[2]];

                return $indexCache[$pString];
            }
        }

        throw new \Exception('Column string index can not be ' . ((isset($pString[0])) ? 'longer than 3 characters' : 'empty'));
    }

    /**
     * String from column index.
     *
     * @param int $columnIndex Column index (A = 1)
     *
     * @return string
     */
    public static function stringFromColumnIndex(int $columnIndex): string
    {
        static $indexCache = [];

        if (!isset($indexCache[$columnIndex])) {
            $indexValue = $columnIndex;
            $base26 = null;
            do {
                $characterValue = ($indexValue % 26) ?: 26;
                $indexValue = ($indexValue - $characterValue) / 26;
                $base26 = chr($characterValue + 64) . ($base26 ?: '');
            } while ($indexValue > 0);
            $indexCache[$columnIndex] = $base26;
        }

        return $indexCache[$columnIndex];
    }

    public static function coordinateFromString(string $cellAddress): array
    {
        if (preg_match('/^(?<col>\$?[A-Z]{1,3})(?<row>\$?\d{1,7})$/i', $cellAddress, $matches)) {
            return [$matches['col'], $matches['row']];
        } elseif ($cellAddress === '') {
            throw new \Exception('Cell coordinate can not be zero-length string');
        }

        throw new \Exception('Invalid cell coordinate ' . $cellAddress);
    }
    #endregion PHP Spreadsheet Coordinate methods


    private function setCellValue(int $colIndex, int $rowIndex, $value)
    {
        $cell = $this->calculateCellAddress($colIndex, $rowIndex);

        $this->validateCellAddress($cell);

        $this->updateMaxCell($colIndex, $rowIndex);

        $this->filledCellList[$cell] = [
            self::CellValueAddressKey => $cell,
            self::CellValueValueKey   => $value,
        ];
    }

    private function validateCellAddress(string $cellAddress): void
    {
        $pattern = '/^(?:[A-Z]|[A-Z][A-Z]|[A-X][A-F][A-D])(?:[1-9]|[1-9][0-9]|[1-9][0-9][0-9]|[1-9][0-9][0-9][0-9]|[1-9][0-9][0-9][0-9][0-9]|[1-9][0-9][0-9][0-9][0-9][0-9]|10[0-3][0-9][0-9][0-9][0-9]|104[0-7][0-9][0-9][0-9]|1048[0-4][0-9][0-9]|10485[0-6][0-9]|104857[0-6])$/';
        
        if (!preg_match($pattern, $cellAddress)) {
            throw new \Exception('Invalid cell coordinate ' . $cellAddress);
        }
    }

    private function updateMaxCell(int $colIndex, int $rowIndex)
    {
        if ($this->maxColumnIndex < $colIndex) {
            $this->maxColumnIndex = $colIndex;
        }

        if ($this->maxRowIndex < $rowIndex) {
            $this->maxRowIndex = $rowIndex;
        }
    }
}
