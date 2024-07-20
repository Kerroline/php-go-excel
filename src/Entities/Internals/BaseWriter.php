<?php

namespace Kerroline\PhpGoExcel\Entities\Internals;

use Kerroline\PhpGoExcel\Interfaces\{
    GeneratorInterface,
    SerializedDataServiceInterface
};
use Kerroline\PhpGoExcel\Entities\Spreadsheet;

abstract class BaseWriter
{
    private const BITS_IN_BYTE = 8;
    private const WINDOWS = 'Win';

    public final function saveAsString(Spreadsheet $spreadsheet): string 
    {
        return $this->save($spreadsheet, '', true);
    }

    public final function saveAsFile(Spreadsheet $spreadsheet, string $filePath): void 
    {
        $this->save($spreadsheet, $filePath);
    }

    private final function save(Spreadsheet $spreadsheet, string $filePath, bool $asString = false): string 
    {
        $data = [
            'spreadsheet' => $spreadsheet->serialize(),
            'filename' => $filePath,
            'asString' => $asString,
        ];

        $dataService = $this->getDataService();
        $dataService->saveToFile($serializedData);

        $generator = $this->getGenerator();

        $result = $generator->execute($data);

        return $result;
    }

    private final function getDataService(): SerializedDataServiceInterface
    {
        return new SerializedDataService($this->getDataFilePath());
    }

    private final function getDataFilePath(): string
    {
        return dirname(__DIR__, 3) . '/data/json_data.json';
    }

    private final function getGenerator(): GeneratorInterface
    {
        return new Generator($this->getGeneratorCommandPath());
    }


    protected abstract function getGeneratorCommandPath(): string;
    
}
