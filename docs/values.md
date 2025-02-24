ref `Kerroline/gophp-excel/src/Entities/Sheet`

Вы можете установить значение конкретной ячейки двумя способами:

 1) через адрес ячейки:
    ```php
    $sheet->setCellValue('A1', 123);
    ```
 2) через координаты ячейки:
    ```php
    $sheet->setCellValueByCoordinates(1, 1, 123);
    ```
    В рамках мы заполняем ячейку А1 значением 123
     |   |  A  |
     |---|:---:|
     | 1 | 123 | 

Дополнительно вы можете установить значения нескольких ячеек в одной строке.
Для этого можно воспользоваться методом 
```php
setRow($rowIndex, array $values)
```

```php
$rowHeader = ['ID', 'Title', 'Price'];
$rowValues = [1, 'Product-F', 250];
$sheet->setRow(1, $rowHeader);
$sheet->setRow(2, $rowValues);
```

В рамках примера мы заполняем 2 строки значениями:

|   | A  |     B     |   C   | 
|---|:--:|:---------:|:-----:|
| 1 | ID | Title     | Price | 
| 2 | 1  | Product-F | 250   |

