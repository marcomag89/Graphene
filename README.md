#Graphene (alpha)#
This framework allows you to create Action Oriented REST services with more less lines of code, like this:
```PHP
class HelloWorld extends Action{
	public function run ()
	{
		$this->sendMessage('Hello world');
	}
}
```

##Creating Graphene project##
1. Download Graphene as ZIP file and extract content into a folder named "Graphene".
2. Copy Graphene folder into your project root.

##Configuration##
Open "configs" folder and edit "settings.xml" file
```XML
<graphene>
	<debug>false</debug>
	<showLog>false</showLog>
	<baseUrl>Graphene</baseUrl>
	<moduleurl>modules</moduleurl>
	<syntax>JSON</syntax>
	<appname>your_app_name</appname>
	<approot>root_of_your_app</approot>
	<localhost>Host_of_your_production_server</localhost>
	<storageDriver>CrudMySql</storageDriver>
	<storageConfig>
		<type>mysql</type>
		<host>db_Host</host>
		<dbName>db_name</dbName>
		<prefix>tables_prefix</prefix>
		<username>db_usernale</username>
		<password>db_password</password>
	</storageConfig>
</graphene>
```
<b>StorageDriver</b> is a name of database driver. Is now supported mySql for native storage

##Ready to go##
Before starting your project, check <your_project_address>/_system/status. 
If you have message like this:
```JSON
{
    "GrapheneStatus": {
        "framework-infos": "Graphene 0.1b developed by Marco Magnetti <marcomagnetti@gmail.com>",
        "framework-version": "0.1b",
        "app-name": "your_app_name",
        "installed-modules": 8,
        "db": {
            "connectionStatus": "ok",
            "driver": "mySql CRUD-JSON driver v.1b, for Graphene 0.1b"
        },
        "server": {
            "time": "2014-09-26 00:21:44"
        }
    }
}
```
you are ready to start your project with graphene ;)

##Wiki##
we are very excited that you want to use Graphene therefore we are working so that you can use it to its full potential by writing up to date wiki.
https://github.com/marcomag89/Graphene/wiki
###HowTo###
<b>Hello world</b>https://github.com/marcomag89/Graphene/wiki/examples/helloWorld
