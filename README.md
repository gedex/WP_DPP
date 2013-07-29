WP_DPP ( WordPress Design Pattern in Plugin )
=============================================

Applying Design Pattern in WordPress Plugin. From [Wikipedia](http://en.wikipedia.org/wiki/Software_design_pattern):

> In software engineering, a design pattern is a general reusable solution to a
> commonly occurring problem within a given context in software design. A design
> pattern is not a finished design that can be transformed directly into source or
> machine code. It is a description or template for how to solve a problem that
> can be used in many different situations.

## Structure

Each pattern consists of one or more plugin(s).

## Covered Patterns

### Creational patterns

* [Abstract factory](#)
* [Factory method](#)
* [Singleton](#)

### Stuctural patterns

* [Adapter](#)
* [Bridge](#)
* [Composite](#)
* [Decorator](#)
* [Facade](#)

### Behavioral patterns

* [Iterator](#)
* [Observer](#)
* [Strategy](#)
* [Template method](#)

## How to try?

If you don't have web development setup locally, you may try install Vagrant first then clone this repo.

~~~text
git clone <this-github-repo>
cd <cloned-repo>
vagrant up
~~~

If you want to try on your existing WordPress environment, just copy-paste `<design-pattern-name>` directory to your `/wp-content/plugins/` directory. For example:

~~~text
git clone <this-github-repo>
cp -r <cloned-repo>/factory-method /path/to/existing-WordPress-installation/wp-content/plugins/factory-method
~~~

For Vagrant environment, here's the credentials:

* MySQL `root:wp`
* WordPress `admin:wp`

## Reference

* http://en.wikipedia.org/wiki/Software_design_pattern
* https://github.com/domnikl/DesignPatternsPHP

## License - MIT License

Copyright (c) 2013 Akeda Bagus <admin@gedex.web.id>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
