<?php
/**
 * Latvian language file for online radio module
 */

$dictionary = array(
/* general */
'RADIO_NAME' =>'Interneta Rādio'


/* end module names */
);

foreach ($dictionary as $k=>$v)
{
   if (!defined('LANG_' . $k))
   {
      define('LANG_' . $k, $v);
   }
}

?>
