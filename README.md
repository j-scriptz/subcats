.2 Install via Composer From your Magento root directory: 
<code>
composer require jscriptz/module-subcats 
</code>
Then refresh Magento: 
<code>
bin/magento module:enable Jscriptz_Subcats 
bin/magento setup:upgrade 
bin/magento setup:di:compile 
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
</code>
