<?php

namespace Webkul\Bulkupload\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Models\ProductImage;

/**
 * Product Image Repository
 *
 */
class ProductImageRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Webkul\Product\Contracts\ProductImage';
    }

    /**
     * @param array $data
     * @param mixed $product
     * @return mixed
     */
    public function uploadImages($data, $product)
    {
        $previousImageIds = $product->images()->pluck('id');

        if (isset($data['images'])) {
            foreach ($data['images'] as $imageId => $image) {
                $file = 'images.' . $imageId;
                $dir = 'product/' . $product->id;

                if (str_contains($imageId, 'image_')) {
                    if (request()->hasFile($file)) {
                        $this->create([
                                'path' => request()->file($file)->store($dir),
                                'product_id' => $product->id
                            ]);
                    }
                } else {
                    if (is_numeric($index = $previousImageIds->search($imageId))) {
                        $previousImageIds->forget($index);
                    }

                    if (request()->hasFile($file)) {
                        if ($imageModel = $this->find($imageId)) {
                            Storage::delete($imageModel->path);
                        }

                        $this->update([
                                'path' => request()->file($file)->store($dir)
                            ], $imageId);
                    }
                }
            }
        }

        foreach ($previousImageIds as $imageId) {
            if ($imageModel = $this->find($imageId)) {
                Storage::delete($imageModel->path);

                $this->delete($imageId);
            }
        }
    }

    /**
     * @param array $data
     * @param mixed $product
     * @param array $imageZipName
     *
     * @return mixed
     */
    public function bulkuploadImages($data, $product, $imageZipName)
    {
        if (isset($data['images'])) {
            foreach($data['images'] as $key => $value) {
                if ( ! is_null($imageZipName)) {
                    $files = "imported-products/extracted-images/admin/".$data['dataFlowProfileRecordId'].'/'. $imageZipName['dirname'].'/'.basename($value);
                } else {
                    $files = __DIR__."/../../../../../public/imported-products/extracted-images/admin/".$data['dataFlowProfileRecordId'].'/'.basename($value);
                }

                $directory = __DIR__."/../../../../../../images/product/".$product->id;
                $destination = __DIR__."/../../../../../../images/product/".$product->id.'/'.basename($value);
//                $destination = "product/".$product->id.'/'.basename($value);
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                copy($files, $destination);
//                Storage::copy($files, $destination);

                $productExist = $this->findOneByField('product_id', $product->id);

                if (!$productExist instanceof ProductImage) {
                    $this->create([
                        'path' => '/images/product/' . $product->id . '/' . basename($value),
                        'product_id' => $product->id
                    ]);
                }

//                $values = array('path' => '/images/product/' . $product->id .'/'. basename($value),'product_id' => $product->id);
//                DB::table('product_images')->insert($values);
            }
        }
    }
}