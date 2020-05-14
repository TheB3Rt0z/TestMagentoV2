#!/bin/bash

if test $1 && test $1 == "build"; then
    #composer update;
    #bin/magento maintenance:enable;
    bin/magento setup:upgrade;
    bin/magento setup:di:compile;
    #bin/magento setup:static-content:deploy;
    #bin/magento setup:static-content:deploy en_US;
    #bin/magento setup:static-content:deploy de_DE;
    bin/magento indexer:reindex;
    #bin/magento cache:clean;
    bin/magento cache:flush;
    #bin/magento maintenance:disable;
fi

if test $1 && test $1 == "pack-ipib"; then
    cd app/code/Iways/PaypalInstalmentsBanners/;
    zip -r iways_paypal-instalments-banners-1.0.1.zip . -x './.git/*' '.gitignore' '.project' '*.md' '*.DS_Store';
fi

if test $1 && test $1 == "sniff"; then
	COMMAND="vendor/bin/phpcs -s --colors";
    EXTENSIONS="--extensions=css,js,json,php,phtml";

	PEAR_EXCLUDE="--exclude=Generic.PHP.DisallowShortOpenTag,Generic.Files.LineLength";
	PEAR_EXCLUDE+=",PEAR.Commenting.ClassComment";
	PEAR_EXCLUDE+=",PEAR.Commenting.FunctionComment";
	PEAR_EXCLUDE+=",PEAR.NamingConventions.ValidFunctionName";
	PEAR_EXCLUDE+=",PEAR.NamingConventions.ValidVariableName";

	printf "\nTesting with PEAR standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PEAR $PEAR_EXCLUDE;
    printf "\nDONE!\n";
    
    printf "\nTesting with PSR2 standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PSR2;
    printf "\nDONE!\n";

    MAGENTO2_EXCLUDE="--exclude=Generic.Files.LineLength,Generic.PHP.DisallowShortOpenTag,Generic.PHP.Syntax";
    
    printf "\nTesting with Magento2 standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=Magento2 $MAGENTO2_EXCLUDE;
    printf "\nDONE!\n";

    EXTENSIONS="--extensions=xml";

    PEAR_EXCLUDE="--exclude=Generic.Files.LineLength,Generic.PHP.DisallowShortOpenTag,PEAR.Commenting.FileComment"

    printf "\nTesting XML files with PEAR standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PEAR $PEAR_EXCLUDE;
    printf "\nDONE!\n";

    PSR2_EXCLUDE="--exclude=Generic.Files.LineLength,Generic.PHP.DisallowShortOpenTag";
    
    printf "\nTesting XML files with PSR2 standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=PSR2 $PSR2_EXCLUDE;
    printf "\nDONE!\n";

    MAGENTO2_EXCLUDE="--exclude=Generic.Files.LineLength,Generic.PHP.DisallowShortOpenTag,Generic.PHP.Syntax";

    printf "\nTesting XML files with Magento2 standard:\n";
    $COMMAND $2 $EXTENSIONS --standard=Magento2 $MAGENTO2_EXCLUDE;
    printf "\nDONE!\n";
fi

printf "\n";
