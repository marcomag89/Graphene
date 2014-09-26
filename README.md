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
1. Download Graphene as ZIP file and extract content into your project root.
2. If you running apache server, change filename of `htaccess.txt` to `.htaccess` it will enable apache url rewriting module.
3. Edit configuration file.
4. Check if you are ready to go on `yourProject.address/_system/status` on your browser.
5. Enjoy creating your first module!

##Wiki##
we are very excited that you want to use Graphene therefore we are working so that you can use it to its full potential by writing up to date wiki.
https://github.com/marcomag89/Graphene/wiki
###HowTo###
**Hello world** https://github.com/marcomag89/Graphene/wiki/examples/helloWorld
**StorageDriver** is a name of database driver. Is now supported mySql for native storage
