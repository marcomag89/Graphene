#Graphene#

A framework for build action oriented REST web services.

##Install to your project##
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
	<appname>graphene_framework</appname>
	<approot></approot>
	<localhost></localhost>
	<storageDriver>CrudMySql</storageDriver>
	<storageConfig>
		<type>mysql</type>
		<host>127.0.0.1</host>
		<dbName>sanitronic_db</dbName>
		<prefix>stc</prefix>
		<username>root</username>
		<password>mysql</password>
	</storageConfig>
</graphene>
```
