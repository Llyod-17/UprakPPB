<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('order_items', function (Blueprint $table) {
        $table->renameColumn('unit_price', 'price');        // rename unit_price → price
        $table->decimal('subtotal', 12, 2)->after('price'); // tambah subtotal
    });
}

public function down(): void
{
    Schema::table('order_items', function (Blueprint $table) {
        $table->renameColumn('price', 'unit_price');
        $table->dropColumn('subtotal');
    });
}
};  
