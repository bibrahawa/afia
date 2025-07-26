<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->integer('age')->nullable();
            $table->enum('gender', array('Homme', 'Femme'));
            $table->string('birth_date')->nullable();
            $table->string('country')->default('Guinee');
            $table->string('state')->default('Conakry');
            $table->string('district')->nullable();
            $table->string('location')->nullable();
            $table->string('occupation')->nullable();
            $table->string('description')->nullable();
            $table->string('relative_name')->nullable();
            $table->string('relative_phone')->nullable();
            $table->string('marital_status')->nullable();
            $table->enum('blood_group', array('A+','A-','B+','AB+','AB-','B-','O+','O-'))->nullable();
            $table->boolean('first_visit')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
