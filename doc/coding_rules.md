Coding Rules
============

1. Coding conventions
---------------------

To keep the code clear, I've choose the Zend Coding Conventions :<br />
http://framework.zend.com/manual/en/coding-standard.coding-style.html

For NetBeans IDE, you can enable automatic formating with the following<br /> 
configuration :

Go to : Tools / Options / Editor icon / Formatting Tab

Languages Combo : PHP 

* Category : Tabs And indents

Check Override global options<br />
Check expand tabs to spaces<br />
Number of spaces per indent : 4<br />
Tab size                    : 4<br />
Margin size                 : 80<br />
Initial indentation         : 0<br />
Continuation indentation    : 4<br />
Array declaration indent    : 4<br />

* Category : Braces

Class Declaration           : New line<br />
Method Declaration          : New line<br />

To use the automatic formating : ALT+SHIFT+F

2. Design rules
---------------

- Be pragmatic, follow the K.I.S.S and D.R.Y philosophy
- Follow the S.O.L.I.D rules
- Avoid using **statics or globals**, they are **evil**

The *MOST* important rule is O.C.P : you never should change<br />
the code to modify its behavior or add a new functionality.


3. Namespaces
-------------

Beaba does not follow the PSR because it's not the way to go to keep it simple.

The namespaces are prefixes the first part is pointing to a folder, and the rest
is used to create the full filename path.

There are only 2 declared namespaces :

 - beaba\... pointing the root of the beaba framework
 - app\..... pointing to the root of the application (not the www root)

4. Classes coding
-----------------

Avoid using false to indicate that the called method was failed. Always 
throw an exception based error. Try to not use directly exception. Take a 
look at SPL exceptions.

Never return void after an action, return current object, thats will enable
call chains.

Example :

```php
    class Foo 
    {
        // do not do this :
        public function doBadBar() 
        {
            if ( perform() ) {
                return true;
            } else {
                return false;
            }
        }

        // should do this :
        public function doGoodBar() {
            if ( !perform() ) {
                throw new LogicalException('Could not perform');
            }
            return $this;
        }
    }
```