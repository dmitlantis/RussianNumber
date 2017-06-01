# RussianNumber
Представляет число русской прописью

###### Примеры использования:

```php
echo new RussianNumber(123.45);
// сто двадцать три рубля сорок пять копеек

echo RussianNumber::instantiate(100500.5, 1)
    ->setUnits(0, 'десятая', 'десятых', 'десятых', RussianNumber::GENDER_FEMALE)
    ->setUnits(1, 'целая', 'целой', 'целых', RussianNumber::GENDER_FEMALE);
// сто тысяч пятьсот целых пять десятых
```