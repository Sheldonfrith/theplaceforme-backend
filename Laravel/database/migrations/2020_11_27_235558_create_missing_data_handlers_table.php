<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingDataHandlersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missing_data_handlers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('method_name');
            $table->text('method_name_formatted')->nullable();
            $table->boolean('requires_input');
            $table->text('input_type_required')->nullable()->default(null);
            $table->text('description')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('missing_data_handlers');
    }
}
