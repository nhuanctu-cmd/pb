<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;

class PosSeeder extends Seeder
{
    public function run()
    {
        $tenantId = 1; // Assuming tenant ID 1 exists

        // Create categories
        $categories = [
            ['name_vi' => 'Nước uống', 'name_en' => 'Beverages'],
            ['name_vi' => 'Bóng', 'name_en' => 'Balls'],
            ['name_vi' => 'Thuê vợt', 'name_en' => 'Racket Rental'],
            ['name_vi' => 'Áo', 'name_en' => 'Shirts'],
            ['name_vi' => 'Phụ kiện', 'name_en' => 'Accessories'],
        ];

        $categoryIds = [];
        $now = date('Y-m-d H:i:s');
        foreach ($categories as $category) {
            $category['tenant_id']  = $tenantId;
            $category['created_at'] = $now;
            $category['updated_at'] = $now;
            $this->db->table('product_categories')->insert($category);
            $categoryIds[] = $this->db->insertID();
        }

        // Create products
        $products = [
            // Nước uống (10 products)
            ['category_id' => $categoryIds[0], 'name_vi' => 'Nước suối', 'name_en' => 'Mineral Water', 'sku' => 'DRINK001', 'unit' => 'chai', 'cost_price' => 3000, 'sale_price' => 5000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Nước ngọt', 'name_en' => 'Soda', 'sku' => 'DRINK002', 'unit' => 'lon', 'cost_price' => 5000, 'sale_price' => 8000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Trà đào', 'name_en' => 'Peach Tea', 'sku' => 'DRINK003', 'unit' => 'chai', 'cost_price' => 6000, 'sale_price' => 10000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Cà phê', 'name_en' => 'Coffee', 'sku' => 'DRINK004', 'unit' => 'ly', 'cost_price' => 8000, 'sale_price' => 15000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Sinh tố', 'name_en' => 'Smoothie', 'sku' => 'DRINK005', 'unit' => 'ly', 'cost_price' => 15000, 'sale_price' => 25000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Nước cam', 'name_en' => 'Orange Juice', 'sku' => 'DRINK006', 'unit' => 'ly', 'cost_price' => 10000, 'sale_price' => 18000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Trà sữa', 'name_en' => 'Milk Tea', 'sku' => 'DRINK007', 'unit' => 'ly', 'cost_price' => 12000, 'sale_price' => 20000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Bia', 'name_en' => 'Beer', 'sku' => 'DRINK008', 'unit' => 'lon', 'cost_price' => 12000, 'sale_price' => 20000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Nước dừa', 'name_en' => 'Coconut Water', 'sku' => 'DRINK009', 'unit' => 'trái', 'cost_price' => 15000, 'sale_price' => 25000],
            ['category_id' => $categoryIds[0], 'name_vi' => 'Sữa tươi', 'name_en' => 'Fresh Milk', 'sku' => 'DRINK010', 'unit' => 'hộp', 'cost_price' => 8000, 'sale_price' => 12000],

            // Bóng (10 products)
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng pickleball chính hãng', 'name_en' => 'Official Pickleball', 'sku' => 'BALL001', 'unit' => 'quả', 'cost_price' => 15000, 'sale_price' => 25000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng tập luyện', 'name_en' => 'Training Ball', 'sku' => 'BALL002', 'unit' => 'quả', 'cost_price' => 8000, 'sale_price' => 15000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng thi đấu', 'name_en' => 'Match Ball', 'sku' => 'BALL003', 'unit' => 'hộp', 'cost_price' => 80000, 'sale_price' => 120000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng trong nhà', 'name_en' => 'Indoor Ball', 'sku' => 'BALL004', 'unit' => 'quả', 'cost_price' => 10000, 'sale_price' => 18000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng ngoài trời', 'name_en' => 'Outdoor Ball', 'sku' => 'BALL005', 'unit' => 'quả', 'cost_price' => 12000, 'sale_price' => 20000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng lặn sóng', 'name_en' => 'Wiffle Ball', 'sku' => 'BALL006', 'unit' => 'quả', 'cost_price' => 5000, 'sale_price' => 8000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng nhựa', 'name_en' => 'Plastic Ball', 'sku' => 'BALL007', 'unit' => 'quả', 'cost_price' => 3000, 'sale_price' => 6000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng cao cấp', 'name_en' => 'Premium Ball', 'sku' => 'BALL008', 'unit' => 'quả', 'cost_price' => 20000, 'sale_price' => 35000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng trẻ em', 'name_en' => 'Kids Ball', 'sku' => 'BALL009', 'unit' => 'quả', 'cost_price' => 5000, 'sale_price' => 10000],
            ['category_id' => $categoryIds[1], 'name_vi' => 'Bóng tập', 'name_en' => 'Practice Ball Set', 'sku' => 'BALL010', 'unit' => 'bộ', 'cost_price' => 50000, 'sale_price' => 80000],

            // Thuê vợt (10 products)
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt 1 giờ', 'name_en' => 'Racket Rental 1h', 'sku' => 'RENT001', 'unit' => 'lượt', 'cost_price' => 20000, 'sale_price' => 50000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt 2 giờ', 'name_en' => 'Racket Rental 2h', 'sku' => 'RENT002', 'unit' => 'lượt', 'cost_price' => 35000, 'sale_price' => 80000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt cả ngày', 'name_en' => 'Racket Rental Full Day', 'sku' => 'RENT003', 'unit' => 'lượt', 'cost_price' => 80000, 'sale_price' => 150000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt evening', 'name_en' => 'Racket Rental Evening', 'sku' => 'RENT004', 'unit' => 'lượt', 'cost_price' => 40000, 'sale_price' => 70000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt cuối tuần', 'name_en' => 'Weekend Rental', 'sku' => 'RENT005', 'unit' => 'lượt', 'cost_price' => 60000, 'sale_price' => 120000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt carbon', 'name_en' => 'Carbon Racket Rental', 'sku' => 'RENT006', 'unit' => 'lượt', 'cost_price' => 50000, 'sale_price' => 100000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt trẻ em', 'name_en' => 'Kids Racket Rental', 'sku' => 'RENT007', 'unit' => 'lượt', 'cost_price' => 15000, 'sale_price' => 30000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt Pro', 'name_en' => 'Pro Racket Rental', 'sku' => 'RENT008', 'unit' => 'lượt', 'cost_price' => 80000, 'sale_price' => 180000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt đôi', 'name_en' => 'Double Racket Rental', 'sku' => 'RENT009', 'unit' => 'cặp', 'cost_price' => 40000, 'sale_price' => 80000],
            ['category_id' => $categoryIds[2], 'name_vi' => 'Thuê vợt VIP', 'name_en' => 'VIP Racket Rental', 'sku' => 'RENT010', 'unit' => 'lượt', 'cost_price' => 100000, 'sale_price' => 200000],

            // Áo (10 products)
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo thun pickleball', 'name_en' => 'Pickleball T-shirt', 'sku' => 'SHIRT001', 'unit' => 'cái', 'cost_price' => 80000, 'sale_price' => 150000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo polo', 'name_en' => 'Polo Shirt', 'sku' => 'SHIRT002', 'unit' => 'cái', 'cost_price' => 150000, 'sale_price' => 280000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo thể thao', 'name_en' => 'Sports Shirt', 'sku' => 'SHIRT003', 'unit' => 'cái', 'cost_price' => 100000, 'sale_price' => 200000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo thể thao nữ', 'name_en' => 'Women Sports Shirt', 'sku' => 'SHIRT004', 'unit' => 'cái', 'cost_price' => 120000, 'sale_price' => 220000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo hoodie', 'name_en' => 'Hoodie', 'sku' => 'SHIRT005', 'unit' => 'cái', 'cost_price' => 200000, 'sale_price' => 350000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo khoác', 'name_en' => 'Jacket', 'sku' => 'SHIRT006', 'unit' => 'cái', 'cost_price' => 250000, 'sale_price' => 450000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo sơ mi', 'name_en' => 'Shirt', 'sku' => 'SHIRT007', 'unit' => 'cái', 'cost_price' => 180000, 'sale_price' => 320000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo thể thao trẻ em', 'name_en' => 'Kids Sports Shirt', 'sku' => 'SHIRT008', 'unit' => 'cái', 'cost_price' => 60000, 'sale_price' => 120000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo thun dài tay', 'name_en' => 'Long Sleeve T-shirt', 'sku' => 'SHIRT009', 'unit' => 'cái', 'cost_price' => 150000, 'sale_price' => 250000],
            ['category_id' => $categoryIds[3], 'name_vi' => 'Áo tank top', 'name_en' => 'Tank Top', 'sku' => 'SHIRT010', 'unit' => 'cái', 'cost_price' => 70000, 'sale_price' => 130000],

            // Phụ kiện (10 products)
            ['category_id' => $categoryIds[4], 'name_vi' => 'Băng đeo cổ tay', 'name_en' => 'Wristband', 'sku' => 'ACC001', 'unit' => 'cái', 'cost_price' => 15000, 'sale_price' => 30000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Băng đeo đầu', 'name_en' => 'Headband', 'sku' => 'ACC002', 'unit' => 'cái', 'cost_price' => 20000, 'sale_price' => 40000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Tất thể thao', 'name_en' => 'Sports Socks', 'sku' => 'ACC003', 'unit' => 'đôi', 'cost_price' => 20000, 'sale_price' => 40000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Grip vợt', 'name_en' => 'Racket Grip', 'sku' => 'ACC004', 'unit' => 'cái', 'cost_price' => 10000, 'sale_price' => 20000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Bảo vệ vợt', 'name_en' => 'Racket Guard', 'sku' => 'ACC005', 'unit' => 'cái', 'cost_price' => 30000, 'sale_price' => 60000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Túi đựng vợt', 'name_en' => 'Racket Bag', 'sku' => 'ACC006', 'unit' => 'cái', 'cost_price' => 80000, 'sale_price' => 150000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Kính thể thao', 'name_en' => 'Sports Glasses', 'sku' => 'ACC007', 'unit' => 'cái', 'cost_price' => 50000, 'sale_price' => 100000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Mũ lưỡi trai', 'name_en' => 'Cap', 'sku' => 'ACC008', 'unit' => 'cái', 'cost_price' => 40000, 'sale_price' => 80000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Bình nước thể thao', 'name_en' => 'Sports Bottle', 'sku' => 'ACC009', 'unit' => 'cái', 'cost_price' => 30000, 'sale_price' => 60000],
            ['category_id' => $categoryIds[4], 'name_vi' => 'Khăn thể thao', 'name_en' => 'Sports Towel', 'sku' => 'ACC010', 'unit' => 'cái', 'cost_price' => 15000, 'sale_price' => 30000],
        ];

        foreach ($products as $product) {
            $product['tenant_id']  = $tenantId;
            $product['created_at'] = $now;
            $product['updated_at'] = $now;
            $this->db->table('products')->insert($product);
        }

        // Insert initial inventory (100 units for each product)
        $productIds = $this->db->table('products')->where('tenant_id', $tenantId)->get()->getResult('array');
        foreach ($productIds as $product) {
            $exists = $this->db->table('inventories')
                ->where('tenant_id', $tenantId)
                ->where('branch_id', 1)
                ->where('product_id', $product['id'])
                ->countAllResults();

            if ($exists) {
                $this->db->table('inventories')
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', 1)
                    ->where('product_id', $product['id'])
                    ->update(['quantity' => 100, 'updated_at' => $now]);
            } else {
                $this->db->table('inventories')->insert([
                    'tenant_id'  => $tenantId,
                    'branch_id'  => 1,
                    'product_id' => $product['id'],
                    'quantity'   => 100,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
