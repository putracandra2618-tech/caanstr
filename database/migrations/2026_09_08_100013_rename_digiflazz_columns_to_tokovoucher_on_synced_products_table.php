<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('synced_products', function (Blueprint $table) {
            $table->dropIndex('synced_products_digiflazz_sku_index');
            $table->renameColumn('digiflazz_sku', 'tokovoucher_sku');
            $table->renameColumn('digiflazz_price', 'tokovoucher_price');
            $table->index('tokovoucher_sku');
        });
    }

    public function down(): void
    {
        Schema::table('synced_products', function (Blueprint $table) {
            $table->dropIndex(['tokovoucher_sku']);
            $table->renameColumn('tokovoucher_sku', 'digiflazz_sku');
            $table->renameColumn('tokovoucher_price', 'digiflazz_price');
            $table->index('digiflazz_sku');
        });
    }
};
