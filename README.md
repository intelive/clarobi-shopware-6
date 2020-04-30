todo: 
* docs
* install page on clarobi
* repo on github
* add event on product view

```php
// ClarobiProductController.php:232
//        /** @var PriceCollection $priceColl */
//        $priceColl = $product['price'];
//        /** @var Price $price */
//        foreach ($priceColl->getElements() as $price) {
//            $mappedKeys['price_net'] = $price->getNet();
//            $mappedKeys['price_gross'] = $price->getGross();
//            break;
//        }
```

```php
//    private function mapOrderEntity($order){
//        foreach ($order as $key => $value) {
//            echo "'".$key."',\n";
//        }
//        die;
//
//        return $mappedKeys;
//    }
```

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

 // create a log channel
 $log = new Logger('product');
 try {
     $log->pushHandler(new StreamHandler(__DIR__ . '/../../product.log', Logger::WARNING));
 } catch (\Exception $e) {
 }

 // add records to the log
 $log->warning('product paged access');
```
```php
 // create table for document_extension entity
 $connection->executeQuery('
        CREATE TABLE IF NOT EXISTS `document_extension` (
            `id` BINARY(16) NOT NULL,
        )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_unicode_ci;
');
```
```php
            /**
             * @todo add mapping on multiple levels
             */
//            if (is_object($mappedKeys[$key])) {
//                var_dump($key);
//                foreach ($mappedKeys[$key] as $key2 => $line) {
//
////                    foreach (self::IGNORE_KEYS_IN_LINE_ITEMS[$entity] as $ignore_line_key) {
////                        if (isset($return[$key][$key2][$ignore_line_key])) {
////                            unset($return[$key][$key2][$ignore_line_key]);
////                        }
////                    }
//                }
//            }
```
```php
// function from client
   /**
     * @param $options
     * @param $properties
     * @param null $itemId
     * @return array
     * @todo delete
     */
//    protected function compressOptionsPropAttrId($options, $properties, $itemId = null)
//    {
//        $mappedOptions = [];
//        foreach ($options as $option) {
//            $attrId = (int)gmp_strval(gmp_init(substr(md5($option->attribute_id), 0, 4), 16), 10);
//            $option->attribute_id = $attrId;
//            ($itemId ? $option->item_id = $itemId : '');
//            $mappedOptions[] = $option;
//        }
//
//        // Add properties to options array
//        foreach ($properties as $label => $property) {
//            $attrId = (int)gmp_strval(gmp_init(substr(md5($property->attribute_id), 0, 4), 16), 10);
//            $option = new stdClass();
//            $option->attribute_id = $attrId;
//            $option->value = $property->value;
//            $option->label = $label;
//            ($itemId ? $option->item_id = $itemId : '');
//            $mappedOptions[] = $option;
//        }
//
//        return $mappedOptions;
//    }

// using this function in product and order mapping 
// Product
//        $options = $this->compressOptionsPropAttrId($entity->options, $entity->properties);
// Order - item
//                        'options' => $this->compressOptionsPropAttrId(
//                            $lineItem->product->options,
//                            $lineItem->product->properties,
//                            $lineItem->product->autoIncrement
//                        )
```
