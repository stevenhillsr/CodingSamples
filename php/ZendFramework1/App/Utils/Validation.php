<?php

/**
 * Validation Methods
 *
 *   based on searches to find examples and then modifies for specific use
 *      sources are many and varied and not captured...
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Utils_Validation
{
  /**
   *  Validate a phone number based on simple rules.
   *  Provide phone number (raw input)
   *  Returns true if the phone number striped of all but digits 
   *    is 7 to 10 digits in length and not all the same number.
   */
  public static function validPhone($phone)
  {
    $justDigits = preg_replace('/\D/','',$phone);
    
    if (!preg_match('/^\d{7,10}$/',$justDigits))
    {
      return FALSE;
    }
    
    if ($justDigits == '0000000' || $justDigits == '0000000000') { return FALSE; }
    if ($justDigits == '1111111' || $justDigits == '1111111111') { return FALSE; }
    if ($justDigits == '2222222' || $justDigits == '2222222222') { return FALSE; }
    if ($justDigits == '3333333' || $justDigits == '3333333333') { return FALSE; }
    if ($justDigits == '4444444' || $justDigits == '4444444444') { return FALSE; }
    if ($justDigits == '5555555' || $justDigits == '5555555555') { return FALSE; }
    if ($justDigits == '6666666' || $justDigits == '6666666666') { return FALSE; }
    if ($justDigits == '7777777' || $justDigits == '7777777777') { return FALSE; }
    if ($justDigits == '8888888' || $justDigits == '8888888888') { return FALSE; }
    if ($justDigits == '9999999' || $justDigits == '9999999999') { return FALSE; }
    if ($justDigits == '5551212') { return FALSE; }
    
    return TRUE;
  }

  /**
   *  Validate an email address.
   *  Provide email address (raw input)
   *  Returns true if the email address has the email 
   *  address format and the domain exists.
   */
  public static function validEmail($email,$checkDns = false)
  {
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
      $isValid = false;
    } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
        // local part length exceeded
        $isValid = false;
      } else {
        if ($domainLen < 1 || $domainLen > 255) {
          // domain part length exceeded
          $isValid = false;
        } else {
          if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
          } else {
            if (preg_match('/\\.\\./', $local)) {
              // local part has two consecutive dots
              $isValid = false;
            } else {
              if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
              } else {
                if (preg_match('/\\.\\./', $domain)) {
                  // domain part has two consecutive dots
                  $isValid = false;
                } else {
                  if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                                  str_replace("\\\\","",$local))) {
                    // character not valid in local part unless 
                    // local part is quoted
                    if (!preg_match('/^"(\\\\"|[^"])+"$/',
                        str_replace("\\\\","",$local))) {
                      $isValid = false;
                    }
                  }
                  if ($checkDns) {
                    if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
                      // domain not found in DNS
                      $isValid = false;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    return $isValid;
  }

}