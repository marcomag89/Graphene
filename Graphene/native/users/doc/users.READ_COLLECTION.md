# Read Users collection
this action allows to read all users, implementing **GCI** (*Graphene Collection Interface*).
This interface is automatically paged and you can quest this action using search and sort url parameters

* **page_no** page selector, default is **1**
* **page_size** allows to select a page size, default is **20**
* **search** search string default is an empty string
* **sort_by** set this parameter with name of field
* **sort_discend** if value is '1' sort will bi discend default **0**

## Examples

First page of user sorted ascend by username 
* **page_no** = **1**
* **sort_by** = **username**
* **sort_discend** = **0**

```
GET http://hostname/users/collection?page_no=1&sort_by=username&sort_discend=0
```