#!/bin/bash

if test $1 == "sniff"; then
	COMMAND="vendor/bin/phpcs -s --colors";
    EXTENSIONS="--extensions=css,js,php,phtml";
	PEAR_EXCLUDE="--exclude=Generic.PHP.DisallowShortOpenTag,Generic.Files.LineLength";
	PEAR_EXCLUDE+=",PEAR.Commenting.ClassComment";
	PEAR_EXCLUDE+=",PEAR.Commenting.FunctionComment";
	PEAR_EXCLUDE+=",PEAR.NamingConventions.ValidFunctionName";
	PEAR_EXCLUDE+=",PEAR.NamingConventions.ValidVariableName";

	printf "\nTesting with PEAR Standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PEAR $PEAR_EXCLUDE;
    printf "\nDONE!\n";
    
    printf "\nTesting with PSR2 Standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PSR2;
    printf "\nDONE!\n";
    
    printf "\nTesting with Magento2 Standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=Magento2;
    printf "\nDONE!\n";
fi;

printf "\n";
