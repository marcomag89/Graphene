#Graphene#
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
