<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("description");
            $table->decimal("price", 8, 2)->default(0.00);
            $table->integer('quantity')->default(0);
            $table->integer('sold')->default(0)->comment('How many times product sold.');
            $table->string('photo')->default('blank.png');
            $table->boolean('shipping')->nullable()->default(false)->comment('some product will be digital like gift card');
            $table->foreignIdFor(Category::class)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
