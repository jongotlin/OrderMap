OrderMap
========

Your orders on a Google Map.

![Order map](https://raw.github.com/jongotlin/OrderMap/master/map.png)

Configure `app/config/parameters.yml` to import orders from either an [E-butik.se][1] store or from a json-file. 

Set up cron to run the command for importing orders as often you want.

    app/console import:ebutik

or

    app/console import:general

then set up cron to run the geolookup command

    app/console google:geolookup

### Custom json
If you're not using E-butik you need to set up a json file with your orders. It should have the following syntax.

    [
      {
        "order_number":1000,
        "street":"Foobargatan 1",
        "zip":"12345",
        "city":"Foo bar",
        "order_date":"2013-06-01 06:00:00"
      }
    ]
[1]:  http://www.e-butik.se
