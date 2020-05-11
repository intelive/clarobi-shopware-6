# ClaroBi

## About
[ClaroBi][clarobi]

Allows ClaroBi to gather information from Products, Stocks, Orders, Invoices, Customers, Abandoned Carts.  
This module is available for Shopware6.

## Installation

To install module on Shopware 6, you can clone/download it from our *GitHub* repository.

### Download zip
* Click *Clone or download → Download ZIP*
* After the download is complete, unzip the folder and rename it to `Clarobi`
* Zip back the plugin folder
* Login into *Shopware 6 Admin* and go to *Settings → System → Plugins*, click *Upload plugin*
 and choose the plugin zip folder

#### Clone repository
* Open a terminal and go to your Shopware 6 project
* From here go to `plugins` folder
```
C:\path\to\shopware6\project>cd custom/plugins
```
* Clone the project by running: 
```
C:\path\to\shopware6\project\custom\plugins>git clone https://github.com/intelive/clarobi-shopware-6.git Clarobi
```

## Configuration

To configure the module you need to have an account on our website.  
> If you do not have one, please access [ClaroBi][clarobi] and start your free trial.  
After you have successfully registered you will receive from ClariBi 2 keys necessary for authentication   
>and data encryption ( `API KEY` and `API SECRET` ) and your `LICENSE KEY`.

After you have all the necessary keys, please follow the steps:    
* Go to *Shopware 6 Admin → Settings → System → Plugins*
* Find *Clarobi* plugin and click the 3 dots next to it, after click *Config* 
* In the configuration form, you will need to provide all the data as follows:
    * `Sales Channel` - make sure to have selected *All Sales Channels* (default option)
    * `Api License` key: license key provided by ClaroBi
    * `Api Key`: Api key provided by ClaroBi
    * `Api Secret`: Api secret provided by ClaroBi
* After all the inputs have been completed, click *Save*.

## Uninstall
* Go to *Shopware 6 Admin → Settings → System → Plugins*
* Click the 3 dots from the plugin row, click `Uninstall`

[clarobi]: https://clarobi.com/
[clarobi-login]: https://app.clarobi.com/login
[clarobi-repo]:  https://github.com/intelive/clarobi-shopware
[addons]: https://www.shopware.com/en/

