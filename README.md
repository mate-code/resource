# README #

### Einleitung ###

Der Sinn der Resource Objekte ist es, beliebige Daten immer gleich zu behandeln. Das bedeutet, dass der Wert in einem XML Dokument auf dieselbe Art und Weise abgefragt wird wie der in einem Array oder einer JSON. Dieses Prinzip funktioniert auch für unterschiedliche Typen innerhalb eines Datensatzes. So kann ein Wert rekursiv über einen Pfad abgefragt werden, obwohl der Pfad erst durch ein Objekt in ein mehrdimensionales Array verweist etc.

### Wiki ###

[http://192.168.100.227:3000/projects/modo-lib/wiki/Resource](http://192.168.100.227:3000/projects/modo-lib/wiki/Resource)

### Composer ###


```
#!json
{
  "require": {
    "modolib/resource": "1.2.*"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "git@bitbucket.org:modotex/resource.git"
    }
  ]
}
```

### Autoren ###

* Marius Teller - [marius.teller@modotex.com](marius.teller@modotex.com)