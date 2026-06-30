<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProductVariant;

class VariantUpdateTest extends TestCase
{
    public function test_variant_update()
    {
        $user = User::first();
        $variant = ProductVariant::first();
        
        $response = $this->actingAs($user, 'sanctum')->putJson('/api/admin/product-variants/' . $variant->id, [
            'product_id' => $variant->product_id,
            'name' => 'Test',
            'sku' => $variant->sku,
            'unit_qty' => '',
            'unit_type' => 'pcs',
            'price' => '',
            'status' => 1
        ]);
        
        dd($response->json());
    }
}
