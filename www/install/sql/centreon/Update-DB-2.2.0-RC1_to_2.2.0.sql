 UPDATE `informations` SET `value` = '2.2.0' WHERE CONVERT( `informations`.`key` USING utf8 )  = 'version' AND CONVERT ( `informations`.`value` USING utf8 ) = '2.2.0-RC1' LIMIT 1;