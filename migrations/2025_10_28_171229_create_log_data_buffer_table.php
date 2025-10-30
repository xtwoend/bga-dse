<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_data_buffer', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group', 255);
            $table->string('tag', 255);
            $table->float('value');
            $table->datetimes();
            $table->unique(['group', 'tag'], 'unique_log_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_data_buffer');
    }
};
