<?php


require_once 'Zend/Validate/Abstract.php';

class Zend_Validate_Ccnum extends Zend_Validate_Abstract
{

    const LENGTH   = 'ccnumLength';

    const CHECKSUM = 'ccnumChecksum';

    protected static $_filter = null;

    protected $_messageTemplates = array(
        self::LENGTH   => "'%value%' must contain between 13 and 19 digits",
        self::CHECKSUM => "Luhn algorithm (mod-10 checksum) failed on '%value%'"
    );

    public function isValid($value)
    {
        $this->_setValue($value);

        if (null === self::$_filter) {

            require_once 'Zend/Filter/Digits.php';
            self::$_filter = new Zend_Filter_Digits();
        }

        $valueFiltered = self::$_filter->filter($value);

        $length = strlen($valueFiltered);

        if ($length < 13 || $length > 19) {
            $this->_error(self::LENGTH);
            return false;
        }

        $sum    = 0;
        $weight = 2;

        for ($i = $length - 2; $i >= 0; $i--) {
            $digit = $weight * $valueFiltered[$i];
            $sum += floor($digit / 10) + $digit % 10;
            $weight = $weight % 2 + 1;
        }

        if ((10 - $sum % 10) % 10 != $valueFiltered[$length - 1]) {
            $this->_error(self::CHECKSUM, $valueFiltered);
            return false;
        }

        return true;
    }

}
