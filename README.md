#Graphene (alpha)#
This framework allows you to create Action Oriented REST services with less lines of code, like this:
```PHP
class HelloWorld extends Action{
	public function run ()
	{
		$this->sendMessage('Hello world');
	}
}
```

##Install Graphene in 4 steps##
1. [Download Graphene](https://github.com/marcomag89/Graphene/releases/latest) as ZIP file and extract content into your project root.
2. Create [settings.json](#setting-up-graphene) file.
3. Check if you are [ready to go](https://github.com/marcomag89/Graphene/wiki#ready-to-go) on `<yourProject.com>/system/status` on your browser.
4. Enjoy creating your [first module](https://github.com/marcomag89/Graphene/wiki/Hello-World-tutorial)!

##Setting up graphene##
Create `settings.json` file into your project root pairs to Graphene folder and `.htaccess` or `web.config` file, and paste the following content.

```JSON
{
  "debug"        : false,
  "baseUrl"      : "",
  "frameworkDir" : "",
  "modulesUrl"   : "modules",
  "appName"      : "Graphene",
  "storageConfig" : {
    "driver"    : "CrudMySql2",
    "type"      : "mysql",
    "dbName"    : "graphene_db",
    "prefix"    : "gdb",
    "username"  : "root",
    "password"  : "mysql"
  },

  "log" : {
    "all"      : "logs/graphene.log",
    "requests" : "logs/requests.log",
    "warnings" : "logs/warnings.log",
    "errors"   : "logs/errors.log",
    "debug"    : "logs/debug.log"
  }
}
```
for more details visit [settings.json reference](https://github.com/marcomag89/Graphene/wiki/settings.json)

##Wiki##
we are very excited that you want to use Graphene therefore we are working so that you can use it to its full potential by writing up to date wiki.
[Go to Graphene wiki](https://github.com/marcomag89/Graphene/wiki)

###HowTo###
**Hello world** [tutorial](https://github.com/marcomag89/Graphene/wiki/Hello-World-tutorial)
