##Icecat plugin v3.0.0

**Install latest version**

~~~~~~~~~~~~~~~~~~~~~
$ composer require icecat/icecat-integration:^3.0
~~~~~~~~~~~~~~~~~~~~~

**Update existing icecat plugin**

~~~~~~~~~~~~~~~~~~~~~
$ composer require icecat/icecat-integration:^3.0
$ bin/console doctrine:migrations:migrate --prefix=IceCatBundle\\Migrations
~~~~~~~~~~~~~~~~~~~~~

## New features listing

**Addition of new fields in import**

Reviews import

![img1](./images/reviews.png)

Product Stories import

![img1](./images/product-stories.png)

Related Products import

We have provided a checkbox to switch on / off related products import. 
![img1](./images/import-related-product-flag.png)

If its ON and if the related product is not present already, Pimcore will create that product on the fly and associate it.
If its OFF, Pimcore will not create any related product on the fly. It will only associate those related products which are present and skip the ones which are missing. 

![img1](./images/related-products.png)


Change video field type to Many to many relation.

![img1](./images/videos.png)

**More filter options added**

Additional filters added on Icecat filter screen

![img1](./images/filter-options.png)

**Icecat links added on login screen**

Added Icecat links to register new user, forgot password and contact us.

![img1](./images/redirection-links.png)

**Overwritable fields**

Provided users the ability to not overwrite product fields while (re)importing products from Icecat.
Whenever user makes any updates to Icecat class object fields, change logs get recorded in Pimcore and while re-importing Pimcore make sure to not overwrite those fields again.

All the change logs can be seen under "Icecat overwritten fields log" folder.

![img1](./images/overwritable-fields.png)

At any point in time, user can delete (all / some) log entries from the folder making those fields overwritable again.