# Install via Composer From your Magento root directory: 

<pre>
composer require jscriptz/module-subcats 
</pre>

# Enable the module:
<pre>
  bin/magento module:enable Jscriptz_Subcats 
</pre>

Then refresh Magento: 

<pre>
bin/magento setup:upgrade 
bin/magento setup:di:compile 
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
</pre>

# License Key
Up to 5 Domains (plus 5 dev domains):
Visit <a href="https://mage.jscriptz.com/jscriptz-subcats.html" target="_blank">https://mage.jscriptz.com/jscriptz-subcats.html</a>
