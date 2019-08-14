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

    private function getProductInfo(Mage_Catalog_Model_Product $product, Mage_Catalog_Model_Product $parent = null, $variantOptions = null, $lastUpdate = null)
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
        }

        $data = $product->getData();

        /* @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

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
            $value = $attribute->getFrontend()->getValue($product);

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
            $usefulAttributes[$attributeCode] = $value;
        }
//            exit();

        //category path
        $categories = $product->getCategoryIds();

        $categoryPathsToEvaluate = array();
        $maxDepth = 0;
        $categoryPathToUse = '';

        if (!empty($categories))
        {
            //we will get all the category paths and then use the most refined, deepest one
            foreach ($categories as $rootCategoryId)
            {
                $depth = 0;
                $category_path = '';

                $mageCategoryPath = Mage::getModel('catalog/category')->load($rootCategoryId)->getPath();
                $allCategoryIds = explode('/', $mageCategoryPath);
                unset($allCategoryIds[0]);

                $categoryPath = '';
                /**
                 * @var Mage_Catalog_Model_Category $category
                 */
                foreach ($allCategoryIds as $categoryId)
                {
                    $depth++;
                    $category = Mage::getModel('catalog/category')->load($categoryId);
                    $category_name = $category->getName();
                    if ($category_name != 'Root Catalog' && $category_name != 'Default Category')
                    {
                        if (!empty($categoryPath))
                        {
                            $categoryPath.= ' > ';
                        }
                        $categoryPath.= $category_name;
                    }
                }

                $categoryPathsToEvaluate[$rootCategoryId]['path'] = $categoryPath;
                $categoryPathsToEvaluate[$rootCategoryId]['depth'] = $depth;

                if ($maxDepth < $depth)
                {
                    $maxDepth = $depth;
                    $categoryPathToUse = $categoryPath;
                }
            }
        }

        if ($isVariant && isset($variant))
        {
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

                    $variantPrice += $option['price'];

                    $urlHashParts[] = $option['attributeId'].'='.$option['valueId'];
                }
            }

            $variantOptionsTitle = implode(' / ', $variantOptionsTitle);
            $title = $data['name'] . ' - ' . $variantOptionsTitle;
            $sku = $variant->getData('sku');
            $price = $variantPrice;
            $variantImage = $variant->getImage();
            if (!is_null($variantImage) && !empty($variantImage))
            {
                $imageFile = $variant->getImage();
                $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
                    'catalog/product'.$imageFile;
                $imageLocalPath = $variant->getMediaConfig()->getMediaPath($imageFile);
            }
            else
            {
                $imageFile = $product->getImage();
                $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
                    'catalog/product'.$imageFile;
                $imageLocalPath = $product->getMediaConfig()->getMediaPath($imageFile);
            }
            $productUrl = $product->getProductUrl().'#'.implode('&', $urlHashParts);

//            var_dump($variantOptionsTitle);
//            var_dump($variantPrice);
//            exit();
        }
        else
        {
            $p['internal_variant_id'] = '';
            $title = $data['name'];
            $sku = $data['sku'];
            $price = $product->getPrice();
            $imageFile = $product->getImage();
            $imageUrl = $p['image_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).
                'catalog/product'.$imageFile;
            $imageLocalPath = $product->getMediaConfig()->getMediaPath($imageFile);
            $productUrl = $product->getProductUrl();
        }

        //if we have previously captured this product and it hasn't changed, don't send through full payload
        $wasPreviouslyCaptured = !is_null($lastUpdate) && isset($usefulAttributes['updated_at']) && strtotime($usefulAttributes['updated_at']) < $lastUpdate;
        if ($wasPreviouslyCaptured)
        {
            $p['internal_id'] = $product->getId();
            $p['internal_update_time'] = $usefulAttributes['updated_at'];
        }
        else
        {
            $p['category'] = $categoryPathToUse;
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

            $p['price'] = $price;// Mage::helper('checkout')->convertPrice($priceModel->getPrice($product), false);
            $salePrice = $product->getSpecialPrice();// Mage::helper('checkout')->convertPrice($priceModel->getFinalPrice(null, $product), false);
            $p['sale_price'] = '';
            $p['sale_price_effective_date'] = '';
            if ($salePrice != $p['price'])
            {
                $p['sale_price'] = $salePrice;
                if ($product->getSpecialFromDate()!=null && $product->getSpecialToDate()!=null)
                {
                    $p['sale_price_effective_date'] = date("c", strtotime($product->getSpecialFromDate())).'/'.date("c", strtotime($product->getSpecialToDate()));
                }
            }

            $p['delivery_cost'] = 0.00;
            $p['tax'] = 0.00;
            $p['url'] = $productUrl;
            $p['internal_update_time'] = isset($usefulAttributes['updated_at']) ? $usefulAttributes['updated_at'] : '';

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

    public function getItems($page = null, $numPerPage = 1000, $lastUpdate = null)
    {
        /* @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

        $store='french';
        $collection->addStoreFilter(Mage::app()->getStore($store)->getId());

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
            $product = Mage::getModel('catalog/product')->load($productId);

            /**
             * Get variants, if there are any
             * If there are variants that are visible in the catalog, we will skip them when we iterate normally
             */

            //if we have a configurable product, capture the variants
            if ($product->getTypeId() == 'configurable')
            {
                /** @var Mage_Catalog_Model_Product_Type_Configurable $configModel */
                $configModel = Mage::getModel('catalog/product_type_configurable');

                //$children = $configModel->getChildrenIds($product->getId());
                $children = $configModel->getUsedProducts(null,$product);

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
                            $price = $option['price'];
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


                    foreach ($children as $variant)
                    {
                        /** @var Mage_Catalog_Model_Product $variant */
                        //$variant = Mage::getModel('catalog/product')->load($variantId);

                        $productData = $this->getProductInfo($variant, $parent, $variantOptions, $lastUpdate);
                        if (!empty($productData))
                        {
                            $products[] = $productData;
                        }
                    }
                }
            }
            else
            {
                $productData = $this->getProductInfo($product, null, null, $lastUpdate);
                if (!empty($productData))
                {
                    $products[] = $productData;
                }
            }
        }

        return $products;
    }

    public function getItem($itemId)
    {
        $products = array();

        $product = Mage::getModel('catalog/product')->load($itemId);

        $products[] = $this->getProductInfo($product);

        return $products;
    }
}