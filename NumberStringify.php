<?php

namespace app\components\helpers;

/**
 * Число русской прописью
 *
 * От тысячных долей до триллиона (12 цифр максимум)
 *
 * Идея позаимствована и отрефакторена:
 * @link https://habrahabr.ru/post/53210/
 */
class RussianNumber
{
    const MAX_DIGITS = 12;

    const NUL ='ноль';
    const DIGITS = [
        self::GENDER_MALE => ['','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'],
        self::GENDER_FEMALE => ['','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'],
    ];
    const TEN_TO_TWENTY = ['десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать'];
    const DECS = [2 => 'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто'];
    const HUNDREDS = ['','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот'];

    const CASE_SUBJECTIVE = 'subjective'; // Именительный падеж
    const CASE_GENITIVE = 'genitive'; // Родительный падеж
    const CASE_PLURAL = 'plural'; // Множественное число
    const GENDER = 'gender'; // Пол
    const GENDER_MALE = false;
    const GENDER_FEMALE = true;

    /** @var array Именование единиц измерения числовых регистров */
    protected $units = [
        [
            self::CASE_SUBJECTIVE => 'копейка',
            self::CASE_GENITIVE   => 'копейки',
            self::CASE_PLURAL     => 'копеек',
            self::GENDER => self::GENDER_FEMALE
        ],[
            self::CASE_SUBJECTIVE => 'рубль',
            self::CASE_GENITIVE   => 'рубля',
            self::CASE_PLURAL     => 'рублей',
            self::GENDER => self::GENDER_MALE
        ],[
            self::CASE_SUBJECTIVE => 'тысяча',
            self::CASE_GENITIVE   => 'тысячи',
            self::CASE_PLURAL     => 'тысяч',
            self::GENDER => self::GENDER_FEMALE
        ],[
            self::CASE_SUBJECTIVE => 'миллион',
            self::CASE_GENITIVE   => 'миллиона',
            self::CASE_PLURAL     => 'миллионов',
            self::GENDER => self::GENDER_MALE
        ],[
            self::CASE_SUBJECTIVE => 'миллиард',
            self::CASE_GENITIVE   => 'милиарда',
            self::CASE_PLURAL     => 'миллиардов',
            self::GENDER => self::GENDER_MALE
        ],
    ];

    protected $registers = [];

    /**
     * Раскидывает число по разрядам
     * @param float $number
     * @param int $digitsAfterZero Количество цифр после нуля
     * @throws \InvalidArgumentException
     */
    public function __construct($number, int $digitsAfterZero = 2)
    {
        if ($digitsAfterZero <= 0) {
            $rub = sprintf('%0' . static::MAX_DIGITS . 'd', floor($number));
        } elseif($digitsAfterZero > 3) {
            throw new \InvalidArgumentException('Больше 3 разрядов после запятой не поддерживается.');
        } else {
            $totalDigits = static::MAX_DIGITS + $digitsAfterZero + 1; // + точка
            list($rub, $kop) = explode('.', sprintf("%0$totalDigits.{$digitsAfterZero}f", floatval($number)));
        }
        if (intval($rub) > 0) {
            foreach(str_split($rub, 3) as $uk => $digits) { // by 3 symbols
                if (!intval($digits)) {
                    continue;
                }
                $this->registers[count($this->units) - $uk - 1] = $digits;
            }
        }
        else {
            $this->registers[1] = 0;
        }
        if (!empty($kop)) {
            $this->registers[0] = str_pad(str_pad($kop, $digitsAfterZero, '0', STR_PAD_RIGHT), 3, '0', STR_PAD_LEFT);
        }
    }

    public static function instantiate($number, int $digitsAfterZero = 2)
    {
        return new static($number, $digitsAfterZero);
    }

    public function getRegisters()
    {
        return $this->registers;
    }

    /**
     * Задаёт название числовому регистру из 3 цифр
     *
     * @param int    $register Регистр (0 - сотые доли, далее - по порядку)
     * @param string $subjectiveCase Именительный падеж
     * @param string $genitiveCase Родительный падеж
     * @param string $pluralCase Множественное число
     * @param bool   $femaleGender Пол
     * @return $this
     */
    public function setUnits(int $register, string $subjectiveCase, string $genitiveCase, string $pluralCase, bool $femaleGender = false)
    {
        $this->units[$register] = [
            static::CASE_SUBJECTIVE => $subjectiveCase,
            static::CASE_GENITIVE   => $genitiveCase,
            static::CASE_PLURAL     => $pluralCase,
            static::GENDER          => $femaleGender
        ];
        return $this;
    }

    /**
     * Применяет настройки именования числовых разрядов из приводит к единой строке
     */
    public function __toString()
    {
        $result = [];
        foreach ($this->registers as $register => $tripod) {

            if (empty($tripod) || $tripod == '000') {
                $result[] = static::NUL;
            } else {
                $gender = $this->units[$register][static::GENDER];
                list($hundreds, $decs, $units) = str_split($tripod, 1);

                if (!empty($hundreds)) {
                    $result[] = static::HUNDREDS[$hundreds]; // 1xx-9xx
                }
                if ($decs == 1) { // 10-19
                    $result[] = static::TEN_TO_TWENTY[$units];
                } else {
                    if ($decs > 1) { // 20-99
                        $result[] = static::DECS[$decs];
                    }
                    if (!empty($units)) { // 1-9
                        $result[] = static::DIGITS[$gender][$units];
                    }
                }
            }

            $morph = $this->morph($register, $tripod);
            if (!empty($morph)) { // Возможность не указывать единицу измерения регистра
                $result[] = $morph;
            }
        }
        return implode(' ', $result);
    }

    /**
     * Возвращает словоформу регистра
     *
     * @param int $register Числовой регистр из 3 цифр
     * @param int $n
     * @return string
     */
    protected function morph(int $register, $n) {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) {
            return $this->units[$register][static::CASE_PLURAL];
        }
        $n %= 10;
        if ($n > 1 && $n < 5) {
            return $this->units[$register][static::CASE_GENITIVE];
        }
        if ($n == 1) {
            return $this->units[$register][static::CASE_SUBJECTIVE];
        }
        return $this->units[$register][static::CASE_PLURAL];
    }

}