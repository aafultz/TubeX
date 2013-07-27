<?php


require_once 'Zend/Exception.php';

require_once 'Zend/Gdata/Gapps/Error.php';

class Zend_Gdata_Gapps_ServiceException extends Zend_Exception
{
    
    protected $_rootElement = "AppsForYourDomainErrors";

    protected $_errors = array();

    public function __construct($errors = null) {
        parent::__construct("Server errors encountered");
        if ($errors !== null) {
            $this->setErrors($errors);
        }
    }

    public function addError($error) {
        // Make sure that we don't try to index an error that doesn't 
        // contain an index value.
        if ($error->getErrorCode() == null) {
            require_once 'Zend/Gdata/App/Exception.php';
            throw new Zend_Gdata_App_Exception("Error encountered without corresponding error code.");
        }
        
        $this->_errors[$error->getErrorCode()] = $error;
    }

    public function setErrors($array) {
        $this->_errors = array();
        foreach ($array as $error) {
            $this->addError($error);
        }
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getError($errorCode) {
        if (array_key_exists($errorCode, $this->_errors)) {
            $result = $this->_errors[$errorCode];
            return $result;
        } else {
            return null;
        }
    }

    public function hasError($errorCode) {
        return array_key_exists($errorCode, $this->_errors);
    }

    public function importFromString($string) {
        if ($string) {
            // Check to see if an AppsForYourDomainError exists
            //
            // track_errors is temporarily enabled so that if an error 
            // occurs while parsing the XML we can append it to an 
            // exception by referencing $php_errormsg
            @ini_set('track_errors', 1);
            $doc = new DOMDocument();
            $success = @$doc->loadXML($string);
            @ini_restore('track_errors');
            
            if (!$success) {
                require_once 'Zend/Gdata/App/Exception.php';
                // $php_errormsg is automatically generated by PHP if 
                // an error occurs while calling loadXML(), above.
                throw new Zend_Gdata_App_Exception("DOMDocument cannot parse XML: $php_errormsg");
            }
            
            // Ensure that the outermost node is an AppsForYourDomain error.
            // If it isn't, something has gone horribly wrong.
            $rootElement = $doc->getElementsByTagName($this->_rootElement)->item(0);
            if (!$rootElement) {
                require_once 'Zend/Gdata/App/Exception.php';
                throw new Zend_Gdata_App_Exception('No root <' . $this->_rootElement . '> element found, cannot parse feed.');
            }
            
            foreach ($rootElement->childNodes as $errorNode) {
                if (!($errorNode instanceof DOMText)) {
                    $error = new Zend_Gdata_Gapps_Error();
                    $error->transferFromDom($errorNode);
                    $this->addError($error);
                }
            }
            return $this;
        } else {
            require_once 'Zend/Gdata/App/Exception.php';
            throw new Zend_Gdata_App_Exception('XML passed to transferFromXML cannot be null');
        }
        
    }

    public function __toString() {
        $result = "The server encountered the following errors processing the request:";
        foreach ($this->_errors as $error) {
            $result .= "\n" . $error->__toString();
        }
        return $result;
    }
}
