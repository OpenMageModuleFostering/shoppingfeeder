<?php

class ShoppingFeeder_Service_Model_Offers extends Mage_Core_Model_Abstract
{
    public function __construct()
    {
        $this->_init('shoppingfeeder_service/offers');
    }

    private function hasParent($product)
    {
        $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        return !empty($parents);
    }

    private function getProductInfo(Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $parent = null, $variantOptions = null, $lastUpdate = null, $priceCurrency, $priceCurrencyRate)
    {
        /** @var Mage_Catalog_Model_Product_Type_Configurable $configModel */
        $configModel = Mage::getModel('catalog/product_type_configurable');

        $p = array();

        $isVariant = !is_null($parent);

        /**
         * We only want to pull variants (children of configurable products) that are children, not as standalone products
         */
        //if this product's parent is visible in catalog and search, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        //we will find this product when we fetch all the children of this parent through a normal iteration, so return nothing
        if (!$isVariant && $this->hasParent($product))
        {
            return array();
        }

        if ($isVariant)
        {
            $variant = $product;
            $product = $parent;
            /* @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($variant);
        }
        else
        {
            /* @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        }

        $data = $product->getData();

        $attributes = $product->getAttributes();

        $manufacturer = '';
        $brand = '';

        $usefulAttributes = array();

        /**
         * @var Mage_Eav_Model_Entity_Attribute_Abstract $attribute
         */
//            var_dump("");
//            var_dump("");
//            var_dump("");
        foreach ($attributes as $attribute)
        {
            $attributeCode = $attribute->getAttributeCode();
            $attributeLabel = $attribute->getData('frontend_label');

            if ($isVariant)
            {
                $value = $attribute->getFrontend()->getValue($variant);
            }
            else
            {
                $value = $attribute->getFrontend()->getValue($product);
            }

//                var_dump($attributeCode. ' : '.print_r($value, true));
//                var_dump($attributeLabel. ' : '.print_r($value, true));

            if (preg_match('/^manufacturer$/i', $attributeCode) || preg_match('/^manufacturer$/i', $attributeLabel))
            {
                $manufacturer = $value;
            }

            if (preg_match('/^brand$/i', $attributeCode) || preg_match('/^brand$/i', $attributeLabel))
            {
                $brand = $value;
            }

            /*
            if (preg_match('/age/i', $attributeCode) || preg_match('/age/i', $attributeLabel))
            {
                $usefulAttributes['age'] = $value;
            }
            if (preg_match('/color|colour/i', $attributeCode) || preg_match('/color|colour/i', $attributeLabel))
            {
                $usefulAttributes['colour'] = $value;
            }
            if (preg_match('/size/i', $attributeCode) || preg_match('/size/i', $attributeLabel))
            {
                $usefulAttributes['size'] = $value;
            }
            if (preg_match('/gender|sex/i', $attributeCode) || preg_match('/gender|sex/i', $attributeLabel))
            {
                $usefulAttributes['gender'] = $value;
            }
            if (preg_match('/material/i', $attributeCode) || preg_match('/material/i', $attributeLabel))
            {
                $usefulAttributes['material'] = $value;
            }
            if (preg_match('/pattern/i', $attributeCode) || preg_match('/pattern/i', $attributeLabel))
            {
                $usefulAttributes['pattern'] = $value;
            }
            */

            $attributeValue = $attribute->getFrontend()->getValue($product);
            //don't deal with arrays
            if (!is_array($attributeValue))
            {
                if (!is_null($product->getData($attributeCode)) && ((string)$attributeValue != ''))
                {
                    $usefulAttributes[$attributeCode] = $value;
                }
            }
        }
//            exit();

        //category path
        $categories = $product->getCategoryIds();

        $categoryPathsToEvaluate = array();
        $maxDepth = 0;
        $categoryPathToUse = '';

        $storeRootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        $storeRootCategoryName = Mage::getModel('catalog/category')->load($storeRootCategoryId)->getName();

        $lastCatUrl = null;
        if (!empty($categories))
        {
            /** @var Mage_Catalog_Model_Resource_Category_Collection $categoryCollection */
            $categoryCollection = $product->getCategoryCollection()->addAttributeToSelect('name');

            $depth = 0;
            foreach($categoryCollection as $cat1){
                $pathIds = explode('/', $cat1->getPath());
                unset($pathIds[0]);

                $collection = Mage::getModel('catalog/category')->getCollection()
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('is_active')
                    ->addFieldToFilter('entity_id', array('in' => $pathIds))
                    ->addUrlRewriteToResult();

                $pathByName = array();
                /** @var Mage_Catalog_Model_Category $cat */
                foreach($collection as $cat){
                    if ($cat->getName() != $storeRootCategoryName)
                    {
                        $pathByName[] = $cat->getName();

                        //try get the category URL
                        try {
                            $lastCatUrl = $cat->getUrl();
                        }
                        catch (Exception $e) {

                        }
                    }
                }

                //take the longest (generally more detailed) path
                $thisDepth = count($pathByName);
                if ($thisDepth > $depth)
                {
                    $depth = $thisDepth;
                    $categoryPathToUse = implode(' > ', $pathByName);
                }
            }

//            //we will get all the category paths and then use the most refined, deepest one
//            foreach ($categories as $rootCategoryId)
//            {
//                $depth = 0;
//                $category_path = '';
//
//                $mageCategoryPath = Mage::getModel('catalog/category')->load($rootCategoryId)->getPath();
//                $allCategoryIds = explode('/', $mageCategoryPath);
//                unset($allCategoryIds[0]);
//
//                $categoryPath = '';
//                /**
//                 * @var Mage_Catalog_Model_Category $category
//                 */
//                foreach ($allCategoryIds as $categoryId)
//                {
//                    $depth++;
//                    $category = Mage::getModel('catalog/category')->load($categoryId);
//                    $category_name = $category->getName();
//                    if ($category_name != $storeRootCategoryName)
//                    {
//                        if (!empty($categoryPath))
//                        {
//                            $categoryPath.= ' > ';
//                        }
//                        $categoryPath.= $category_name;
//                    }
//                }
//
//                $categoryPathsToEvaluate[$rootCategoryId]['path'] = $categoryPath;
//                $categoryPathsToEvaluate[$rootCategoryId]['depth'] = $depth;
//
//                if ($maxDepth < $depth)
//                {
//                    $maxDepth = $depth;
//                    $categoryPathToUse = $categoryPath;
//                }
//            }
        }

        if ($isVariant && isset($variant))
        {
//            var_dump($usefulAttributes);
            $p['internal_variant_id'] = $variant->getId();

            $variantOptionsTitle = array();
            $variantPrice = $variantOptions['basePrice'];

            $urlHashParts = array();

            // Collect options applicable to the configurable product
            if (isset($variantOptions['refactoredOptions'][$variant->getId()]))
            {
                foreach ($variantOptions['refactoredOptions'][$variant->getId()] as $attributeCode => $option) {
                    $variantOptionsTitle[] = $option['value'];

                    //add these configured attributes to the set of parent's attributes
                    $usefulAttributes[$attributeCode] = $option['value'];

                    if (is_null($option['price']))
                    {
                        $variantPrice = $variant->getPrice();
                    }
                    else
                    {
                        $variantPrice += $option['price'];
                    }

                    $urlHashParts[] = $option['attributeId'].'='.$option['valueId'];
                }
            }

            $variantOptionsTitle = implode(' / ', $variantOptionsTitle);
            $title = $data['name'] . ' - ' . $variantOptionsTitle;
            $sku = $variant->getData('sku');
            $price = $variantPrice;
            $salePrice = $variant->getSpecialPrice();
            $variantImage = $variant->getImage();

            if (!is_null($variantImage) && !empty($variantImage) && $variantImage!='no_selection')
            {
                $imageFile = $variant->getImage();
//                $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
//                    'catalog/product/'.preg_replace('/^\//', '', $imageFile);
                $imageUrl = $p['image_url'] = $variant->getMediaConfig()->getMediaUrl($imageFile);
                $imageLocalPath = $variant->getMediaConfig()->getMediaPath($imageFile);
            }
            else
            {
                $imageFile = $product->getImage();
//                $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
//                    'catalog/product/'.preg_replace('/^\//', '', $imageFile);
                $imageUrl = $p['image_url'] = $product->getMediaConfig()->getMediaUrl($imageFile);
                $imageLocalPath = $product->getMediaConfig()->getMediaPath($imageFile);
            }
            $productUrl = $product->getProductUrl().'#'.implode('&', $urlHashParts);
        }
        else
        {
            $p['internal_variant_id'] = '';
            $title = $data['name'];
            $sku = $data['sku'];


            if ($product->getTypeId() == 'bundle')
            {
                /**
                 * @var $priceModel Mage_Bundle_Model_Product_Price
                 */
                $priceModel  = $product->getPriceModel();

                list($price, $_maximalPriceTax) = $priceModel->getTotalPrices($product, null, null, false);
                list($priceInclTax, $_maximalPriceInclTax) = $priceModel->getTotalPrices($product, null, true, false);
            }
            else
            {
                $price = $product->getPrice();
            }

            $salePrice = $product->getSpecialPrice();

            $imageFile = $product->getImage();
//            $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
//                'catalog/product/'.preg_replace('/^\//', '', $imageFile);
            $imageUrl = $p['image_url'] = $product->getMediaConfig()->getMediaUrl($imageFile);
            $imageLocalPath = $product->getMediaConfig()->getMediaPath($imageFile);
            $productUrl = $product->getProductUrl();
        }

        //if we have previously captured this product and it hasn't changed, don't send through full payload
        $wasPreviouslyCaptured = !is_null($lastUpdate) && isset($usefulAttributes['updated_at']) && strtotime($usefulAttributes['updated_at']) < $lastUpdate;
        if ($wasPreviouslyCaptured)
        {
            $p['internal_id'] = $product->getId();
            $p['internal_update_time'] = date("c", strtotime($usefulAttributes['updated_at']));
        }
        else
        {
            $p['category'] = $categoryPathToUse;
            $p['category_url'] = $lastCatUrl;
            $p['title'] = $title;
            $p['brand'] = ($brand=='No') ? (($manufacturer == 'No') ? '' : $manufacturer) : $brand;
            $p['manufacturer'] = ($manufacturer=='No') ? $brand : $manufacturer;
            $p['mpn'] = isset($data['model']) ? $data['model'] : $data['sku'];
            $p['internal_id'] = $product->getId();
            $p['description'] = $data['description'];
            $p['short_description'] = $data['short_description'];
            $p['weight'] = isset($data['weight']) ? $data['weight'] : 0.00;
            $p['sku'] = $sku;
            $p['gtin'] = '';

            //$priceModel = $product->getPriceModel();

            //do a currency conversion. if the currency is in base currency, it will be 1.0
            $price = $price * $priceCurrencyRate;
            $salePrice = $salePrice * $priceCurrencyRate;

            $p['currency'] = $priceCurrency;
            $p['price'] = $price;// Mage::helper('checkout')->convertPrice($priceModel->getPrice($product), false);
            $p['sale_price'] = '';
            $p['sale_price_effective_date'] = '';
            if ($salePrice != $p['price'])
            {
                $p['sale_price'] = $salePrice;
                if ($product->getSpecialFromDate()!=null && $product->getSpecialToDate()!=null)
                {
                    $p['sale_price_effective_date'] = date("c", strtotime(date("Y-m-d 00:00:00", strtotime($product->getSpecialFromDate())))).'/'.date("c", strtotime(date("Y-m-d 23:59:59", strtotime($product->getSpecialToDate()))));
                }
            }

            $p['delivery_cost'] = 0.00;
            $p['tax'] = 0.00;
            $p['url'] = $productUrl;
            $p['internal_update_time'] = isset($usefulAttributes['updated_at']) ? date("c", strtotime($usefulAttributes['updated_at'])) : '';

            $p['image_url'] = $imageUrl;
            if (file_exists($imageLocalPath))
            {
                $p['image_modified_time'] = date("c", filemtime($imageLocalPath));
            }
            $p['availability'] = ($stockItem->getIsInStock())?'in stock':'out of stock';
            $p['quantity'] = $stockItem->getQty();
            $p['condition'] = '';
            $p['availability_date'] = '';
            $p['attributes'] = $usefulAttributes;
            $imageGallery = array();
            foreach ($product->getMediaGalleryImages() as $image)
            {
                $galleryImage = array();
                $galleryImage['url'] = $image['url'];
                if (file_exists($image['path']))
                {
                    $galleryImage['image_modified_time'] = date("c", filemtime($image['path']));
                }
                $imageGallery[] = $galleryImage;
            }
            $p['extra_images'] = $imageGallery;
        }

        return $p;
    }

    public function getItems($page = null, $numPerPage = 1000, $lastUpdate = null, $store = null, $priceCurrency = null, $priceCurrencyRate = null, $allowVariants = true)
    {
        /* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

        /**
         * For per-store system
         */
        if (!is_null($store))
        {
            $collection->addStoreFilter(Mage::app()->getStore($store)->getId());
        }

        if (!is_null($page))
        {
            $offset = ($page * $numPerPage) - $numPerPage;
            $productIds = $collection->getAllIds($numPerPage, $offset);
        }
        else
        {
            $productIds = $collection->getAllIds();
        }

        $products = array();
        foreach ($productIds as $productId)
        {
            Mage::getModel('catalog/product')->reset();
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($productId);

            /**
             * Get variants, if there are any
             * If there are variants that are visible in the catalog, we will skip them when we iterate normally
             */

            //if we have a configurable product, capture the variants
            if ($product->getTypeId() == 'configurable' && $allowVariants)
            {
                /** @var Mage_Catalog_Model_Product_Type_Configurable $configModel */
                $configModel = Mage::getModel('catalog/product_type_configurable');

//                $timeStart = microtime(true);
//                $children = $configModel->getChildrenIds($product->getId());
//                $children = array_pop($children);
//                var_dump("Time for GetIDs: ".(microtime(true) - $timeStart));

                $timeStart = microtime(true);
                $children = Mage::getResourceSingleton('catalog/product_type_configurable')
                    ->getChildrenIds($product->getId());
//                var_dump("Time for GetIDs 2: ".(microtime(true) - $timeStart));
                $children = array_pop($children);
//                var_dump($children);

//                $timeStart = microtime(true);
//                $children = $configModel->getUsedProducts(null,$product);
//                var_dump("Time for GetUsed: ".(microtime(true) - $timeStart));
//                exit();

                if (count($children) > 0)
                {
                    $parent = $product;

                    //get variant options
                    $layout = Mage::getSingleton('core/layout');
                    $block = $layout->createBlock('catalog/product_view_type_configurable');
                    $block->setProduct($parent);
                    $variantOptions = Mage::helper('core')->jsonDecode($block->getJsonConfig());

                    $variantAttributes = array();
                    foreach ($variantOptions['attributes'] as $attributeId => $options)
                    {
                        $code = $options['code'];
                        foreach ($options['options'] as $option)
                        {
                            $value = $option['label'];
                            $price = @$option['price'];
                            $valueId = $option['id'];
                            foreach ($option['products'] as $productId)
                            {
                                //$children[] = $productId;
                                $variantAttributes[$productId][$code]['value'] = $value;
                                $variantAttributes[$productId][$code]['price'] = $price;
                                $variantAttributes[$productId][$code]['valueId'] = $valueId;
                                $variantAttributes[$productId][$code]['attributeId'] = $attributeId;
                            }
                        }
                    }
                    $variantOptions['refactoredOptions'] = $variantAttributes;


                    foreach ($children as $variantId)
                    {
                        /** @var Mage_Catalog_Model_Product $variant */
                        $variant = Mage::getModel('catalog/product')->load($variantId);

                        $productData = $this->getProductInfo($variant, $parent, $variantOptions, $lastUpdate, $priceCurrency, $priceCurrencyRate);
                        if (!empty($productData))
                        {
                            $products[] = $productData;
                        }
                    }
                }
            }
            else
            {
                $productData = $this->getProductInfo($product, null, null, $lastUpdate, $priceCurrency, $priceCurrencyRate);
                if (!empty($productData))
                {
                    $products[] = $productData;
                }
            }
        }

        return $products;
    }

    public function getItem($itemId, $store = null, $priceCurrency = null, $priceCurrencyRate = null, $allowVariants = true)
    {
        $lastUpdate = null;
        $products = array();

        $product = Mage::getModel('catalog/product')->load($itemId);

        if ($product->getTypeId() == 'configurable' && $allowVariants)
        {
            /** @var Mage_Catalog_Model_Product_Type_Configurable $configModel */
            $configModel = Mage::getModel('catalog/product_type_configurable');

//                $timeStart = microtime(true);
//                $children = $configModel->getChildrenIds($product->getId());
//                $children = array_pop($children);
//                var_dump("Time for GetIDs: ".(microtime(true) - $timeStart));

            $timeStart = microtime(true);
            $children = Mage::getResourceSingleton('catalog/product_type_configurable')
                ->getChildrenIds($product->getId());
//                var_dump("Time for GetIDs 2: ".(microtime(true) - $timeStart));
            $children = array_pop($children);
//                var_dump($children);

//                $timeStart = microtime(true);
//                $children = $configModel->getUsedProducts(null,$product);
//                var_dump("Time for GetUsed: ".(microtime(true) - $timeStart));
//                exit();

            if (count($children) > 0)
            {
                $parent = $product;

                //get variant options
                $layout = Mage::getSingleton('core/layout');
                $block = $layout->createBlock('catalog/product_view_type_configurable');
                $block->setProduct($parent);
                $variantOptions = Mage::helper('core')->jsonDecode($block->getJsonConfig());

                $variantAttributes = array();
                foreach ($variantOptions['attributes'] as $attributeId => $options)
                {
                    $code = $options['code'];
                    foreach ($options['options'] as $option)
                    {
                        $value = $option['label'];
                        $price = @$option['price'];
                        $valueId = $option['id'];
                        foreach ($option['products'] as $productId)
                        {
                            //$children[] = $productId;
                            $variantAttributes[$productId][$code]['value'] = $value;
                            $variantAttributes[$productId][$code]['price'] = $price;
                            $variantAttributes[$productId][$code]['valueId'] = $valueId;
                            $variantAttributes[$productId][$code]['attributeId'] = $attributeId;
                        }
                    }
                }
                $variantOptions['refactoredOptions'] = $variantAttributes;


                foreach ($children as $variantId)
                {
                    /** @var Mage_Catalog_Model_Product $variant */
                    $variant = Mage::getModel('catalog/product')->load($variantId);

                    $productData = $this->getProductInfo($variant, $parent, $variantOptions, $lastUpdate, $priceCurrency, $priceCurrencyRate);
                    if (!empty($productData))
                    {
                        $products[] = $productData;
                    }
                }
            }
        }
        else
        {
            $products[] = $this->getProductInfo($product, null, null, null, $priceCurrency, $priceCurrencyRate);
        }

        return $products;
    }

    public function getStockQuantity($itemId, $store = null)
    {
        $product = Mage::getModel('catalog/product')->load($itemId);

        /* @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

        return $stockItem->getQty();
    }
}